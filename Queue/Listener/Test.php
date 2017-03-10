<?php
namespace Core\Queue\Listener;

use Core\Queue\ListenerAbstract;
use Core\Queue\ListenerInterface;

use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;


class Message extends ListenerAbstract implements ListenerInterface
{
    protected $queueName;
    protected $tries;
    private $queue;

    /**
     * @param int $tries
     */
    public function __construct($tries = 3)
    {
    }

    public function checkJob( $data )
    {
        return true;
    }


    private function getMessageCount($admin, $conversation)
    {
        $params = [
            'id_conversation'   => $conversation->id_conversation,
            'paginate'          => [
                'key'           => 'updated_time',
                'limit'         => -1
            ]
        ];

        $result     = $this->api->message->user($admin)->messages(NULL, "GET", $params);
        $messages   = $result->value;

        return (true === is_array($messages) ? count($messages) : 0);
    }

    public function executeJob( $data )
    {
        $this->sm->get('Email')->setMergeLanguage( 'handlebars' );

        if ($this->sm->get('AppConfig')->isLocal())
        {
            $this->getLogger()->warn('Cron in test mode (mail send into debug mandrill mode)');
            $this->sm->get("Email")->setDebug( true );
        }

        $admin          = $this->sm->get('UserTable')->getConsoleUser( 'admin' );

        $params = [
            'id_conversation'   => $data['id_conversation'],
        ];

        $result         = $this->api->message->user($this->user)->conversation(NULL, "GET", $params);
        $conversation  = $result->value;


        // check if only have one message
        $count = $this->getMessageCount($admin, $conversation);

        // do not send
        if ($count <= 1)
        {
            // update time mail
            $this->sm->get('MessageTable')->updateMailTime( $conversation );
            return;
        }

        $params = [
            'id_conversation'   => $conversation->id_conversation,
            'type'              => 'null', // string because we need to add a string, it means we want only null type
            'paginate'          => [
                'key'           => 'updated_time',
                'limit'         => -1,
                'previous'      => $conversation->mail_time
            ]
        ];

        $result     = $this->api->message->user($admin)->messages(NULL, "GET", $params);
        $messages   = $result->value;

        $params = [
            'id_conversation'   => $conversation->id_conversation,
            'deleted'           => 2,
            'paginate'          => [
                'key'           => 'updated_time',
                'limit'         => -1
            ]
        ];

        $result     = $this->api->message->user($admin)->messages(NULL, "GET", $params);
        $messages_d = $result->value;

        if (count($messages_d) > 0)
        {
            $this->getLogger()->error('Messages not verified (' . count($messages_d) . ').' );
            return;
        }

        if ((true === is_array($messages) && 0 === count($messages)) || !is_array($messages))
        {
            $this->getLogger()->error('Error, no messages found.');
            // update time mail
            $this->sm->get('MessageTable')->updateMailTime( $conversation );
            return;
        }

        $comments       = [];
        $id_from        = null;
        $to             = null;
        $from           = null;
        $hash           = null;
        $cc             = [];

        foreach ($messages as $message)
        {
            if (null === $id_from)
                $id_from = (int) $message->id_user;

            if ($id_from != $message->id_user)
                break;

            array_unshift($comments, $message->text);
        }

        foreach ($conversation->participants as $participant)
        {
            if ($id_from == $participant->id_user)
            {
                $from = $this->sm->get('UserTable')->getUser( $id_from );
            }
            else
            {
                if (!isset($to))
                {
                    $to   = $this->sm->get('UserTable')->getUser( $participant->id_user );
                    $hash = $participant->hash;
                }
                else
                {
                    $cc[] = $this->sm->get('UserTable')->getUser( $participant->id_user );
                }
            }
        }

        $reply_to = null;

        if (isset($from) && $from->hasRole('candidate'))
        {
            $cv = $this->sm->get('CVTable')->createForm($from);

            if ($cv)
            {
                $cv->retrieveAllFields();
                $from->cv = $cv;
            }

            // to is a company
            if (isset($to) && true === $to->hasAts())
            {
                $this->getLogger()->normal('Company `' . $to->getCompany()->name . '` has an ATS : ' . $to->getAts());
                // reply to ATS : get the last reply
                $reply_to = $this->sm->get('AtsMessageTable')->getLastReplyTo( $message->id_conversation, $to->id );

                $content = nl2br(implode(PHP_EOL, $comments));

                $this->getLogger()->warn('replying through ATS notification message');

                $ats        = $this->sm->get('AtsTable')->getAts( $to->getAts() );
                $ats_api    = $this->sm->get('ApiManager')->get( $to->getAts() );
                $user_ats   = $this->sm->get('UserTable')->getNetworkByUser( $to->getAts(), $to->id );

                $exist      = $this->sm->get('AtsCandidateTable')->getByCandidateID( $from->id, $ats['id_ats'], $to->id );
                $id_api     = $exist['id_api'];

                if (array_key_exists('refresh_token', $user_ats))
                {
                    if (null === $user_ats['access_token'] || null === $user_ats['refresh_token'])
                    {
                        $this->sm->get('Log')->error('access_token or refresh_token is null. Do not execute the job.');
                        return;
                    }
                    $ats_api->setAccessToken( $user_ats['access_token'], $user_ats['refresh_token'] );
                }
                else
                {
                    if (null === $user_ats['access_token'])
                    {
                        $this->sm->get('Log')->error('access_token is null. Do not execute the job.');
                        return;
                    }
                    $ats_api->setAccessToken( $user_ats['access_token'] );
                }

                if ($to->getAts() === 'greenhouse')
                {
                    if (null === $user_ats['harvest_key'])
                    {
                        $this->sm->get('Log')->error('Harvest key is null. Do not execute the job.');
                        return;
                    }
                    $ats_api->setHarvestKey( $user_ats['harvest_key'], $user_ats['id_user'] );
                }

                $ats_api->setUser( $user_ats );

                $tagCandidate = $ats_api->tagCandidate( $id_api );
                $content    = $from->first_name . ' ' . $from->last_name . (!empty($tagCandidate) ? ' (' . $ats_api->tagCandidate( $id_api ) . ')' : '') . ' sent you a message on YBorder:' . PHP_EOL . $content;

                $ats_send = false;
                try
                {
                    if (null === $id_api)
                        throw new \Exception('id_api_null');

                    $ats_api->sendMessage( $id_api, $content, true );
                    $ats_send = true;
                }
                catch (\Exception $e)
                {
                    $this->sm->get('MessageTable')->updateMailTime( $conversation );
                    $this->getLogger()->error($e->getMessage());
                    return;
                }

                if (null !== $reply_to)
                {
                    // send to ATS as a reply #EMAIL
                    $content = nl2br(implode(PHP_EOL, $comments));

                    $this->getLogger()->warn('replying to ' . $reply_to);

                    $this->sm->get("Email")->sendRaw(['inbox', 'message', 'new'], $content, $reply_to, 'inmail@yborder.com');

                    $ats_send = true;
                }

                // if send, do not sent the YBorder's email
                if (true === $ats_send)
                {
                    $this->sm->get('MessageTable')->updateMailTime( $conversation );
                    return;
                }
            }
        }

        $this->sm->get('MessageTable')->updateMailTime( $conversation );
        if ($from != null && $to != null)
        {
            $users_to = array_merge([$to], $cc);

            foreach ($users_to as $user_to)
            {
                $this->getLogger()->normal('Send email from ' . $from->first_name . ' (' . $from->id . ') to ' . $user_to->first_name . ' (' . $user_to->id . ')');

                $this->sm->get("Email")->sendEmailTemplate(['inbox', 'message', 'new'], 'new-message-on-yborder', $user_to, 'inmail@yborder.com', null, null, [
                    'from_name'         => $from->first_name,
                    'to_name'           => $user_to->first_name,
                    'comment'           => nl2br(implode(PHP_EOL, $comments)),
                    'sender_hash'       => $hash,
                    '_headers'          => [
                        'Message-Id'    => '<' . $hash . '@yborder.com>'
                    ],
                    'id_conversation'   => $conversation->id_conversation,
                    'company_name'      => ($from->getCompany() !== null ? $from->getCompany()->name : null),
                    'profile_picture'   => $this->sm->get('CandidateService')->getPicture($from, 45, 45)
                ]);
            }

            $this->sm->get("Notifications")->chat($from, $to, array("id_conversation"=>$conversation->id, "message"=>implode(PHP_EOL, $comments)));
        }
        else
        {
            $this->getLogger()->error('Error, users not found');
        }
    }
}

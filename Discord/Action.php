<?php

namespace Core\Discord;

use Logger;
use App\User;
use Api;
use React\Promise\Deferred;

class Action
{
    public $action = null;
    public $user = null;
    public $data = [];
    public $channel = null;
    public $guild = null;
    public $discord;
    public $deferred;

    public function __construct($discord, $deferred)
    {
        $this->discord  = $discord;
        $this->deferred = $deferred;
    }

    public function formatData( $data )
    {
        foreach ($data as $key => $value)
        {
            $this->{ $key } = $value;
        }

        if (isset($data['id_user']))
            $this->user	    =User::find($data['id_user']);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle($data)
    {
        $this->formatData( $data );

        Logger::normal('action => ' . $this->action);

        return $this->{ $this->action }();
    }

    public function notification()
    {
        $messages   = str_split($this->data, 2000);
        $extra      = null;
        $promises   = [];

        foreach ($messages as $key => $message)
        {
            if($extra != null)
            {
                $message .= $extra;
            }

            if ($key != count($messages) - 1)
            {
                $message = mb_substr($message, 0, mb_strrpos($message, "\n"));
                $extra = mb_substr($message, mb_strrpos($message, "\n") + 1);
            }

            $promises[] = $this->getChannel()->sendMessage($message);
        }

        \React\Promise\all($promises)->then(function ($result) {
            Logger::info('Message send');
            $this->deferred->resolve('success');
        }, function (\Exception $e) use ($message) {
            Logger::error('Message send error ' . $e->getMessage());
            $this->deferred->resolve('error');
        });
    }

    public function getChannelName( $name = null )
    {
        if (!$name)
            $name = 'test';

        return $name;
    }

    public function getGuild()
    {
        if (!$this->guild)
        {
            $this->guild = $this->discord->guilds->get('id', config('services.discord.bot.guild'));
        }
        return $this->guild;
    }

    public function getChannel( $name = null )
    {
        if (!$this->channel)
        {
            $guild = $this->getGuild();
            $this->channel = $guild->channels->get('name', $this->getChannelName( $name ));
        }

        return $this->channel;
    }

    public function getBot()
    {
        return $this->getGuild()->members->get('id', config('services.discord.bot.id'));
    }
}
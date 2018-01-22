<?php

namespace Core\Discord;

use Illuminate\Support\Facades\Cache as CacheService;

use Logger;
use Discord\Discord;

use React\Promise\Deferred;
use Core\Model\Error as ErrorService;

class Bot
{
    
    public function start( $bot_id, $bot_token, $guild_id)
    {
        $discord = new Discord([
            'token'     => $bot_token,
            'logging'   => true
        ]);

        $this->bot_id       = $bot_id;
        $this->bot_token    = $bot_token;
        $this->guild_id     = $guild_id;
        $this->discord      = $discord;

        $discord->loop->addPeriodicTimer(10, function () use ($discord) {
            $this->repeat();
        });

        $discord->loop->addPeriodicTimer(300, function () use ($discord) {
            Logger::info('I\'m alive');
        });            

        $discord->on('ready', function ($discord) {
            
            Logger::info('READY');
            $this->discord = $discord;

            // Listen for messages.
            $discord->on('message', function ($message, $discord) {

                if (mb_strpos($message->content, '!') === 0)
                {
                    $method = 'action_' . mb_substr($message->content, 1);

                    if (true === method_exists($this, $method))
                    {
                        Logger::info($method);
                        $this->{ $method }($message, $discord);
                    }
                }
            });
        });

        $discord->run();
    }

    public function getActionClass()
    {
        return '\Core\Discord\Action';
    }

    public function nextAction()
    {
        $deferred = new Deferred();

        $action_data = $this->shift();
        
        if ($action_data)
        {
            $class  = $this->getActionClass();
            $action = new $class( $this->discord, $deferred );

            try
            {
                $action->handle($action_data);
            }
            catch (\Exception $e)
            {
                Logger::error($e->getMessage());
                ErrorService::record($e);
                $deferred->resolve( false );    
            }
        }
        else
        {
            $deferred->resolve( false );    
        }
        
        return $deferred->promise();
    }

    public function repeat()
    {
        $promise = $this->nextAction();
        $promise->then(function($result) {
            if (false !== $result)
            {
                Logger::normal($result);
                $this->repeat();
            }
            else
            {
                // Logger::normal('end_repeat');
            }
        });
    }

    public function action_help($message, $discord)
    {
        $methods = get_class_methods($this);
        $methods = array_filter($methods, function($item){
            return mb_strpos($item, 'action_') === 0;
        });
        $methods = array_map(function($item){
            return '!' . mb_substr($item, 7);
        }, $methods);

        $content = 'Commands available: ' . implode(' ', $methods);

        $message->reply( $content );
    }

    public function action_ping($message, $discord)
    {
        $message->author->user->sendMessage( 'pong!' );
    }

    public function shift()
    {
        $key    = 'discord-' . config('services.discord.bot.id');
        $data   = CacheService::get( $key );

        if (null === $data)
        {
            return [];
        }

        $result = array_shift($data);

        CacheService::forever($key, $data);

        return $result;
    }

    static public function addAction($data)
    {
        $key    = 'discord-' . config('services.discord.bot.id');
        $queue   = CacheService::get( $key );

        if (null === $queue)
        {
            CacheService::forever( $key, []);
            $queue = [];
        }

        $queue[] = $data;

        CacheService::forever($key, $queue);
    }
}

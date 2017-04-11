<?php

namespace Core\Events;
use Core\Model\UserConnector;
class SocialAddedEvent
{
    public $connector;
    public function __construct(UserConnector $connector)
    {
        $this->connector = $connector;
    }
}
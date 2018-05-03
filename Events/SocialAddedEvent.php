<?php

namespace Core\Events;
use Core\Model\ConnectorUser;
class SocialAddedEvent
{
    public $connector;
    public function __construct(ConnectorUser $connector)
    {
        $this->connector = $connector;
    }
}
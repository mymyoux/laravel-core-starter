<?php

namespace Core\Events;
use Core\Model\UserConnector;
class SocialScopeChangedEvent extends SocialAddedEvent
{
    public $scopes_removed;
    public $scopes_added;
    public function __construct(UserConnector $connector, $scopes_added, $scopes_removed)
    {
        parent::__construct($connector);
        $this->scopes_added = $scopes_added;
        $this->scopes_removed = $scopes_removed;
    }
}
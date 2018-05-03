<?php

namespace Core\Events;
use Core\Model\ConnectorUser;
class SocialScopeChangedEvent extends SocialAddedEvent
{
    public $scopes_removed;
    public $scopes_added;
    public function __construct(ConnectorUser $connector, $scopes_added, $scopes_removed)
    {
        parent::__construct($connector);
        $this->scopes_added = $scopes_added;
        $this->scopes_removed = $scopes_removed;
    }
}
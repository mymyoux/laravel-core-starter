<?php
namespace Core\Auth;
use Illuminate\Auth\SessionGuard as BaseGuard;

class Guard extends BaseGuard
{
    public function type()
    {
        if($this->check())
            return $this->user()->type;
        return NULL;
    }
}
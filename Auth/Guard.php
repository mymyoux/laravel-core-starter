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
    // public function isAdmin()
    // {
    //     return $this->type() == "admin";
    // }
    // public function isRealAdmin()
    // {
    //     return $this->isAdmin() || ($this->check() && $this->user()->isAdmin());
    // }
    public function __call($name, $params)
    {
        if(starts_with($name, "is"))
        {
            if($this->check())
                return $this->user()->$name(...$params);
            return False;
        }
        if(starts_with($name, "get"))
        {
            if($this->check())
                return $this->user()->$name(...$params);
            return NULL;
        }
        throw new \Exception($name." doesn't exist");
    }
    public function __get($name)
    {
            if($this->check())
                return $this->user()->$name;
         return NULL;
    }
}
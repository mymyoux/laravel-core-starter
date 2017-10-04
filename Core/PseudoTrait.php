<?php
namespace Core\Core;

use Core\Core\PseudoTrait\PseudoTrait as PT;
trait PseudoTrait
{
   protected $_traits = [];
   protected $traits = [];
   public function hasPseudoTrait($name)
   {
        return isset($this->_traits[$name]);
   }
   public function getPseudoTrait($name)
   {
        return $this->_traits[$name];
   }
//    public function __call($name, $arguments)
//    {
//         foreach($this->_traits as $key=>$pseudotrait)
//         {
//             if(method_exists($pseudotrait, $name))
//             {
//                 return call_user_func_array(array($pseudotrait, $name), $arguments);
//             }
//         }
//         return parent::__call($name, $arguments);//new \Exception(get_class($this).": No trait implements the method '".$name."'");
//    }
   public function __isset($key)
   {
      return ! is_null($this->_getAttribute($key));
   }
   public function __get($name)
   {
      return $this->_getAttribute($name);
   }
    public function __set($key, $value)
    {
        $this->_setAttribute($key, $value);
    }
   protected function _getAttribute($name)
   {
        if(method_exists($this, "getAttribute"))
        {
            $value = $this->getAttribute($name);
        }else
        {
            if(property_exists($this, $name))
            {
              $value = $this->$name;
            }
        }
        if(!isset($value))
        {
          foreach($this->_traits as $key=>$pseudotrait)
          {
              if(property_exists($pseudotrait, $name))
              {
                  return $pseudotrait->$name;
              }
          }
        }
        return $value;
   }
   protected function _setAttribute($name, $value)
   {
    if(method_exists($this, "setAttribute"))
    {
        $this->setAttribute($name, $value);
    }elseif(property_exists($this, $name))
    {
        $this->$name = $value;
    }else
    {
       foreach($this->_traits as $key=>$pseudotrait)
        {
            if(property_exists($pseudotrait, $name))
            {
                $pseudotrait->$name = $value;
                return;
            }
        }
     }
   }
   public function addPseudoTrait($trait)
   {
      if (null === $trait) return;

      if(is_string($trait))
      {
        if(isset($this->traits[$trait]))
        {
            $cls = $this->traits[$trait];
            $trait = new $cls();
        }else
        {
          //ignore
          return;
        }
      }
      $this->_traits[$trait->getName()] = $trait;
      $trait->link($this);
   }
}

<?php
namespace Core\Api\Annotations;


class CoreObject
{

    public function hasData()
    {
        return False;
    }
    public function exchangeAnnotation($annotation)
    {
        foreach($annotation as $key=>$value)
        {
            if(isset($value) && property_exists($this, $key))
            {
                $this->$key = $value;
            }
        }
    }
    public function exchangeArray($data)
    {
        foreach($data as $key=>$value)
        {
            if(isset($value) && property_exists($this, $key))
            {
                $this->$key = $value;
            }
        }
    }
    public function exchangeRequest($data)
    {

    }
    public function exchangeResult(&$data)
    {

    }

}

interface ICoreObjectValidation
{
    public function isValid($sm, $apiRequest);
}

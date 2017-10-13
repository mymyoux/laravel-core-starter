<?php

namespace Core\Database\Query\Processors;
use Illuminate\Database\Query\Processors\MySqlProcessor as BaseMySqlProcessor;
use Illuminate\Database\Query\Builder;

class MySqlProcessor extends BaseMySqlProcessor
{
    protected $selectListener;
    public function setSelectListener($selectListener)
    {
        $this->selectListener = $selectListener;
    }
    public function processSelect(Builder $query, $results)
    {
         $results = parent::processSelect($query, $results);
         $results = array_map([$this,"convertType"], $results);
         if(isset($this->selectListener))
            $this->selectListener->onResults($query, $results);
        return $results;
    }
    protected function convertType($item)
    {
        foreach($item as $key=>$value)
        {
            if(mb_strlen($value)<19 && is_numeric($value))
            {
                if(ctype_digit($value))
                {
                    $tmp = (int)$value;
                    if(!is_infinite($tmp))
                    {
                        $item->$key = $tmp;
                    }
                }else
                {
                    if(ctype_digit(str_replace(".","",$value)))
                    {
                        $tmp = (float)$value;
                        if(!is_infinite($tmp))
                        {
                            $item->$key = $tmp;
                        }
                    }
                }
            }
        }
        return $item;
    }
}

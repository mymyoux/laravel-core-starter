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
            $this->selectListener->onResults($results);
        return $results;
    }
    protected function convertType($item)
    {
        foreach($item as $key=>$value)
        {
            if(is_numeric($value))
            {
                if(ctype_digit($value))
                {
                    $item->$key = (int)$value;
                }else
                {
                    $item->$key = (float)$value;
                }
            }
        }
        return $item;
    }
}

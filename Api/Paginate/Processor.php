<?php

namespace Core\Api\Paginate;

use Illuminate\Database\Query\Builder;

class Processor extends \Illuminate\Database\Query\Processors\Processor
{
   protected $lastresults;
   protected $paginate;
   public function __construct($paginate)
   {
        $this->paginate = $paginate;
   }
    public function processSelect(Builder $query, $results)
    {
        $this->lastresults = parent::processSelect($query, $results);
        $this->paginate->onResults($this->lastresults);
        return $this->lastresults;
    }
    public function getLastResults()
    {
        return $this->lastresults;
    }
}

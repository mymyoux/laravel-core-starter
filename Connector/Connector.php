<?php

namespace Core\Connector;

use Illuminate\Database\Eloquent\Model;
use DB;

class Connector extends \Tables\Model\Connector
{
    protected $table = 'connector';
    protected $primaryKey = 'id_connector';

    static public function getConnector( $name )
    {
    	$connector = Connector::where('name', '=' , $name)->first();

    	return $connector;
    }

    public function isPrimary()
    {
    	return (bool) $this->is_primary;
    }

}

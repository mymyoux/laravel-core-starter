<?php

namespace Core\Connector;

use Core\Connector\Connector;

class Manager
{
	public function get( $api, $data )
	{
		$class 		= '\Core\Connector\\' . ucfirst($api);
		$connector 	= new $class( $data );

		$model = Connector::getConnector( $api );
		$connector->setConnectorModel( $model );

		return $connector;
	}
}

<?php
namespace Core\Queue;
use Core\Queue\Job;

class FJob
{

	public static function create( $name, ...$arguments )
	{
		$class = '\Core\Queue\\' . ucfirst($name);

		$object     = new $class(...$arguments);
		$job 		= new Job($name, $object->data);

        return $job;
	}
	 /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

}

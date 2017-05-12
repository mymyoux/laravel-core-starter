<?php
namespace Core\Services;
use Core\Queue\Job as QJob;
use Queue;

class Job
{
    public static function putRaw( $tube, $data )
    {
        $beanstalk = Queue::getPheanstalk();

        $id = $beanstalk->putInTube($tube, json_encode($data));

        return $id;
    }

	public static function create( $class, $arguments  = NULL)
	{
        // if(!defined($class."::name"))
        // {
        //     throw new \Exception("Queue $class doesn't have a constant name");
        // }
		$job = new QJob($class, $arguments);
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
        return (new QJob)->$method(...$parameters);
    }

}

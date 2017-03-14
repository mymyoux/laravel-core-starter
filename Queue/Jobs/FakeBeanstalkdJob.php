<?php

namespace Core\Queue\Jobs;



class FakeBeanstalkdJob
{
	public $job;
	protected $failed;
	public function __construct($job)
	{
		$this->job = $job;
	}   
	public function hasFailed()
	{
		return $this->failed === true;
	}
	public function payload()
	{
		return NULL;
	}
	public function getOriginalJob()
	{
		return $this->job;
	}
	public function maxTries()
	{
		return $this->job->tries;
	}
	public function attempts()
	{
		return $this->job->current_tries;
	}
	public function tries()
	{
		return $this->job->current_tries++;
	}
}

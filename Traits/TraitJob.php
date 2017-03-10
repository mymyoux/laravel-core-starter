<?php

namespace Core\Traits;

trait TraitJob
{
	protected $user = null;

	public function setUser( $user )
	{
		$this->user = $user;

		return $this;
	}
}

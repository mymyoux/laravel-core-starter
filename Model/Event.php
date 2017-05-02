<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;
use Illuminate\Database\Eloquent\Builder;
class Event extends Model
{
	/**
	 * traitment on going
	 */
	const STATE_PENDING = "pending";
	/**
	 * failed
	 */
	const STATE_FAILED = "failed";
	/**
	 * done
	 */
	const STATE_DONE = "done";
	/**
	 * Have to be traited
	 */
	const STATE_CREATED = "created";

	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';


    protected $table = 'event';
    protected $primaryKey = 'id_event';

    protected $fillable = ['external_id','external_type','type','state','data','result','owner_id','owner_type'];
	public function owner()
    {
        return $this->morphTo();
    }
	public function external()
    {
        return $this->morphTo();
    }
	protected function create($type, $data = NULL, $owner = NULL, $external = NULL)
	{
		$event = new Event();
		$event->type = $type;
		if(isset($external))
			$event->external()->associate($external);
		if(isset($owner))
			$event->owner()->associate($owner);
		if(isset($data))
		{
			$event->data = json_encode($data);
		}
		return $event;
	}
	public function data()
	{
		return isset($this->data)?json_decode($this->data):NULL;
	}
	public function result($data)
	{
		$this->result = $data;
	}

	 protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('state', function (Builder $builder) {
            $builder->whereNotIn('state', [static::STATE_FAILED,static::STATE_DONE]);
        });
    }
}

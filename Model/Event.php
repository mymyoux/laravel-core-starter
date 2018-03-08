<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use DB;
use Auth;
use Illuminate\Database\Eloquent\Builder;
class Event extends \Tables\Model\Event
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
	const STATE_POSTPONED = "postpone";
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
	public function answer($result, $state = NULL, $postpone_time = NULL, $id_user = NULL)
	{
		if($state === NULL)
		{
			$state =  isset($postpone_time)?static::STATE_POSTPONED:static::STATE_DONE;
		}
		if($state == static::STATE_POSTPONED || $postpone_time === True)
		{
			if(!isset($postpone_time) || $postpone_time === True)
			{
				$postpone_time = date('Y-m-d H:i:s', time());
			}
		}
		if(!isset($id_user))
		{
			$id_user = Auth::id();
		}
		if($id_user === False)
		{
			$id_user = NULL;
		}
		$data = ["id_event"=>$this->getKey(), "step"=>$this->step,"result" => json_encode($result), "id_user"=>$id_user,"state"=>$state,"notification_time"=>$postpone_time];
		DB::table('event_action')->insert($data);
		foreach($data as $key=>$value)
		{
			if($key == "id_user")
			{
				continue;
			}	
			$this->$key = $value;
		}
		if(isset($data["notification_time"]))
		{
			$this->state = $state;//static::STATE_POSTPONED;
		}
		$handler = $this->type;
		$this->save();
		$handler::handle($this);
	}
	public function done($result = NULL, $id_user = NULL)
	{
		return $this->answer($result, static::STATE_DONE, NULL, $id_user);
	}
	public function fail($result = NULL, $id_user = NULL)
	{
		return $this->answer($result, static::STATE_FAILED, NULL, $id_user);
	}
	public function nextStep($step, $result, $state = NULL, $postpone_time = NULL, $id_user = NULL)
	{
		$this->step = $step;
		if($state === NULL)
		{
			$state =  isset($postpone_time)?static::STATE_POSTPONED:static::STATE_PENDING;
		}
		return $this->answer($result, $state, $postpone_time, $id_user);
	}
	protected function get($external)
	{
		return static::where(["external_type"=>get_class($external),"external_id"=>$external->getKey()])->first();
	}
	protected function create($data = NULL, $owner = NULL, $external = NULL, $notification = NULL)
	{
		if(static::class===Event::class)
		{
			throw new \Exception("you can't use event class directly");
		}
		$cls = static::class;
		$event = new $cls();
		$event->type = $cls;
		$event->notification_time = $notification;
		if(isset($external))
			$event->external()->associate($external);
		if(isset($owner))
			$event->owner()->associate($owner);
		if(isset($data))
		{
			$event->data = json_encode($data);
		}
		if(!isset($event->notification_time))
		{
			$event->notification_time = date('Y-m-d H:i:s', time());
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


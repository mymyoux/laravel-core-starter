<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;
use Route;
use App;
class Webhook extends \Tables\Model\Webhook
{
	protected static function boot()
    {
        parent::boot();
    }
    public function external()
    {
        return $this->morphTo();
    }
}

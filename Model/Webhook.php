<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Tables\WEBHOOK as Table;
use Auth;
use Route;
use App;
class Webhook extends \Tables\Model\Webhook
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = Table::TABLE;
	protected $primaryKey = 'id_webhook';

	protected static function boot()
    {
        parent::boot();
    }
    public function external()
    {
        return $this->morphTo();
    }
}

<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
// use Tables\ERROR as TERROR;
use Auth;
use Route;
use Core\Services\IP;
use App;
use Request;
use Illuminate\Console\Application;
class Mail extends Model
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = 'mail';
	protected $primaryKey = 'id';
    protected $fillable = [
        'type','id_user','subject','recipient','sender','message','from','created_time','updated_time','reason','status','id_mandrill'
    ];
   
}

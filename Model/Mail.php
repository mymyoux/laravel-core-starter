<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Auth;
use Route;
use Core\Services\IP;
use App;
use Request;
use Illuminate\Console\Application;
use DB;
class Mail extends \Tables\Model\Mail
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = 'mail';
	protected $primaryKey = 'id';
    protected $fillable = [
        'type','id_user','subject','recipient','sender','message','from','created_time','updated_time','reason','status','id_mandrill'
    ];
    protected function getLastRejectReason($id_user)
    {
        $result = Mail::join('mail_webhook','mail_webhook.id_mandrill','=','mail.id_mandrill')->select(["mail_webhook.type"])->where(["id_user"=>$id_user])->whereNotIn('mail_webhook.type', ['click','open','deferral','send', 'reject'])->orderBy('mail_webhook.created_time','DESC')->first();
        if(isset($result))
        {
            return $result->type;
        }
        return NULL;
    }
    protected function getIDUserFromIDMandrill($id_mandrill)
    {
        $mail = Mail::where(["id_mandrill"=>$id_mandrill])->first();
        if(!isset($mail))
            return null;
        return $mail->id_user;
    }
    protected function getByMandrill($id_mandrill)
    {
        return Mail::where(["id_mandrill"=>$id_mandrill])->first();
    }
}

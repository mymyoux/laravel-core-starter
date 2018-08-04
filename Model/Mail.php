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
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'type','id_user','subject','recipient','sender','message','from','created_at','updated_at','reason','status','id_mandrill'
    ];
    protected function getLastRejectReason($id_user)
    {
        $result = Mail::join('mail_webhook','mail_webhook.id_mandrill','=','mail.id_mandrill')->select(["mail_webhook.type"])->where(["id_user"=>$id_user])->whereNotIn('mail_webhook.type', ['click','open','deferral','send', 'reject'])->orderBy('mail_webhook.created_at','DESC')->first();
        if(isset($result))
        {
            return $result->type;
        }
        return NULL;
    }

    protected function noEmailSince($id_user)
    {
        $result = Mail::join('mail_webhook','mail_webhook.id_mandrill','=','mail.id_mandrill')->select(["mail_webhook.*"])->where(["id_user"=>$id_user])->whereNotIn('mail_webhook.type', ['click','open','deferral','send', 'reject'])->orderBy('mail_webhook.created_at','DESC')->first();
        if(isset($result))
        {
            return $result->created_at;
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

    public function scopeFilterByType($filter, $types, $id_user = NULL, $date = NULL)
    {
        if(!is_string($types))
        {
            $types = implode('-', $types);
        }
        $filter->where("type","=",$types);
        if(isset($id_user))
        {
            $filter->where("id_user","=",$id_user);
        }
        if(isset($date))
        {
            //TODO:test this + maybe authorize use of carbon instead of php date
            $filter->where(DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d")'),'=', date('Y-m-d', strtotime($date)));
        }

        return $filter;

        // if (null !== $date)
        //     $where->and->expression('DATE_FORMAT(tp.created_at, "%Y-%m-%d") = "' . date('Y-m-d', strtotime($date)) . '"', []);

        // $request = $this->select([ 'tp' => self::TABLE ])
        //             ->where( $where )
        //             ->order('created_at DESC');

        // $result = $this->execute($request);

        // $data = $result->current();

        // if (!$data) return null;
        // return $data;
        //  */
    }
}

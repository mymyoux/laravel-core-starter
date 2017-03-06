<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Tables\ERROR as TERROR;
use Auth;
use Route;
use Core\Services\IP;
use App;
use Request;
use Illuminate\Console\Application;
class Error extends Model
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = TERROR::TABLE;
	protected $primaryKey = 'id';
	
	protected static function boot()
    {
        parent::boot();
    }
    protected function record($exception)
    {
    	$info = [];


        $info = array();
        $info["type"] = get_class($exception);
        $info["code"] = $exception->getCode();
        $info["message"] = $exception->getMessage();
        $info["file"] = $exception->getFile();
        $info["line"] = $exception->getLine();
        $info["stack"] = $exception->getTraceAsString();
        $exception->test = "ok";
        $info["ip"] = IP::getRequestIP();
        global $argv;
        if (App::runningInConsole())
        {
            global $argv;
            $info["url"] = "php ".implode(" ", $argv);
        }else
        {
            $info["url"] = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        }
        try
        {
            if(isset($_GET))
            {
                $info["get"] = json_encode($_GET);
            }

        }catch(\Exception $e)
        {
            $info["get"] = "error";
        }
        try
        {
            if(isset($_POST))
            {
                $info["post"] = json_encode($_POST);
            }

        }catch(\Exception $e)
        {
            $info["post"] = "error";
        }

        $info["id_user"] = 0;

        try
        {
            /**
             * @var \Core\Service\Identity $identity
             */
            $user = Auth::getUser();
            if(isset($user))
            {
                $info["id_user"] = $user->id_user;
                $info["id_real_user"] = $user->getRealUser()->id_user;
            }
        }catch(\Exception $e)
        {

        }
        //return id
        return static::insertGetId($info);

        // try
        // {
        //     if($this->sm->get("Identity")->isLoggued())
        //     {
        //         $info["user"] = $this->sm->get("Identity")->user;
        //     }
        //     $info["id_error"] = $this->table()->lastInsertValue;
        //     $this->sm->get("Notifications")->sendError($info);
        // }catch(\Exception $e)
        // {

        // }
    }
}

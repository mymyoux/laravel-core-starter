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
class Error extends \Tables\Model\Error
{


    protected static $muted = False;

    public static function mute()
    {
        static::$muted = True;
    }
    public static function unmute()
    {
        static::$muted = False;
    }
    public static function isMuted()
    {
        return static::$muted;
    }

	protected static function boot()
    {
        parent::boot();
    }
    protected function record($exception)
    {
        if(App::isLocal())
        {
            //return;
        }
        if(static::isMuted())
        {
            return;
        }
    	$info = [];


        $info = array();
        $info["type"] = get_class($exception);
        $info["code"] = $exception->getCode();
        $info["message"] = $exception->getMessage();
        $info["file"] = $exception->getFile();
        $info["line"] = $exception->getLine();
        $info["stack"] = $exception->getTraceAsString();
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
            $user = Auth::user();
            if(isset($user))
            {
                $info["id_user"] = $user->getKey();
                $info["id_real_user"] = $user->getRealUser()->getKey();
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

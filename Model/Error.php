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
use Core\Exception\ApiException;
use App\Model\Error\Javascript;

class Error extends \Tables\Model\Error//\Tables\Model\Error
{
    protected $table = "error";

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
        $info["source"] = 'laravel';
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

        if ($exception instanceof ApiException)
        {
            $info['is_api'] = true;
        }

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



    protected function recordJS($data, $hardware = NULL, $error = NULL)
    {
        $keys = array("id_user","session","error_name","error_message","error_url","error_line","error_column","error_stack","hardware_cordovaVersion",
           "hardware_os","hardware_uuid","hardware_osVersion","hardware_android","hardware_blackberry","hardware_ios","hardware_mobile","hardware_windowsPhone",
           "hardware_screenWidth","hardware_screenHeight","hardware_landscape","hardware_portrait","hardware_browser", 'hardware_cookie' ,"url","type");

        if (!isset($hardware))
        {
            $result = Javascript::where('session',  '=', $data['session'])->whereNotNull('hardware_os')->first();

             if($result)
             {
                $hardware = array();
                foreach($result as $key=>$value)
                {
                    if(mb_substr($key, 0, 9) == "hardware_")
                    {
                        $hardware[mb_substr($key,9)] = $value;
                    }
                }
             }
        }
        if(isset($hardware))
        {
            foreach($hardware as $key=>$value)
            {
                $data["hardware_".$key] = $value;
            }
        }
        if(isset($error))
        {
            foreach($error as $key=>$value)
            {
                $data["error_".$key] = $value;
            }
        }

        $error = new Javascript;
        $values = array();
        foreach($keys as $key)
        {
            if(isset($data[$key]))
            {
                $error->{ $key } = $data[$key];
            }
        }

        $error->save();

        return $error;
    }
}

<?php
namespace Core\Model;
use Core\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Tables\STATS_API_CALL;
use Auth;
use Route;
use App;
use Request;

class Api extends \Tables\Model\Stats\Api\Call
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

	protected $table = STATS_API_CALL::TABLE;
	protected $primaryKey = 'id_call';

	const API_NAME = "v2";
	protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('api', function (Builder $builder) {
            $builder->where(STATS_API_CALL::api, '=', Api::API_NAME);
        });
    }
    protected function record($request, $response)
    {
		if(App::isLocal())
		{
			return;
		}
    	$data = ["api"=>Api::API_NAME];

    	$user = Auth::getUser();
    	if(isset($user))
    	{
    		$data["id_user"] = $user->getRealUser()->getKey();

    		if($user->isImpersonated())
    		{
    			$data["id_user_impersonated"] = $user->getKey();
    		}
		}
		$data["path"] = $request->getPathInfo(); // Use $request path instead of route path, resolved bug sub api call
    	$data["method"] = $request->method();
    	$data["params"] = json_encode($request->all());
    	$data["value"] = method_exists($response , "getOriginalContent")?$response->getOriginalContent():$response;
    	$data["success"] = is_array($data["value"]) && !isset($data["value"]["exception"]);
    	if(isset($data["value"]["exception"]))
    	{
    		$data["id_error"] = $data["value"]["exception"]["id"];
    		if(!isset($data["value"]["exception"]["api"]))
    		{
    			$data["error_type"] = 0;
    		}else
    			$data["error_type"] = $data["value"]["exception"]["api"];
    	}
    	$duration = microtime(true)-LARAVEL_START;
        $duration = floor($duration*1000);

		$data["value"] = json_encode($data["value"]);
    	$data["duration"] = $duration;
		static::insert($data);
    	return;
    }
}

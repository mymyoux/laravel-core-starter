<?php

namespace Core\Exception;
use Logger;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Core\Exception\ApiException;
use Core\Exception\Exception as CoreException;
use Core\Http\Middleware\Api\Jsonp;
use Core\Model\Error as ErrorService;
use App;
use Auth;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
    	if(config('database.connections.mysql.database') == "forge" ){
    	   Logger::error('Are you sure to have correctly setup your .env file ? ');
    		return;//	dd($exception);
    	}

        try
        {
            if(class_exists('App') && App::runningInConsole())
            {
                try
                {
                    if(strpos($exception->getTraceAsString(), __FILE__)===False)
                      ErrorService::record($exception);
                }catch(\Exception $e)
                {
                }
            }
            parent::report($exception);
        }catch(\Exception $e)
        {
            // dd($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        $rawexception = CoreException::convertToJsonObject($exception);

        if(!(config('database.connections.mysql.database') == "forge"))
        {
            $rawexception["id"] = ErrorService::record($exception);
        }

        $allowed_ip = config('app.ip_debug')??[];
        if((!Auth::check() || !Auth::user()->isAdmin()) && !config('app.debug') && !in_array(App::ip(), $allowed_ip) )
        {
            //remove extra data
            $rawexception = [
                "message"=>$rawexception["message"],
                "fatal"=>$rawexception["fatal"],
                "code"=>$rawexception["code"]];
        }

        return Jsonp::convert($request, ["exception"=>$rawexception]);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}

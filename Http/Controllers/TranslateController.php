<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;
use Api;
use Core\Exception\ApiException;
use \Core\Api\Paginate;
use Core\Api\Annotations as ghost;
use App;
use Notification;

use Db;
use Sheets;
use Google;
use Job;
use Core\Jobs\Test;
use Logger;
use Apiz;
use Crawl as CrawlService;

use Core\Model\Crawl;
use Core\Model\CrawlAttempt;
use Core\Model\Translation;
use Illuminate\Support\Facades\Redis;
class TranslateController extends Controller
{
     /**
     * /translate/resolve
     * @ghost\Param(name="key", required=true)
     * @ghost\Param(name="locale", required=true)
     * @ghost\Param(name="all", required=false,default=false)
     * @return JsonModel
     */
    public function resolve(Request $request)
    {
        $key = $request->input('key');
        $locale = $request->input('locale');
        $all = $request->input('all');
        if(!Translation::isSupportedLocale($locale))
        {
            $locale = Translation::DEFAULT_LOCALE;
        }
        if(!is_array($key))
        {
            $key = [$key];
        }
        if($all)
        {
            $key = array_map(function($k)
            {
                return explode(".", $k)[0].'.';
            }, $key);
        }
        //get all subkeys
        $translation = Translation::translates($key, $locale, Auth::check()?Auth::user()->type:NULL, True);
       return ["translations"=>$translation->map(function($item)
       {
           $data = ["key"=>$item->fullKey(),"singular"=>$item->singular];
           if(isset($item->plurial))
           {
               $data["plurial"] = $item->plurial;
           }
           $data["updated_time"] = $item->updated_time->format("Y-m-d H:i:s.u");
           return $data;
       }),"locale"=>$locale];
    }

	 /**
     * /translate/update
     * @ghost\Param(name="keys", required=true)
     * @ghost\Param(name="locale", required=false)
     * @return JsonModel
     */
    public function update(Request $request)
    {
        $keys = $request->input('keys');
        $locale = $request->input('locale');

        if(!Translation::isSupportedLocale($locale))
        {
            $locale = Translation::DEFAULT_LOCALE;
        }
        $hkeys = [];
        foreach($keys as $key=>$date)
        {
            $hkeys[] = $key.".";
        }
        $translation = Translation::translates($hkeys, $locale, Auth::check()?Auth::user()->type:NULL, True);
        return $translation->filter(function($item) use($keys)
        {
            return $item->updated_time->format("Y-m-d H:i:s.u")>$keys[$item->shortKey()];
        })->values()->map(function($item)
        {
           $data = ["key"=>$item->fullKey(),"singular"=>$item->singular];
           if(isset($item->plurial))
           {
               $data["plurial"] = $item->plurial;
           }
           $data["updated_time"] = $item->updated_time->format("Y-m-d H:i:s.u");
           return $data;
        });
    }
}

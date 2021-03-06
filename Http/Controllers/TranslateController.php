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

use DB;
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
use Core\Model\LangModel;
use Illuminate\Support\Facades\Redis;
use Response;
use Core\Jobs\Translation as TranslationJob;
class TranslateController extends Controller
{
    /**
     * /translate/resolve
     * @ghost\Param(name="path", required=true)
     * @ghost\Param(name="value", required=true)
     * @ghost\Param(name="locale", required=true)
     * @return JsonModel
     */
    public function check(Request $request)
    {
        $path = $request->input('path');
        $value = $request->input('value');
        $locale = $request->input('locale');

        $translation = Translation::where('path', '=', $path)->where('singular', '=', $value)->first();

        if ($translation && $translation->locale != $locale)
        {
            $translation = Translation::where('path', '=', $path)->where('locale', '=', $locale)->first();
            
            return $translation->singular;
        }

        return null;
    }
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
        $translation = Translation::translates($key, $locale, Auth::type(), True);
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
     * @ghost\Role("admin")
     * @return void
     */
    public function clean(Request $request)
    {
        $translates = Translation::get();
        $translates = $translates->filter(function($item)
        {
           return preg_match("/[^ \<>=\/é@0-9a-zA-Z\._-]+/", $item->path, $matches)!=0;
        });
        foreach($translates as &$translate )
        {
             $translate->path = preg_replace("/[^ \<>=\/é@0-9a-zA-Z\._-]+/", "", $translate->path);
            Api::path("translate/edit")->param("id", $translate->getKey())->param("locale", $translate->locale)->param("path", $translate->path)->param("type", $translate->type)->param("singular", $translate->singular)->param("plurial", $translate->plurial)->send();
        }
       return 1;
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
        $translation = Translation::translates($hkeys, $locale, Auth::type(), True);
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
    /**
     * @ghost\Role("admin")
     * @ghost\Param(name="id",requirements="\d+",type="int", required=true)
     * @ghost\Param(name="locale", required=true)
     * @ghost\Param(name="path", required=true)
     * @ghost\Param(name="type", required=false)
     * @ghost\Param(name="singular", required=false)
     * @ghost\Param(name="plurial", required=false)
     */
    public function edit(Request $request)
    {
        $translation = Translation::find($request->input('id'));
        if(!isset($translation))
        {
            throw new ApiException('bad_id');
        }
        $translation->locale = $request->input('locale');
        $translation->path = $request->input('path');
        $translation->path = preg_replace("/[^ \<>=é\/@0-9a-zA-Z\._-]+/", "", $translation->path);
        $paths = explode(".", $translation->path);
        if(count($paths) == 1){
            $translation->path = "app.".$translation->path;
            $paths = explode(".", $translation->path);
        }
        $translation->controller = $paths[0];
        if(count($paths)>1)
            $translation->action = $paths[1];
        if(count($paths)>2)
            $translation->key = $paths[2];

        $translation->type = $request->input('type');
        $translation->singular = $request->input('singular');
        $translation->plurial = $request->input('plurial');
        $translation->save();
        $this->rebuild();
        return $translation;
    }
    /**
     * @ghost\Role("admin")
     * @ghost\Param(name="id",requirements="\d+",type="int",required=true)
     */
    public function delete(Request $request)
    {
        Translation::destroy($request->input('id'));
        $this->rebuild();
    }
    /**
     * @ghost\Role("admin")
     * @ghost\Param(name="id",requirements="\d+",type="int")
     * @ghost\Param(name="locale", required=true)
     * @ghost\Param(name="path", required=true)
     * @ghost\Param(name="type", required=false)
     */
    public function valid(Request $request)
    {
        $path = $request->input('path');
        $paths = explode(".", $path);
        if(count($paths) == 1){
            $path = "app.".$path;
        }

        $locale = DB::table('translate_locales')->where(["locale"=>$request->input('locale')])->first();
        if(!isset($locale))
            return ["column"=>"locale","error"=>"locale doesn't exist"];

        $type = $request->input('type');
        if(isset($type))
        {
            $type = DB::table('user')->where(["type"=>$type])->first();
            if(!isset($type))
                return ["column"=>"type","error"=>"type doesn't exist"];

        }
        $where = ["locale"=>$request->input('locale'),"path"=>$path];
        if($request->input('type'))
        {
            $where["type"] = $request->input('type');
        }
        $req = Translation::where($where);
        if(!$request->input('type'))
        {
            $req->whereNull('type');
        }
        if($request->input('id'))
        {
            $req = $req->where('id','!=',$request->input('id'));
        }
        $result = $req->first();
        if(isset($result))
            return ["column"=>"path","error"=>"key/locale already exists"];
        if($request->input('id'))
        {
            return (int)$request->input('id');
        }
        $translation = new Translation;
        $translation->locale = $request->input('locale');
        $translation->type = $request->input('type');
        $translation->path = $request->input('path');
        $translation->save();
        return (int)$translation->id;
    }
    /**
     * @ghost\Role("admin")
     * @ghost\Param(name="filter",required=false)
     * @ghost\Param(name="search",required=false)
     * @ghost\Paginate(allowed="id,created_time,updated_time,path,locale,singular,plurial",keys="created_time",directions="-1", limit=10)
     */
    public function list(Request $request, Paginate $paginate)
    {
        $filter = $request->input('filter');
        $search = $request->input('search');
        $request = Translation::whereNotNull('locale');

        if(isset($filter))
        {
            if(isset($filter["path"]))
            {
                $request = $request->where('path','like','%'.$filter["path"].'%');
            }
            if(isset($filter["locale"]))
            {
                $request = $request->where('locale','like','%'.$filter["locale"].'%');
            }
             if(isset($filter["singular"]))
            {
                $request = $request->where('singular','like','%'.$filter["singular"].'%');
            }
             if(isset($filter["plurial"]))
            {
                $request = $request->where('plurial','like','%'.$filter["plurial"].'%');
            }
             if(isset($filter["type"]))
            {
                $request = $request->where('type','like','%'.$filter["type"].'%');
            }
        }
        if(isset($search) && isset($filter))
        {
            $request = $request->where(function ($query) use($filter, $search) {
                $keys =array_keys($filter);
                foreach($keys as $key)
                {
                    $query = $query->orWhere($key,'like','%'.$search.'%');
                }
            });
        }
        $request = $paginate->apply($request, "translate");
        
        $data= $request->select(["*",DB::raw("IF(path IS NULL,CONCAT(controller,'.',action,'.',`key`),path) as path")])->get()->makeVisible(['id','created_time'])->map(function($item)
        {
            if(!isset($item->path))
            {
                $item->path = $item->controller.".".$item->action.".".$item->key;
            }
            return $item;
        })->makeHidden(['controller','action','key'])->toArray();
        
        return $data;
    }   
    protected function rebuild()
    {
        Job::create(TranslationJob::class)->throttle(60);
    }
}

<?php

namespace Core\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Auth;
use Api;
use Core\Exception\ApiException;
use \Core\Api\Paginate;
use Exception;
use Core\Api\Annotations as ghost;
use App;
use Notification;

use Db;
use Sheets;
use Google;
use Job;
use Core\Jobs\Test;
use Logger;
use Core\Util\ModuleHelper;
use Illuminate\Support\Facades\Redis;
use stdClass;
use File;
use Tables\TEMPLATE;
use View;
use \Illuminate\View\Compilers\BladeCompiler;
class VueController extends Controller
{
    const DEFAULT_EXTENSION = "vue";
    protected $folders;
    protected $paths;
    protected $extension;
    protected $skiphelpers = False;
	/**
	 * Get template for a view
     * @ghost\Param(name="path",required=true)
     * @ghost\Param(name="type",required=false)
     * @ghost\Param(name="skiphelpers",requirements="boolean",default=false,required=false)
     * @return Asked template
	 */
    public function get(Request $request)
    {   
        $this->skiphelpers = $request->input('skiphelpers');
        $this->extension = static::DEFAULT_EXTENSION;
        $this->paths = $this->getPaths();
        $folders = ["app", "core"];
        $type = $request->input('type', Auth::check()?Auth::user()->type:NULL);
        if(isset($type))
            array_unshift($folders, $type);
        $this->folders = $folders;
        $requestedPath = $request->input('path');

        list($template, $components) = $this->load($requestedPath);
        return 
        [
            "template"=>$template,
            "components"=>$components,
            "version"=>$this->getVersion($request)
        ];
    } 
    /**
     * @ghost\Api
     * @return list of all existing template paths
     */
    public function getAll(Request $request)
    {
        $this->paths = $this->getPaths();
        $paths = [];
        foreach($this->paths as $path)
        {
            $files = File::allfiles($path);
            foreach($files as $file)
            {
                if($file->getExtension() !== ViewController::DEFAULT_EXTENSION)
                {
                    continue;
                }
                $subpath = substr($file->getPathname(), strlen($path)+1);
                $paths[] = substr(join("/", array_slice(explode("/", $subpath), 1)), 0, -strlen(ViewController::DEFAULT_EXTENSION)-1);

            }
        }
        return array_values(array_unique($paths));
    }
    /**
	 * Get template version
     * @ghost\Param(name="path",required=true)
     * @ghost\Param(name="type",required=false)
     * @return Get template version or md5 if not found
	 */
    public function getVersion(Request $request)
    {
        $path = $request->input('path');
        $type = $request->input('type', Auth::check()?Auth::user()->type:NULL);
        $folders = ["app", "core"];
        if(isset($type))
            array_unshift($folders, $type);

        $requests = [];
        
        foreach($folders as $folder)
        {
            $requests[] =  TEMPLATE::select('*')
            ->where('type', '=', $folder)
            ->where('path', '=', $path);
        }
        
        $union = $requests[0];
        for($i=1; $i<count($requests); $i++)
        {
            $union->union($requests[$i]);
        }

        $result = DB::table( DB::raw("({$union->toSql()}) as temps") )
        ->mergeBindings($union)
        ->groupBy('temps.path')->first();
        if(!isset($result))
        {
            return 0;
        }
        return $result->version;
    }
    /**
     * Tests versions of given paths 
     *  @ghost\Param(name="templates",array=true,required=true)
     *  @return array List of expired paths
     */
    public function getExpired(Request $request)
    {
        $templates = $request->input('templates');

        $type = Auth::check()?Auth::user()->type:"app";
        $request = TEMPLATE::select('path')->where('type','=',$type)
        ->where(function($request) use($templates)
        {
            foreach($templates as $template)
            {
                $request = $request->orWhere(function($query) use($template)
                {
                    $query->where('path','=',$template["url"]);
                    if(isset($template["version"]))
                    {
                        $query->where("version",'<>',$template['version']);
                    }else
                    if(isset($template["md5"]))
                    {
                        $query->where("md5",'<>',$template['md5']);
                    }
                });
            }
            
        });
       $results = $request
        ->get()->pluck('path')->all();
        return $results;
    }
    protected function getPaths()
    {
        $modules = ModuleHelper::getModulesFromComposer();;
        $paths = [];
        foreach($modules as $module)
        {
            $path = base_path(join_paths($module["path"], "Http", "views"));
            if(file_exists($path))
            {
                $paths[] = $path;
            }
        }
        return $paths;
    }
    protected function load($requestedPath, $folders = NULL, $paths = NULL)
    {
        $this->components = [];
        $content = $this->_load($requestedPath, $folders = NULL, $paths = NULL);
        $components = $this->components;
        $this->components = null;
        return [$content, $components];
    }
    protected function _load($requestedPath, $folders = NULL, $paths = NULL)
    {
        if(!isset($folders))
        {
            $folders = $this->folders;
        }elseif(!is_array($folders))
        {
            $folders = [$folders];
        }
        if(!isset($paths))
        {
            $paths = $this->paths;
        }elseif(!is_array($paths))
        {
            $paths = [$paths];
        }
        $content = NULL;
        foreach($folders as $folder)
        {
            foreach($paths as $path)
            {
                $full_path = join_paths($path, $folder, $requestedPath).".".$this->extension;
                if(file_exists($full_path))
                {
                    $foundPath = new stdClass();
                    $foundPath->path = $path;
                    $foundPath->full_path = $full_path;
                    $foundPath->folder = $folder;
                    $foundPath->requestedPath = $requestedPath;
                    $content = file_get_contents($full_path);
                    // ob_start();
                    // echo App::make(BladeCompiler::class)->compileString($content)->render();
                    // $content = ob_get_contents();
                    // ob_end_clean();
                    // dd($content);
                    //TODO:use .vue as .blade && compile php files into another directory
                    //@see PhpEngine::evaluatePath
                    break 2;
                }
            }
        }
        if(!isset($content))
        {
            return NULL;
        }
        return $this->parse($content, $foundPath);
    }
    protected function parse($content, $path)
    {
        $content = preg_replace_callback("/\(\(([^\)]+)\)\)/", function($matches) use($path, $content)
        {
            $line = $matches[1];
           if(starts_with($line,"#"))
            {
                $replacement = $this->_helpers(substr($line, 1), $path);
            }else
            {
                $replacement = $this->_translate($line, $path);
            }
            return $replacement;
        }, $content);
        $count = preg_match_all("/<component-([^> ]+)/", $content, $matches);
        if($count)
        {
            foreach($matches[1] as $match)
            {
                if(!in_array($match, $this->components))
                {
                    $this->components[] = $match;
                }
            }
        }
        return $content;
    }
    protected function _translate($content, $path, $line = 0)
    {
        //TODO:handle
        return $content;
    }
    protected function _helpers($content, $path, $line = 0)
    {
        $parts = explode(" ", $content);
        $key = $parts[0];
        if($this->skiphelpers && !in_array($key, ["parent", "include"]))
        {
            //skip helpers (skip random data)
            return $content;
        }
        try
        {
            return $this->{"__$key"}(join(" ",array_slice($parts, 1)), $path);
        }catch(Exception $e)
        {
            $exception = new Exception($e->getMessage().":".$line);
            throw $exception;
        }
    }
    protected function __include($content, $path)
    {
         if(trim($content) == "")
        {
            throw new Exception("((#include)) can't be specified without url");
        }
        $subcontent = $this->_load($content);
        if(!isset($subcontent))
        {
            throw new Exception('((#include '.$content.')) file not found: '.$path->requestedPath);
        }
        return $subcontent;
    }
    protected function __parent($content, $path)
    {
        $index = array_search($path->folder, $this->folders);
        if($index === count($this->folders)-1)
        {
            throw new Exception("((#parent)) can't be used on bottom level folder: ".$path->requestedPath);
        }
        $index++;
        $subcontent = $this->_load($path->requestedPath, $this->folders[$index]);
        if(!isset($subcontent))
        {
            throw new Exception('((#parent)) file not found: '.$path->requestedPath);
        }
        return $subcontent;
    }
    protected function __url($content, $path)
    {
        if(trim($content) == "")
        {
            throw new Exception("((#url)) can't be specified without url");
        }
        $parts = explode(" ", $content);
        $url = $parts[0];
        $params = NULL;
        if(count($parts)>1)
        {
            $params = join(" ", array_slice($parts, 1));
            $params = json_decode($params, True);
        }
        if(isset($params))
            return route($url, $params);
        return route($url);
    }
    protected function __partial($content, $path)
    {
        $parts = explode(" ", $content);
        $url = $parts[0];
        $data = NULL;
        if(count($parts)>1)
        {
            $data = join(" ", array_slice($parts, 1));
        }

        $name = trim($url);
        $name = '"'.$name.'"';
        $name = str_replace("{{", '"+', $name);
        $name = str_replace("}}", '+"', $name);
        while(starts_with($name,'""+'))
        {
            $name = mb_substr($name, 3);
        }
         while(ends_with($name, '+""'))
        {
            $name = mb_substr($name, 0, mb_strlen($name)-3);
        }
        if(!strlen($data) || str_replace(" ","",$data) == "{}")
        {
            unset($data);
        }else
        {
            $index = mb_strpos($data, "{");
            if($index !== False)
            {
                $tmp = mb_substr($data, 0, $index+1);
                $tmp .= "item:this._this?this._this:this,item:this._this?this._this:this,";
                $tmp .= mb_substr($data, $index+1);
                $data = $tmp;
            }

        }
        $result =  '{{^_partials['.$name.']}}'.PHP_EOL;
                if(isset($data))
                    $result .= '{{# '.$data.' }}'.PHP_EOL;

                $result .= "\t".'{{>makePartial('.$name.(isset($data)?",".$data:'').')}}'.PHP_EOL;
                if(isset($data))
                    $result .= '{{/}}'.PHP_EOL;
                $result .= '{{/}}'.PHP_EOL;
        return $result;
    }
    protected function __component($content, $path)
    {
        $parts = explode(" ", $content);
        $url = $parts[0];
        $data = NULL;
        if(count($parts)>1)
        {
            $data = join(" ", array_slice($parts, 1));
        }

        $name = trim($url);
        $name = ucfirst($name);
        $name = '"'.$name.'"';
        $name = str_replace("{{", '"+', $name);
        $name = str_replace("}}", '+"', $name);
        while(starts_with($name,'""+'))
        {
            $name = mb_substr($name, 3);
        }
         while(ends_with($name, '+""'))
        {
            $name = mb_substr($name, 0, mb_strlen($name)-3);
        }
        if(!strlen($data) || str_replace(" ","",$data) == "{}")
        {
            unset($data);
        }
        $token = "'".generate_token(40)."'";
        
        $result =  '{{^_components['.strtolower($token).']}}'.PHP_EOL;

                $result .= "\t".'{{>makeComponent('.$name.','.$token.''.(isset($data)?",".$data:'').')}}'.PHP_EOL;
                $result .= '{{/}}'.PHP_EOL;
        return $result;
    }
}
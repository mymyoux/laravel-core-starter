<?php

namespace Core\Model;

use Core\Database\Eloquent\Model;
use Db;
use Auth;
use Illuminate\Database\Eloquent\Builder;
class Translation extends Model
{

    const DEFAULT_LOCALE = "en";
	const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';


    protected $table = 'translate';
    protected $primaryKey = 'id';

    protected $fillable = ['controller','action','key','singular','plurial','locale','type','missing','id_user'];
    public $hidden = ["id","missing","id_user","sync_time","created_time"];


    private function getPluralForm($locale, $quantity)
    {
        if(!isset($quantity))
            return "singular";
        if($locale == "fr")
        {
            return $quantity>1?'plurial':'singular';
        }
        if($locale == "en")
        {
            return $quantity!=1?'plurial':'singular';
        }
        return "singular";
    }

    protected function isSupportedLocale($locale)
    {
        $localedb = Db::table('translate_locales')->where(["locale"=>$locale])->first();
        return isset($localedb);
    }
    protected function getLocales()
    {
        return Db::table('translate_locales')->get()->pluck('locale');
    }
    protected function getLocale($locale)
    {
        if(!is_array($locale))
        {
            $locale = [$locale];
        }
        $accepted_locales = array_reduce($locale, function($previous, $item)
        {
            $item = strtolower($item);
            if(!in_array($item, $previous))
                $previous[] = $item;
            $index = strpos($item, "-");
            if($index !== False)
            {
                $item = substr($item, 0, $index);
                if(!in_array($item, $previous))
                {
                    $previous[] = $item;
                }
            }
            return $previous;
        }, []);
        $locales = $this->getLocales()->toArray();
        foreach($accepted_locales as $accepted)
        {
            if(in_array($accepted, $locales))
            {
                return $accepted;
            }
        }
        return static::DEFAULT_LOCALE;
    }
    protected function translate($key, $locale, $type, $options = NULL)
    {
        $translation = $this->translates($key, $locale, $type, False);
        if(!isset($translation))
            return $key;
        if(!empty($options))
        {
            if(is_numeric($options))
            {
                $plurial = $this->getPluralForm($locale, $options);
                if($plurial == "plurial" && isset($translation["plurial"]) && strlen($translation["plurial"]))
                    return str_replace('{{count}}',$options,$translation["plurial"]);
            }else
            {
                $translate = $translation["singular"];
                foreach($options as $key=>$value)
                {
                    $translate = str_replace('{{'.$key.'}}',$value,$translate);
                }
                return $translate;
            }

        }
        return $translation["singular"];
    }
    protected function translates($keys, $locale, $type, $all = false)
    {
        $locales = [$this->parseLocale($locale)];
        if($locales[0] != static::DEFAULT_LOCALE)
        {
            $locales[] =  static::DEFAULT_LOCALE;
        }
        if(!is_array($keys))
        {
            $keys = [$keys];
        }else {
            $all = true;
        }
        
        $hkey = array_map([$this, "parseKey"], $keys);
        foreach($hkey as &$key)
        {
            if(isset($key->type))
            {
                $type = $key->type;
                //in case of
                $type = preg_replace("/[^a-zA-Z0-9]+/", "", $type);
                unset($key->type);
            }
        }
        
        if($all)
        {
            $request = static::where(function ($query) use($hkey) {
                foreach($hkey as $key)
                    $query->orWhere("path","like",$key->key."%");
                });
        }else {
             $request = static::where(function ($query) use($hkey) {
                foreach($hkey as $key)
                    $query->orWhere("path","=",$key->key);
                });
        }

            
        $request->whereIn("locale", $locales);
        if(isset($type))
        {
            $request->where(function ($query) use($type) {
                $query->where("type",'=',$type)
                ->orWhereNull('type');
            });
        }else
        {
            $request->whereNull('type');
        }
        $request->orderBy(Db::raw('locale="'.$locales[0].'"'), 'DESC');
        if(isset($type))
        {
            $request->orderBy(Db::raw('type="'.$type.'"'), 'DESC');
        }
        if(!$all)
        {
           return $request->first();
        }
        $all = $request->get();
        
        $keys = [];
        $all = $all->filter(function($item) use(&$keys)
        {
            if(in_array( $item->fullKey(), $keys))
                return false;
            $keys[] = $item->fullKey();
            return true;
        });
        return $all->values();
    }
     protected function translateUpdated($key, $locale, $type, $date)
    {
        $locales = [$this->parseLocale($locale)];
        if($locales[0] != static::DEFAULT_LOCALE)
        {
            $locales[] =  static::DEFAULT_LOCALE;
        }
        $hkey = $this->parseKey($key);
        if(isset($hkey->type))
        {
            $type = $hkey->type;
            //in case of
            $type = preg_replace("/[^a-zA-Z0-9]+/", "", $type);
            unset($hkey->type);
        }
        $request = static::where("path",'like',$hkey->key."%")->whereIn("locale", $locales);
        if(isset($type))
        {
           
            $request->where(function ($query) use($type) {
                $query->where("type",'=',$type)
                ->orWhereNull('type')
                ->orderBy(Db::raw('locale="'.$locales[0].'"'), 'DESC');
            });
            $all = $request->get();
            $all = $all->filter(function($item) use(&$keys)
            {
                if(in_array( $item->fullKey(), $keys))
                    return false;
                $keys[] = $item->fullKey();
                return true;
            });

        }else
        {
            $request->whereNull('type');
        }
        $request->orderBy(Db::raw('locale="'.$locales[0].'"'), 'DESC');
        if(isset($type))
        {
            $request->orderBy(Db::raw('type="'.$type.'"'), 'DESC');
        }
        if(!$all)
        {
           return $request->first();
        }
        $all = $request->get();
        
        $keys = [];
        $all = $all->filter(function($item) use(&$keys)
        {
            if(in_array( $item->fullKey(), $keys))
                return false;
            $keys[] = $item->fullKey();
            return true;
        });
        return $all->values();
    }
    private function parseLocale($locale)
    {
        if(!isset($locale))
        {
            return Translation::DEFAULT_LOCALE;
        }
        $index = mb_strpos($locale, "-");
        if($index !== FALSE)
        {
            return mb_substr($locale, 0, $index);
        }
        return $locale;
    }
    private function parseKey($key)
	{
        $hkey = new \stdClass;

        $index = mb_strpos($key, "-");
        if($index !== False)
		{
			$type = mb_substr($key, $index+1);
			$key = mb_substr($key, 0, $index);
            $hkey->type = $type;
		}
        $hkey->key = $key;
        return $hkey;
	}
    public function fullKey()
    {
        return $this->path.(isset($this->type)?"-".$this->type:"");
    }
    public function shortKey()
    {
        return explode(".", $this->path)[0];
    }
}


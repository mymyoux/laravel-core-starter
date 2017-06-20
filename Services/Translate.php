<?php

namespace Core\Services;

use Core\Model\Translation;
class Translate
{
    protected $default_locale;
    public function setDefaultLocale($locale)
    {
        $this->default_locale = $locale;
    }
    protected function getDefaultLocale()
    {
        if(!isset($this->default_locale))
        {
            $this->default_locale = Translation::DEFAULT_LOCALE;
        }
        return $this->default_locale;
    }
    public function getLocale($locale)
    {
           return Translation::getLocale($locale);
    }
    public function t($key,  $type, $locale, $options = NULL)
    {
        if(!isset($locale))
        {
            $locale = $this->getDefaultLocale();
        }
        $locale = $this->getLocale($locale);
        return Translation::translate($key, $locale, $type, $options);
    }
}
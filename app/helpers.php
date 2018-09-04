<?php
if ( ! function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (! function_exists('mb_ucfirst')) {
    /**
     * Upper case first letter for multibyte string
     *
     * @param  string  $string
     * @param  string  $encoding
     * @return string
     */
    
    function mb_ucfirst($string, $encoding = 'utf-8')
    {
        $strlen = mb_strlen($string, $encoding);
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, $strlen - 1, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $then;
    }
}

if (! function_exists('set_system_locale')) {
    /**
     * Sets system locale
     *
     * @param  string  $locale
     * @param  string  $encoding
     * @param  int  $category
     * @return string
     */
    
    function set_system_locale(string $locale, $encoding = 'utf-8', int $category = LC_TIME)
    {   
        $locales = [
            'en' => 'en_EN',
            'ru' => 'ru_RU'
        ];

        if (!isset($locales[$locale])) {
            throw new \Exception("Locale $locale is not supported");
        }

        return setlocale($category, $locales[$locale].'.'.$encoding);
    }
}
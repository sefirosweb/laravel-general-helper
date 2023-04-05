<?php

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

class CacheRequest
{
    private static $cache;

    public static function set($key, $value)
    {
        self::$cache[$key] = $value;
    }

    public static function get($key)
    {
        if (isset(self::$cache[$key])) return self::$cache[$key];
        return null;
    }

    public static function delete($key)
    {
        if (isset(self::$cache[$key])) {
            unset(self::$cache[$key]);
        }
    }

    public static function remember($key, $cb)
    {
        if (isset(self::$cache[$key])) return self::$cache[$key];

        if (is_callable($cb)) {
            $data = call_user_func($cb);
            self::set($key, $data);
            return $data;
        }
    }
}

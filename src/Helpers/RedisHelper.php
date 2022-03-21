<?php

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

use Exception;
use Illuminate\Support\Facades\Redis as FacadesRedis;

class Redis
{
    static function set($key, $value, $EX = 86400)
    {
        try {
            FacadesRedis::set($key, json_encode($value), 'EX', $EX);
        } catch (Exception $e) {
            return null;
        }
    }

    static function publish($channel_name, $publish)
    {
        try {
            return FacadesRedis::publish($channel_name, json_encode($publish));
        } catch (Exception $e) {
            return null;
        }
    }

    static function get($key)
    {
        try {
            $data = FacadesRedis::get($key);
            if (!$data) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
        return self::objectToArray(json_decode($data));
    }

    static function delete($key)
    {
        return FacadesRedis::del($key);
    }

    static  function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }

        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = self::objectToArray($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

    static function call($function, $key = '', $prod = false, $EX = 86400)
    {
        if (
            (config('app.env') === 'local' || $prod === true)
            &&
            $data = self::get($key)
        ) {
            return $data;
        }

        if (is_callable($function)) {
            $data = call_user_func($function);
            self::set($key, $data, $EX);
            return self::objectToArray($data);
        }

        return null;
    }
}

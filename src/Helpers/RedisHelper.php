<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

use Exception;
use Illuminate\Support\Facades\Redis;

class RedisHelper
{
    public static function set(string $key, mixed $value, int $EX = 86400): void
    {
        try {
            Redis::set($key, json_encode($value), 'EX', $EX);
        } catch (Exception $e) {
            // swallow: caller cannot recover if redis is down
        }
    }

    public static function publish(string $channel_name, mixed $publish): mixed
    {
        try {
            return Redis::publish($channel_name, json_encode($publish));
        } catch (Exception $e) {
            return null;
        }
    }

    public static function get(string $key): mixed
    {
        try {
            $data = Redis::get($key);
            if (!$data) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }

        return self::objectToArray(json_decode($data));
    }

    public static function delete(string $key): mixed
    {
        return Redis::del($key);
    }

    public static function objectToArray(mixed $obj): mixed
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $new[$key] = self::objectToArray($val);
            }
            return $new;
        }

        return $obj;
    }

    public static function call(callable $function, string $key = '', bool $prod = false, int $EX = 86400): mixed
    {
        if (config('app.env') === 'local' || $prod === true) {
            $cached = self::get($key);
            if ($cached !== null) {
                return $cached;
            }
        }

        $data = $function();
        self::set($key, $data, $EX);

        return self::objectToArray($data);
    }
}

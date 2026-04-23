<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Helpers;

class CacheRequest
{
    private static array $cache = [];

    public static function set(string $key, mixed $value): void
    {
        self::$cache[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return self::$cache[$key] ?? null;
    }

    public static function delete(string $key): void
    {
        unset(self::$cache[$key]);
    }

    public static function remember(string $key, callable $cb): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $data = $cb();
        self::set($key, $data);

        return $data;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}

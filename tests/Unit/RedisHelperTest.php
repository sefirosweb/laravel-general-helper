<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Unit;

use Illuminate\Support\Facades\Redis;
use Sefirosweb\LaravelGeneralHelper\Helpers\RedisHelper;
use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

class RedisHelperTest extends TestCase
{
    public function test_set_serialises_to_json_and_calls_redis_with_expiry(): void
    {
        Redis::shouldReceive('set')
            ->once()
            ->with('users:1', json_encode(['name' => 'Alice']), 'EX', 3600);

        RedisHelper::set('users:1', ['name' => 'Alice'], 3600);
    }

    public function test_set_swallows_exception_without_throwing(): void
    {
        Redis::shouldReceive('set')->andThrow(new \RuntimeException('connection refused'));

        // Must not throw
        RedisHelper::set('x', 'y');
        $this->addToAssertionCount(1);
    }

    public function test_get_returns_null_when_redis_has_no_value(): void
    {
        Redis::shouldReceive('get')->with('missing')->andReturn(null);

        $this->assertNull(RedisHelper::get('missing'));
    }

    public function test_get_decodes_json_and_converts_object_to_array(): void
    {
        Redis::shouldReceive('get')->with('obj')->andReturn(json_encode(['a' => ['b' => 1]]));

        $this->assertSame(['a' => ['b' => 1]], RedisHelper::get('obj'));
    }

    public function test_get_returns_null_when_redis_throws(): void
    {
        Redis::shouldReceive('get')->andThrow(new \RuntimeException('offline'));

        $this->assertNull(RedisHelper::get('whatever'));
    }

    public function test_publish_delegates_to_redis_with_json_payload(): void
    {
        Redis::shouldReceive('publish')
            ->once()
            ->with('channel', json_encode(['event' => 'ping']))
            ->andReturn(3);

        $this->assertSame(3, RedisHelper::publish('channel', ['event' => 'ping']));
    }

    public function test_delete_delegates_to_redis_del(): void
    {
        Redis::shouldReceive('del')->once()->with('k')->andReturn(1);

        $this->assertSame(1, RedisHelper::delete('k'));
    }

    public function test_call_runs_callback_when_cache_is_empty(): void
    {
        config()->set('app.env', 'testing');

        // prod=false, env=testing -> skip cache lookup, always run callback + set
        Redis::shouldReceive('set')->once();

        $result = RedisHelper::call(fn () => ['computed' => true], 'key');

        $this->assertSame(['computed' => true], $result);
    }

    public function test_call_uses_cached_value_when_available_and_prod_true(): void
    {
        Redis::shouldReceive('get')->once()->with('key')->andReturn(json_encode(['cached' => true]));

        $result = RedisHelper::call(fn () => $this->fail('Callback should NOT run on cache hit'), 'key', true);

        $this->assertSame(['cached' => true], $result);
    }

    public function test_object_to_array_recursively_flattens_stdclass(): void
    {
        $obj = (object) [
            'a' => (object) ['b' => 1, 'c' => [(object) ['d' => 2]]],
        ];

        $arr = RedisHelper::objectToArray($obj);

        $this->assertSame(['a' => ['b' => 1, 'c' => [['d' => 2]]]], $arr);
    }
}

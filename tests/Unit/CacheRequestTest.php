<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sefirosweb\LaravelGeneralHelper\Helpers\CacheRequest;

class CacheRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CacheRequest::flush();
    }

    public function test_set_and_get_roundtrip(): void
    {
        CacheRequest::set('foo', 'bar');

        $this->assertSame('bar', CacheRequest::get('foo'));
    }

    public function test_get_returns_null_for_missing_key(): void
    {
        $this->assertNull(CacheRequest::get('missing'));
    }

    public function test_delete_removes_key(): void
    {
        CacheRequest::set('foo', 'bar');
        CacheRequest::delete('foo');

        $this->assertNull(CacheRequest::get('foo'));
    }

    public function test_delete_is_noop_on_missing_key(): void
    {
        CacheRequest::delete('never-existed');
        $this->addToAssertionCount(1);
    }

    public function test_remember_invokes_callback_only_on_cache_miss(): void
    {
        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
            return 'computed';
        };

        $first = CacheRequest::remember('k', $callback);
        $second = CacheRequest::remember('k', $callback);

        $this->assertSame('computed', $first);
        $this->assertSame('computed', $second);
        $this->assertSame(1, $calls, 'Callback must run exactly once across two remember calls');
    }

    public function test_remember_caches_null_values(): void
    {
        $calls = 0;
        $callback = function () use (&$calls) {
            $calls++;
            return null;
        };

        CacheRequest::remember('nullable', $callback);
        CacheRequest::remember('nullable', $callback);

        $this->assertSame(1, $calls, 'Null returns should be cached, not retried');
    }

    public function test_flush_clears_all_keys(): void
    {
        CacheRequest::set('a', 1);
        CacheRequest::set('b', 2);

        CacheRequest::flush();

        $this->assertNull(CacheRequest::get('a'));
        $this->assertNull(CacheRequest::get('b'));
    }
}

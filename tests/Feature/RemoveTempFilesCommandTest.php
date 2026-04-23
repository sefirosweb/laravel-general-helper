<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Feature;

use Illuminate\Support\Facades\File;
use Sefirosweb\LaravelGeneralHelper\Tests\TestCase;

class RemoveTempFilesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset storage/tmp before every test.
        $tmp = storage_path('tmp');
        if (File::isDirectory($tmp)) {
            File::deleteDirectory($tmp);
        }
    }

    protected function tearDown(): void
    {
        $tmp = storage_path('tmp');
        if (File::isDirectory($tmp)) {
            File::deleteDirectory($tmp);
        }

        parent::tearDown();
    }

    public function test_runs_against_an_empty_temp_dir_without_error(): void
    {
        config()->set('app.env', 'local');

        $exit = $this->artisan('purge:temp');
        $exit->assertExitCode(0);

        $this->assertSame([], File::files(pathTemp()));
    }

    public function test_local_env_removes_every_file_regardless_of_age(): void
    {
        config()->set('app.env', 'local');

        $old   = pathTemp() . '/old_file.txt';
        $fresh = pathTemp() . '/fresh_file.txt';
        File::put($old, 'old');
        File::put($fresh, 'fresh');
        // Pretend the first file is 2 years old (irrelevant in local env).
        touch($old, time() - 2 * 365 * 24 * 60 * 60);

        $this->artisan('purge:temp')->assertExitCode(0);

        $this->assertFileDoesNotExist($old);
        $this->assertFileDoesNotExist($fresh);
    }

    public function test_production_env_keeps_files_younger_than_seven_days(): void
    {
        config()->set('app.env', 'production');

        $fresh = pathTemp() . '/fresh.txt';
        File::put($fresh, 'fresh');
        // touch to "now" explicitly so getCTime reflects it.
        touch($fresh, time());

        $this->artisan('purge:temp')->assertExitCode(0);

        $this->assertFileExists($fresh);
    }

    public function test_production_env_removes_files_older_than_seven_days(): void
    {
        config()->set('app.env', 'production');

        $stale = pathTemp() . '/stale.txt';
        File::put($stale, 'stale');

        // Set mtime (which backs getCTime on most filesystems via touch()
        // when ctime is not independently settable) to > 7 days ago.
        $elevenDaysAgo = time() - (11 * 24 * 60 * 60);
        touch($stale, $elevenDaysAgo, $elevenDaysAgo);

        $this->artisan('purge:temp')->assertExitCode(0);

        $this->assertFileDoesNotExist($stale);
    }
}

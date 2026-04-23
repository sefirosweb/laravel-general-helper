<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RemoveTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purge:temp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove files in temp storage path';

    public function handle(): int
    {
        collect(File::files(pathTemp()))->map(function ($file) {
            return [
                'file' => $file,
                // Use mtime rather than ctime. On Linux, ctime tracks inode
                // metadata changes (permissions, ownership) and is not
                // backdateable via touch(); mtime is the conventional "how
                // old is this file" signal and is what we actually want.
                'time' => $file->getMTime(),
                // SplFileInfo::getBasename(suffix) returns the filename with
                // the suffix stripped. PHP 8 pathinfo() rejects SplFileInfo
                // because of its typed string parameter.
                'basename' => $file->getBasename('.' . $file->getExtension()),
            ];
        })->filter(function ($file) {
            if (config('app.env') === 'local') {
                return true;
            }
            return (time() - $file['time']) / 60 / 60 / 24 > 7; // Delete files older than 7 days.
        })->each(function ($file) {
            try {
                // $file['file'] is a SplFileInfo; PHP 8 unlink() rejects it
                // because of its typed string parameter, so ask for the path
                // explicitly.
                unlink($file['file']->getPathname());
                if (config('app.env') === 'local') {
                    $this->line('Deleting: ' . $file['basename']);
                }
            } catch (Exception) {
                // Silently ignore individual unlink failures so one stale
                // handle does not break the whole purge.
            }
        });

        return self::SUCCESS;
    }
}

<?php

namespace Sefirosweb\LaravelGeneralHelper\Commands;

use App\Models\User;
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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        collect(File::files(pathTemp()))->map(function ($file) {
            return [
                'file' => $file,
                'time' => $file->getCTime(),
                'basename' => pathinfo($file)['filename']
            ];
        })->filter(function ($file) {
            if (config('app.env') === 'local') return true;
            return (time() - $file['time']) / 60 / 60 / 24 > 7; // Deletete after 7 days
        })->each(function ($file) {
            try {
                unlink($file['file']);
                if (config('app.env') === 'local') {
                    echo "Deleting: " . $file['basename'] . PHP_EOL;
                }
            } catch (Exception $e) {
            }
        });

        return 0;
    }
}

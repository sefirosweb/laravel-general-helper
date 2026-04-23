<?php

use Illuminate\Support\Facades\Route;
use Sefirosweb\LaravelGeneralHelper\Http\Controllers\FileController;

Route::get('download_file/{savedFile}', [FileController::class, 'download_file']);
Route::get('show_file/{savedFile}', [FileController::class, 'show_file']);

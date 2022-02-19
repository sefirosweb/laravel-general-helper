<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'Sefirosweb\LaravelGeneralHelper\Http\Controllers'
], function () {

    Route::get('download_file/{savedFile}', 'FileController@download_file');
    Route::get('show_file/{savedFile}', 'FileController@show_file');
});

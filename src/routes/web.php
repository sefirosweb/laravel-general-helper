<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'Sefirosweb\LaravelGeneralHelper\Http\Controllers'
], function () {

    Route::get('get_file', 'DownloadFileController@get_file');
});

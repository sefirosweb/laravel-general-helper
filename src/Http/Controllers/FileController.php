<?php

namespace Sefirosweb\LaravelGeneralHelper\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Http\Request;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;

class FileController extends Controller
{
    // public function test(Request $request, SavedFile $savedFile)
    // {
    //     if (!$savedFile->user || Auth::id() !== $savedFile->user->id) {
    //         if ($request->expectsJson()) {
    //             return response()->json(['error' => "You don't have permissions for this site"], 401);
    //         }

    //         return response("You don't have permissions for this site", 401)
    //             ->header('Content-Type', 'text/plain');
    //     }

    //     return response()->download($savedFile->path, $savedFile->file_name . '.' . $savedFile->extension);
    // }

    public function download_file(SavedFile $savedFile)
    {
        if (!$savedFile->user || Auth::id() !== $savedFile->user->id) {
            return response("You don't have permissions for this site", 401)
                ->header('Content-Type', 'text/plain');
        }

        return response()->download($savedFile->path, $savedFile->file_name . '.' . $savedFile->extension);
    }

    public function show_file(SavedFile $savedFile)
    {
        if (!$savedFile->user || Auth::id() !== $savedFile->user->id) {
            return response("You don't have permissions for this site", 401)
                ->header('Content-Type', 'text/plain');
        }

        return response()->file($savedFile->path);
    }
}

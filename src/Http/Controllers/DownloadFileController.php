<?php

namespace Sefirosweb\LaravelGeneralHelper\Http\Controllers;

use App\Http\Controllers\Controller;

class DownloadFileController extends Controller
{
    public function get_file()
    {
        $data =  [
            [
                'hid' => 1,
                'hidd' => 2,
            ],
            [
                'hid' => 1,
                'hidd' => 2,
            ]
        ];
        saveCsvInServer($data, 'asd');
        return 'hello world';
    }
}



// class General
// {
//     public function GetFile(Request $request)
//     {
//         $saved_file = SavedFiles::findOrFail($request->id);

//         if ($saved_file->id_user != Auth::user()->id) {
//             return response('User not valid <a href="/">Volver</a>');
//         }

//         $file = ($saved_file->path);

//         $filetype = filetype($file);

//         $filename = basename($file);

//         header("Content-Type: " . $filetype);

//         header("Content-Length: " . filesize($file));

//         header("Content-Disposition: attachment; filename=" . $filename);

//         readfile($file);
//     }
// }

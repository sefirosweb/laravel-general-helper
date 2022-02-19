<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Sefirosweb\LaravelGeneralHelper\Helpers\ExcelHelper;
use Sefirosweb\LaravelGeneralHelper\Http\Models\SavedFile;

if (!function_exists('array_group_by')) {
    function array_group_by(array $array, $key = null, $onlyFirstValue = false)
    {
        if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key)) {
            trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
            return null;
        }
        $func = (is_callable($key) ? $key : null);
        $_key = $key;
        // Load the new array, splitting by the target key
        $grouped = [];
        foreach ($array as $value) {
            if (is_callable($func)) {
                $key = call_user_func($func, $value);
            } elseif (is_object($value) && isset($value->{$_key})) {
                $key = $value->{$_key};
            } elseif (isset($value[$_key])) {
                $key = $value[$_key];
            } else {
                continue;
            }
            if ($onlyFirstValue) {
                $grouped[$key] = $value;
            } else {
                $grouped[$key][] = $value;
            }
        }
        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 3) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $params = array_merge([$value], array_slice($args, 3, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $params);
            }
        }
        return $grouped;
    }
}


if (!function_exists('array_group_by_multidimensional')) {
    function array_group_by_multidimensional($array, $union_by, $onlyFirstValue = false)
    {
        if (!is_array($union_by) || !is_array($array)) {
            return null;
        }

        if (count($union_by) === 0) {
            return $array;
        }

        $union = array_shift($union_by);
        if (count($union_by) === 0) {
            $result_array = array_group_by($array, $union, $onlyFirstValue);
        } else {
            $result_array = array_group_by($array, $union, false);
        }
        foreach ($result_array as $key => $result) {
            $result_array[$key] = array_group_by_multidimensional($result, $union_by, $onlyFirstValue);
        }
        return $result_array;
    }
}


if (!function_exists('objectToArray')) {
    function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }

        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = objectToArray($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }
}


if (!function_exists('generateMarks')) {
    function generateMarks($markName, $array)
    {
        $sql = [];
        $assoc = [];

        foreach ($array as $index => $data) {
            array_push($sql, ':' . $markName . '_' . $index);
            $assoc = array_merge($assoc, [$markName . '_' . $index => $data]);
        }

        $sql = implode(',', $sql);

        $returnData = new stdClass();
        $returnData->sql = count($assoc) > 0 ?  $sql : 'false';
        $returnData->assoc = $assoc;
        return $returnData;
    }
}

if (!function_exists('createMarks')) {
    function createMarks($array)
    {
        $sql = [];
        $assoc = [];

        foreach ($array as $data) {
            array_push($sql, $data['filter']);
            $assoc = array_merge($assoc, [$data['name'] => $data['value']]);
        }

        $sql = implode(PHP_EOL, $sql);

        $returnData = new stdClass();
        $returnData->sql = $sql;
        $returnData->assoc = $assoc;
        return $returnData;
    }
}


if (!function_exists('mergeArrays')) {
    function mergeArrays($main_array, $secondary_array, $union_by, $union_first_value = false, $force_mmatch = false)
    {
        $main_array = objectToArray($main_array);
        $secondary_array = objectToArray($secondary_array);

        if (is_string($union_by)) {
            $union_by = [$union_by];
        }

        $secondary_array = array_group_by_multidimensional($secondary_array, $union_by);

        foreach ($main_array as $key_main_array => $item) {

            $checkAllKeys = true;

            $dataFound = null;
            foreach ($union_by as $union) {
                $key = $item[$union];

                if ($dataFound === null) {
                    if (isset($secondary_array[$key])) {
                        $dataFound = $secondary_array[$key];
                    } else {
                        $checkAllKeys = false;
                        break;
                    }
                } else {
                    if (isset($dataFound[$key])) {
                        $dataFound = $dataFound[$key];
                    } else {
                        $checkAllKeys = false;
                        break;
                    }
                }
            }

            if ($checkAllKeys) {
                if ($union_first_value) {
                    $main_array[$key_main_array] = array_merge($item, $dataFound[0]);
                } else {
                    $main_array[$key_main_array] = array_merge($item, $dataFound);
                }
            } else {
                if ($force_mmatch) {
                    unset($main_array[$key_main_array]);
                }
            }
        }

        return $main_array;
    }
}

if (!function_exists('mergeArraysOnSubArray')) {
    function mergeArraysOnSubArray($main_array, $secondary_array, $union_by, $name, $union_first_value = false)
    {

        // Fill default data
        $main_array = array_map(function ($row) use ($name, $union_first_value) {
            if ($union_first_value) {
                $row[$name] = null;
            } else {
                $row[$name] = [];
            }
            return $row;
        }, $main_array);
        if (is_string($union_by)) {
            $union_by = [$union_by];
        }

        $secondary_array = array_group_by_multidimensional($secondary_array, $union_by);

        foreach ($main_array as $key_main_array => $item) {

            $checkAllKeys = true;
            $dataFound = null;

            foreach ($union_by as $union) {
                $key = $item[$union];

                if ($dataFound === null) {
                    if (isset($secondary_array[$key])) {
                        $dataFound = $secondary_array[$key];
                    } else {
                        $checkAllKeys = false;
                        break;
                    }
                } else {
                    if (isset($dataFound[$key])) {
                        $dataFound = $dataFound[$key];
                    } else {
                        $checkAllKeys = false;
                        break;
                    }
                }
            }

            if ($checkAllKeys) {
                if ($union_first_value) {
                    $main_array[$key_main_array][$name] = $dataFound[0];
                } else {
                    $main_array[$key_main_array][$name] = $dataFound;
                }
            }
        }

        return $main_array;
    }
}


if (!function_exists('query')) {
    function query($query, $marks = null, $database = false)
    {
        if ($database) {
            if ($marks === null) {
                return objectToArray(DB::connection($database)->select(DB::raw($query)));
            } else {
                return objectToArray(DB::connection($database)->select(DB::raw($query), $marks));
            }
        }
        if ($marks === null) {
            return objectToArray(DB::select(DB::raw($query)));
        } else {
            return objectToArray(DB::select(DB::raw($query), $marks));
        }
    }
}

if (!function_exists('excelToArray')) {
    function excelToArray($filePath, $encode = false)
    {
        $filetypeAccepted = ['csv', 'xls', 'xlsx'];

        try {
            $inputFileType = IOFactory::identify($filePath);
            $objReader = IOFactory::createReader($inputFileType);

            if ($encode) {
                $objReader->setInputEncoding($encode);
            }

            if (!in_array(strtolower($inputFileType), $filetypeAccepted)) {
                die("Excel en formato $filetypeAccepted no soportado!");
            }

            if (strtolower($inputFileType) === 'csv') {
                $objReader->setDelimiter(';');
            }

            $objPHPExcel = $objReader->load($filePath);
            $arrayData = $objPHPExcel->getSheet(0)->toArray();
            $arrayData = array_filter($arrayData, function ($row) {
                $allRowIsNotNull = false;
                foreach ($row as $value) {
                    if (!is_null($value)) {
                        $allRowIsNotNull = true;
                        break;
                    }
                }
                return $allRowIsNotNull;
            });


            //Creating an assoc array using $arrayData
            $assoc_array = array();
            $headers = count($arrayData[0]);

            for ($i = 1; $i < count($arrayData); $i++) {
                if (!isset($arrayData[$i])) continue;
                $temp = array();
                for ($y = 0; $y < ($headers); $y++) {
                    $header_name = $arrayData[0][$y];
                    $temp[$header_name] = is_null($arrayData[$i][$y]) ? null : $arrayData[$i][$y];
                }
                $assoc_array[] = $temp;
            }

            return $assoc_array;
        } catch (Exception $e) {
            print_r($e);
            exit;
        }
    }
}

if (!function_exists('pathTemp')) {
    function pathTemp()
    {
        $path = storage_path('tmp/');

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
            $fp = fopen($path . '/.gitignore', 'w');
            fwrite($fp, "*" . PHP_EOL);
            fwrite($fp, "!.gitignore" . PHP_EOL);
            fclose($fp);
        }

        return $path;
    }
}

if (!function_exists('saveCsvInServerAndDownload')) {
    function saveCsvInServerAndDownload($arrayData, $fileName, $delimiter = ';', $enclosure = '"', $latingMode = false, $headers = true, $utf8_decode = false, $enclosureAll = false)
    {
        $savedFile = saveCsvInServer($arrayData, $fileName, $delimiter, $enclosure, $latingMode, $headers, $utf8_decode, $enclosureAll);
        return response()->download($savedFile->path);
    }
}

if (!function_exists('saveCsvInServer')) {
    function saveCsvInServer($arrayData, $fileName, $delimiter = ';', $enclosure = '"', $latingMode = false, $headers = true, $utf8_decode = false, $enclosureAll = false)
    {
        $path = pathTemp();

        $arrayData = objectToArray($arrayData);
        $folderPath = $path . $fileName . '.csv';

        foreach ($arrayData as $key => $row) {
            foreach ($row as $keyName => $field) {
                if (is_array($field)) {
                    unset($arrayData[$key][$keyName]);
                }
            }
        }

        if ($utf8_decode) {

            foreach ($arrayData as $keyArray => $row) {
                foreach ($row as $keyData => $data) {
                    $arrayData[$keyArray][$keyData] = utf8_decode($data);
                }
            }
        }

        $fp = fopen($folderPath, 'w');

        if (isset($arrayData[0])) {
            if ($latingMode === true) {
                $BOM = "\xEF\xBB\xBF"; // UTF-8 BOM
                fwrite($fp, $BOM);
            }

            if ($headers) {
                $firstData = $arrayData[0];
                $headers = array();
                foreach ($firstData as $header => $field) {
                    array_push($headers, $header);
                }
                fputcsv($fp, $headers, $delimiter, $enclosure);
                fseek($fp, -1, SEEK_CUR);
                fwrite($fp, "\r\n");
            }

            if ($enclosureAll) {
                foreach ($arrayData as $field) {
                    fputs($fp, implode($delimiter, array_map("encodeFunc", $field)) . "\r\n");
                }
            } else {
                foreach ($arrayData as $field) {
                    fputcsv($fp, $field, $delimiter, $enclosure);
                    fseek($fp, -1, SEEK_CUR);
                    fwrite($fp, "\r\n");
                }
            }
        } else {
            fputcsv($fp, array('No data'), $delimiter, $enclosure);
        }

        fclose($fp);

        $savedFile = new SavedFile;
        $savedFile->user()->associate(Auth::user());
        $savedFile->file_name = $fileName;
        $savedFile->extension = 'csv';
        $savedFile->path = $folderPath;
        $savedFile->save();
        return $savedFile;
    }
}

if (!function_exists('validateArray')) {
    function validateArray($array, $rules)
    {
        $errors = [];
        foreach ($array as $row) {
            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                $row['errors'] = implode(PHP_EOL, array_map(function ($err) {
                    return implode(PHP_EOL, $err);
                }, $validator->errors()->toArray()));
                array_push($errors, $row);
            }
        }

        return $errors;
    }
}

if (!function_exists('saveExcelInServer')) {
    function saveExcelInServer($arrayData, $filename, $headers = true)
    {
        $excel = new ExcelHelper($filename);
        $excel->addSheet($arrayData, 'Hoja1', $headers);
        return $excel->save();
    }
}

if (!function_exists('saveExcelInServerAndDownload')) {
    function saveExcelInServerAndDownload($arrayData, $filename, $headers = true)
    {
        $savedFile = saveExcelInServer($arrayData, $filename, $headers);
        return response()->download($savedFile->path);
    }
}


if (!function_exists('br2nl')) {
    function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }
}

if (!function_exists('eliminar_tildes')) {
    function eliminar_tildes($cadena)
    {
        //Codificamos la cadena en formato utf8 en caso de que nos de errores
        // $cadena = utf8_encode($cadena);

        //Ahora reemplazamos las letras
        $cadena = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $cadena
        );

        $cadena = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $cadena
        );

        $cadena = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $cadena
        );

        $cadena = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $cadena
        );

        $cadena = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $cadena
        );

        $cadena = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C'),
            $cadena
        );

        return $cadena;
    }
}

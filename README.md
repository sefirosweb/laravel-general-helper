# Laravel Ggeneral Helper

A bundle of helpers for develop, contains method for generate excels , csv, array optimizations and more

## Installation

```
composer require sefirosweb/laravel-general-helper
```

For save file need a run migration for manage who has created the file, only have access own creator,

```
php artisan migrate
```

## Optional configuration

You can change the middleware and prefix path for get the files, need publish config

```
php artisan vendor:publish --provider="Sefirosweb\LaravelGeneralHelper\LaravelGeneralHelperServiceProvider"
```

**Helpers**

- [pathTemp](#pathTemp)
- [array_group_by](#array_group_by)
- [array_group_by_multidimensional](#array_group_by_multidimensional)
- [objectToArray](#objectToArray)
- [generateMarks](#generateMarks)
- [createMarks](#createMarks)
- [char_at](#char_at)
- [mergeArrays](#mergeArrays)
- [mergeArraysOnSubArray](#mergeArraysOnSubArray)
- [query](#query)
- [excelToArray](#excelToArray)
- [saveCsvInServerAndDownload](#saveCsvInServerAndDownload)
- [saveCsvInServer](#saveCsvInServer)
- [validateArray](#validateArray)
- [saveExcelInServer](#saveExcelInServer)
- [saveExcelInServerAndDownload](#saveExcelInServerAndDownload)
- [br2nl](#br2nl)
- [eliminar_tildes](#eliminar_tildes)

### pathTemp

Obtain and create the path "tmp" if it does not exist, it is created automatically, in this path the temporary files used for this package are stored

### array_group_by

Group an array by "identifier", if the third optional value is true, only get the first element of group

```php
array_group_by(array $array, string $key, bool $onlyFirstValue = false)

$array = [
    [
        'name' => 'pablo',
        'date' => '02'
    ],
    [
        'name' => 'pablo',
        'date' => '03'
    ],
    [
        'name' => 'selena',
        'date' => 'XX'
    ]
];

$arrayAgrouped = array_group_by($array, 'name');
// Returns:
[
    'pablo' => [
        [
            'name' => 'pablo',
            'date' => '02',
        ],
        [
            'name' => 'pablo',
            'date' => '03',
        ]
    ],
    'selena' => [
        [
            'name' => 'selena',
            'date' => 'XX'
        ]
    ]
]

$arrayAgrouped = array_group_by($array, 'name', true);
[
    'pablo' =>
        [
            'name' => 'pablo',
            'date' => '02',
        ],
    'selena' =>
        [
            'name' => 'selena',
            'date' => 'XX'
        ]
]

```

### array_group_by_multidimensional

Same as function "array_group_by" but in this case you can sub-group the array in multiple conditions

```php
array_group_by_multidimensional(array $array, array $union_by, bool $onlyFirstValue = false)

$array = [
    [
        'name' => 'pablo',
        'date' => '02'
    ],
    [
        'name' => 'pablo',
        'date' => '03'
    ],
    [
        'name' => 'selena',
        'date' => 'XX'
    ]
];

$arrayAgrouped = array_group_by_multidimensional($array, ['name','date']);
// Returns:
[
    'pablo' => [
        '02' => [ // <--- sub levels, you can add all levels of you need
            [
                'name' => 'pablo',
                'date' => '02',
            ],
        ],
        '03' => [
            [
                'name' => 'pablo',
                'date' => '03',
            ]
        ]
    ],
    'selena' => [
        'XX' => [
            [
                'name' => 'selena',
                'date' => 'XX'
            ]
        ]
    ]
]

$arrayAgrouped = array_group_by_multidimensional($array, ['name','date'], true);
// Returns:
[
    'pablo' => [
        '02' =>
            [ // Only returns the first item in level of 02
                'name' => 'pablo',
                'date' => '02',
            ],

        '03' =>
            [
                'name' => 'pablo',
                'date' => '03',
            ]
    ],
    'selena' => [
        'XX' =>
            [
                'name' => 'selena',
                'date' => 'XX'
            ]
    ]
]

```

### objectToArray

Convert stdClass object to full array values, it is required for use the array group functions

### generateMarks

### createMarks

### char_at

### mergeArrays

### mergeArraysOnSubArray

### query

### excelToArray

### saveCsvInServerAndDownload

### saveCsvInServer

Function for save an structured array data into csv, and returns a object "FileSaved"

```php
saveCsvInServer(array $arrayData, string $fileName, string $delimiter = ';', string $enclosure = '"', bool $latingMode = false, bool $headers = true, bool $utf8_decode = false, bool $enclosureAll = false)

$array = [
    [
        'name' => 'pablo',
        'date' => '02'
    ],
    [
        'name' => 'pablo',
        'date' => '03'
    ],
    [
        'name' => 'selena',
        'date' => 'XX'
    ]
];

$file = saveCsvInServer($array, 'filename');
// Returns a SavedFile model object
$file->id
$file->path
...

```

### validateArray

### saveExcelInServer

Function for save an structured array data into excel (for huge data is recommended CSV, save data in excel have low performance)

```php
$array = [
    [
        'name' => 'pablo',
        'date' => '02'
    ],
    [
        'name' => 'pablo',
        'date' => '03'
    ],
    [
        'name' => 'selena',
        'date' => 'XX'
    ]
];

$file = saveCsvInServer($array, 'filename');
// Returns a SavedFile model object
$file->id
$file->path
...
```

### saveExcelInServerAndDownload

### br2nl

Replace \<br> into new lines "\n"

### eliminar_tildes

Function for remove the latin characters "á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'" into "a / A"

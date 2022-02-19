<?php

namespace Sefirosweb\LaravelGeneralHelper\Http\Models;

use App\Models\User as ModelsUser;

class User extends ModelsUser
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function saved_files()
    {
        return $this->belongsToMany(SavedFile::class);
    }
}

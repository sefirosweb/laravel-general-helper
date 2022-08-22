<?php

namespace Sefirosweb\LaravelGeneralHelper\Http\Models;

use App\Models\User as ModelsUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends ModelsUser
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function saved_files(): BelongsToMany
    {
        return $this->belongsToMany(SavedFile::class);
    }
}

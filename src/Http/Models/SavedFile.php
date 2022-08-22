<?php

namespace Sefirosweb\LaravelGeneralHelper\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedFile extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'user_id', 'file_name', 'extension', 'path'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

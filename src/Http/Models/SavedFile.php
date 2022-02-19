<?php

namespace Sefirosweb\LaravelGeneralHelper\Http\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

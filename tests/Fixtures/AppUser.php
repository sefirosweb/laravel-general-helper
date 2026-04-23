<?php

declare(strict_types=1);

// Test fixture: minimal App\Models\User stub for Testbench.
// The package's User model extends App\Models\User which is provided by
// the host Laravel app in production, but not by Testbench skeleton.
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];

    protected $table = 'users';
}

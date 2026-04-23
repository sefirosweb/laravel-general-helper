<?php

declare(strict_types=1);

namespace Sefirosweb\LaravelGeneralHelper\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}

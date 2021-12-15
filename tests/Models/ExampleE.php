<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExampleE extends Model
{
    use SoftDeletes;

    protected $table = 'example_e';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];
}

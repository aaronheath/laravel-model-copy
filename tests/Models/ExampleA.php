<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExampleA extends Model
{
    use SoftDeletes;

    protected $table = 'example_a';

    protected $guarded = [];
}

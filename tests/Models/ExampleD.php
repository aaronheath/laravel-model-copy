<?php

namespace Tests\Models;

use Tests\database\factories\ExampleDFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExampleD extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'example_d';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected static function newFactory()
    {
        return ExampleDFactory::new();
    }
}

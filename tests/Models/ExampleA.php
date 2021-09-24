<?php

namespace Tests\Models;

use Tests\database\factories\ExampleAFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExampleA extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'example_a';

    protected $guarded = [];

    protected static function newFactory()
    {
        return ExampleAFactory::new();
    }
}

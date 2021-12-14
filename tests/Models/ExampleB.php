<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\database\factories\ExampleBFactory;

class ExampleB extends Model
{
    use HasFactory;

    protected $table = 'example_b';

    protected $guarded = [];

    protected static function newFactory()
    {
        return ExampleBFactory::new();
    }
}

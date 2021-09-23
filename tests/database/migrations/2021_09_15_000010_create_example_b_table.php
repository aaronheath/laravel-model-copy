<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExampleBTable extends Migration
{
    public function up()
    {
        Schema::create('example_b', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('a');
            $table->boolean('b');
            $table->string('c')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('example_b');
    }
}

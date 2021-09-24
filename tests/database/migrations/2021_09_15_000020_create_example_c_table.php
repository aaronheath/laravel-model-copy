<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExampleCTable extends Migration
{
    public function up()
    {
        Schema::create('example_c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('a');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('example_c');
    }
}

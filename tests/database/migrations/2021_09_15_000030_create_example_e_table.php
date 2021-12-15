<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExampleETable extends Migration
{
    public function up()
    {
        Schema::create('example_e', function (Blueprint $table) {
            $table->char('id', 36);
            $table->string('a');
            $table->boolean('b');
            $table->string('c')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('example_e');
    }
}

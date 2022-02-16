<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Otps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('identifier');
            $table->string('token');
            $table->integer('validity');
            $table->boolean('expired')->default(false);
            $table->integer('no_times_generated')->default(0);
            $table->integer('no_times_attempted')->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ots');
    }
}

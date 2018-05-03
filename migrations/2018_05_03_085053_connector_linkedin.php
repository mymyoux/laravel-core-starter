<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConnectorLinkedin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('connector_linkedin', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->string('id', 128);

            $table->longText('headline')->nullable();
            $table->string('first_name', 256)->nullable();
            $table->string('last_name', 256)->nullable();
            $table->string('email', 256)->nullable();
            $table->string('link', 256)->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->primary('user_id');
        });

        DB::unprepared('INSERT INTO connectors VALUES(NULL, "linkedin", 1, NOW(), NOW());');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('connector_linkedin');
        DB::unprepared('delete from connectors where name = "linkedin";');
    }
}

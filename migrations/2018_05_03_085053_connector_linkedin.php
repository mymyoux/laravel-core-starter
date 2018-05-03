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
            $table->integer('id_user')->unsigned();
            $table->string('id', 128);

            $table->longText('headline')->nullable();
            $table->string('first_name', 256)->nullable();
            $table->string('last_name', 256)->nullable();
            $table->string('email', 256)->nullable();
            $table->string('link', 256)->nullable();

            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->primary('id_user');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('connector_linkedin');
    }
}

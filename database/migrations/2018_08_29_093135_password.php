<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Password extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('password', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('user_id');
            $table->string('account_name');
            $table->string('password_encrypted');
            $table->softDeletes();
            $table->timestamps();

        });

        Schema::table('user', function (Blueprint $table) {
            $table->string('master_password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('password');
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('master_password');
        });
    }
}

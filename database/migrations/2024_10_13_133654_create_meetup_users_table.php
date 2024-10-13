<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetupUsersTable extends Migration
{
    public function up()
    {
        Schema::create('meetup_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('meetup_id')->unique();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meetup_users');
    }
}

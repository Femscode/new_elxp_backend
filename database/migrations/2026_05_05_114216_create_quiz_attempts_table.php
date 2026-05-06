<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('user_id'); // Match User.uuid
            $table->unsignedBigInteger('quiz_setting_id');
            $table->integer('score');
            $table->integer('total_points');
            $table->json('answers')->nullable();
            $table->string('status')->default('failed'); // passed, failed
            $table->timestamps();

            $table->foreign('quiz_setting_id')->references('id')->on('quiz_setting')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quiz_attempts');
    }
}

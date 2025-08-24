<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quiz_setting', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); 
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('uuid')->nullable(); 
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('time_limit')->default(0);
            $table->integer('attempts')->default(1);
            $table->integer('passing_score')->default(0);
            $table->json('settings')->nullable(); // shuffleQuestions, shuffleOptions, etc.
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
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
        Schema::dropIfExists('quiz_setting');
    }
}

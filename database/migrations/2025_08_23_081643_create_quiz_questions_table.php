<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->foreignId('quiz_setting_id')->constrained('quiz_setting')->onDelete('cascade');
            $table->string('type'); // multiple-choice, true-false, etc.
            $table->text('question');
            $table->integer('points')->default(1);
            $table->text('correct_answer')->nullable(); 
            $table->text('explanation')->nullable();
            $table->json('options')->nullable(); 
            $table->boolean('required')->default(true);
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
        Schema::dropIfExists('quiz_questions');
    }
}

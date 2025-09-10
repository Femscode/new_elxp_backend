<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_question', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('survey_id')->constrained('survey')->onDelete('cascade');
            $table->string('type'); // multiple-choice, checkbox, rating, text, textarea, likert
            $table->text('question');
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();        // for MC/checkbox
            $table->json('likert_options')->nullable(); // for likert
            $table->json('scale')->nullable();          // for rating {min, max, minLabel, maxLabel}
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
        Schema::dropIfExists('survey_question');
    }
}

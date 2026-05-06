<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentCompletionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_completions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('course_id');
            $table->unsignedBigInteger('content_id');
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique(['user_id', 'content_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_completions');
    }
}

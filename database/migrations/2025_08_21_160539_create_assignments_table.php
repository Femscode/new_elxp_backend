<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Link to user who created it
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('uuid')->unique(); // unique UUID for external use
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->integer('points')->default(0);
            $table->enum('submission_type', ['file', 'text', 'link'])->default('file');
            $table->json('allowed_file_types')->nullable();
            $table->integer('max_file_size')->nullable();
            $table->integer('attempts')->default(1);
            $table->string('course_uuid');
            $table->enum('status', ['draft', 'published','archived'])->default('draft');
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
        Schema::dropIfExists('assignments');
    }
}

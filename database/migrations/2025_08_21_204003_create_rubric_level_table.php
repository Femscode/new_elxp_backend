<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRubricLevelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rubric_level', function (Blueprint $table) {
            $table->id(); 
            $table->uuid('uuid')->unique(); 
            $table->foreignId('rubric_id')->constrained('rubric')->onDelete('cascade'); 
            $table->string('name'); 
            $table->integer('points'); 
            $table->text('description')->nullable(); // Optional explanation
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
        Schema::dropIfExists('rubric_level');
    }
}

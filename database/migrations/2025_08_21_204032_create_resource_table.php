<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resource', function (Blueprint $table) {
            $table->id(); 
            $table->uuid('uuid')->unique();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade'); 
            $table->string('name');
            $table->string('type')->nullable(); 
            $table->string('file_path')->nullable(); 
            $table->string('url')->nullable(); 
            $table->text('description')->nullable(); 
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
        Schema::dropIfExists('resource');
    }
}

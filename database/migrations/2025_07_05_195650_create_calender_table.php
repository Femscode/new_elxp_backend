<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalenderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calender', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // foreign key to users table
            $table->uuid('uuid')->unique(); // unique UUID for public references
            $table->string('name'); // event name/title
            $table->text('description')->nullable(); // optional detailed description
            $table->string('duration'); // consider changing to integer if this is numeric
            $table->date('date'); // event date
            $table->time('time'); // event time
            $table->enum('unit', ['minutes', 'hours']); // unit of duration
            $table->enum('audience', ['private', 'specific', 'public']); // access level
            $table->string('color')->nullable(); // optional color tag
            $table->boolean('status')->default(0); // active/inactive
            $table->enum('repeatUnit', ['days', 'weeks'])->nullable(); // repeat frequency (removed extra comma)
            $table->unsignedBigInteger('repeatInterval')->default(0); // how often it repeats
            $table->unsignedBigInteger('occurrences')->default(0); // how many times it repeats
            $table->timestamps(); // created_at and updated_at
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calender');
    }
}

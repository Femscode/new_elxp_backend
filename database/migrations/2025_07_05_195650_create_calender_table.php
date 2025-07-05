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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();   // Added description (long text)
            $table->string('duration');                // Added duration (you can change to integer if it's numeric)
            $table->date('date');
            $table->time('time');
            $table->enum('unit', ['minutes', 'hours']);
            $table->enum('audience', ['private', 'specific', 'public']);
            $table->string('color')->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('calender');
    }
}

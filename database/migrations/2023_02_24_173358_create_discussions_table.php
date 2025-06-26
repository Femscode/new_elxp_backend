<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscussionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('course_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('title');
            $table->string('content')->nullable();;
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('allowed_users')->nullable();
            $table->string('image')->nullable();
            $table->text('files')->nullable();
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('reply_count')->default(0);
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
        Schema::dropIfExists('discussions');
    }
}

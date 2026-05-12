<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscussionInteractionsTable extends Migration
{
    public function up()
    {
        Schema::create('discussion_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('discussion_id')->constrained('discussions')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'discussion_id']);
        });

        Schema::create('discussion_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('discussion_id')->constrained('discussions')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'discussion_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('discussion_likes');
        Schema::dropIfExists('discussion_saves');
    }
}

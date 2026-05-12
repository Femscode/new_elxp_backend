<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMediaColumnsToDiscussionsAndReplies extends Migration
{
    public function up()
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->string('video')->nullable()->after('image');
        });

        Schema::table('replies', function (Blueprint $table) {
            $table->string('image')->nullable()->after('file');
            $table->string('video')->nullable()->after('image');
        });
    }

    public function down()
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->dropColumn('video');
        });

        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn(['image', 'video']);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('replies', function (Blueprint $table) {
            if (!Schema::hasColumn('replies', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('discussion_id');
            }
            if (!Schema::hasColumn('replies', 'like_count')) {
                $table->integer('like_count')->default(0)->after('parent_id');
            }
        });
    }

    public function down()
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'like_count']);
        });
    }
};

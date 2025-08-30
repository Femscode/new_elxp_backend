<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolymorphicFieldsToContentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        Schema::table('course_contents', function (Blueprint $table) {
            $table->unsignedBigInteger('contentable_id')->nullable()->after('contentType');
            $table->string('contentable_type')->nullable()->after('contentable_id');
            $table->index(['contentable_id', 'contentable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_contents', function (Blueprint $table) {
            $table->dropIndex(['contentable_id', 'contentable_type']);
            $table->dropColumn(['contentable_id', 'contentable_type']);
        });
    }
}

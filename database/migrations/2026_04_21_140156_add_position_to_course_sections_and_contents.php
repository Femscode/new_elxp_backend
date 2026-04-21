<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPositionToCourseSectionsAndContents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->integer('position')->default(0)->after('id');
        });
        Schema::table('course_contents', function (Blueprint $table) {
            $table->integer('position')->default(0)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropColumn('position');
        });
        Schema::table('course_contents', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseContent extends Model
{
    use HasFactory;
    protected $table = 'course_contents';
    protected $guarded = [];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

}

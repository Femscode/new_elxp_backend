<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'course_categories';

    public function getRouteKeyName()
    {
        return 'uuid';
    }

}

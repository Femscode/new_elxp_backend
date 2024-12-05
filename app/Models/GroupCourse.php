<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupCourse extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'group_courses';
    public function courses() {
        return $this->hasMany(Course::class,'id','course_id');
    }
}

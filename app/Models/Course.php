<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['title', 'description', 'price','image', 'course_code','user_id', 'instructor_id','status','uuid'];
    protected $table = 'courses';

    public function getRouteKeyName()
    {
        return 'uuid';
    }

}

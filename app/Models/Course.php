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
    protected $hidden = ['created_at', 'updated_at'];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function sections()
    {
        return $this->hasMany(Section::class, 'course_id', 'uuid');
    }

    public function contents()
    {
        return $this->hasMany(Content::class, 'course_id', 'uuid');
    }

}

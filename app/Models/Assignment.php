<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';



    protected $fillable = [
        'title',
        'description',
        'instructions',
        'due_date',
        'points',
        'submission_type',
        'allowed_file_types',
        'max_file_size',
        'attempts',
        'course_uuid',  
        'status',
        'user_id',
        'uuid',
        'content_id',
    ];

    
    protected $casts = [
        'allowed_file_types' => 'array',
        'due_date' => 'datetime',
    ];


     public function content()
    {
        return $this->morphOne(Content::class, 'contentable');
    }
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_uuid', 'uuid');
    }

    // Assignment belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // One assignment can have many rubrics
    public function rubrics()
    {
        return $this->hasMany(Rubric::class);
    }

    // One assignment can have many resources
    public function resources()
    {
        return $this->hasMany(Resource::class);
    }
}

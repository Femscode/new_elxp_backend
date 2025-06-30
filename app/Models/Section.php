<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'course_sections';

    protected $hidden = ['created_at', 'updated_at'];
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'uuid');
    }

    public function contents()
    {
        return $this->hasMany(Content::class, 'section_id', 'id');
    }
}

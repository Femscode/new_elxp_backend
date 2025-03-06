<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'sections';
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'uuid');
    }

    public function contents()
    {
        return $this->hasMany(Content::class, 'section_id', 'id');
    }
}

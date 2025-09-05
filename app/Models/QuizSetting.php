<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizSetting extends Model
{
    use HasFactory;

      protected $table = 'quiz_setting';
      protected $fillable = [
        'uuid',
        'title',
        'description',
        'time_limit',
        'attempts',
        'passing_score',
        'settings',
        'course_id',
        'status',
        'user_id',
        'content_id'
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * A quiz setting belongs to a course
     */

      public function content()
    {
        return $this->morphOne(Content::class, 'contentable');
    }
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * A quiz setting has many questions
     */
    public function questions()
    {
        return $this->hasMany(QuizQuestions::class, 'quiz_setting_id');
    }
       // quiz belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'quiz_setting_id',
        'score',
        'total_points',
        'answers',
        'status',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function quizSetting()
    {
        return $this->belongsTo(QuizSetting::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }
}

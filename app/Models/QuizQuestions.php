<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestions extends Model
{
    use HasFactory;
    
    protected $table = 'quiz_questions';
    protected $fillable = [
        'uuid',
        'quiz_setting_id',
        'type',
        'question',
        'points',          
        'correct_answer',  
        'options',
        'explanation',
        'required',
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
    ];

    /**
     * A quiz question belongs to a quiz setting
     */
    public function quizSetting()
    {
        return $this->belongsTo(QuizSetting::class, 'quiz_setting_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $table = 'survey_question';

    protected $fillable = [
        'uuid',
        'survey_id',
        'type',
        'question',
        'required',
        'options',
        'likert_options',
        'scale',
        'textAnswer',
    ];

    protected $casts = [
        'options' => 'array',
        'likert_options' => 'array',
        'scale' => 'array',
        'required' => 'boolean',
    ];

 
    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }
}

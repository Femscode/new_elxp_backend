<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'survey_id',
        'responses',
    ];

    protected $casts = [
        'responses' => 'array',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}

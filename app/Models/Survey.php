<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $table = 'survey'; 

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'anonymous',
        'allow_multiple_responses',
        'show_results',
        'course_id',
        'status',
        'user_id'
    ];

     public function content()
    {
        return $this->morphOne(Content::class, 'contentable');
    }
    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class, 'survey_id');
    }
       // Survey belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

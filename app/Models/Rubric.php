<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rubric extends Model
{
    use HasFactory;

    protected $table = 'rubric';

    protected $fillable = [
        'uuid',
        'assignment_id',
        'name',
        'description',
        'points'
    ];

    // A rubric belongs to an assignment
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    // A rubric has many levels
    public function levels()
    {
        return $this->hasMany(RubricLevel::class);
    }
}

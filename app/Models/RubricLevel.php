<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RubricLevel extends Model
{
    use HasFactory;

    protected $table = 'rubric_level';

    protected $fillable = [
        'uuid',
        'rubric_id',
        'name',
        'description',
        'points',
    ];

    // A rubric level belongs to a rubric
    public function rubric()
    {
        return $this->belongsTo(Rubric::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $table = 'resource';

    protected $fillable = [
        'uuid',
        'assignment_id',
        'name',
        'type',
        'url',
        'description',
    ];

    // A resource belongs to an assignment
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}

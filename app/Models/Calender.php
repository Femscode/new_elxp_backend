<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calender extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'calender';
    // protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = [
        'user_id',
        'uuid',
        'name',
        'date',
        'time',
        'unit',
        'duration',
        'audience',
        'color',
        'description',
        'status',
        'repeatInterval',
        'repeatUnit',
        'occurrences',
];
 protected $casts = [
        'status' => 'boolean',
    ];

}

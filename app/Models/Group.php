<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $table = 'groups';
    protected $guarded = [];

    protected $casts = [
        'users' => 'array',
        'courses' => 'array',
        'files' => 'array',
    ];

    
    public function groupusers() {
        return $this->hasMany(GroupUser::class);
    }
    public function groupcourses() {
        return $this->hasMany(GroupCourse::class);
    }
   
}

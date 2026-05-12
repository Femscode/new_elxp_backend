<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    use HasFactory;
    protected $table = 'discussions';
    protected $guarded = [];
    protected $casts = [
        'allowed_users' => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'discussion_likes', 'discussion_id', 'user_id')->withTimestamps();
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'discussion_saves', 'discussion_id', 'user_id')->withTimestamps();
    }
}

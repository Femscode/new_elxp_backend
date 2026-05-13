<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reply extends Model
{
    use HasFactory;
    protected $table = 'replies';
    protected $guarded = [];
    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    public function parentReply()
    {
        return $this->belongsTo(Reply::class, 'parent_reply_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function replies()
    {
        return $this->hasMany(Reply::class, 'parent_reply_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'reply_likes', 'reply_id', 'user_id')->withTimestamps();
    }

    public function getImageAttribute($value)
    {
        if ($value) return 'https://elxp-backend.connectinskillz.com/new_elxp_files/public/replyImages/' . $value;
        return null;
    }

    public function getVideoAttribute($value)
    {
        if ($value) return 'https://elxp-backend.connectinskillz.com/new_elxp_files/public/replyVideos/' . $value;
        return null;
    }

    public function getFileAttribute($value)
    {
        if ($value) return 'https://elxp-backend.connectinskillz.com/new_elxp_files/public/replyFiles/' . $value;
        return null;
    }
}

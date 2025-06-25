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
}

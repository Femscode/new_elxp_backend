<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupUser extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'group_users';

    public function users() {
        return $this->belongsTo(User::class, 'id','user_id');
    }
}

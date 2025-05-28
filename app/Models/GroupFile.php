<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupFile extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'group_files';
    protected $hidden = ['created_at', 'updated_at'];

    protected $fillable = [
    'group_id',
    'user_id',
    'uuid',
    'filename',
    'filepath',
    'name',
    'file_type',
    'file_size',
];

}

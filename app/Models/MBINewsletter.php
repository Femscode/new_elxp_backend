<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MBINewsletter extends Model
{
    use HasFactory;

    protected $table = 'm_b_i_newsletters';

    protected $fillable = [
        'email',
    ];
}

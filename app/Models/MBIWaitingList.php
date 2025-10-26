<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MBIWaitingList extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'm_b_i_waiting_lists';
}

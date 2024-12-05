<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $guarded = [];
    // protected $fillable = ['uuid', 'first_name', 'last_name', 'email', 'bio', 'username', 'user_type', 'timezone', 'language', 'phone', 'image'];
    protected $table = 'users';

    // define transaction relationship 

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id', 'uuid');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    //use uuid to relate to users instead of ID for security reasons
    public function getRouteKeyName()
    {
        return 'uuid';
    }





    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

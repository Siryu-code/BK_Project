<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'kode_akses',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'kode_akses',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'kode_akses' => 'hashed',
        ];
    }

    public function jurnalBk()
    {
        return $this->hasMany(JurnalBk::class);
    }
}

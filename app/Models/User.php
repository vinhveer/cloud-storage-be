<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'storage_limit', 'storage_used',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'storage_limit' => 'integer',
        'storage_used' => 'integer',
    ];

    // Relationships
    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function fileVersions()
    {
        return $this->hasMany(FileVersion::class);
    }

    public function receivedShares()
    {
        return $this->belongsToMany(Share::class, 'receives_shares')
            ->withPivot('permission');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'folder_id', 'file_id', 'shareable_type', 'permission', 'token', 'expired_at', 'revoked_at'
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
}

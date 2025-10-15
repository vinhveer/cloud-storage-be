<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'folder_id', 'user_id', 'display_name', 'file_size', 'mime_type', 'file_extension', 'is_deleted', 'last_opened_at'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_deleted' => 'boolean',
        'last_opened_at' => 'datetime',
    ];

    protected $hidden = [];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function versions()
    {
        return $this->hasMany(FileVersion::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }
}

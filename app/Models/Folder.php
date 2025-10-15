<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'fol_folder_id', 'folder_name', 'is_deleted'
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    protected $hidden = [];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'fol_folder_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'fol_folder_id');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }
}

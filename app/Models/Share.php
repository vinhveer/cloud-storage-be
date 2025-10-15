<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id', 'folder_id', 'user_id', 'shareable_type', 'permission'
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

    public function receivers()
    {
        return $this->belongsToMany(User::class, 'receives_shares')
            ->withPivot('permission');
    }
}

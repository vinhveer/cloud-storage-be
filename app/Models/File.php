<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * File model
 *
 * Invariants / conventions:
 * - `file_size` represents the size in bytes of the current/latest version of the file.
 * - Historical versions are stored in `file_versions` (see `FileVersion` model), each with its own `file_size`.
 * - `storage_used` on the `users` table/accounts should reflect the total bytes stored for that user (commonly the sum of all stored versions); services that add/remove versions must update `storage_used` accordingly.
 *
 * Notes for implementers:
 * - When copying a file we create a new `File` and duplicate all `FileVersion` records and physical objects.
 *   The new `File.file_size` should be set to the size of the latest version (not the sum of all versions).
 * - Accounting (incrementing `storage_used`) should be done based on actual bytes copied (sum of sizes of versions copied).
 */
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

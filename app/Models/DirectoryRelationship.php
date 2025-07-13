<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DirectoryRelationship extends Model
{
    use HasUuids;

    protected $fillable = [
        'directory_id',
        'linked_directory_id',
        'link_type',
        'designation',
        'permissions',
        'status',
        'start_date',
        'end_date',
        'remark',
    ];

    protected $casts = [
        'permissions' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function directory()
    {
        return $this->belongsTo(Directory::class, 'directory_id');
    }

    public function linkedDirectory()
    {
        return $this->belongsTo(Directory::class, 'linked_directory_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;  // Import BelongsTo

class Directory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'description', 'profile_picture',
        'directory_type_id', 'registration_type_id', 'registration_number',
        'gender', 'date_of_birth',
        'phone', 'email', 'website',
        'country_id', 'island_id', 'address', 'location_type',
        'properties_id','status', // Make sure to add this to fillable if you want to mass assign it
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(DirectoryType::class, 'directory_type_id');
    }

    public function registrationType(): BelongsTo
    {
        return $this->belongsTo(RegistrationType::class, 'registration_type_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class);
    }

    // --- New relationship added ---
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'properties_id');
    }

    public function linkedDirectories()
    {
        return $this->hasMany(DirectoryRelationship::class, 'directory_id');
    }

    public function relatedAs()
    {
        return $this->hasMany(DirectoryRelationship::class, 'linked_directory_id');
    }

        public function contactPersonRelationship(): HasOne
    {
        return $this->hasOne(DirectoryRelationship::class, 'directory_id');
    }

    /**
     * A convenient accessor to get the linked contact Directory model directly.
     */
    public function getContactPersonAttribute()
    {
        return $this->contactPersonRelationship->linkedDirectory ?? null;
    }

}

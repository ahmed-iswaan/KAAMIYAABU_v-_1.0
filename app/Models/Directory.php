<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Directory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    // Updated fillable list to reflect new migration schema
    protected $fillable = [
        'name', 'description', 'profile_picture',
        'id_card_number',
        'gender', 'date_of_birth', 'death_date',
        'phones', 'email', 'website',
        'country_id', 'island_id', 'address', 'street_address', 'properties_id',
        'current_country_id', 'current_island_id', 'current_address', 'current_street_address', 'current_properties_id',
        'party_id', 'sub_consite_id', 'status'
    ];

    protected $casts = [
        'phones' => 'array',
        'date_of_birth' => 'date',
        'death_date' => 'date',
    ];

    // Removed obsolete relationships: type(), registrationType()

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'properties_id');
    }

    // Current (dynamic) location relationships
    public function currentCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'current_country_id');
    }

    public function currentIsland(): BelongsTo
    {
        return $this->belongsTo(Island::class, 'current_island_id');
    }

    public function currentProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'current_properties_id');
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

    public function getContactPersonAttribute()
    {
        return $this->contactPersonRelationship->linkedDirectory ?? null;
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'directories_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function subConsite(): BelongsTo
    {
        return $this->belongsTo(SubConsite::class, 'sub_consite_id');
    }

    public function opinions()
    {
        return $this->hasMany(VoterOpinion::class, 'directory_id');
    }

    public function voterNotes()
    {
        return $this->hasMany(VoterNote::class, 'directory_id');
    }

    public function voterRequests()
    {
        return $this->hasMany(VoterRequest::class, 'directory_id');
    }

    public function pledges()
    {
        return $this->hasMany(VoterPledge::class,'directory_id');
    }

    public function permanentLocationString()
    {
        $locationParts = [];
        if ($this->country) {
            $locationParts[] = 'Country: ' . $this->country->name;
        }
        if ($this->property) {
            $locationParts[] = 'Property: ' . $this->property->name;
        }
        if ($this->address) {
            $locationParts[] = 'Address: ' . $this->address;
        }
        if ($this->street_address) {
            $locationParts[] = 'Street: ' . $this->street_address;
        }

        return !empty($locationParts) ? implode(', ', $locationParts) : 'N/A';
    }

    public function currentLocationString()
    {
        $locationParts = [];
        if ($this->currentCountry) {
            $locationParts[] = 'Country: ' . $this->currentCountry->name;
        }
        if ($this->currentProperty) {
            $locationParts[] = 'Property: ' . $this->currentProperty->name;
        }
        if ($this->current_address) {
            $locationParts[] = 'Address: ' . $this->current_address;
        }
        if ($this->current_street_address) {
            $locationParts[] = 'Street: ' . $this->current_street_address;
        }

        return !empty($locationParts) ? implode(', ', $locationParts) : 'N/A';
    }
}

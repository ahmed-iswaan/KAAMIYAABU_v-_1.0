<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Directory extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'description', 'profile_picture',
        'directory_type_id', 'registration_type_id', 'registration_number',
        'gender', 'date_of_birth',
        'contact_person', 'phone', 'email', 'website',
        'country_id', 'island_id', 'address', 'location_type',
    ];

    public function type()
    {
        return $this->belongsTo(DirectoryType::class, 'directory_type_id');
    }

    public function registrationType()
    {
        return $this->belongsTo(RegistrationType::class, 'registration_type_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function island()
    {
        return $this->belongsTo(Island::class);
    }
}

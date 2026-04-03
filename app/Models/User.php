<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\VotingBox;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'profile_picture',
        'staff_id',
        'job_title',
        'phone_number',
        'email',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Sub consites assigned to the user.
     */
    public function subConsites(): BelongsToMany
    {
        return $this->belongsToMany(SubConsite::class, 'users_sub_consites', 'user_id', 'sub_consite_id')
            ->withTimestamps();
    }

    /**
     * Voting boxes assigned to the user.
     */
    public function votingBoxes(): BelongsToMany
    {
        return $this->belongsToMany(VotingBox::class, 'users_voting_boxes', 'user_id', 'voting_box_id')
            ->withTimestamps();
    }
}

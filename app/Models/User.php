<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
    'name',
    'email',
    'password',
    'institution',
    'avatar',
];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(DataAccessRequest::class);
    }

    public function ownedDatasets(): HasMany
    {
        return $this->hasMany(Dataset::class, 'owner_id');
    }

    public function uploadedSourceDocuments(): HasMany
    {
        return $this->hasMany(SourceDocument::class, 'uploaded_by');
    }

    public function requestedExtractions(): HasMany
    {
        return $this->hasMany(AiExtractionJob::class, 'requested_by');
    }

    public function createdDataConnectors(): HasMany
    {
        return $this->hasMany(DataConnector::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isResearcher(): bool
    {
        return $this->role === 'researcher';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

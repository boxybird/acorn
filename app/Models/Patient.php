<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * @property \Illuminate\Support\Carbon|null $magic_link_expires_at
 * @property \Illuminate\Support\Carbon|null $synced_at
 */
class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'name',
        'preferred_locale',
        'magic_link_token',
        'magic_link_expires_at',
        'sync_status',
        'synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'magic_link_expires_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function hasValidMagicLink(): bool
    {
        return $this->magic_link_token !== null
            && $this->magic_link_expires_at !== null
            && $this->magic_link_expires_at->isFuture();
    }
}

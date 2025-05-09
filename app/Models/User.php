<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use CWSPS154\UsersRolesPermissions\Models\HasRole;
use MixCode\FilamentMulti2fa\Enums\TwoFactorAuthType;
use MixCode\FilamentMulti2fa\Traits\UsingTwoFA;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements HasMedia, HasAvatar, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRole;
    use UsingTwoFA;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role_id',
        'last_seen',
        'is_active',
         'two_factor_type',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_sent_at',
        'two_factor_expires_at',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'last_seen' => 'datetime',
            'is_active' => 'boolean',
            'two_factor_type' => TwoFactorAuthType::class,
            'two_factor_sent_at' => 'datetime',
            'two_factor_expires_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's avatar URL.
     *
     * @return string|null
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ?? null;
    }

    /**
     * Determine if the user can access the Filament admin panel.
     *
     * @return bool
     */
    public function canAccessFilament(): bool
    {
        // You can customize this logic based on your requirements
        // By default, allow access if the user is active or if is_active field doesn't exist yet
        return $this->is_active ?? true;
    }
}

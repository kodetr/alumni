<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'email', 'nim', 'tanggal_lahir', 'password', 'role', 'is_blocked', 'access_permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ADMIN = self::ROLE_SUPERADMIN;

    public const ROLE_ALUMNI = 'alumni';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tanggal_lahir' => 'date',
            'is_blocked' => 'boolean',
            'access_permissions' => 'array',
            'password' => 'hashed',
        ];
    }

    public static function defaultAlumniAccessPermissions(): array
    {
        return [
            'actions' => [
                'create' => false,
                'edit' => true,
                'delete' => false,
            ],
            'features' => [
                'profile_edit' => true,
                'social_forum' => true,
                'social_chat' => true,
                'social_groups' => true,
                'career_jobs' => true,
                'career_center' => true,
                'event_reunion' => true,
                'event_webinar' => true,
                'event_networking' => true,
                'event_rsvp' => true,
                'mapping_locations' => true,
                'mapping_global' => true,
                'donation_online' => true,
                'donation_scholarship' => true,
                'donation_crowdfunding' => true,
                'business_marketplace' => true,
                'business_partnership' => true,
                'business_mentorship' => true,
            ],
        ];
    }

    public static function globalAlumniAccessPermissions(): array
    {
        if (! Schema::hasTable('integration_settings') || ! Schema::hasColumn('integration_settings', 'default_alumni_permissions')) {
            return self::defaultAlumniAccessPermissions();
        }

        $stored = IntegrationSetting::query()->value('default_alumni_permissions');

        return self::mergeWithDefaultAlumniAccessPermissions(is_array($stored) ? $stored : null);
    }

    public static function mergeWithDefaultAlumniAccessPermissions(?array $storedPermissions = null): array
    {
        return array_replace_recursive(
            self::defaultAlumniAccessPermissions(),
            is_array($storedPermissions) ? $storedPermissions : []
        );
    }

    public function resolvedAccessPermissions(): array
    {
        return array_replace_recursive(
            self::globalAlumniAccessPermissions(),
            is_array($this->access_permissions) ? $this->access_permissions : []
        );
    }

    public function hasAccessPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return (bool) data_get($this->resolvedAccessPermissions(), $permission, false);
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPERADMIN, 'admin'], true);
    }

    public function isAlumni(): bool
    {
        return $this->role === self::ROLE_ALUMNI;
    }
}

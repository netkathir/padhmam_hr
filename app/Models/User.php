<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Traits\BelongsToBranch;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BelongsToBranch;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'branch_id',
        'status',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'created_by',
        'updated_by',
        'password',
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
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasPermissionTo(string $slug): bool
    {
        if ($this->isSuperAdministrator()) {
            return true;
        }

        if ($this->permissions->contains('slug', $slug)) {
            return true;
        }

        return $this->roles->contains(function (Role $role) use ($slug): bool {
            return $role->permissions->contains('slug', $slug);
        });
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdministrator(): bool
    {
        return $this->hasRole(config('hrms.roles')[0]['slug']);
    }

    public function primaryRoleName(): string
    {
        return $this->roles->first()?->name ?? 'User';
    }

    public function createdRecords(): HasMany
    {
        return $this->hasMany(self::class, 'created_by');
    }

    public function updatedRecords(): HasMany
    {
        return $this->hasMany(self::class, 'updated_by');
    }
}

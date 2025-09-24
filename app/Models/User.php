<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'email',
        'password',
        'role',
        'fonction',
        'avatar',
        'numero_telephone',
        'team_id',
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => 'string',
    ];

    /**
     * Get the user's full name.
     */
    public function getNameAttribute()
    {
        return $this->nom;
    }

    /**
     * Get the user's team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get teams where user is the responsible/manager
     */
    public function teamsAsResponsable(): HasMany
    {
        return $this->hasMany(Team::class, 'responsable_id');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRoleLevel(string $role): bool
    {
        $roleHierarchy = [
            'stagiaire' => 1,
            'cadre' => 2,
            'responsable_n2' => 3,
            'responsable_n1' => 4,
            'manager' => 5,
            'super_admin' => 6,
        ];

        $userLevel = $roleHierarchy[$this->role] ?? 0;
        $requiredLevel = $roleHierarchy[$role] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Check if user can manage teams
     */
    public function canManageTeams(): bool
    {
        return $this->hasRoleLevel('manager');
    }

    /**
     * Check if user is team leader
     */
    public function isTeamLeader(): bool
    {
        return $this->teamsAsResponsable()->exists();
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'email',
        'password',
        'fonction',
        'avatar',
        'bio',
        'numero_telephone',
        'adresse',
        'role',
        'team_id',
        'language',
        'timezone',
        'notification_preferences',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'notification_preferences' => 'array',
        'password' => 'hashed',
    ];

    protected $appends = [
        'initials',
        'full_name',
        'avatar_url',
    ];

    /**
     * Activity logging configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nom', 'email', 'role', 'team_id', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    /**
     * User's primary team
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Teams where user is owner
     */
    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    /**
     * Teams where user is a member
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'permissions', 'joined_at', 'last_active_at', 'notifications_enabled'])
            ->withTimestamps();
    }

    /**
     * Team member records
     */
    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Team messages sent by user
     */
    public function teamMessages()
    {
        return $this->hasMany(TeamMessage::class);
    }

    /**
     * Team announcements created by user
     */
    public function teamAnnouncements()
    {
        return $this->hasMany(TeamAnnouncement::class);
    }

    /**
     * Team resources created by user
     */
    public function teamResources()
    {
        return $this->hasMany(TeamResource::class);
    }

    /**
     * Team activities performed by user
     */
    public function teamActivities()
    {
        return $this->hasMany(TeamActivity::class);
    }

    /**
     * User's team presence records
     */
    public function teamPresence()
    {
        return $this->hasMany(TeamPresence::class);
    }

    // TODO: Uncomment when Projet, Activite, Tache models are created
    // public function projets()
    // {
    //     return $this->hasMany(Projet::class, 'responsable_id');
    // }

    // public function activites()
    // {
    //     return $this->hasMany(Activite::class, 'responsable_id');
    // }

    // public function taches()
    // {
    //     return $this->belongsToMany(Tache::class, 'tache_user')
    //         ->withPivot('role')
    //         ->withTimestamps();
    // }

    // public function comments()
    // {
    //     return $this->hasMany(Comment::class);
    // }

    /**
     * Notification preferences
     */
    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Push subscriptions
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Get or create notification preferences
     */
    public function getOrCreateNotificationPreference(): NotificationPreference
    {
        return $this->notificationPreference()->firstOrCreate([
            'user_id' => $this->id,
        ]);
    }

    /**
     * Check if user should receive notification
     */
    public function shouldReceiveNotification(string $type, string $channel): bool
    {
        $preferences = $this->notificationPreference;

        if (!$preferences) {
            return true; // Default to sending if no preferences set
        }

        return $preferences->shouldReceive($type, $channel);
    }

    // Accessors
    public function getInitialsAttribute(): string
    {
        $names = explode(' ', $this->nom);
        $initials = '';

        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return substr($initials, 0, 2);
    }

    public function getFullNameAttribute(): string
    {
        return $this->nom;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return \Storage::url($this->avatar);
        }

        return null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('fonction', 'LIKE', "%{$search}%");
        });
    }

    // Helper Methods
    public function hasRoleLevel(string $role): bool
    {
        $hierarchy = [
            'super_admin' => 6,
            'manager' => 5,
            'responsable_n1' => 4,
            'responsable_n2' => 3,
            'cadre' => 2,
            'stagiaire' => 1,
        ];

        return ($hierarchy[$this->role] ?? 0) >= ($hierarchy[$role] ?? 0);
    }

    public function canManageUser(User $targetUser): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        if ($this->role === 'manager') {
            return $targetUser->role !== 'super_admin';
        }

        return false;
    }

    public function updateLoginInfo(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }
}

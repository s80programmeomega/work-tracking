# Authentication Module - Complete Implementation Flow

## 🎯 Overview
Complete authentication and authorization system using Laravel Fortify + Sanctum + Spatie Permissions with Vue.js 3 frontend.

---

## 🏗️ Architecture Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (Vue.js 3)                      │
├─────────────────────────────────────────────────────────────┤
│  Components → Composables → API Client → Axios Interceptors │
└─────────────────────────────────────────────────────────────┘
                              ↓ HTTP/JSON
┌─────────────────────────────────────────────────────────────┐
│                  BACKEND (Laravel 10)                       │
├─────────────────────────────────────────────────────────────┤
│  Routes → Middleware → Controllers → Services → Models      │
├─────────────────────────────────────────────────────────────┤
│  Fortify (Auth) | Sanctum (API) | Spatie (Permissions)     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              DATABASE (PostgreSQL/MySQL)                    │
├─────────────────────────────────────────────────────────────┤
│  users | roles | permissions | model_has_roles | sessions  │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 Backend Implementation Flow

### 1. Database Schema

#### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    current_team_id BIGINT UNSIGNED NULL,
    profile_photo_path VARCHAR(2048) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
);
```

#### Spatie Permissions Tables (from migration)
```sql
-- roles, permissions, model_has_roles, model_has_permissions,
-- role_has_permissions (auto-created by Spatie)
```

#### Sessions Table (Sanctum)
```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);
```

#### Personal Access Tokens Table (Sanctum)
```sql
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tokenable (tokenable_type, tokenable_id)
);
```

#### Activity Log Table (Spatie Activity Log)
```sql
CREATE TABLE activity_log (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    log_name VARCHAR(255) NULL,
    description TEXT NOT NULL,
    subject_type VARCHAR(255) NULL,
    subject_id BIGINT UNSIGNED NULL,
    causer_type VARCHAR(255) NULL,
    causer_id BIGINT UNSIGNED NULL,
    properties JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_causer (causer_type, causer_id),
    INDEX idx_log_name (log_name)
);
```

### 2. Models & Enums

#### User Model
```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'profile_photo_path',
        'is_active', 'last_login_at', 'last_login_ip'
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret',
        'two_factor_recovery_codes'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // Activity logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relationships
    public function taches()
    {
        return $this->belongsToMany(Tache::class, 'tache_user')
            ->withTimestamps()
            ->withPivot('role'); // 'assignee', 'observer', etc.
    }
}
```

#### Role Enum
```php
// app/Enums/Role.php
namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case MANAGER = 'manager';
    case RESPONSABLE_N1 = 'responsable_n1';
    case RESPONSABLE_N2 = 'responsable_n2';
    case CADRE = 'cadre';
    case STAGIAIRE = 'stagiaire';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrateur',
            self::MANAGER => 'Manager',
            self::RESPONSABLE_N1 => 'Responsable N1',
            self::RESPONSABLE_N2 => 'Responsable N2',
            self::CADRE => 'Cadre',
            self::STAGIAIRE => 'Stagiaire',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            self::SUPER_ADMIN => ['*'], // All permissions
            self::MANAGER => [
                'projets.create', 'projets.update', 'projets.delete',
                'activites.create', 'activites.update', 'activites.delete',
                'taches.create', 'taches.update', 'taches.delete',
                'users.view', 'users.assign', 'reports.view'
            ],
            self::RESPONSABLE_N1 => [
                'activites.view', 'activites.update',
                'taches.create', 'taches.update', 'taches.delete',
                'taches.validate'
            ],
            self::RESPONSABLE_N2 => [
                'taches.view', 'taches.update', 'taches.validate'
            ],
            self::CADRE => [
                'taches.view', 'taches.update', 'taches.comment'
            ],
            self::STAGIAIRE => [
                'taches.view', 'taches.comment'
            ],
        };
    }
}
```

### 3. Service Layer

#### AuthService
```php
// app/Services/AuthService.php
namespace App\Services;

use App\Models\User;
use App\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // Assign default role
            $defaultRole = $data['role'] ?? Role::CADRE->value;
            $user->assignRole($defaultRole);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('User registered');

            return $user;
        });
    }

    public function login(array $credentials, bool $remember = false): array
    {
        if (!Auth::attempt($credentials, $remember)) {
            throw new \Exception('Invalid credentials');
        }

        $user = Auth::user();

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        // Create API token
        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('User logged in');

        return [
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    public function logout(): void
    {
        $user = Auth::user();

        // Revoke current token
        $user->currentAccessToken()->delete();

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('User logged out');

        Auth::guard('web')->logout();
    }

    public function refreshToken(): array
    {
        $user = Auth::user();

        // Revoke old token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth_token', ['*'], now()->addDays(7))->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    public function verifyEmail(User $user): void
    {
        $user->markEmailAsVerified();

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('Email verified');
    }

    public function resetPassword(string $email): void
    {
        // Handled by Laravel Fortify
        // Send password reset notification
    }

    public function enable2FA(User $user): array
    {
        // Generate 2FA secret
        $secret = app(\PragmaRX\Google2FALaravel\Google2FA::class)->generateSecretKey();

        $user->update([
            'two_factor_secret' => encrypt($secret),
        ]);

        $qrCodeUrl = app(\PragmaRX\Google2FALaravel\Google2FA::class)
            ->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            );

        return [
            'secret' => $secret,
            'qr_code' => $qrCodeUrl,
        ];
    }

    public function confirm2FA(User $user, string $code): bool
    {
        $valid = app(\PragmaRX\Google2FALaravel\Google2FA::class)
            ->verifyKey(decrypt($user->two_factor_secret), $code);

        if ($valid) {
            $user->update([
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes())),
            ]);

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('2FA enabled');
        }

        return $valid;
    }

    public function disable2FA(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('2FA disabled');
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(sha1(random_bytes(20)), 0, 8));
        }
        return $codes;
    }
}
```

### 4. Controllers

#### AuthController
```php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth()->user()->load('roles', 'permissions')),
        ]);
    }

    public function refresh(): JsonResponse
    {
        $result = $this->authService->refreshToken();

        return response()->json([
            'data' => $result,
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 400);
        }

        $this->authService->verifyEmail($user);

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }

    public function enable2FA(): JsonResponse
    {
        $result = $this->authService->enable2FA(auth()->user());

        return response()->json([
            'message' => '2FA setup initiated',
            'data' => $result,
        ]);
    }

    public function confirm2FA(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $valid = $this->authService->confirm2FA(
            auth()->user(),
            $request->code
        );

        if (!$valid) {
            return response()->json([
                'message' => 'Invalid code',
            ], 422);
        }

        return response()->json([
            'message' => '2FA enabled successfully',
        ]);
    }

    public function disable2FA(): JsonResponse
    {
        $this->authService->disable2FA(auth()->user());

        return response()->json([
            'message' => '2FA disabled successfully',
        ]);
    }
}
```

### 5. Form Requests

#### LoginRequest
```php
// app/Http/Requests/Auth/LoginRequest.php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['boolean'],
        ];
    }
}
```

#### RegisterRequest
```php
// app/Http/Requests/Auth/RegisterRequest.php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
        ];
    }
}
```

### 6. Middleware

#### CheckRole Middleware
```php
// app/Http/Middleware/CheckRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$request->user()->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Unauthorized. Required role: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
```

#### CheckPermission Middleware
```php
// app/Http/Middleware/CheckPermission.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$request->user()->hasAnyPermission($permissions)) {
            return response()->json([
                'message' => 'Unauthorized. Missing permission: ' . implode(', ', $permissions)
            ], 403);
        }

        return $next($request);
    }
}
```

### 7. Routes

#### API Routes
```php
// routes/api.php
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);

        // 2FA routes
        Route::post('/2fa/enable', [AuthController::class, 'enable2FA']);
        Route::post('/2fa/confirm', [AuthController::class, 'confirm2FA']);
        Route::delete('/2fa/disable', [AuthController::class, 'disable2FA']);
    });

    // Role-based routes
    Route::middleware(['role:super_admin,manager'])->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::middleware(['permission:projets.create'])->group(function () {
        Route::post('projets', [ProjetController::class, 'store']);
    });
});
```

### 8. Configuration

#### Fortify Configuration
```php
// config/fortify.php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

#### Sanctum Configuration
```php
// config/sanctum.php
'expiration' => 60 * 24 * 7, // 7 days
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

'middleware' => [
    'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
],
```

#### CORS Configuration
```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

---

## 🎨 Frontend Implementation Flow

### 1. Project Structure

```
resources/js/
├── api/
│   ├── axios.js              # Axios instance with interceptors
│   └── auth.js               # Auth API endpoints
├── composables/
│   ├── useAuth.js            # Auth composable (state + methods)
│   ├── usePermissions.js     # Permission checks
│   └── useRoles.js           # Role checks
├── stores/
│   └── authStore.js          # Pinia store for auth state
├── middleware/
│   ├── auth.js               # Route guard
│   ├── guest.js              # Guest guard
│   └── permission.js         # Permission guard
├── components/
│   ├── auth/
│   │   ├── LoginForm.vue
│   │   ├── RegisterForm.vue
│   │   ├── ForgotPasswordForm.vue
│   │   ├── ResetPasswordForm.vue
│   │   ├── TwoFactorSetup.vue
│   │   └── TwoFactorChallenge.vue
│   └── layout/
│       ├── AuthLayout.vue
│       └── AppLayout.vue
├── pages/
│   ├── auth/
│   │   ├── Login.vue
│   │   ├── Register.vue
│   │   ├── ForgotPassword.vue
│   │   └── ResetPassword.vue
│   └── Dashboard.vue
├── router/
│   └── index.js              # Vue Router config
└── App.vue
```

### 2. Axios Configuration

```javascript
// resources/js/api/axios.js
import axios from 'axios';
import router from '@/router';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor - Add auth token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor - Handle errors
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        // 401 Unauthorized - Token expired
        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;

            try {
                const { data } = await api.post('/auth/refresh');
                localStorage.setItem('auth_token', data.data.token);
                originalRequest.headers.Authorization = `Bearer ${data.data.token}`;
                return api(originalRequest);
            } catch (refreshError) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                router.push('/login');
                return Promise.reject(refreshError);
            }
        }

        // 403 Forbidden - Insufficient permissions
        if (error.response?.status === 403) {
            router.push('/unauthorized');
        }

        return Promise.reject(error);
    }
);

export default api;
```

### 3. Auth API Module

```javascript
// resources/js/api/auth.js
import api from './axios';

export const authAPI = {
    // Get CSRF cookie first (for session-based auth)
    async getCsrfCookie() {
        await api.get('/sanctum/csrf-cookie');
    },

    async register(data) {
        await this.getCsrfCookie();
        return api.post('/auth/register', data);
    },

    async login(credentials) {
        await this.getCsrfCookie();
        return api.post('/auth/login', credentials);
    },

    async logout() {
        return api.post('/auth/logout');
    },

    async getUser() {
        return api.get('/auth/me');
    },

    async refreshToken() {
        return api.post('/auth/refresh');
    },

    async forgotPassword(email) {
        await this.getCsrfCookie();
        return api.post('/auth/forgot-password', { email });
    },

    async resetPassword(data) {
        await this.getCsrfCookie();
        return api.post('/auth/reset-password', data);
    },

    async verifyEmail() {
        return api.post('/auth/verify-email');
    },

    async enable2FA() {
        return api.post('/auth/2fa/enable');
    },

    async confirm2FA(code) {
        return api.post('/auth/2fa/confirm', { code });
    },

    async disable2FA() {
        return api.delete('/auth/2fa/disable');
    },

    async verify2FA(code) {
        return api.post('/auth/2fa/verify', { code });
    },
};
```

### 4. Pinia Auth Store

```javascript
// resources/js/stores/authStore.js
import { defineStore } from 'pinia';
import { authAPI } from '@/api/auth';
import router from '@/router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('auth_token') || null,
        isAuthenticated: !!localStorage.getItem('auth_token'),
        loading: false,
        error: null,
    }),

    getters: {
        currentUser: (state) => state.user,

        userRoles: (state) => state.user?.roles?.map(r => r.name) || [],

        userPermissions: (state) => {
            const rolePerms = state.user?.roles?.flatMap(r => r.permissions) || [];
            const directPerms = state.user?.permissions || [];
            return [...rolePerms, ...directPerms].map(p => p.name);
        },

        hasRole: (state) => (role) => {
            return state.user?.roles?.some(r => r.name === role) || false;
        },

        hasAnyRole: (state) => (roles) => {
            return roles.some(role => state.user?.roles?.some(r => r.name === role));
        },

        hasPermission: (state) => (permission) => {
            const allPerms = [
                ...state.user?.roles?.flatMap(r => r.permissions) || [],
                ...state.user?.permissions || []
            ];
            return allPerms.some(p => p.name === permission);
        },

        hasAnyPermission: (state) => (permissions) => {
            const allPerms = [
                ...state.user?.roles?.flatMap(r => r.permissions) || [],
                ...state.user?.permissions || []
            ];
            return permissions.some(perm =>
                allPerms.some(p => p.name === perm)
            );
        },

        isSuperAdmin: (state) => {
            return state.user?.roles?.some(r => r.name === 'super_admin') || false;
        },
    },

    actions: {
        async register(data) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.register(data);
                router.push('/login');
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Registration failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async login(credentials) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.login(credentials);
                const { user, token } = response.data.data;

                this.user = user;
                this.token = token;
                this.isAuthenticated = true;

                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('auth_token', token);

                router.push('/dashboard');
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            this.loading = true;

            try {
                await authAPI.logout();
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.user = null;
                this.token = null;
                this.isAuthenticated = false;

                localStorage.removeItem('user');
                localStorage.removeItem('auth_token');

                router.push('/login');
                this.loading = false;
            }
        },

        async fetchUser() {
            if (!this.token) return;

            this.loading = true;

            try {
                const response = await authAPI.getUser();
                this.user = response.data.data;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (error) {
                this.logout();
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async refreshToken() {
            try {
                const response = await authAPI.refreshToken();
                this.token = response.data.data.token;
                localStorage.setItem('auth_token', this.token);
            } catch (error) {
                this.logout();
                throw error;
            }
        },

        async forgotPassword(email) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.forgotPassword(email);
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Request failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async resetPassword(data) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.resetPassword(data);
                router.push('/login');
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Reset failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async enable2FA() {
            this.loading = true;

            try {
                const response = await authAPI.enable2FA();
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || '2FA setup failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async confirm2FA(code) {
            this.loading = true;

            try {
                const response = await authAPI.confirm2FA(code);
                await this.fetchUser(); // Refresh user data
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || '2FA confirmation failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async disable2FA() {
            this.loading = true;

            try {
                const response = await authAPI.disable2FA();
                await this.fetchUser(); // Refresh user data
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || '2FA disable failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
```

### 5. Auth Composable

```javascript
// resources/js/composables/useAuth.js
import { computed } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { storeToRefs } from 'pinia';

export function useAuth() {
    const authStore = useAuthStore();
    const { user, isAuthenticated, loading, error } = storeToRefs(authStore);

    const hasRole = (role) => authStore.hasRole(role);
    const hasAnyRole = (roles) => authStore.hasAnyRole(roles);
    const hasPermission = (permission) => authStore.hasPermission(permission);
    const hasAnyPermission = (permissions) => authStore.hasAnyPermission(permissions);

    return {
        // State
        user,
        isAuthenticated,
        loading,
        error,

        // Getters
        userRoles: computed(() => authStore.userRoles),
        userPermissions: computed(() => authStore.userPermissions),
        isSuperAdmin: computed(() => authStore.isSuperAdmin),

        // Methods
        login: authStore.login,
        register: authStore.register,
        logout: authStore.logout,
        fetchUser: authStore.fetchUser,
        forgotPassword: authStore.forgotPassword,
        resetPassword: authStore.resetPassword,
        enable2FA: authStore.enable2FA,
        confirm2FA: authStore.confirm2FA,
        disable2FA: authStore.disable2FA,

        // Helpers
        hasRole,
        hasAnyRole,
        hasPermission,
        hasAnyPermission,
    };
}
```

### 6. Route Guards

```javascript
// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('@/pages/auth/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'ForgotPassword',
        component: () => import('@/pages/auth/ForgotPassword.vue'),
        meta: { guest: true },
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('@/pages/Dashboard.vue'),
        meta: {
            requiresAuth: true,
            roles: ['super_admin', 'manager', 'cadre']
        },
    },
    {
        path: '/admin',
        name: 'Admin',
        component: () => import('@/pages/Admin.vue'),
        meta: {
            requiresAuth: true,
            roles: ['super_admin'],
        },
    },
    {
        path: '/unauthorized',
        name: 'Unauthorized',
        component: () => import('@/pages/Unauthorized.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Global navigation guard
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Check if route requires authentication
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next({ name: 'Login', query: { redirect: to.fullPath } });
    }

    // Check if route is for guests only
    if (to.meta.guest && authStore.isAuthenticated) {
        return next({ name: 'Dashboard' });
    }

    // Check role requirements
    if (to.meta.roles && !authStore.hasAnyRole(to.meta.roles)) {
        return next({ name: 'Unauthorized' });
    }

    // Check permission requirements
    if (to.meta.permissions && !authStore.hasAnyPermission(to.meta.permissions)) {
        return next({ name: 'Unauthorized' });
    }

    next();
});

export default router;
```

### 7. Vue Components

#### Login Form Component
```vue
<!-- resources/js/components/auth/LoginForm.vue -->
<template>
  <form @submit.prevent="handleLogin" class="space-y-6">
    <div>
      <label for="email" class="block text-sm font-medium text-gray-700">
        Email
      </label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        required
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        :class="{ 'border-red-500': errors.email }"
      />
      <p v-if="errors.email" class="mt-1 text-sm text-red-600">
        {{ errors.email }}
      </p>
    </div>

    <div>
      <label for="password" class="block text-sm font-medium text-gray-700">
        Password
      </label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        required
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        :class="{ 'border-red-500': errors.password }"
      />
      <p v-if="errors.password" class="mt-1 text-sm text-red-600">
        {{ errors.password }}
      </p>
    </div>

    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <input
          id="remember"
          v-model="form.remember"
          type="checkbox"
          class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        />
        <label for="remember" class="ml-2 block text-sm text-gray-900">
          Remember me
        </label>
      </div>

      <router-link
        to="/forgot-password"
        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
      >
        Forgot password?
      </router-link>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>

    <button
      type="submit"
      :disabled="loading"
      class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
    >
      <span v-if="loading">Logging in...</span>
      <span v-else>Sign in</span>
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { login, loading, error } = useAuth();

const form = reactive({
  email: '',
  password: '',
  remember: false,
});

const errors = ref({});

const handleLogin = async () => {
  errors.value = {};

  try {
    await login(form);
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    }
  }
};
</script>
```

#### Permission Directive
```javascript
// resources/js/directives/permission.js
export const permissionDirective = {
    mounted(el, binding) {
        const { value } = binding;
        const authStore = useAuthStore();

        if (!authStore.hasPermission(value)) {
            el.style.display = 'none';
        }
    },
};

// Register in main.js
app.directive('permission', permissionDirective);

// Usage in components
<button v-permission="'taches.delete'">Delete</button>
```

---

## 🔄 Authentication Flow Diagrams

### Registration Flow
```
User → Frontend → Backend → Database
  1. Fill registration form
  2. POST /api/auth/register
  3. Validate input (FormRequest)
  4. Hash password
  5. Create user record
  6. Assign default role (Spatie)
  7. Send verification email (Fortify)
  8. Log activity
  9. Return success response
  10. Redirect to login page
```

### Login Flow
```
User → Frontend → Backend → Database
  1. Fill login form
  2. POST /api/auth/login
  3. Validate credentials
  4. Check user is active
  5. Create Sanctum token
  6. Update last_login_at & IP
  7. Log activity
  8. Return user + token
  9. Store token in localStorage
  10. Redirect to dashboard
```

### Token Refresh Flow
```
Request with expired token
  ↓
Axios interceptor catches 401
  ↓
POST /api/auth/refresh
  ↓
Revoke old token
  ↓
Create new token
  ↓
Update localStorage
  ↓
Retry original request
```

### 2FA Flow
```
1. Enable 2FA:
   - POST /api/auth/2fa/enable
   - Generate secret + QR code
   - Return to user

2. Confirm 2FA:
   - User scans QR with authenticator app
   - POST /api/auth/2fa/confirm {code}
   - Verify code with secret
   - Save confirmed_at timestamp
   - Generate recovery codes

3. Login with 2FA:
   - Regular login succeeds
   - Check if 2FA enabled
   - Prompt for 2FA code
   - POST /api/auth/2fa/verify {code}
   - Verify and complete login
```

---

## 🛡️ Security Best Practices

### Backend Security
1. **Password Security**
   - Minimum 8 characters
   - Mixed case, numbers, symbols required
   - Bcrypt hashing (Laravel default)
   - Password history (prevent reuse)

2. **Token Security**
   - Short expiration (7 days max)
   - Secure token storage
   - Token rotation on refresh
   - Revoke on logout

3. **Rate Limiting**
   ```php
   // routes/api.php
   Route::middleware(['throttle:5,1'])->group(function () {
       Route::post('/auth/login', [AuthController::class, 'login']);
   });
   ```

4. **CSRF Protection**
   - Sanctum CSRF cookie
   - Verify CSRF token on state-changing requests

5. **Activity Logging**
   - Log all auth events
   - Track IP addresses
   - Monitor suspicious activity

### Frontend Security
1. **XSS Prevention**
   - Sanitize user input
   - Use v-text instead of v-html
   - Content Security Policy headers

2. **Token Storage**
   - Use httpOnly cookies (preferred) or localStorage
   - Never expose tokens in URLs
   - Clear tokens on logout

3. **Route Protection**
   - Navigation guards
   - Role/permission checks
   - Redirect unauthorized users

---

## 📊 Database Seeders

```php
// database/seeders/RolePermissionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\Role as RoleEnum;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'projets.view', 'projets.create', 'projets.update', 'projets.delete',
            'activites.view', 'activites.create', 'activites.update', 'activites.delete',
            'taches.view', 'taches.create', 'taches.update', 'taches.delete', 'taches.validate',
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign',
            'reports.view', 'reports.create',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::create([
                'name' => $roleEnum->value,
                'guard_name' => 'web'
            ]);

            $rolePermissions = $roleEnum->permissions();

            if (in_array('*', $rolePermissions)) {
                $role->givePermissionTo(Permission::all());
            } else {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }
}
```

---

## 🧪 Testing

### Backend Tests
```php
// tests/Feature/Auth/LoginTest.php
namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                    'token_type',
                    'expires_at',
                ],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }
}
```

---

## 🚀 Deployment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Generate `APP_KEY`
- [ ] Configure database credentials
- [ ] Set `SESSION_DRIVER=redis`
- [ ] Set `CACHE_DRIVER=redis`
- [ ] Configure `SANCTUM_STATEFUL_DOMAINS`
- [ ] Set `FRONTEND_URL` for CORS
- [ ] Enable HTTPS
- [ ] Configure email driver (SMTP/SES)
- [ ] Set up queue worker
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed roles/permissions: `php artisan db:seed --class=RolePermissionSeeder`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Cache config: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`

---

## 📚 API Documentation Example

### POST /api/auth/login

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "remember": true
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "roles": [
        {
          "id": 2,
          "name": "manager",
          "permissions": [...]
        }
      ]
    },
    "token": "1|abc123...",
    "token_type": "Bearer",
    "expires_at": "2025-10-11T00:00:00.000000Z"
  }
}
```

**Errors:**
- `401` - Invalid credentials
- `422` - Validation error
- `429` - Too many attempts

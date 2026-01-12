<?php

namespace App\Models;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject; // <-- add this

class User extends Authenticatable implements JWTSubject // <-- implement interface
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes;
    use HasRoles;
    
    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'reset_token',
        'reset_link_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * JWT required methods
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // usually the user id
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    
    public function userType(){
        return $this->belongsTo(UserType::class);
    }
}

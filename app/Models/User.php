<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'nick_name',
        'name',
        'email',
        'password',
        'gender',
        'birthdate',
        'profile_photo',
        'about',
        'coins',
        'beans',
        'country_code',
        'contact_number',
        'status',
        'auth_provider',
        'auth_provider_id',

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
            'deleted_at' => 'datetime',
            'updated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
    // return country code with contact number in format +countrycode contactnumber also need flag emoji of country code in front of contact number if country code is present
    public function getContactNumberAttribute($value)
    {
        if ($value && $this->country_code) {
            return $this->country_code ." ". $value;
        }
        return $value;
    }

    // whenever user is returned in api response we will append country flag emoji if country code is present from countries table
    public function getCountryFlagAttribute()
    {
        if ($this->country_code) {
           
            $country = Country::where('code', $this->country_code)->first();
            if ($country) {
                return env('APP_URL', 'http://localhost') . $country->flag;
            }
        }
        return env('APP_URL', 'http://localhost') . "/settings/flags/default-flag.png";
    }
     protected $appends = [ 'country_flag'];

    
}

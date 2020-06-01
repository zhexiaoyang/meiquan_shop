<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasMultiAuthApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function findForPassport($username)
    {
        $credentials['name'] = $username;

        return self::query()->where($credentials)->first();
    }

    /**
     * 购物车
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * 收货地址
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function address()
    {
        return $this->hasMany(Address::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function histories()
    {
        return $this->belongsToMany(Product::class, 'histories')->withTimestamps();
    }
}

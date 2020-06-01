<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'name',
        'tel',
        'province',
        'city',
        'county',
        'address_detail',
        'areaCode',
        'postal_code',
        'area_code',
        'last_used_at',
    ];

    protected $casts = [
        "isDefault" => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute()
    {
        return "{$this->province}{$this->city}{$this->county}{$this->address_detail}";
    }
}

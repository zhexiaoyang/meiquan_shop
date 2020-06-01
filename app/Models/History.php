<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $fillable = [
        'user_id', 'product_id'
    ];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

}
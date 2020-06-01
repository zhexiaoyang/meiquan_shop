<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $fillable = [
        'parent_id', 'title', 'image', 'sort', 'status'
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}

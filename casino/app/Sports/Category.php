<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'sports_categories';

    protected $fillable = ['name', 'odds_api_name', 'slug', 'icon', 'regions', 'status'];

    protected $casts = [
        'regions' => 'array',
    ];

    public function leagues()
    {
        return $this->hasMany(League::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}

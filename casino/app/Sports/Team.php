<?php

namespace VanguardLTE\Sports;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $table = 'sports_teams';

    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'short_name',
        'image',
        'manually_added'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}

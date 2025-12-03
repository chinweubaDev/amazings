<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'name',
        'type',
        'logo',
        'country_name',
        'country_code',
        'country_flag',
        'is_top_league',
        'seasons',
    ];

    protected $casts = [
        'is_top_league' => 'boolean',
        'seasons' => 'array', // stored as JSON
    ];

    /**
     * A league belongs to a country.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Scope for top leagues only.
     */
    public function scopeTop($query)
    {
        return $query->where('is_top_league', true);
    }
    public function fixtures()
    {
        return $this->hasMany(Fixture::class, 'league_id', 'league_id');
    }

    public function getSlugAttribute()
    {
        $country = \Illuminate\Support\Str::slug($this->country_name);
        $name = \Illuminate\Support\Str::slug($this->name);
        return "football-predictions-for-{$country}/{$name}-{$this->league_id}";
    }
}

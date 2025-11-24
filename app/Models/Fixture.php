<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fixture extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id',
        'referee',
        'timezone',
        'date',
        'timestamp',
        'venue_name',
        'venue_city',
        'status_long',
        'status_short',
        'elapsed',
        'league_id',
        'league_name',
        'league_country',
        'league_logo',
        'league_flag',
        'league_round',
        'league_season',
        'home_team_id',
        'home_team_name',
        'home_team_logo',
        'home_team_winner',
        'away_team_id',
        'away_team_name',
        'away_team_logo',
        'away_team_winner',
        'goals_home',
        'goals_away',
        'halftime_home',
        'halftime_away',
        'fulltime_home',
        'fulltime_away',
        'odds',
        'has_odds',
        'head2head',
        'statistics',
    ];
    
    protected $casts = [
        'odds' => 'array',
        'odds_fetched' => 'boolean',
        'head2head' => 'array',
        'statistics' => 'array',
    ];
    public function odds()
{
    return $this->hasMany(Odd::class, 'fixture_id', 'fixture_id');
}
public function league()
{
    return $this->belongsTo(League::class, 'league_id', 'league_id');
}

/**
 * Scope fixtures that don't have odds fetched.
 */
public function scopeWithoutOdds($query)
{
    return $query->whereNull('odds')->orWhere('odds_fetched', false);
}
}

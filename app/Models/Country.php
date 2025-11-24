<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'flag',
    ];

    /**
     * A country can have many leagues.
     */
    public function leagues()
    {
        return $this->hasMany(League::class, 'country_code', 'code');
    }
    public function fixtures()
    {
        return $this->hasManyThrough(Fixture::class, League::class, 'country_code', 'league_id', 'code', 'league_id');
    }
}

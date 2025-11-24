<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odd extends Model
{
    protected $fillable = [
        'fixture_id',
        'bookmaker_id',
        'bookmaker_name',
        'bet_name',
        'bet_value',
        'odd',
    ];

    public function fixture()
    {
        return $this->belongsTo(Fixture::class, 'fixture_id', 'fixture_id');
    }
}

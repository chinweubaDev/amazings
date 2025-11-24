<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use Illuminate\Http\Request;

class FixtureController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $fixtures = Fixture::whereDate('date', $date)->get();

        return response()->json([
            'date' => $date,
            'count' => $fixtures->count(),
            'fixtures' => $fixtures,
        ]);
    }
}

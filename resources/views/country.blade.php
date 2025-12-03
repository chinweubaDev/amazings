@extends('layouts.masters')

@section('title', $pageTitle)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $pageTitle }}</h1>
            @if(count($grouped) > 0)
                @foreach($grouped as $date => $leagues)
                    <h3>{{ $date }}</h3>
                    @foreach($leagues as $leagueId => $group)
                    <div class="card mb-3">
                        <div class="card-header">
                            <img src="{{ $group['league']['logo'] ?? '' }}" alt="{{ $group['league']['name'] }}" style="height: 20px;">
                            {{ $group['league']['name'] }} ({{ $group['league']['country'] }})
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Home</th>
                                        <th>Away</th>
                                        <th>Prediction</th>
                                        <th>Confidence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group['matches'] as $match)
                                    <tr>
                                        <td>{{ $match['time'] }}</td>
                                        <td>
                                            <img src="{{ $match['home_logo'] }}" alt="{{ $match['home_team'] }}" style="height: 20px;">
                                            {{ $match['home_team'] }}
                                        </td>
                                        <td>
                                            <img src="{{ $match['away_logo'] }}" alt="{{ $match['away_team'] }}" style="height: 20px;">
                                            {{ $match['away_team'] }}
                                        </td>
                                        <td>{{ $match['prediction'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $match['prediction_color'] }}">
                                                {{ $match['confidence'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                @endforeach
            @else
                <p>No upcoming fixtures found for this country.</p>
            @endif
        </div>
    </div>
</div>
@endsection

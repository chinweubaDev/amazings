@extends('layouts.masters')

@push('styles')
<style>
    .card-header img { border-radius: 4px; }
    table img { vertical-align: middle; }
    th, td { vertical-align: middle !important; }
    .prediction-badge { font-weight: bold; padding: 8px 12px; border-radius: 20px; }
    .prediction-yes { background-color: #28a745; color: white; }
    .prediction-no { background-color: #dc3545; color: white; }
    .confidence-high { color: #28a745; font-weight: bold; }
    .confidence-medium { color: #ffc107; font-weight: bold; }
    .confidence-low { color: #dc3545; font-weight: bold; }
    .btts-stats { font-size: 0.85em; }
    .odds-section { background-color: #f8f9fa; padding: 8px; border-radius: 6px; }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">⚽ Both Teams to Score Predictions</h2>
        <a href="{{ route('home') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>
    </div>

    @if(isset($grouped) && count($grouped) > 0)
        @foreach ($grouped as $groupName => $fixtures)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white d-flex align-items-center">
                    @if(!empty($fixtures[0]['country_flag']))
                        <img src="{{ $fixtures[0]['country_flag'] }}" width="30" height="20" class="me-2" alt="Flag">
                    @endif
                    <strong>{{ $groupName }}</strong>
                    <span class="badge bg-secondary ms-2">{{ count($fixtures) }} matches</span>
                </div>

                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th class="text-end">Home Team</th>
                            <th></th>
                            <th class="text-start">Away Team</th>
                            <th>BTTS Prediction</th>
                            <th>Confidence</th>
                            <th>Predicted Odds</th>
                            <th>BTTS Stats</th>
                            <th>All Odds</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($fixtures as $f)
                            <tr>
                                <td class="fw-bold">
                                    {{ \Carbon\Carbon::parse($f['match_date'])->format('H:i') }}
                                </td>

                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <span class="me-2">{{ $f['home_team'] ?? 'Home Team' }}</span>
                                        @if(!empty($f['home_logo']))
                                            <img src="{{ $f['home_logo'] }}" width="25" height="25" alt="Home">
                                        @endif
                                    </div>
                                </td>

                                <td class="text-center fw-bold text-muted">vs</td>

                                <td class="text-start">
                                    <div class="d-flex align-items-center">
                                        @if(!empty($f['away_logo']))
                                            <img src="{{ $f['away_logo'] }}" width="25" height="25" class="me-2" alt="Away">
                                        @endif
                                        <span>{{ $f['away_team'] ?? 'Away Team' }}</span>
                                    </div>
                                </td>

                                <td>
                                    @php
                                        $predictionClass = $f['prediction'] === 'Yes' ? 'prediction-yes' : 'prediction-no';
                                    @endphp
                                    <span class="prediction-badge {{ $predictionClass }}">
                                        {{ $f['prediction'] ?? '—' }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $confidence = $f['confidence'] ?? 0;
                                        $confidenceClass = 'confidence-low';
                                        if($confidence >= 75) $confidenceClass = 'confidence-high';
                                        elseif($confidence >= 60) $confidenceClass = 'confidence-medium';
                                    @endphp
                                    <span class="{{ $confidenceClass }}">
                                        {{ $confidence }}%
                                    </span>
                                </td>

                                <td>
                                    @if($f['predicted_odd'])
                                        <span class="badge bg-success">{{ $f['predicted_odd'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if(isset($f['btts_stats']) && $f['btts_stats']['total_matches'] > 0)
                                        <div class="btts-stats">
                                            <div><small><strong>BTTS:</strong> {{ $f['btts_stats']['both_scored_count'] }}/{{ $f['btts_stats']['total_matches'] }}</small></div>
                                            <div><small><strong>Rate:</strong> {{ $f['btts_stats']['btts_percentage'] }}%</small></div>
                                            <div><small><strong>Avg Goals:</strong> {{ $f['btts_stats']['avg_goals'] }}</small></div>
                                        </div>
                                    @else
                                        <small class="text-muted">No stats</small>
                                    @endif
                                </td>

                                <td>
                                    @if(!empty($f['available_odds']) && is_array($f['available_odds']))
                                        <div class="odds-section">
                                            @foreach($f['available_odds'] as $outcome => $odd)
                                                <div class="d-flex justify-content-between">
                                                    <small><strong>{{ $outcome }}:</strong></small>
                                                    <small>{{ $odd }}</small>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <small class="text-muted">No odds</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <!-- Summary Statistics -->
        <div class="card mt-4 shadow-sm">
            <div class="card-header bg-info text-white">
                <strong><i class="bi bi-graph-up"></i> BTTS Summary</strong>
            </div>
            <div class="card-body">
                @php
                    $allFixtures = collect($grouped)->flatten(1);
                    $totalMatches = $allFixtures->count();
                    $yesCount = $allFixtures->where('prediction', 'Yes')->count();
                    $noCount = $allFixtures->where('prediction', 'No')->count();
                    $avgConfidence = $allFixtures->avg('confidence');
                    $avgBttsRate = $allFixtures->where('btts_stats.total_matches', '>', 0)->avg('btts_stats.btts_percentage');
                @endphp
                
                <div class="row text-center">
                    <div class="col-md-2">
                        <h5 class="text-primary">{{ $totalMatches }}</h5>
                        <small>Total Matches</small>
                    </div>
                    <div class="col-md-2">
                        <h5 class="text-success">{{ $yesCount }}</h5>
                        <small>Yes Predictions</small>
                    </div>
                    <div class="col-md-2">
                        <h5 class="text-danger">{{ $noCount }}</h5>
                        <small>No Predictions</small>
                    </div>
                    <div class="col-md-3">
                        <h5 class="text-info">{{ number_format($avgConfidence, 1) }}%</h5>
                        <small>Average Confidence</small>
                    </div>
                    <div class="col-md-3">
                        <h5 class="text-warning">{{ number_format($avgBttsRate, 1) }}%</h5>
                        <small>Avg H2H BTTS Rate</small>
                    </div>
                </div>
            </div>
        </div>

    @else
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No both teams to score predictions available for today.
        </div>
    @endif
</div>
@endsection
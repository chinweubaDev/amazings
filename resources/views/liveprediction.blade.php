@extends('layouts.masters')

@push('styles')
<style>
    .card-header img { border-radius: 4px; }
    table img { vertical-align: middle; }
    th, td { vertical-align: middle !important; }
    .prediction-badge { font-weight: bold; padding: 8px 12px; border-radius: 20px; }
    .prediction-home { background-color: #28a745; color: white; }
    .prediction-away { background-color: #dc3545; color: white; }
    .prediction-draw { background-color: #ffc107; color: black; }
    .confidence-high { color: #28a745; font-weight: bold; }
    .confidence-medium { color: #ffc107; font-weight: bold; }
    .confidence-low { color: #dc3545; font-weight: bold; }
    .h2h-stats { font-size: 0.85em; }
    .odds-section { background-color: #f8f9fa; padding: 8px; border-radius: 6px; }
    .live-badge { 
        animation: pulse 2s infinite; 
        background-color: #dc3545; 
        color: white; 
        padding: 4px 8px; 
        border-radius: 4px; 
        font-weight: bold;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .score-display {
        font-size: 1.2em;
        font-weight: bold;
        color: #007bff;
    }
    .auto-refresh-info {
        background-color: #e7f3ff;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">⚽ Live Predictions</h2>
        <a href="{{ route('home') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>
    </div>

    <!-- Auto-refresh info -->
    <div class="auto-refresh-info">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-arrow-clockwise"></i> 
                <strong>Auto-refresh enabled</strong> - Updates every 30 seconds
            </div>
            <div>
                <small>Last updated: <span id="last-update">{{ now()->format('H:i:s') }}</span></small>
            </div>
        </div>
    </div>

    <!-- Live fixtures container -->
    <div id="live-fixtures-container">
        @if(isset($grouped) && count($grouped) > 0)
            @foreach ($grouped as $groupName => $fixtures)
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        @if(!empty($fixtures[0]['country_flag']))
                            <img src="{{ $fixtures[0]['country_flag'] }}" width="30" height="20" class="me-2" alt="Flag">
                        @endif
                        <strong>{{ $groupName }}</strong>
                        <span class="badge bg-secondary ms-2">{{ count($fixtures) }} matches</span>
                        <span class="live-badge ms-2">LIVE</span>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th>Time</th>
                                <th class="text-end">Home Team</th>
                                <th>Score</th>
                                <th class="text-start">Away Team</th>
                                <th>Prediction</th>
                                <th>Confidence</th>
                                <th>Predicted Odds</th>
                                <th>H2H Stats</th>
                                <th>All Odds</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($fixtures as $f)
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">{{ $f['status_short'] ?? 'LIVE' }}</span>
                                        @if(isset($f['elapsed']) && $f['elapsed'])
                                            <br><small>{{ $f['elapsed'] }}'</small>
                                        @endif
                                    </td>

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

                                    <td class="text-center">
                                        <span class="score-display">
                                            {{ $f['home_score'] ?? 0 }} - {{ $f['away_score'] ?? 0 }}
                                        </span>
                                        @if(isset($f['halftime_home']) && isset($f['halftime_away']))
                                            <br><small class="text-muted">(HT: {{ $f['halftime_home'] }}-{{ $f['halftime_away'] }})</small>
                                        @endif
                                    </td>

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
                                            $predictionClass = 'prediction-draw';
                                            if($f['prediction'] === 'Home') $predictionClass = 'prediction-home';
                                            elseif($f['prediction'] === 'Away') $predictionClass = 'prediction-away';
                                        @endphp
                                        <span class="prediction-badge {{ $predictionClass }}">
                                            {{ $f['prediction'] ?? '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        @php
                                            $confidence = $f['confidence'] ?? 0;
                                            $confidenceClass = 'confidence-low';
                                            if($confidence >= 80) $confidenceClass = 'confidence-high';
                                            elseif($confidence >= 65) $confidenceClass = 'confidence-medium';
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
                                        @if(isset($f['h2h_stats']) && $f['h2h_stats']['total_matches'] > 0)
                                            <div class="h2h-stats">
                                                <div><small><strong>H:</strong> {{ $f['h2h_stats']['home_wins'] }}</small></div>
                                                <div><small><strong>D:</strong> {{ $f['h2h_stats']['draws'] }}</small></div>
                                                <div><small><strong>A:</strong> {{ $f['h2h_stats']['away_wins'] }}</small></div>
                                                <div><small class="text-muted">({{ $f['h2h_stats']['total_matches'] }} games)</small></div>
                                            </div>
                                        @else
                                            <small class="text-muted">No H2H data</small>
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
                    <strong><i class="bi bi-graph-up"></i> Live Matches Summary</strong>
                </div>
                <div class="card-body">
                    @php
                        $allFixtures = collect($grouped)->flatten(1);
                        $totalMatches = $allFixtures->count();
                        $homePredictions = $allFixtures->where('prediction', 'Home')->count();
                        $awayPredictions = $allFixtures->where('prediction', 'Away')->count();
                        $drawPredictions = $allFixtures->where('prediction', 'Draw')->count();
                        $avgConfidence = $allFixtures->avg('confidence');
                    @endphp
                    
                    <div class="row text-center">
                        <div class="col-md-2">
                            <h5 class="text-primary">{{ $totalMatches }}</h5>
                            <small>Live Matches</small>
                        </div>
                        <div class="col-md-2">
                            <h5 class="text-success">{{ $homePredictions }}</h5>
                            <small>Home Wins</small>
                        </div>
                        <div class="col-md-2">
                            <h5 class="text-warning">{{ $drawPredictions }}</h5>
                            <small>Draws</small>
                        </div>
                        <div class="col-md-2">
                            <h5 class="text-danger">{{ $awayPredictions }}</h5>
                            <small>Away Wins</small>
                        </div>
                        <div class="col-md-4">
                            <h5 class="text-info">{{ number_format($avgConfidence, 1) }}%</h5>
                            <small>Average Confidence</small>
                        </div>
                    </div>
                </div>
            </div>

        @else
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle"></i> No live matches at the moment. Check back when matches are in progress!
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Auto-refresh every 30 seconds
    let refreshInterval;

    function updateLiveFixtures() {
        fetch('{{ route('ajax.live.predictions') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update last update time
                    const now = new Date();
                    document.getElementById('last-update').textContent = 
                        now.toLocaleTimeString('en-US', { hour12: false });
                    
                    // If data has changed significantly, reload the page
                    // For simplicity, we'll just reload. You could do a more sophisticated DOM update
                    if (data.count > 0) {
                        console.log('Live fixtures updated:', data.count, 'matches');
                        // Optionally reload to show updated data
                        // location.reload();
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching live fixtures:', error);
            });
    }

    // Start auto-refresh when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Refresh every 30 seconds
        refreshInterval = setInterval(updateLiveFixtures, 30000);
    });

    // Clear interval when leaving page
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
</script>
@endpush
@endsection

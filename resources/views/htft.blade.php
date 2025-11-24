<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Halftime/Fulltime Predictions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-header img { border-radius: 4px; }
        table img { vertical-align: middle; }
        th, td { vertical-align: middle !important; }
        .prediction-badge { font-weight: bold; padding: 8px 12px; border-radius: 20px; }
        .prediction-home { background-color: #28a745; color: white; }
        .prediction-away { background-color: #dc3545; color: white; }
        .prediction-draw { background-color: #ffc107; color: black; }
        .prediction-mixed { background-color: #6f42c1; color: white; }
        .confidence-high { color: #28a745; font-weight: bold; }
        .confidence-medium { color: #ffc107; font-weight: bold; }
        .confidence-low { color: #dc3545; font-weight: bold; }
        .h2h-stats { font-size: 0.85em; }
        .odds-section { background-color: #f8f9fa; padding: 8px; border-radius: 6px; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold">⏱️ Halftime/Fulltime Predictions</h2>
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
                            <th>HT/FT Prediction</th>
                            <th>Confidence</th>
                            <th>Predicted Odds</th>
                            <th>H2H Stats</th>
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
                                        $prediction = $f['prediction'] ?? '';
                                        $predictionClass = 'prediction-mixed';
                                        
                                        if(in_array($prediction, ['1/1'])) $predictionClass = 'prediction-home';
                                        elseif(in_array($prediction, ['2/2'])) $predictionClass = 'prediction-away';
                                        elseif(in_array($prediction, ['X/X'])) $predictionClass = 'prediction-draw';
                                    @endphp
                                    <span class="prediction-badge {{ $predictionClass }}">
                                        {{ $prediction ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $confidence = $f['confidence'] ?? 0;
                                        $confidenceClass = 'confidence-low';
                                        if($confidence >= 65) $confidenceClass = 'confidence-high';
                                        elseif($confidence >= 45) $confidenceClass = 'confidence-medium';
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
                                        <div class="odds-section" style="max-height: 100px; overflow-y: auto;">
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
                <strong><i class="bi bi-graph-up"></i> HT/FT Summary</strong>
            </div>
            <div class="card-body">
                @php
                    $allFixtures = collect($grouped)->flatten(1);
                    $totalMatches = $allFixtures->count();
                    $consistentResults = $allFixtures->whereIn('prediction', ['1/1', 'X/X', '2/2'])->count();
                    $mixedResults = $totalMatches - $consistentResults;
                    $avgConfidence = $allFixtures->avg('confidence');
                    
                    $homeHome = $allFixtures->where('prediction', '1/1')->count();
                    $drawDraw = $allFixtures->where('prediction', 'X/X')->count();
                    $awayAway = $allFixtures->where('prediction', '2/2')->count();
                @endphp
                
                <div class="row text-center">
                    <div class="col-md-2">
                        <h5 class="text-primary">{{ $totalMatches }}</h5>
                        <small>Total Matches</small>
                    </div>
                    <div class="col-md-2">
                        <h5 class="text-success">{{ $homeHome }}</h5>
                        <small>1/1 Predictions</small>
                    </div>
                    <div class="col-md-2">
                        <h5 class="text-warning">{{ $drawDraw }}</h5>
                        <small>X/X Predictions</small>
                    </div>
                    <div class="col-md-2">
                        <h5 class="text-danger">{{ $awayAway }}</h5>
                        <small>2/2 Predictions</small>
                    </div>
                    <div class="col-md-2">
                        <h5 class="text-info">{{ $mixedResults }}</h5>
                        <small>Mixed Results</small>
                    </div>
                    <div class="col-md-2">
                        <h5 class="text-secondary">{{ number_format($avgConfidence, 1) }}%</h5>
                        <small>Avg Confidence</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Legend:</strong> 1/1 = Home/Home, X/X = Draw/Draw, 2/2 = Away/Away, Mixed = Different HT/FT outcomes
                    </small>
                </div>
            </div>
        </div>

    @else
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No halftime/fulltime predictions available for today.
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
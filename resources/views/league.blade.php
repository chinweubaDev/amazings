@extends('layouts.masters')

@section('title', $pageTitle)

@section('content')
@push('styles')
<style>
    .card-header img { border-radius: 4px; }
    table img { vertical-align: middle; }
    th, td { vertical-align: middle !important; }
    .prediction-green { background-color: #28a745; color: white; padding: 5px 10px; border-radius: 6px; font-weight: bold; }
    .prediction-white { background-color: white; border: 1px solid #dc3545; color: #dc3545; padding: 5px 10px; border-radius: 6px; font-weight: bold; }
    .prediction-red { background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 6px; font-weight: bold; }
    .odds-highlight { font-weight: bold; border: 2px solid #000; }

    .match-container {
        width: 100%;
        max-width: 1200px;
        margin: auto;
        font-family: Arial, sans-serif;
    }

    .league-header {
        background: #edf5ff;
        padding: 12px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: bold;
        border-top: 1px solid #ccc;
    }

    .league-header img.flag {
        width: 26px;
        height: 20px;
    }

    .standings {
        margin-left: auto;
        font-size: 13px;
        color: #0044aa;
        font-weight: 600;
        cursor: pointer;
    }

    .match-row {
        display: grid;
        grid-template-columns: 2fr 1.4fr 0.7fr 1.2fr 0.8fr 0.3fr;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid #e8e8e8;
        background: #fff;
    }

    .match-teams .team-name {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .match-date {
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }

    .team-logos {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 5px;
    }

    .team-logo {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    .odds {
        display: flex;
        gap: 8px;
    }

    .odd-box {
        padding: 6px 10px;
        border: 1px solid #aaa;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        min-width: 45px;
        text-align: center;
        background: #f8f9fa;
    }

    .odd-box.highlight {
        border-color: #28a745;
        background-color: #d4edda;
        font-weight: bold;
    }

    .avg {
        font-size: 16px;
        font-weight: 600;
        text-align: center;
    }

    .prediction {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pred-tag {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: bold;
        min-width: 30px;
        text-align: center;
    }

    .pred-tag.green { background-color: #28a745; color: white; }
    .pred-tag.yellow { background-color: #ffc107; color: #000; }
    .pred-tag.red { background-color: #dc3545; color: white; }

    .percent {
        font-size: 14px;
        font-weight: bold;
        color: #666;
    }

    .time {
        font-weight: 600;
        color: #333;
    }

    .status {
        font-weight: bold;
        font-size: 12px;
        color: #666;
    }

    /* Live match indicator */
    .live-indicator {
        background: #ff4444;
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        animation: blink 1.5s infinite;
    }

    @keyframes blink {
        0%, 50% { opacity: 1; }
        51%, 100% { opacity: 0.6; }
    }

    /* Responsive design */
    @media (max-width: 900px) {
        .match-row {
            grid-template-columns: 1fr 1fr;
            grid-row-gap: 15px;
        }

        .odds { justify-content: flex-start; }
        .avg, .prediction, .time, .status { text-align: left; }
    }

    @media (max-width: 600px) {
        .league-header { font-size: 15px; padding: 10px; }
        
        .match-row {
            grid-template-columns: 1fr;
            padding: 15px 10px;
        }

        .odds { margin-top: 8px; }
        .team-name { font-size: 15px; }
        .odd-box { padding: 5px 8px; font-size: 13px; }
    }
</style>
@endpush

@section('content')
<div class="col-lg-9 col-12">
    <div style="margin-top: 6px; margin-bottom: 3px;">
        <div class="row d-block d-lg-none" style="margin: auto; padding-top: 5px; border: 1px solid white; border-radius: 3px; background-color: white;">
            <div class="col-lg-12 col-sm-12 o-hidden">
                <div class="nav scrollable nav-fill small position-relative flex-nowrap fixturesTextSize">
                    <!-- Date navigation can be added here -->
                </div>
            </div>
            <div class="col-sm-12 datePicker" id="datePickerT">
                <div class="row custom-select">
                    <!-- Date picker can be added here -->
                </div>
            </div>
        </div>
    </div>
 <div class="col-sm-12 text-center text-nowrap sites-card mb-1" style="background-color: rgb(238, 247, 255); font-weight: bold;">
        <h1 class="h1headerTitle mb-0">T
           
                            {{ $pageTitle }}
        </h1>
    </div>
 @include('partials.topbar')

      <div class="match-container">
    <div class="row">
        <div class="col-md-12">
            
           
            @if(count($grouped) > 0)
                @foreach($grouped as $date => $group)
                    <h3>{{ $date }}</h3>
                    <div class="card mb-3">

   
                        
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
            @else
                <p>No upcoming fixtures found for this league.</p>
            @endif
        </div>
    </div>
</div>
</div>
@endsection

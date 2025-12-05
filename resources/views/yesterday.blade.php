@extends('layouts.masters')

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
        <h1 class="h1headerTitle mb-0">{{ $pageTitle ?? 'Today\'s Football Predictions and Tips' }}</h1>
    </div>

 @include('partials.topbar')
    <!-- Date Navigation -->
    <div class="d-flex justify-content-center mb-3">
        <div class="btn-group" role="group" aria-label="Date Navigation">
            <a href="{{ route('yesterday') }}" class="btn btn-outline-primary {{ request()->routeIs('yesterday') ? 'active' : '' }}">Yesterday</a>
            <a href="{{ route('home') }}" class="btn btn-outline-primary {{ request()->routeIs('home') ? 'active' : '' }}">Today</a>
            <a href="{{ route('tomorrow') }}" class="btn btn-outline-primary {{ request()->routeIs('tomorrow') ? 'active' : '' }}">Tomorrow</a>
            <a href="{{ route('weekend') }}" class="btn btn-outline-primary {{ request()->routeIs('weekend') ? 'active' : '' }}">Weekend</a>
            <a href="{{ route('upcoming') }}" class="btn btn-outline-primary {{ request()->routeIs('upcoming') ? 'active' : '' }}">Upcoming</a>
            <a href="{{ route('must.win') }}" class="btn btn-outline-danger {{ request()->routeIs('must.win') ? 'active' : '' }}">Must Win</a>
        </div>
    </div>

    <div class="match-container" data-total-leagues="{{ $totalLeagues }}" data-initial-limit="{{ $initialLimit }}">
        @if($grouped->isEmpty())
            <div class="alert alert-info text-center" style="margin: 20px; padding: 20px;">
                <h4>No matches scheduled for today</h4>
                <p>Check back later or browse other prediction pages.</p>
            </div>
        @else
            <div id="leagues-container">
                @php
                    $leagueCount = 0;
                @endphp
                @foreach($grouped as $leagueKey => $matches)
                    @if($leagueCount < $initialLimit)
                        @include('partials.league-section', [
                            'leagueKey' => $leagueKey,
                            'matches' => $matches,
                            'firstMatch' => $matches->first()
                        ])
                        @php
                            $leagueCount++;
                        @endphp
                    @endif
                @endforeach
            </div>

            @if($totalLeagues > $initialLimit)
                <div class="text-center my-4">
                    <button id="show-more-btn" class="btn btn-primary btn-lg" data-offset="{{ $initialLimit }}">
                        <span class="btn-text">Show More Leagues</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                    <div class="mt-2">
                        <small class="text-muted">Showing <span id="loaded-count">{{ $initialLimit }}</span> of <span id="total-count">{{ $totalLeagues }}</span> leagues</small>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if($grouped->isNotEmpty())
        <div class="text-center mt-4 mb-3">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Predictions are based on statistical analysis and should be used as guidance only. 
                <strong>Please bet responsibly.</strong>
            </small>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh live matches every 30 seconds
    const liveMatches = document.querySelectorAll('.live-indicator');
    if (liveMatches.length > 0) {
        setInterval(function() {
            // You can implement AJAX refresh here for live matches
            console.log('Checking for live match updates...');
        }, 30000);
    }

    // Add click handlers for team names (optional - link to team stats)
    document.querySelectorAll('.team-name').forEach(function(element) {
        element.style.cursor = 'pointer';
        element.addEventListener('click', function() {
            // Optional: Add team stats modal or redirect
            console.log('Team clicked:', this.textContent);
        });
    });

    // Show More button functionality
    const showMoreBtn = document.getElementById('show-more-btn');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            const btn = this;
            const btnText = btn.querySelector('.btn-text');
            const spinner = btn.querySelector('.spinner-border');
            const offset = parseInt(btn.dataset.offset);
            const limit = 10;

            // Get current page date from URL or use today
            const currentPath = window.location.pathname;
            let date = '{{ \Carbon\Carbon::today()->toDateString() }}';
            let mustWinOnly = false;
            let mixMarkets = false;

            // Determine date based on current route
            if (currentPath.includes('yesterday')) {
                date = '{{ \Carbon\Carbon::yesterday()->toDateString() }}';
            } else if (currentPath.includes('tomorrow')) {
                date = '{{ \Carbon\Carbon::tomorrow()->toDateString() }}';
            } else if (currentPath.includes('must-win')) {
                mustWinOnly = true;
            } else if (currentPath.includes('upcoming')) {
                mixMarkets = true;
            }

            // Show loading state
            btn.disabled = true;
            btnText.textContent = 'Loading...';
            spinner.classList.remove('d-none');

            // Make AJAX request
            fetch('{{ route("load.more.leagues") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    date: date,
                    offset: offset,
                    limit: limit,
                    mustWinOnly: mustWinOnly,
                    mixMarkets: mixMarkets
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Append new leagues to container
                    const container = document.getElementById('leagues-container');
                    container.insertAdjacentHTML('beforeend', data.html);

                    // Update offset
                    btn.dataset.offset = data.loaded;

                    // Update counter
                    document.getElementById('loaded-count').textContent = data.loaded;

                    // Hide button if no more leagues
                    if (!data.hasMore) {
                        btn.parentElement.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading more leagues:', error);
                alert('Failed to load more leagues. Please try again.');
            })
            .finally(() => {
                // Reset button state
                btn.disabled = false;
                btnText.textContent = 'Show More Leagues';
                spinner.classList.add('d-none');
            });
        });
    }
});
</script>
@endpush
```
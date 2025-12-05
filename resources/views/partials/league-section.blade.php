@php
    $countryCode = strtolower($firstMatch['country_flag'] ?? 'xx');
    // Handle country code mapping
    $flagMappings = [
        'england' => 'gb-eng',
        'scotland' => 'gb-sct',
        'wales' => 'gb-wls',
        'northern ireland' => 'gb-nir'
    ];
    $countryCode = $flagMappings[$countryCode] ?? $countryCode;
@endphp

<!-- League Header -->
<div class="league-header">
    @if($firstMatch['country_flag'])
        <img src="{{ $firstMatch['country_flag'] }}" 
             class="flag" 
             alt="{{ $firstMatch['country'] }}"
             >
    @endif
    <span class="league-title">{{ $firstMatch['country'] }} : {{ $firstMatch['league'] }}</span>
    <span class="standings" onclick="window.open('https://www.flashscore.com', '_blank')">Standings</span>
</div>

@foreach($matches as $match)
    <!-- Match Row -->
    <div class="match-row">
        <div class="match-teams">
            <div class="team-logos">
                @if($match['home_logo'])
                    <img src="{{ $match['home_logo'] }}" class="team-logo" alt="{{ $match['home_team'] }}">
                @endif
                <div class="team-name">{{ $match['home_team'] }}</div>
            </div>
            
            <div class="team-logos">
                @if($match['away_logo'])
                    <img src="{{ $match['away_logo'] }}" class="team-logo" alt="{{ $match['away_team'] }}">
                @endif
                <div class="team-name">{{ $match['away_team'] }}</div>
            </div>
            
            <div class="match-date">{{ \Carbon\Carbon::parse($match['match_date'])->format('d/m/Y') }}</div>
        </div>

        <div class="odds">
            @if($match['odds']['home'])
                <div class="odd-box {{ $match['prediction'] === '1' ? 'highlight' : '' }}">
                    {{ number_format($match['odds']['home'], 2) }}
                </div>
            @else
                <div class="odd-box">-</div>
            @endif

            @if($match['odds']['draw'])
                <div class="odd-box {{ $match['prediction'] === 'X' ? 'highlight' : '' }}">
                    {{ number_format($match['odds']['draw'], 2) }}
                </div>
            @else
                <div class="odd-box">-</div>
            @endif

            @if($match['odds']['away'])
                <div class="odd-box {{ $match['prediction'] === '2' ? 'highlight' : '' }}">
                    {{ number_format($match['odds']['away'], 2) }}
                </div>
            @else
                <div class="odd-box">-</div>
            @endif
        </div>

        <div class="avg">
            {{ $match['avg_goals'] ? number_format($match['avg_goals'], 1) : 'N/A' }}
        </div>

        <div class="prediction">
            @php
                $predClass = 'yellow'; // default
                if($match['prediction_color'] === 'green') $predClass = 'green';
                elseif($match['prediction_color'] === 'red') $predClass = 'red';
            @endphp
            <span class="pred-tag {{ $predClass }}">{{ $match['prediction'] }}</span>
            <span class="percent">{{ $match['confidence'] }}%</span>
        </div>

        <div class="time">
            @if($match['has_started'] && !$match['is_finished'])
                <span class="live-indicator">LIVE</span>
                @if($match['elapsed'])
                    <div style="font-size: 11px; margin-top: 2px;">{{ $match['elapsed'] }}'</div>
                @endif
            @elseif($match['is_finished'])
                <span style="color: #28a745; font-weight: bold;">FT</span>
                @if($match['home_score'] !== null && $match['away_score'] !== null)
                    <div style="font-size: 12px; margin-top: 2px;">
                        {{ $match['home_score'] }}-{{ $match['away_score'] }}
                    </div>
                @endif
            @else
                {{ $match['match_time'] }}
            @endif
        </div>

        <div class="status">
            @if($match['has_started'] && !$match['is_finished'])
                {{ $match['status_short'] }}
            @elseif($match['is_finished'])
                FT
            @else
                -
            @endif
        </div>
    </div>
@endforeach

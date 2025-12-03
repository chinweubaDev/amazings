<div class="" id="sidebar-wrapper">
   <div class="sideNavCustom">
      <div class="list-group list-group-flush">
         <span class="list-group-item list-group-item-action p-1 sideNavCustom1" style="background-color: rgb(53, 63, 81);"></span><a href="/tips180" class="list-group-item list-group-item-action p-1 sideNavCustom1 countryNameLink ">180 Predictions Today</a><a href="/live-predictions" class="list-group-item list-group-item-action p-1 sideNavCustom1 countryNameLink ">Live Score</a><a href="/upcoming-popular-matches" class="list-group-item list-group-item-action sideNavCustom1 p-1 countryNameLink activeElement">Upcoming Football Predictions</a><a href="/tomorrow-predictions" class="list-group-item list-group-item-action sideNavCustom1 p-1 countryNameLink ">Football Tips Tomorrow</a><a href="/weekend-football-prediction" class="list-group-item list-group-item-action sideNavCustom1 p-1 countryNameLink ">Weekend Football Predictions</a><a href="/yesterday-predictions" class="list-group-item list-group-item-action sideNavCustom1 p-1 countryNameLink ">Yesterday Football Predictions</a><a href="/victor-predict" class="list-group-item list-group-item-action sideNavCustom1 p-1 countryNameLink ">Top Predictions (Top Picks)</a><a href="/jackpot-predictions" class="list-group-item list-group-item-action sideNavCustom1 p-1 countryNameLink ">Jackpot Predictions</a><a href="/top-trends" class="list-group-item list-group-item-action sideNavCustom1 p-1 countryNameLink ">Top Trends</a>
         <div class="border-bottom" id="sidenavDynamicheader" style="background-color: rgb(53, 63, 81); color: white;">Top Leagues</div>
         <div class="responsivecell team-link">
            @foreach($topLeagues as $league)
            <div class="d-flex align-items-center countryNameLink">
               &nbsp;
               <div style="height: 10%; width: 10%; object-fit: contain;">
                   <img src="{{ $league->country_flag ?? '/images/countries/default.svg' }}" height="100%" width="100%" class="img-fluid" alt="{{ $league->country_name }}-football-predictions" loading="lazy" style="background-color: whitesmoke;">
               </div>
               <a href="{{ route('league.show', ['slug' => $league->slug]) }}" class="list-group-item list-group-item-action sideNavCustom1 border-none countryNameLink d-flex align-items-center " title="{{ $league->name }}">{{ $league->name }}</a>
            </div>
            @endforeach
         </div>
         <div class="border-bottom" id="sidenavDynamicheader" style="background-color: rgb(53, 63, 81); color: white;">Countries</div>
            @foreach($countries as $country)
            @php
                $countryId = 'Country' . $country->id . 'Menu';
                $collapseId = 'Country' . $country->id . 'Collapse';
            @endphp
            
            @if($loop->index == 10)
                <div class="more-countries-chunk" style="display: none;">
            @elseif($loop->index > 10 && ($loop->index - 10) % 10 == 0)
                </div><div class="more-countries-chunk" style="display: none;">
            @endif

            <div class="list-group list-group-flush collapsibleNav" id="{{ $countryId }}">
                <div class="d-flex align-items-center">
                    <a href="{{ route('country.show', ['slug' => $country->slug]) }}" class="list-group-item list-group-item-action sideNavCustom1 countryNameLink p-1 false" type="button" title="{{ $country->name }}" style="display: flex; justify-content: space-between;">
                        {{ $country->name }}
                    </a>
                    <span data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" role="button" aria-expanded="false" aria-controls="{{ $collapseId }}" style="margin-left: auto; cursor: pointer; color: white;">
                        <i class="bi bi-node-plus-fill" role="button" aria-label="Name" aria-labelledby="labeldiv" style="font-size: 15px;"></i>
                    </span>
                </div>
            </div>
            <div id="{{ $collapseId }}" class="collapse " aria-labelledby="{{ $countryId }}">
                <div class="card-body">
                    @foreach($country->leagues as $league)
                    <div class="d-flex align-items-left leagueNameWrapper" style="display: flex; align-items: center;">
                        <a href="{{ route('league.show', ['slug' => $league->slug]) }}" class="list-group-item ml-2 list-group-item-action sideNavCustom1 countryNameLink" title="{{ $league->name }}">
                            {{ $league->name }}
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>

            @if($loop->last && $loop->count > 10)
                </div>
            @endif
            @endforeach

            @if(count($countries) > 10)
            <button id="showMoreCountriesBtn" class="list-group-item p-1 btn btn-link" style="color: rgb(255, 0, 67); text-decoration: underline; font-weight: bold; border: none; text-align: left;" onclick="showNextChunk()">Show More &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <i class="bi bi-arrow-down-short"></i></button><br>
            @endif
      </div>
   </div>
</div>

<script>
function showNextChunk() {
    const chunks = document.querySelectorAll('.more-countries-chunk');
    let anyHidden = false;
    let foundHidden = false;

    for (let chunk of chunks) {
        if (chunk.style.display === 'none') {
            if (!foundHidden) {
                chunk.style.display = 'block';
                foundHidden = true;
            } else {
                anyHidden = true; // Still have more hidden chunks
            }
        }
    }

    if (!anyHidden) {
        document.getElementById('showMoreCountriesBtn').style.display = 'none';
    }
}
</script>
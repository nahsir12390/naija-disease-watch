<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} · Surveillance overview</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="watch-app">
    <aside class="watch-sidebar">
        <a class="watch-brand" href="{{ route('home') }}"><span class="brand-symbol"><x-app-logo-icon class="ui-brand-icon" /></span><span class="brand-name">{{ config('app.name') }}</span></a>
        <div class="workspace-label">NIGERIA HEALTH OBSERVATORY</div>
        <nav aria-label="Main navigation">
            <a class="nav-active" href="{{ route('home') }}"><span><flux:icon.squares-2x2 class="ui-icon" /></span> Overview <span class="nav-arrow"><flux:icon.arrow-up-right class="ui-icon" /></span></a>
            <a href="#trends"><span><flux:icon.arrow-trending-up class="ui-icon" /></span> Disease trends</a>
            <a href="#distribution"><span><flux:icon.map class="ui-icon" /></span> Geographic view</a>
            <a href="#forecast"><span><flux:icon.sparkles class="ui-icon" /></span> Research forecast</a>
            <a href="#records"><span><flux:icon.table-cells class="ui-icon" /></span> Data explorer</a>
            @auth @can('manage-datasets')<a href="{{ route('sources.index') }}"><span><flux:icon.arrow-path class="ui-icon" /></span> NCDC reports</a>@endcan @endauth
        </nav>
        <div class="sidebar-note"><span class="note-icon"><flux:icon.sparkles class="ui-icon" /></span><h3>A clearer picture.<br>A healthier Nigeria.</h3><p>Explore disease patterns, understand the numbers, and make data meaningful.</p><a href="#about">Our approach <span><flux:icon.arrow-up-right class="ui-icon" /></span></a></div>
        <div class="sidebar-bottom"><span class="nigeria-flag" aria-hidden="true"></span> Built for Nigeria <small>Research dashboard · v1.0</small></div>
    </aside>
    <div class="watch-workspace">
        <header class="watch-topbar"><span>Workspace <span class="breadcrumb-divider">/</span> <strong>Surveillance overview</strong></span><div><span class="demo-pill"><i></i> {{ $demo ? 'Demo workspace' : 'Published records' }}</span><a class="account-link" href="{{ route('dashboard') }}">{{ auth()->check() ? 'My workspace' : 'Sign in' }} <flux:icon.arrow-up-right class="ui-icon" /></a></div></header>
        <main class="watch-main">
            <div class="page-intro"><div><div class="eyebrow">PUBLIC HEALTH, IN PERSPECTIVE</div><h1>A pulse on Nigeria’s health<span>.</span></h1><p>Explore infectious disease patterns. Turn information into understanding.</p></div><a class="export-button" href="{{ route('home', [...$selection, 'export' => 'csv']) }}"><flux:icon.arrow-down-tray class="ui-icon" /> <span>Export data</span></a></div>
            <div class="demo-banner"><span><flux:icon.information-circle class="ui-icon" /></span><p>@if($demo)<strong>You’re exploring demonstration data.</strong> These synthetic records illustrate the dashboard and do not represent actual disease counts.@else<strong>Latest published data · {{ ucfirst($caseType) }} cases · {{ $coverage === 'national' ? 'National totals' : 'State records' }}.</strong> Missing periods and blank outcomes mean “not reported”, not zero. Source publication may lag behind today.@endif</p><a href="#about">Learn more <flux:icon.arrow-up-right class="ui-icon" /></a></div>
            <form class="watch-filters" action="{{ route('home') }}" method="GET">
                <label>Dataset<select name="mode"><option value="demo" @selected($demo)>Demonstration</option><option value="imported" @selected(!$demo)>Published records</option></select></label>
                <label>Disease<select name="disease"><option value="">All diseases</option>@foreach(\App\Services\SurveillanceData::DISEASES as $option)<option @selected($disease === $option)>{{ $option }}</option>@endforeach</select></label>
                <label>Reporting year<select name="year">@foreach(array_unique([...$years, $year]) as $option)<option @selected($year === $option) value="{{ $option }}">{{ $option }}</option>@endforeach</select></label>
                @if(!$demo)
                    <label>Reporting period<select name="frequency"><option value="weekly" @selected($frequency === 'weekly')>Weekly</option><option value="monthly" @selected($frequency === 'monthly')>Monthly</option><option value="fortnightly" @selected($frequency === 'fortnightly')>Two-week periods</option></select></label>
                    <label>Case classification<select name="case_type">@foreach(\App\Services\DiseaseReportRules::CASE_TYPES as $option)<option value="{{ $option }}" @selected($caseType === $option)>{{ ucfirst($option) }}</option>@endforeach</select></label>
                    <label>Geographic level<select name="coverage"><option value="states" @selected($coverage === 'states')>State records</option><option value="national" @selected($coverage === 'national')>National totals</option></select></label>
                @endif
                <label>State<select name="state"><option value="">{{ $coverage === 'national' ? 'National total selected' : 'All available states' }}</option>@foreach(\App\Services\SurveillanceData::STATES as $option)<option @selected($state === $option)>{{ $option }}</option>@endforeach</select></label>
                <button type="submit">Apply filters <flux:icon.arrow-up-right class="ui-icon" /></button><a class="reset-link" href="{{ route('home') }}">Reset</a>
            </form>
            @php
                $total = $records->sum('cases');
                $deathCount = $records->whereNotNull('deaths')->count();
                $recoveryCount = $records->whereNotNull('recoveries')->count();
                $metrics = [
                    ['Reported cases', number_format($total), 'Period-specific counts · '.ucfirst($caseType), 'chart-bar', 'green'],
                    ['Reported deaths', $deathCount ? number_format($records->sum('deaths')) : '—', 'Available in '.$deathCount.' of '.$records->count().' records', 'heart', 'red'],
                    ['Reported recoveries', $recoveryCount ? number_format($records->sum('recoveries')) : '—', 'Available in '.$recoveryCount.' of '.$records->count().' records', 'shield-check', 'blue'],
                    ['Locations represented', number_format($states->count()), $coverage === 'national' ? 'Nigeria · national totals only' : ($state ?: 'States with matching records'), 'map-pin', 'gold'],
                ];
            @endphp
            <section class="metric-grid" aria-label="Summary statistics">@foreach($metrics as [$label, $value, $description, $icon, $color])<article class="metric-card"><div class="metric-label">{{ $label }}<span class="metric-icon {{ $color }}"><flux:icon :name="$icon" class="ui-icon" /></span></div><div class="metric-value">{{ $value }}</div><div class="metric-description">{{ $description }}</div></article>@endforeach</section>
            <section class="chart-grid" id="trends">
                <article class="watch-panel trend-panel"><div class="panel-heading"><div><div class="eyebrow">THE BIG PICTURE</div><h2>Disease trends over time</h2><p>{{ ucfirst($frequency) }} reported cases · {{ $year }} · Gaps indicate unavailable data</p></div><span class="legend"><i></i> {{ $disease ?: 'All diseases' }}</span></div><x-surveillance-trend :series="$monthly" :frequency="$frequency" :labels="$periodLabels" /></article>
                <article class="watch-panel burden-panel"><div class="panel-heading"><div><div class="eyebrow">BY DISEASE</div><h2>Share of reported cases</h2><p>Counts across available records, not population rates</p></div></div>
                    @php($colors = ['#176b50', '#e6ae64', '#93b2a4']) @php($offset = 0)
                    <div class="donut-chart"><svg viewBox="0 0 200 200" role="img" aria-label="Disease case shares listed below"><circle cx="100" cy="100" r="74" fill="none" stroke="#edf1ee" stroke-width="22"/>@foreach($diseases as $name => $value)<circle cx="100" cy="100" r="74" fill="none" stroke="{{ $colors[$loop->index] }}" stroke-width="22" stroke-dasharray="{{ $value / max(1, $total) * 465 }} 465" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 100 100)"/>@php($offset += $value / max(1, $total) * 465)@endforeach<text x="100" y="98" text-anchor="middle" class="donut-total">{{ $total >= 1000 ? number_format($total / 1000, 1).'k' : $total }}</text><text x="100" y="119" text-anchor="middle" class="donut-label">total cases</text></svg></div>
                    <div class="disease-legend">@forelse($diseases as $name => $value)<div><span><i style="background:{{ $colors[$loop->index] }}"></i>{{ $name }}</span><strong>{{ number_format($value / max(1, $total) * 100, 1) }}%</strong></div>@empty<p>No matching records.</p>@endforelse</div>
                </article>
            </section>
            <section class="lower-grid" id="distribution">
                <article class="watch-panel"><div class="panel-heading"><div><div class="eyebrow">GEOGRAPHIC SNAPSHOT</div><h2>Cases across Nigeria</h2><p>Choose a state on the map · Gray means no matching record</p></div></div>
                    @if($coverage === 'national')<p class="geography-note">National figures do not provide a state breakdown. Select “State records” to explore geographic coverage.</p>@endif
                    <svg class="nigeria-map" viewBox="0 0 580 460" role="img" aria-label="Nigeria state map. Each state links to its filtered data.">
                        @foreach($map['regions'] as $region)
                            @php($value = $coverage === 'states' ? $states->get($region['name']) : null)
                            @php($intensity = $value === null ? 0 : ($value / max(1, $states->max())))
                            <a href="{{ route('home', [...$selection, 'coverage' => 'states', 'state' => $region['name']]) }}" aria-label="{{ $region['name'] }}: {{ $value === null ? 'no record' : number_format($value).' cases' }}"><path d="{{ $region['path'] }}" fill="{{ $value === null ? '#e8ece8' : ($intensity > .7 ? '#256f50' : ($intensity > .35 ? '#73a28a' : '#bbd2c1')) }}" stroke="#fff" stroke-width="1"><title>{{ $region['name'] }}: {{ $value === null ? 'No record' : number_format($value).' cases' }}</title></path></a>
                        @endforeach
                    </svg>
                    <p class="map-attribution">Boundaries: <a href="{{ $map['source'] }}" target="_blank" rel="noopener noreferrer">geoBoundaries / GRID3</a> · {{ $map['license'] }}. Colors show case counts, not population-adjusted risk.</p>
                </article>
                <article class="watch-panel"><div class="panel-heading"><div><div class="eyebrow">LOCATION COMPARISON</div><h2>Where cases are reported</h2><p>Top available locations · {{ $demo ? 'demonstration data' : 'published records' }}</p></div><span class="subtle-tag">Top {{ min(8, $states->count()) }}</span></div><div class="state-bars">@forelse($states->take(8) as $name => $value)<div class="state-row"><span>{{ $name }}</span><div class="bar-track"><div style="width:{{ $value / max(1, $states->max()) * 100 }}%"></div></div><strong>{{ number_format($value) }}</strong></div>@empty<p class="geography-note">No matching locations. Change the filters or import records.</p>@endforelse</div></article>
            </section>
            <section class="lower-grid">
                <article class="watch-panel" id="forecast"><div class="panel-heading"><div><div class="eyebrow">EXPLORATORY ANALYTICS</div><h2>Next-period research baseline</h2><p>Four-period moving average · no outbreak alerts</p></div></div>
                    @if($forecast)<div class="forecast-value">{{ number_format($forecast['estimate']) }} <small>cases in the next reporting period</small></div><p class="forecast-copy">Based on the four latest consecutive observations for {{ $disease }} in {{ $state ?: 'Nigeria' }}. Last observed period: {{ $periodLabels[$forecast['last_period']] }} of {{ $year }}.</p><p class="forecast-copy">Rolling one-step validation: mean absolute error {{ $forecast['mae'] }} cases across four historical predictions. This simple baseline does not model seasonality or interventions and is not a validated outbreak prediction.</p>@else<p class="forecast-copy">Select one disease and one state (or national totals). A baseline appears when at least eight consecutive reporting periods are available. Missing observations are never replaced with zero.</p>@endif
                    @if($demo)<span class="subtle-tag">Synthetic data · method demonstration only</span>@endif
                </article>
                <article class="insight-panel" id="about"><div class="insight-top"><span><x-app-logo-icon class="ui-brand-icon" /></span> UNDERSTAND THE DATA</div><h2>Good decisions start<br>with good context.</h2><p>{{ $demo ? 'This research prototype uses synthetic monthly records. Switch to published records for administrator-reviewed data.' : 'Records are entered or imported by administrators. NCDC reports can be discovered automatically and reviewed before publication.' }}</p><p>Weekly, two-week and monthly records, national and state totals, and suspected and confirmed cases are shown separately. Reporting coverage can vary. Check the source before comparing periods.</p><div class="insight-footer"><span class="status-dot"></span><div>Data updated: {{ $lastUpdated ? \Illuminate\Support\Carbon::parse($lastUpdated)->timezone('Africa/Lagos')->format('j M Y, g:i A').' WAT' : 'No published records yet' }}<br>NCDC last checked: {{ $lastChecked ? \Illuminate\Support\Carbon::parse($lastChecked)->timezone('Africa/Lagos')->format('j M Y, g:i A').' WAT' : 'Not checked yet' }}</div></div></article>
            </section>
            <section class="watch-panel records-panel" id="records"><div class="panel-heading"><div><div class="eyebrow">LOOK A LITTLE CLOSER</div><h2>Data explorer</h2><p>First 20 of {{ number_format($records->count()) }} matching records. Download the full selection as CSV.</p></div><a class="text-link" href="{{ route('home', [...$selection, 'export' => 'csv']) }}">Download CSV <flux:icon.arrow-up-right class="ui-icon" /></a></div><div class="table-scroll"><table><thead><tr><th>Location</th><th>Disease</th><th>Period</th><th>Cases</th><th>Deaths</th><th>Recoveries</th>@if(!$demo)<th>Source</th>@endif</tr></thead><tbody>@forelse($records->take(20) as $record)<tr><td><strong>{{ $record['state'] }}</strong></td><td><span class="disease-tag">{{ $record['disease'] }}</span></td><td>{{ $record['week'] ? 'Week '.$record['week'].($record['week_end'] ? '–'.$record['week_end'] : '') : date('M', mktime(0, 0, 0, $record['month'], 1)) }} {{ $year }}</td><td>{{ number_format($record['cases']) }}</td><td>{{ $record['deaths'] ?? 'Not reported' }}</td><td>{{ $record['recoveries'] ?? 'Not reported' }}</td>@if(!$demo)<td><a href="{{ $record['source_url'] }}" target="_blank" rel="noopener noreferrer">{{ $record['source'] }} <flux:icon.arrow-up-right class="ui-icon" /></a></td>@endif</tr>@empty<tr><td colspan="7">No matching records. Change the filters or ask an administrator to import data.</td></tr>@endforelse</tbody></table></div></section>
            <footer class="watch-footer"><span>© {{ date('Y') }} {{ config('app.name') }}</span><span>Making health data easier to understand. <span class="footer-mark"><x-app-logo-icon class="ui-brand-icon" /></span></span></footer>
        </main>
    </div>
</body>
</html>

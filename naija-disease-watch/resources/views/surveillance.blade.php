<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Naija Disease Watch · Surveillance overview</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="watch-app">
    <aside class="watch-sidebar">
        <a class="watch-brand" href="{{ route('home') }}"><span class="brand-symbol">✳</span><span>Naija<span class="brand-light">Disease Watch</span></span></a>
        <div class="workspace-label">NIGERIA HEALTH OBSERVATORY</div>
        <nav aria-label="Main navigation">
            <a class="nav-active" href="{{ route('home') }}"><span>▦</span> Overview <span class="nav-arrow">↗</span></a>
            <a href="#trends"><span>↗</span> Disease trends</a>
            <a href="#distribution"><span>◉</span> State comparison</a>
            <a href="#records"><span>▤</span> Data explorer</a>
            <a href="#about"><span>ⓘ</span> About the data</a>
        </nav>
        <div class="sidebar-note"><span class="note-icon">✧</span><h3>A clearer picture.<br>A healthier Nigeria.</h3><p>Explore disease patterns, understand the numbers, and make data meaningful.</p><a href="#about">Our approach <span>↗</span></a></div>
        <div class="sidebar-bottom"><span class="nigeria-flag">▌▐</span> Built for Nigeria <small>Research dashboard · v0.1</small></div>
    </aside>
    <div class="watch-workspace">
        <header class="watch-topbar"><span>Workspace <span class="breadcrumb-divider">/</span> <strong>Surveillance overview</strong></span><div><span class="demo-pill"><i></i> {{ $demo ? 'Demo workspace' : 'Imported records' }}</span><a class="account-link" href="{{ route('dashboard') }}">My account ↗</a></div></header>
        <main class="watch-main">
            <div class="page-intro"><div><div class="eyebrow">PUBLIC HEALTH, IN PERSPECTIVE</div><h1>A pulse on Nigeria’s health<span>.</span></h1><p>Explore infectious disease patterns. Turn information into understanding.</p></div><a class="export-button" href="{{ route('home', array_merge(request()->only(['state', 'disease', 'year', 'mode']), ['export' => 'csv'])) }}">↓ <span>Export data</span></a></div>
            @if($demo)<div class="demo-banner"><span>ⓘ</span><p><strong>You’re exploring demonstration data.</strong> These synthetic records illustrate the dashboard and do not represent actual disease counts.</p><a href="#about">Learn more ↗</a></div>@else<div class="demo-banner"><span>ⓘ</span><p><strong>Administrator-imported records.</strong> Coverage depends on the uploaded datasets. Source details are supplied by the uploader; missing months are not confirmed zero-case months.</p></div>@endif
            <form class="watch-filters" action="{{ route('home') }}" method="GET">
                <label>Dataset<select name="mode"><option value="demo" @selected($demo)>Demonstration</option><option value="imported" @selected(!$demo)>Imported records</option></select></label>
                <label>Disease<select name="disease"><option value="">All diseases</option>@foreach(\App\Services\SurveillanceData::DISEASES as $option)<option @selected($disease === $option)>{{ $option }}</option>@endforeach</select></label>
                <label>Location<select name="state"><option value="">All Nigeria</option>@foreach(\App\Services\SurveillanceData::STATES as $option)<option @selected($state === $option)>{{ $option }}</option>@endforeach</select></label>
                <label>Reporting period<select name="year">@foreach(array_unique([...$years, $year]) as $option)<option @selected($year === $option) value="{{ $option }}">January – December {{ $option }}</option>@endforeach</select></label>
                <button type="submit">Apply filters ↗</button><a class="reset-link" href="{{ route('home') }}">Reset</a>
            </form>
            @php($total = $records->sum('cases'))
            <section class="metric-grid" aria-label="Summary statistics">
                @foreach([['Total reported cases', $total, 'Across available monthly records', '◈', 'green'], ['Reported deaths', $records->sum('deaths'), 'Included in reported cases', '♡', 'red'], ['Reported recoveries', $records->sum('recoveries'), 'Included in reported cases', '↗', 'blue'], ['Locations represented', $states->count(), $state ?: 'Locations with matching records', '◎', 'gold']] as [$label, $value, $description, $icon, $color])
                    <article class="metric-card"><div class="metric-label">{{ $label }}<span class="metric-icon {{ $color }}">{{ $icon }}</span></div><div class="metric-value">{{ number_format($value) }}<span class="metric-unit">{{ $loop->last ? 'locations' : 'people' }}</span></div><div class="metric-description">{{ $description }}</div></article>
                @endforeach
            </section>
            <section class="chart-grid" id="trends">
                <article class="watch-panel trend-panel"><div class="panel-heading"><div><div class="eyebrow">THE BIG PICTURE</div><h2>Disease trends over time</h2><p>Monthly reported cases · {{ $year }}</p></div><span class="legend"><i></i> {{ $disease ?: 'All diseases' }}</span></div>
                    @php($max = max(1, $monthly->max()))
                    @php($points = $monthly->filter(fn ($value) => $value !== null)->map(fn ($value, $month) => (45 + ($month - 1) * 55).','. (190 - $value / $max * 150))->implode(' '))
                    <div class="trend-chart"><svg viewBox="0 0 700 240" role="img" aria-label="Monthly case totals for {{ $year }}. Exact totals are listed below."><defs><linearGradient id="trend-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#13966f" stop-opacity=".2"/><stop offset="100%" stop-color="#13966f" stop-opacity="0"/></linearGradient></defs>@foreach([40,90,140,190] as $line)<line x1="45" x2="660" y1="{{ $line }}" y2="{{ $line }}" stroke="#e9eeeb" stroke-dasharray="4 5"/><text x="0" y="{{ $line + 4 }}" fill="#8b9891" font-size="10">{{ number_format((190 - $line) / 150 * $max / 1000, 1) }}k</text>@endforeach@if($monthly->filter(fn ($value) => $value !== null)->count() === 12)<polygon points="45,195 {{ $points }} 650,195" fill="url(#trend-fill)"/>@endif
                    @foreach($monthly as $month => $value)@if($month > 1 && $value !== null && $monthly[$month - 1] !== null)<line x1="{{ 45 + ($month - 2) * 55 }}" y1="{{ 190 - $monthly[$month - 1] / $max * 150 }}" x2="{{ 45 + ($month - 1) * 55 }}" y2="{{ 190 - $value / $max * 150 }}" stroke="#168366" stroke-width="3"/>@endif @endforeach@foreach($monthly as $month => $value)@if($value !== null)<circle cx="{{ 45 + ($month - 1) * 55 }}" cy="{{ 190 - $value / $max * 150 }}" r="4" fill="#fff" stroke="#168366" stroke-width="2"><title>{{ date('F', mktime(0,0,0,$month,1)) }}: {{ number_format($value) }} cases</title></circle>@endif<text x="{{ 45 + ($month - 1) * 55 }}" y="225" text-anchor="middle" fill="#84918b" font-size="11">{{ date('M', mktime(0,0,0,$month,1)) }}</text>@endforeach</svg></div>
                    <details class="chart-details"><summary>View monthly totals</summary><div class="monthly-totals">@foreach($monthly as $month => $value)<span>{{ date('M', mktime(0,0,0,$month,1)) }} <strong>{{ $value === null ? 'No record' : number_format($value) }}</strong></span>@endforeach</div></details>
                </article>
                <article class="watch-panel burden-panel"><div class="panel-heading"><div><div class="eyebrow">BY DISEASE</div><h2>Share of reported cases</h2><p>A view of the selected disease burden</p></div></div>
                    @php($colors = ['#176b50', '#e6ae64', '#93b2a4'])
                    @php($offset = 0)
                    <div class="donut-chart"><svg viewBox="0 0 200 200" role="img" aria-label="Disease shares listed below"><circle cx="100" cy="100" r="74" fill="none" stroke="#edf1ee" stroke-width="22"/>@foreach($diseases as $name => $value)<circle cx="100" cy="100" r="74" fill="none" stroke="{{ $colors[$loop->index] }}" stroke-width="22" stroke-dasharray="{{ $value / max(1,$total) * 465 }} 465" stroke-dashoffset="{{ -$offset }}" transform="rotate(-90 100 100)"/>@php($offset += $value / max(1,$total) * 465)@endforeach<text x="100" y="98" text-anchor="middle" class="donut-total">{{ number_format($total / 1000, 1) }}k</text><text x="100" y="119" text-anchor="middle" class="donut-label">total cases</text></svg></div>
                    <div class="disease-legend">@foreach($diseases as $name => $value)<div><span><i style="background:{{ $colors[$loop->index] }}"></i>{{ $name }}</span><strong>{{ number_format($value / max(1,$total) * 100, 1) }}%</strong></div>@endforeach</div>
                </article>
            </section>
            <section class="lower-grid">
                <article class="watch-panel" id="distribution"><div class="panel-heading"><div><div class="eyebrow">GEOGRAPHIC SNAPSHOT</div><h2>Where cases are reported</h2><p>Top locations by reported cases · {{ $demo ? 'demonstration data' : 'imported records' }}</p></div><span class="subtle-tag">Top {{ min(6, $states->count()) }}</span></div><div class="state-bars">@foreach($states->take(6) as $name => $value)<div class="state-row"><span>{{ $name }}</span><div class="bar-track"><div style="width:{{ $value / max(1, $states->max()) * 100 }}%"></div></div><strong>{{ number_format($value) }}</strong></div>@endforeach</div></article>
                <article class="insight-panel" id="about"><div class="insight-top"><span>✳</span> UNDERSTAND THE DATA</div><h2>Good decisions start<br>with good context.</h2>@if($demo)<p>This research prototype uses synthetic monthly records for Cholera, Lassa fever and COVID-19 across Nigeria in 2022–2023.</p><p>Filters and exports work with these sample records. Switch to imported records to explore administrator-uploaded datasets.</p>@else<p>This view uses records imported by an administrator. Each monthly record includes the source name and report link supplied during upload.</p><p>Missing records do not indicate an absence of disease. Compare coverage before drawing conclusions. Geographic mapping and forecasting are not yet available.</p>@endif<div class="insight-footer"><span class="status-dot"></span> {{ $demo ? 'Demonstration only' : 'Imported records' }} · No live surveillance feed</div></article>
            </section>
            <section class="watch-panel records-panel" id="records"><div class="panel-heading"><div><div class="eyebrow">LOOK A LITTLE CLOSER</div><h2>Data explorer</h2><p>First 12 of {{ number_format($records->count()) }} matching monthly records. Export for the full selection.</p></div><a class="text-link" href="{{ route('home', array_merge(request()->only(['state', 'disease', 'year', 'mode']), ['export' => 'csv'])) }}">Download CSV ↗</a></div><div class="table-scroll"><table><thead><tr><th>Location</th><th>Disease</th><th>Period</th><th>Cases</th><th>Deaths</th><th>Recoveries</th>@if(!$demo)<th>Source</th>@endif</tr></thead><tbody>@foreach($records->take(12) as $record)<tr><td><strong>{{ $record['state'] }}</strong></td><td><span class="disease-tag">{{ $record['disease'] }}</span></td><td>{{ date('M', mktime(0,0,0,$record['month'],1)) }} {{ $year }}</td><td>{{ number_format($record['cases']) }}</td><td>{{ $record['deaths'] }}</td><td>{{ $record['recoveries'] }}</td>@if(!$demo)<td><a href="{{ $record['source_url'] }}" target="_blank" rel="noopener noreferrer">{{ $record['source'] }} ↗</a></td>@endif</tr>@endforeach@if($records->isEmpty())<tr><td colspan="7">No matching records. Change the filters or ask an administrator to import data.</td></tr>@endif</tbody></table></div></section>
            <footer class="watch-footer"><span>© {{ date('Y') }} Naija Disease Watch</span><span>Making health data easier to understand. <span class="footer-mark">✳</span></span></footer>
        </main>
    </div>
</body>
</html>

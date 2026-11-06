@props(['series', 'frequency', 'labels'])
@php
    $count = $series->count();
    $maximum = max(1, $series->max());
    $step = 610 / max(1, $count - 1);
@endphp
<div class="trend-chart">
    <svg viewBox="0 0 700 240" role="img" aria-label="Reported cases by {{ $frequency === 'fortnightly' ? 'two-week period' : ($frequency === 'weekly' ? 'epidemiological week' : 'month') }}. Exact figures are available below.">
        @foreach([40, 90, 140, 190] as $line)
            <line x1="45" x2="660" y1="{{ $line }}" y2="{{ $line }}" stroke="#e9eeeb" stroke-dasharray="4 5" />
            <text x="0" y="{{ $line + 4 }}" fill="#8b9891" font-size="10">{{ number_format((190 - $line) / 150 * $maximum, 0) }}</text>
        @endforeach
        @foreach($series as $period => $value)
            @if($period > 1 && $value !== null && $series[$period - 1] !== null)
                <line x1="{{ 45 + ($period - 2) * $step }}" y1="{{ 190 - $series[$period - 1] / $maximum * 150 }}" x2="{{ 45 + ($period - 1) * $step }}" y2="{{ 190 - $value / $maximum * 150 }}" stroke="#168366" stroke-width="3" />
            @endif
            @if($value !== null)
                <circle cx="{{ 45 + ($period - 1) * $step }}" cy="{{ 190 - $value / $maximum * 150 }}" r="{{ $frequency === 'monthly' ? 4 : 2.5 }}" fill="#fff" stroke="#168366" stroke-width="2"><title>{{ $labels[$period] }}: {{ number_format($value) }} cases</title></circle>
            @endif
            @if($frequency === 'monthly' || $period === 1 || $period % ($frequency === 'fortnightly' ? 2 : 4) === 0 || $period === $count)
                <text x="{{ 45 + ($period - 1) * $step }}" y="225" text-anchor="middle" fill="#84918b" font-size="10">{{ $labels[$period] }}</text>
            @endif
        @endforeach
    </svg>
</div>
<details class="chart-details"><summary>View exact period totals</summary><div class="monthly-totals">@foreach($series as $period => $value)<span>{{ $labels[$period] }} <strong>{{ $value === null ? 'No record' : number_format($value) }}</strong></span>@endforeach</div></details>

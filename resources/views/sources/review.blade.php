<x-layouts::app title="Review NCDC report">
    <div class="mx-auto w-full max-w-6xl space-y-6">
        <div><a class="text-sm underline" href="{{ route('sources.index') }}"><flux:icon.arrow-left class="ui-icon" /> NCDC reports</a><flux:heading size="xl" class="mt-4">{{ $report->disease }} · Report review</flux:heading><flux:text class="mt-2">{{ $report->title }}</flux:text></div>
        <x-admin-feedback />
        <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><div class="flex flex-wrap items-center gap-4"><a class="text-sm underline" href="{{ $report->url }}" target="_blank" rel="noopener noreferrer">Open original NCDC PDF <flux:icon.arrow-up-right class="ui-icon" /></a>@if($report->status === 'pending')<form method="POST" action="{{ route('sources.extract', $report) }}">@csrf<flux:button size="sm" type="submit">Extract text &amp; suggest figures</flux:button></form>@endif<span class="text-xs text-zinc-500">Status: {{ $report->status }}</span></div>@if($report->extraction_error)<p class="mt-4 text-sm text-amber-700">{{ $report->extraction_error }}</p>@endif
            @if($report->extracted_text)<details class="mt-5"><summary class="cursor-pointer text-sm">Read extracted PDF text (first two pages)</summary><pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-zinc-100 p-4 text-xs whitespace-pre-wrap text-zinc-800">{{ $report->extracted_text }}</pre></details>@endif
        </section>
        @if($report->status === 'pending')
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:heading>Publish reviewed period records</flux:heading><flux:text class="my-3">Use current-period counts, not cumulative totals. For a two-week report, enter both the start and end weeks; never split a combined count across weeks. Keep suspected and confirmed cases separate. Choose “Nigeria” only for a national total. Leave deaths or recoveries blank when the source does not report them for that case classification.</flux:text>
                @if($report->suggestions)<p class="my-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Suggested national confirmed figures were extracted from the current-week table. Verify them against the PDF before publishing.</p>@endif
                @php($defaults = [['state' => 'Nigeria', 'case_type' => $report->suggestions['case_type'] ?? 'confirmed', 'year' => $report->year, 'week' => $report->week, 'week_end' => $report->week_end ?? 0, 'cases' => $report->suggestions['cases'] ?? '', 'deaths' => $report->suggestions['deaths'] ?? '', 'recoveries' => '']])
                <form method="POST" action="{{ route('sources.publish', $report) }}" class="space-y-5" data-review-form>
                    @csrf
                    <div data-review-rows>@foreach(old('records', $defaults) as $index => $row)<div class="review-record my-4 grid gap-4 rounded-lg border border-zinc-200 p-4 md:grid-cols-4 dark:border-zinc-700" data-review-row>
                        <label class="text-xs">Location<select name="records[{{ $index }}][state]" class="mt-2 block w-full rounded border p-2 dark:bg-zinc-900" required>@foreach(['Nigeria', ...\App\Services\SurveillanceData::STATES] as $state)<option value="{{ $state }}" @selected(($row['state'] ?? '') === $state)>{{ $state }}</option>@endforeach</select></label>
                        <label class="text-xs">Case classification<select name="records[{{ $index }}][case_type]" class="mt-2 block w-full rounded border p-2 dark:bg-zinc-900" required>@foreach(\App\Services\DiseaseReportRules::CASE_TYPES as $type)<option value="{{ $type }}" @selected(($row['case_type'] ?? '') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></label>
                        @foreach(['year' => 'Year', 'week' => 'Start / single epi week', 'week_end' => 'End week (0 for single week)', 'cases' => 'Cases', 'deaths' => 'Deaths (optional)', 'recoveries' => 'Recoveries (optional)'] as $field => $label)<label class="text-xs">{{ $label }}<input class="mt-2 block w-full rounded border p-2 dark:bg-zinc-900" type="number" name="records[{{ $index }}][{{ $field }}]" value="{{ $row[$field] ?? '' }}" min="{{ in_array($field, ['year', 'week']) ? ($field === 'year' ? 2000 : 1) : 0 }}" @if(in_array($field, ['year', 'week', 'cases'])) required @endif></label>@endforeach
                        <button class="self-end rounded border p-2 text-xs" type="button" data-remove-row>Remove row</button>
                    </div>@endforeach</div>
                    <flux:button type="button" data-add-review-row>Add another location or case classification</flux:button>
                    <label class="flex items-start gap-3 text-sm"><input type="checkbox" name="confirmed_review" value="1" required class="mt-1"><span>I checked these figures, case classifications and reporting periods against the original NCDC PDF.</span></label>
                    <flux:button type="submit" variant="primary">Publish reviewed records</flux:button>
                </form>
            </section>
            <form method="POST" action="{{ route('sources.dismiss', $report) }}">@csrf<flux:button type="submit">Dismiss this report without publishing</flux:button></form>
        @else
            <flux:text>This report has already been reviewed. Published figures can be corrected in Manage datasets.</flux:text>
        @endif
    </div>
</x-layouts::app>

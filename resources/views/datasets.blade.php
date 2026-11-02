<x-layouts::app title="Manage datasets">
    <div class="mx-auto w-full max-w-5xl space-y-7">
        <div><flux:heading size="xl">Manage disease records</flux:heading><flux:text class="mt-2">Import monthly records and make source-attributed data available on the public dashboard.</flux:text></div>
        @if(session('status'))<div role="status" class="rounded-xl bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading>Import a CSV dataset</flux:heading>
            <flux:text class="mt-2">One row per state, disease and calendar month. Maximum 5,000 rows and 2 MB. Matching months replace existing records; other records remain. The entire file must pass validation before any changes are saved.</flux:text>
            <a class="my-4 inline-block text-sm underline" href="{{ route('datasets.template') }}">Download empty CSV template ↓</a>
            <form method="POST" action="{{ route('datasets.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <flux:input name="source" label="Source name" placeholder="Name of the published report" :value="old('source')" required maxlength="255" />
                <flux:input name="source_url" type="url" label="Source report URL" placeholder="https://…" :value="old('source_url')" required maxlength="2048" />
                <div><label for="dataset-file" class="mb-2 block text-sm">CSV file</label><input id="dataset-file" class="block w-full rounded-lg border p-3 text-sm" name="file" type="file" accept=".csv,text/csv,text/plain" required></div>
                <flux:text>Supported diseases: Cholera, Lassa fever, COVID-19. Use the exact state names shown in the public dashboard and numeric months (1–12). Upload monthly counts, not cumulative totals.</flux:text>
                <flux:button type="submit" variant="primary">Validate and import</flux:button>
            </form>
        </section>
        <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <div class="mb-5 flex items-center justify-between"><flux:heading>{{ number_format($recordCount) }} saved monthly records</flux:heading><a class="text-sm underline" href="{{ route('home', ['mode' => 'imported']) }}">View public dashboard ↗</a></div>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-3">State / disease</th><th class="p-3">Period</th><th class="p-3">Cases</th><th class="p-3">Source</th></tr></thead><tbody>@forelse($reports as $report)<tr class="border-b border-zinc-100 dark:border-zinc-700"><td class="p-3">{{ $report->state }}<div class="text-xs text-zinc-500">{{ $report->disease }}</div></td><td class="p-3">{{ $report->year }}-{{ str_pad($report->month, 2, '0', STR_PAD_LEFT) }}</td><td class="p-3">{{ number_format($report->cases) }}</td><td class="p-3"><a class="underline" href="{{ $report->source_url }}" target="_blank" rel="noopener noreferrer">{{ $report->source }}</a></td></tr>@empty<tr><td class="p-6 text-zinc-500" colspan="4">No records imported yet. Download the template to prepare your first dataset.</td></tr>@endforelse</tbody></table></div>
            <div class="mt-5">{{ $reports->links() }}</div>
        </section>
    </div>
</x-layouts::app>

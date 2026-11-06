<x-layouts::app title="Your workspace">
    <div class="mx-auto w-full max-w-4xl space-y-6">
        <div><flux:heading size="xl">Welcome, {{ auth()->user()->name }}</flux:heading><flux:text class="mt-2">Your {{ config('app.name') }} workspace.</flux:text></div>
        <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Explore Nigeria’s disease data</flux:heading><flux:text class="my-4">View trends, compare states and download the selected records.</flux:text><flux:button :href="route('home')" variant="primary">Open public dashboard <flux:icon.arrow-up-right class="ui-icon" /></flux:button></section>
        @can('manage-datasets')
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Dataset administration</flux:heading><flux:text class="my-4">Upload source-attributed monthly CSV records and review saved data.</flux:text><flux:button :href="route('datasets.index')">Manage datasets</flux:button></section>
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Official NCDC reports</flux:heading><flux:text class="my-4">Check for new reports, review their figures and publish weekly data.</flux:text><flux:button :href="route('sources.index')">Review NCDC reports</flux:button></section>
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Users and project settings</flux:heading><flux:text class="my-4">Manage administrator access, your application name and automatic checks.</flux:text><flux:button :href="route('administration.index')">Administration</flux:button></section>
        @else
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Researcher access</flux:heading><flux:text class="mt-3">You can explore and export public data. Dataset changes require administrator access granted by the project owner.</flux:text></section>
            @if(app()->environment('local') && in_array(request()->ip(), ['127.0.0.1', '::1'], true) && !\App\Models\User::where('is_admin', true)->exists())
                <section class="rounded-xl border border-green-200 bg-green-50 p-6 text-green-900"><h2 class="text-lg font-semibold">Set up your administrator account</h2><p class="my-3 text-sm">No administrator has been configured. Use your signed-in account to manage this project.</p><form method="POST" action="{{ route('administration.claim') }}">@csrf<flux:button type="submit" variant="primary">Make this my administrator account</flux:button></form></section>
            @endif
        @endcan
    </div>
</x-layouts::app>

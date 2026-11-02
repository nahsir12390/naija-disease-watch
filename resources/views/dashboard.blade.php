<x-layouts::app title="Your workspace">
    <div class="mx-auto w-full max-w-4xl space-y-6">
        <div><flux:heading size="xl">Welcome, {{ auth()->user()->name }}</flux:heading><flux:text class="mt-2">Your Naija Disease Watch workspace.</flux:text></div>
        <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Explore Nigeria’s disease data</flux:heading><flux:text class="my-4">View trends, compare states and download the selected records.</flux:text><flux:button :href="route('home')" variant="primary">Open public dashboard ↗</flux:button></section>
        @can('manage-datasets')
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Dataset administration</flux:heading><flux:text class="my-4">Upload source-attributed monthly CSV records and review saved data.</flux:text><flux:button :href="route('datasets.index')">Manage datasets</flux:button></section>
        @else
            <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700"><flux:heading>Researcher access</flux:heading><flux:text class="mt-3">You can explore and export public data. Dataset changes require administrator access granted by the project owner.</flux:text></section>
        @endcan
    </div>
</x-layouts::app>

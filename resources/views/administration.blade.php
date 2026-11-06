<x-layouts::app title="Administration">
    <div class="mx-auto w-full max-w-5xl space-y-7">
        <div><flux:heading size="xl">Users &amp; project settings</flux:heading><flux:text class="mt-2">Manage access and keep your dashboard up to date.</flux:text></div>
        <x-admin-feedback />
        <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading>Project settings</flux:heading>
            <form method="POST" action="{{ route('administration.settings') }}" class="mt-5 space-y-5">
                @csrf @method('PUT')
                <flux:input name="name" label="Application name" :value="old('name', $settings->name)" required maxlength="80" />
                <input type="hidden" name="sync_enabled" value="0">
                <label class="flex items-start gap-3 text-sm"><input class="mt-1" type="checkbox" name="sync_enabled" value="1" @checked(old('sync_enabled', $settings->sync_enabled))><span>Check NCDC for new reports automatically<small class="mt-1 block text-zinc-500">Daily at 6:00 AM, Africa/Lagos. New reports appear in the review queue before figures are published.</small></span></label>
                <flux:button type="submit" variant="primary">Save settings</flux:button>
            </form>
        </section>
        <section class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading>User access</flux:heading><flux:text class="mt-2">Administrators can change records, publish reports and manage other users. Your own administrator access cannot be revoked here.</flux:text>
            <div class="mt-5 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="p-3">Name / email</th><th class="p-3">Role</th><th class="p-3">Access</th></tr></thead><tbody>
                @foreach($users as $user)<tr class="border-b border-zinc-100 dark:border-zinc-700"><td class="p-3">{{ $user->name }}<div class="text-xs text-zinc-500">{{ $user->email }}</div></td><td class="p-3">{{ $user->is_admin ? 'Administrator' : 'Researcher' }}</td><td class="p-3">@if($user->is(auth()->user()))<span class="text-zinc-500">Your account</span>@else<form method="POST" action="{{ route('administration.role', $user) }}">@csrf @method('PUT')<input type="hidden" name="is_admin" value="{{ $user->is_admin ? 0 : 1 }}"><flux:button size="sm" type="submit">{{ $user->is_admin ? 'Make researcher' : 'Make administrator' }}</flux:button></form>@endif</td></tr>@endforeach
            </tbody></table></div><div class="mt-5">{{ $users->links() }}</div>
        </section>
    </div>
</x-layouts::app>

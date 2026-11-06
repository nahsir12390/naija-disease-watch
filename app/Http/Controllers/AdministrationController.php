<?php

namespace App\Http\Controllers;

use App\Models\ProjectSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministrationController extends Controller
{
    public function index(): View
    {
        return view('administration', ['users' => User::orderBy('name')->paginate(20), 'settings' => ProjectSetting::current()]);
    }

    public function claim(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'], true), 403);
        DB::transaction(function () use ($request): void {
            ProjectSetting::current();
            ProjectSetting::whereKey(1)->lockForUpdate()->firstOrFail();
            abort_if(User::where('is_admin', true)->exists(), 409, 'An administrator is already configured.');
            $request->user()->forceFill(['is_admin' => true])->save();
        });

        return to_route('administration.index')->with('status', 'Your administrator account is ready. You can manage everything from here.');
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['is_admin' => ['required', 'boolean']]);
        abort_if($user->is($request->user()), 422, 'You cannot change your own administrator role.');
        DB::transaction(function () use ($user, $validated): void {
            User::where('is_admin', true)->lockForUpdate()->get();
            $user->forceFill(['is_admin' => $validated['is_admin']])->save();
        });

        return back()->with('status', 'User access updated.');
    }

    public function settings(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:80'], 'sync_enabled' => ['required', 'boolean']]);
        ProjectSetting::current()->update($validated);

        return back()->with('status', 'Project settings saved.');
    }
}

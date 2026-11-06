<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['forceLight' => true])
    </head>
    <body class="auth-page">
        <div class="auth-shell">
            <aside class="auth-story" aria-label="About the workspace">
                <div class="auth-story-grid" aria-hidden="true"></div>
                <a class="auth-brand" href="{{ route('home') }}" wire:navigate>
                    <span class="auth-brand-symbol"><x-app-logo-icon /></span>
                    <span>{{ config('app.name') }}<small>PUBLIC HEALTH, IN PERSPECTIVE</small></span>
                </a>
                <div class="auth-story-content">
                    <div class="auth-story-label"><span></span> A clearer view of Nigeria’s health</div>
                    <h2>Behind every number,<br>there’s a bigger<br><em>picture.</em></h2>
                    <p>A shared workspace for exploring disease patterns, understanding trends, and bringing public health data into focus.</p>
                    <div class="auth-preview" aria-hidden="true">
                        <div class="auth-preview-header"><span><i></i> Disease surveillance</span><span>ILLUSTRATIVE PREVIEW</span></div>
                        <div class="auth-preview-title">From data to understanding<span><flux:icon.arrow-up-right class="ui-icon" /></span></div>
                        <svg viewBox="0 0 420 125" fill="none">
                            <defs><linearGradient id="auth-chart-fill" x1="0" y1="0" x2="0" y2="125" gradientUnits="userSpaceOnUse"><stop stop-color="#9bcf9f" stop-opacity=".25"/><stop offset="1" stop-color="#9bcf9f" stop-opacity="0"/></linearGradient></defs>
                            @foreach([25, 65, 105] as $y)<path d="M0 {{ $y }}H420" stroke="white" stroke-opacity=".1" stroke-dasharray="3 6"/>@endforeach
                            <path d="M0 97L38 87L76 92L114 61L152 71L190 47L228 60L266 33L304 44L342 20L380 30L420 12V125H0Z" fill="url(#auth-chart-fill)"/>
                            <path d="M0 97L38 87L76 92L114 61L152 71L190 47L228 60L266 33L304 44L342 20L380 30L420 12" stroke="#b7d98f" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="266" cy="33" r="5" fill="#234f40" stroke="#cce3a8" stroke-width="2"/>
                        </svg>
                        <div class="auth-preview-footer"><span>Explore patterns</span><span>Compare locations</span><span>Trace every source</span></div>
                    </div>
                    <div class="auth-story-facts"><div><strong>37</strong><span>locations: 36 states + FCT</span></div><div><strong>3</strong><span>disease focus areas</span></div><div><strong>One</strong><span>connected workspace</span></div></div>
                </div>
                <div class="auth-story-footer"><span class="auth-nigeria-flag" aria-hidden="true"></span> Built for Nigeria. Designed for understanding.<span><x-app-logo-icon class="ui-brand-icon" /></span></div>
            </aside>
            <div class="auth-main">
                <nav class="auth-topnav" aria-label="Authentication navigation">
                    <a href="{{ route('home') }}" wire:navigate><span aria-hidden="true"><flux:icon.arrow-left class="ui-icon" /></span> Public dashboard</a>
                    @if(request()->routeIs('register'))
                        <span>Already a member? <a href="{{ route('login') }}" wire:navigate>Log in <flux:icon.arrow-up-right class="ui-icon" /></a></span>
                    @elseif(request()->routeIs('login') && Route::has('register'))
                        <span>New here? <a href="{{ route('register') }}" wire:navigate>Create an account <flux:icon.arrow-up-right class="ui-icon" /></a></span>
                    @endif
                </nav>
                <a class="auth-mobile-brand" href="{{ route('home') }}" wire:navigate><span class="auth-brand-symbol"><x-app-logo-icon /></span>{{ config('app.name') }}</a>
                <main class="auth-form-region" id="main-content">
                    <div class="auth-form-card">
                        <div class="auth-form-eyebrow"><span></span> YOUR RESEARCH WORKSPACE</div>
                        {{ $slot }}
                    </div>
                </main>
                <footer class="auth-footer"><span>© {{ date('Y') }} {{ config('app.name') }}</span><a href="{{ route('home') }}" wire:navigate>Explore public data without an account <flux:icon.arrow-up-right class="ui-icon" /></a></footer>
            </div>
        </div>
        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>

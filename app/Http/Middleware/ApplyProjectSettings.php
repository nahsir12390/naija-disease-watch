<?php

namespace App\Http\Middleware;

use App\Models\ProjectSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyProjectSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['app.name' => ProjectSetting::current()->name]);

        return $next($request);
    }
}

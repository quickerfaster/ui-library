<?php

namespace QuickerFaster\UILibrary\Http\Middleware;

use Closure;
use QuickerFaster\UILibrary\Models\SystemSetting;

class CheckSetup
{
    public function handle($request, Closure $next)
    {
        // Allow Livewire update requests (v3 uses /livewire/update)
        if ($request->is('livewire/*')) {
            return $next($request);
        }

        // Allow the setup wizard page itself
        if ($request->routeIs('setup.wizard')) {
            return $next($request);
        }

        $setting = SystemSetting::first();
        if (!$setting || !$setting->setup_completed) {
            \Log::info('CheckSetup redirect', ['url' => $request->fullUrl()]);
            return redirect()->route('setup.wizard');
        }

        return $next($request);
    }
}
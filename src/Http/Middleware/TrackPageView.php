<?php

namespace QuickerFaster\UILibrary\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Models\PageView;

class TrackPageView
{
    /**
     * Record a page view after the response is sent.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Terminable middleware — runs after the response is sent.
     */
    public function terminate(Request $request, $response): void
    {
        if (!config('ui-library.analytics.page_views.enabled', true)) {
            return;
        }

        // Skip non-GET requests and Livewire/AJAX/API calls.
        if (!$request->isMethod('GET')) {
            return;
        }

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Livewire')) {
            return;
        }

        // Skip asset requests.
        $path = $request->path();
        if (preg_match('#\.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?|ttf|map)$#', $path)) {
            return;
        }

        $user = Auth::user();

        PageView::create([
            'user_id'     => $user?->id,
            'company_id'  => $user?->current_company_id ?? null,
            'route'       => $request->getRequestUri(),
            'method'      => $request->method(),
            'referrer'    => $request->header('referer'),
            'created_at'  => now(),
        ]);
    }
}
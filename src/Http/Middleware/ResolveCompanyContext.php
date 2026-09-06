<?php

namespace QuickerFaster\UILibrary\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;

/**
 * Resolves the current company context at the start of a request and persists
 * it to the session, so the CompanyScope (and other session-based consumers)
 * work reliably on both web and API routes without depending on the top-nav
 * view composer having rendered.
 */
class ResolveCompanyContext
{
    public function __construct(
        protected CompanyProvider $companyProvider,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $sessionKey = (string) config('ui-library.tenancy.session_key', 'current_company_id');

        // Preserve an explicitly selected company (including "All Companies"
        // mode, represented by 0). Only resolve lazily when no value is present.
        if (!session()->has($sessionKey)) {
            $companyId = $this->companyProvider->getCurrentCompanyId($user);

            if ($companyId !== null) {
                session()->put($sessionKey, (int) $companyId);
            }
        }

        return $next($request);
    }
}

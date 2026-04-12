<?php

namespace QuickerFaster\UILibrary\Traits;

use Illuminate\Support\Facades\Auth;

trait NavigationFilter
{
    /**
     * Filter menu items based on visibility rules.
     *
     * @param array $items
     * @return array
     */
    protected function filterVisibleItems(array $items): array
    {
        return array_filter($items, function ($item) {
            return $this->checkVisibility($item['visibility'] ?? 'any');
        });
    }

    /**
     * Check a single visibility rule.
     *
     * @param string $rule
     * @return bool
     */
    protected function checkVisibility(string $rule): bool
    {
        if ($rule === 'any') {
            return true;
        }

        if ($rule === 'auth') {
            return Auth::check();
        }

        if ($rule === 'guest') {
            return !Auth::check();
        }

        if (str_starts_with($rule, 'role:')) {
            $role = substr($rule, 5);
            return Auth::check() && Auth::user()->hasRole($role); // adjust to your role implementation
        }

        if (str_starts_with($rule, 'permission:')) {
            $permission = substr($rule, 11);
            return Auth::check() && Auth::user()->can($permission);
        }

        // Unknown rule – assume visible
        return true;
    }
}
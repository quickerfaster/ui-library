<?php

namespace QuickerFaster\UILibrary\Traits\Widgets;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait ResolvesDateStrings
{
    /**
     * Resolve a single value if it's a special date string or relative expression.
     */
    protected function resolveDateString($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $now = Carbon::now();

        // First, handle absolute date strings
        $absolute = match ($value) {
            'today' => $now->toDateString(),
            'yesterday' => $now->subDay()->toDateString(),
            'tomorrow' => $now->addDay()->toDateString(),
            'first day of this month' => $now->copy()->startOfMonth()->toDateString(),
            'last day of this month' => $now->copy()->endOfMonth()->toDateString(),
            'first day of last month' => $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'last day of last month' => $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            'first day of this year' => $now->copy()->startOfYear()->toDateString(),
            'last day of this year' => $now->copy()->endOfYear()->toDateString(),
            'first day of last year' => $now->copy()->subYear()->startOfYear()->toDateString(),
            'last day of last year' => $now->copy()->subYear()->endOfYear()->toDateString(),
            default => null,
        };

        if ($absolute !== null) {
            return $absolute;
        }

        // Then, handle relative expressions like '+30 days', '-7 days', 'next monday', etc.
        if (preg_match('/^([+-]?\d+)\s+(day|days|week|weeks|month|months|year|years)$/i', $value, $matches)) {
            $amount = (int) $matches[1];
            $unit = strtolower($matches[2]);
            $carbon = $now->copy();
            switch ($unit) {
                case 'day':
                case 'days':
                    $carbon->addDays($amount);
                    break;
                case 'week':
                case 'weeks':
                    $carbon->addWeeks($amount);
                    break;
                case 'month':
                case 'months':
                    $carbon->addMonths($amount);
                    break;
                case 'year':
                case 'years':
                    $carbon->addYears($amount);
                    break;
            }
            return $carbon->toDateString();
        }

        // Handle 'next monday', 'last friday', etc. (simple)
        if (preg_match('/^(next|last)\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)$/i', $value, $matches)) {
            $direction = strtolower($matches[1]);
            $day = ucfirst(strtolower($matches[2]));
            if ($direction === 'next') {
                return $now->copy()->next($day)->toDateString();
            } else {
                return $now->copy()->previous($day)->toDateString();
            }
        }

        // If nothing matches, return original value
        return $value;
    }

    /**
     * Resolve an array of conditions (each: [field, operator, value]).
     */
    protected function resolveConditions(array $conditions): array
    {
        $resolved = [];
        foreach ($conditions as $condition) {
            if (count($condition) >= 3) {
                $condition[2] = $this->resolveDateString($condition[2]);
            }
            $resolved[] = $condition;
        }
        return $resolved;
    }
}
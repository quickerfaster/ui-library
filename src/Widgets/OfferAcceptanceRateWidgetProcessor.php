<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;

class OfferAcceptanceRateWidgetProcessor
{
    public function process(array $definition): array
    {
        $months = $definition['months'] ?? 12;
        $startDate = now()->subMonths($months)->startOfMonth();

        $offers = DB::table('job_applications')
            ->where('status', 'offered')
            ->where('offer_date', '>=', $startDate)
            ->count();

        $accepted = DB::table('job_applications')
            ->where('status', 'accepted')
            ->where('offer_date', '>=', $startDate)
            ->count();

        $rate = $offers > 0 ? round(($accepted / $offers) * 100, 1) : 0;

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Offer Acceptance Rate',
            'value' => $rate . '%',
            'icon'  => $definition['icon'] ?? 'fas fa-handshake',
            'width' => $definition['width'] ?? 4,
            'description' => "Last {$months} months",
        ];
    }
}
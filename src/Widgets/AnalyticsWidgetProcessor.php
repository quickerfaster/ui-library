<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;
use QuickerFaster\UILibrary\Models\ActivityLog;
use QuickerFaster\UILibrary\Models\PageView;
use QuickerFaster\UILibrary\Models\UserSession;

class AnalyticsWidgetProcessor
{
    public function process(array $definition): array
    {
        $days = $definition['days'] ?? 7;
        $startDate = now()->subDays($days);

        return [
            'type'              => 'analytics',
            'title'             => $definition['title'] ?? 'Analytics',
            'width'             => $definition['width'] ?? 12,
            'active_users'      => UserSession::where('action', 'login')
                ->where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count('user_id'),
            'page_views'        => PageView::where('created_at', '>=', $startDate)->count(),
            'top_pages'         => PageView::where('created_at', '>=', $startDate)
                ->select('route', DB::raw('count(*) as total'))
                ->groupBy('route')
                ->orderByDesc('total')
                ->limit($definition['top_pages_limit'] ?? 5)
                ->get()
                ->toArray(),
            'actions'           => ActivityLog::where('created_at', '>=', $startDate)
                ->select('action', DB::raw('count(*) as total'))
                ->groupBy('action')
                ->orderByDesc('total')
                ->get()
                ->toArray(),
            'daily_page_views'  => PageView::where('created_at', '>=', $startDate)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->toArray(),
            'daily_logins'      => UserSession::where('action', 'login')
                ->where('created_at', '>=', $startDate)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(distinct user_id) as total'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->toArray(),
            'period_days'       => $days,
        ];
    }
}
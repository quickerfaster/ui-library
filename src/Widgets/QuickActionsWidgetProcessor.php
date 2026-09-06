<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Models\UserFavoriteAction;
use QuickerFaster\UILibrary\Services\QuickActions\ActionRegistry;
use QuickerFaster\UILibrary\Services\QuickActions\RankingEngine;

/**
 * QuickActionsWidgetProcessor renders the current user's top-ranked
 * quick actions as a dashboard widget.
 *
 * Consumes the Phase 2 ranking signal (RankingEngine::score) and exposes the
 * personalized top-N actions as clickable rows. Configured in dashboard data
 * configs via:
 *
 *   ['type' => 'quick_actions', 'limit' => 5, 'title' => 'Frequent Actions']
 *
 * Phase 4: Favorites are interleaved at the top and passed to the view.
 */
class QuickActionsWidgetProcessor
{
    public function process(array $definition): array
    {
        $limit = (int) ($definition['limit'] ?? 8);

        $actions = [];
        $favorites = [];

        if (Auth::check()) {
            /** @var ActionRegistry $registry */
            $registry = app(ActionRegistry::class);

            /** @var RankingEngine $ranking */
            $ranking = app(RankingEngine::class);

            $actions = $ranking->score($registry->authorizedFor(), Auth::id());

            // Phase 4: Load favorites and interleave at top.
            $favorites = UserFavoriteAction::query()
                ->where('user_id', Auth::id())
                ->pluck('action_id')
                ->toArray();

            if (!empty($favorites)) {
                $favoriteSet = array_flip($favorites);
                $pinned = [];
                $rest = [];

                foreach ($actions as $action) {
                    $id = $action['id'] ?? $action['key'] ?? null;
                    if ($id && isset($favoriteSet[$id])) {
                        $action['_pinned'] = true;
                        $pinned[] = $action;
                    } else {
                        $action['_pinned'] = false;
                        $rest[] = $action;
                    }
                }

                usort($pinned, function (array $a, array $b) use ($favorites) {
                    $posA = array_search($a['id'] ?? $a['key'] ?? '', $favorites);
                    $posB = array_search($b['id'] ?? $b['key'] ?? '', $favorites);
                    return ($posA === false ? PHP_INT_MAX : $posA) <=> ($posB === false ? PHP_INT_MAX : $posB);
                });

                $actions = array_merge($pinned, $rest);
            }

            $actions = array_slice($actions, 0, $limit);
        }

        return [
            'type'        => 'quick_actions',
            'title'       => $definition['title'] ?? 'Frequent Actions',
            'description' => $definition['description'] ?? null,
            'icon'        => $definition['icon'] ?? 'fas fa-bolt',
            'color'       => $definition['color'] ?? 'warning',
            'actions'     => $actions,
            'favorites'   => $favorites,
            'width'       => $definition['width'] ?? 4,
        ];
    }
}

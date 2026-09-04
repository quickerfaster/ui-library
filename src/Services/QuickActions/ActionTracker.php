<?php

namespace QuickerFaster\UILibrary\Services\QuickActions;

use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Models\UserActionHistory;

/**
 * ActionTracker records user action executions for personalized ranking.
 *
 * Each palette selection inserts a UserActionHistory row keyed by the
 * action id and the resolved user id. Tracking can be disabled via
 * config('ui-library.quick_actions.tracking.enabled').
 */
class ActionTracker
{
    /**
     * Record a quick action execution for the given (or currently
     * authenticated) user.
     *
     * @param  string   $actionId
     * @param  int|null $userId
     * @return \QuickerFaster\UILibrary\Models\UserActionHistory|null
     */
    public function record(string $actionId, $userId = null): ?UserActionHistory
    {
        if (!config('ui-library.quick_actions.tracking.enabled', true)) {
            return null;
        }

        $userId = $userId ?? Auth::id();

        if (!$userId) {
            return null;
        }

        return UserActionHistory::create([
            'user_id'     => $userId,
            'action_id'   => $actionId,
            'executed_at' => now(),
        ]);
    }
}

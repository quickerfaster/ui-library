<?php

namespace QuickerFaster\UILibrary\Services\QuickActions;

use Illuminate\Support\Facades\Auth;
use QuickerFaster\UILibrary\Models\UserActionHistory;

/**
 * RankingEngine orders quick actions by personalized usage.
 *
 * Scoring uses exponential decay for recency and a saturating curve for
 * frequency:
 *
 *   score = (recency_weight × recency_factor)
 *         + (frequency_weight × frequency_factor)
 *
 *   recency_factor    = exp(-days_since_last_execution / half_life_days)
 *   frequency_factor  = 1 - exp(-execution_count / frequency_saturation)
 *
 * Actions never executed score 0 and fall to the bottom (but still appear).
 */
class RankingEngine
{
    /**
     * Order actions by computed usage score (highest first).
     *
     * The original relative order is preserved for ties (e.g. all actions
     * with score 0), so the palette never loses actions.
     *
     * @param  array<int, array> $actions
     * @param  int|null          $userId
     * @return array<int, array>
     */
    public function score(array $actions, $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        if (!$userId) {
            return $actions;
        }

        $stats = $this->loadStats($userId);

        $scored = [];
        foreach ($actions as $index => $action) {
            $id = $action['id'] ?? $action['key'] ?? 'action_' . $index;

            $stat = $stats[$id] ?? null;
            $score = $stat
                ? $this->computeScore($stat['count'], $stat['last_executed_at'])
                : 0.0;

            $action['_score'] = $score;
            $action['_index'] = $index;

            $scored[] = $action;
        }

        usort($scored, function (array $a, array $b) {
            $cmp = ($b['_score'] <=> $a['_score']);

            if ($cmp === 0) {
                return $a['_index'] <=> $b['_index'];
            }

            return $cmp;
        });

        // Strip internal bookkeeping keys before returning.
        return array_map(function (array $action) {
            unset($action['_score'], $action['_index']);

            return $action;
        }, $scored);
    }

    /**
     * Load per-action execution stats (count + most recent execution) for a
     * user, keyed by action id.
     *
     * @param  int $userId
     * @return array<string, array{count: int, last_executed_at: \Carbon\Carbon|null}>
     */
    protected function loadStats($userId): array
    {
        $rows = UserActionHistory::query()
            ->where('user_id', $userId)
            ->get(['action_id', 'executed_at']);

        $stats = [];

        foreach ($rows as $row) {
            $key = $row->action_id;

            if (!isset($stats[$key])) {
                $stats[$key] = ['count' => 0, 'last_executed_at' => null];
            }

            $stats[$key]['count']++;

            $executedAt = $row->executed_at;
            if ($executedAt !== null) {
                $latest = $stats[$key]['last_executed_at'];

                if ($latest === null || $executedAt->gt($latest)) {
                    $stats[$key]['last_executed_at'] = $executedAt;
                }
            }
        }

        return $stats;
    }

    /**
     * Compute the blended recency + frequency score for a single action.
     *
     * @param  int                              $count
     * @param  \Carbon\Carbon|null              $lastExecutedAt
     * @return float
     */
    protected function computeScore(int $count, $lastExecutedAt): float
    {
        $config = config('ui-library.quick_actions.ranking', []);

        $recencyWeight   = (float) ($config['recency_weight'] ?? 0.6);
        $frequencyWeight = (float) ($config['frequency_weight'] ?? 0.4);
        $halfLifeDays    = (float) ($config['half_life_days'] ?? 7);
        $saturation      = (float) ($config['frequency_saturation'] ?? 5);

        $daysSince = 0.0;
        if ($lastExecutedAt !== null) {
            $daysSince = max(0.0, $lastExecutedAt->diffInSeconds(now(), true) / 86400.0);
        }

        $recencyFactor = $halfLifeDays > 0
            ? exp(-$daysSince / $halfLifeDays)
            : 1.0;

        $frequencyFactor = $saturation > 0
            ? 1 - exp(-$count / $saturation)
            : 1.0;

        return ($recencyWeight * $recencyFactor) + ($frequencyWeight * $frequencyFactor);
    }
}

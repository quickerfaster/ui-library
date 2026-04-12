<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;

class TrainingCompletionRateWidgetProcessor
{
    public function process(array $definition): array
    {
        $trainingId = $definition['training_id'] ?? null;
        $type = $definition['type'] ?? 'all'; // 'mandatory', 'all'

        $query = DB::table('training_assignments')
            ->join('trainings', 'training_assignments.training_id', '=', 'trainings.id');

        if ($trainingId) {
            $query->where('training_id', $trainingId);
        }

        if ($type === 'mandatory') {
            $query->where('trainings.is_mandatory', true);
        }

        $total = $query->count();
        $completed = $query->clone()->where('completed', true)->count();

        $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'Training Completion Rate',
            'value' => $rate . '%',
            'icon'  => $definition['icon'] ?? 'fas fa-graduation-cap',
            'width' => $definition['width'] ?? 4,
        ];
    }
}
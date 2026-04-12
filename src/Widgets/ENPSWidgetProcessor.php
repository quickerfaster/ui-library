<?php

namespace QuickerFaster\UILibrary\Widgets;

use Illuminate\Support\Facades\DB;

class ENPSWidgetProcessor
{
    public function process(array $definition): array
    {
        $surveyId = $definition['survey_id'] ?? null;
        $question = $definition['question'] ?? 'How likely are you to recommend our company as a place to work?';

        $query = DB::table('survey_responses')
            ->where('question', $question);

        if ($surveyId) {
            $query->where('survey_id', $surveyId);
        }

        $responses = $query->pluck('answer_value')->toArray(); // answer_value should be 0-10

        $total = count($responses);
        if ($total === 0) {
            return [
                'type'  => 'stat',
                'title' => $definition['title'] ?? 'eNPS Score',
                'value' => 'N/A',
                'icon'  => $definition['icon'] ?? 'fas fa-star',
                'width' => $definition['width'] ?? 4,
            ];
        }

        $promoters = count(array_filter($responses, fn($v) => $v >= 9));
        $detractors = count(array_filter($responses, fn($v) => $v <= 6));

        $enps = round((($promoters - $detractors) / $total) * 100, 0);

        // Determine category
        $category = match(true) {
            $enps >= 50 => 'Excellent',
            $enps >= 30 => 'Great',
            $enps >= 10 => 'Good',
            $enps >= 0  => 'Needs Improvement',
            default     => 'Critical',
        };

        return [
            'type'  => 'stat',
            'title' => $definition['title'] ?? 'eNPS Score',
            'value' => $enps,
            'icon'  => $definition['icon'] ?? 'fas fa-chart-simple',
            'width' => $definition['width'] ?? 4,
            'description' => "{$category} (Promoters: {$promoters}, Detractors: {$detractors})",
        ];
    }
}
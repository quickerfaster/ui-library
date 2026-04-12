<?php

namespace QuickerFaster\UILibrary\Widgets;

class OnboardingWidgetProcessor
{
    public function process(array $definition): array
    {
        $user = auth()->user();
        $onboarding = $user ? $user->onboarding() : null;

        $inProgress = $onboarding && $onboarding->inProgress();
        $percentage = $onboarding ? $onboarding->percentageCompleted() : 0;
        $steps = [];

        if ($onboarding) {
            foreach ($onboarding->steps as $step) {
                $steps[] = [
                    'title'    => $step->title,
                    'cta'      => $step->cta,
                    'link'     => $step->link,
                    'complete' => $step->complete(),
                ];
            }
        }

        return [
            'type'             => 'onboarding',
            'title'            => $definition['title'] ?? 'Welcome!',
            'description'      => $definition['description'] ?? 'Complete these steps to get started',
            'icon'             => $definition['icon'] ?? 'fas fa-rocket',
            'inProgress'       => $inProgress,
            'percentage'       => $percentage,
            'steps'            => $steps,
            'width'            => $definition['width'] ?? 6,
            'showCompleted'    => $definition['show_completed'] ?? false, // optional
        ];
    }
}
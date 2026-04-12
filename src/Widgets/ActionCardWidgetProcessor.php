<?php

namespace QuickerFaster\UILibrary\Widgets;

use QuickerFaster\UILibrary\Contracts\Widgets\Widget;

class ActionCardWidgetProcessor
{
    public function process(array $definition): array
    {
        return [
            'type'        => 'action_card',
            'title'       => $definition['title'] ?? 'Action',
            'description' => $definition['description'] ?? '',
            'icon'        => $definition['icon'] ?? null,
            'actions'     => $definition['actions'] ?? [],
            'width'       => $definition['width'] ?? 4,
        ];
    }
}








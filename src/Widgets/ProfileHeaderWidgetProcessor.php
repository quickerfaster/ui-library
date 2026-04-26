<?php

namespace QuickerFaster\UILibrary\Widgets;

class ProfileHeaderWidgetProcessor
{
    public function process(array $definition): array
    {
        return [
            'type'        => 'profile_header',
            'title'       => $definition['title'] ?? '',
            'photo_url'   => $definition['photo_url'] ?? null,
            'full_name'   => $definition['full_name'] ?? '',
            'employee_number' => $definition['employee_number'] ?? '',
            'fields'      => $definition['fields'] ?? [],
            'actions'     => $definition['actions'] ?? [],
            'width'       => $definition['width'] ?? 4,
        ];
    }
}
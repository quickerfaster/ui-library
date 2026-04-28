<?php

namespace QuickerFaster\UILibrary\Traits\FieldTypes;

use Illuminate\Support\Facades\View;

trait HasBladeRendering
{
    protected function renderBlade(string $view, array $data = []): string
    {
        return View::make($view, $data)->render();
    }



protected function renderComplexFallback($record, array $extra, string $actionLabel): string
{
    $mode = $this->definition['editable_mode'] ?? 'drawer';
    $rowId = $extra['rowId'] ?? null;
    $configKey = $extra['configKey'] ?? null;
    $recordId = $record->id;

    if ($mode === 'inline') {
        // Simple text input fallback (not recommended)
        return "<input type=\"text\" wire:model.blur=\"editedData.{$rowId}.{$this->name}\" class=\"form-control form-control-sm\">";
    }

    if ($mode === 'modal') {
        $event = 'openAddModal';
        $eventData = [
            'configKey' => $configKey,
            'recordId' => $recordId,
            'inline' => true,
        ];
    } else {
        $event = 'openDrawer';
        $eventData = [
            'component' => 'qf.data-table-form',
            'params' => [
                'configKey' => $configKey,
                'recordId' => $recordId,
                'inline' => true,
                'allowedGroups' => [],
            ],
            'title' => $actionLabel,
        ];
    }

    // Encode JSON with hex entities to avoid breaking the attribute
    $json = json_encode($eventData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    // Use single quotes for the onclick attribute to prevent escaping issues
    $buttonHtml = "<button type=\"button\" class=\"btn btn-sm btn-outline-secondary\" onclick='Livewire.dispatch(\"{$event}\", {$json})'><i class=\"fas fa-edit\"></i> {$actionLabel}</button>";

    return $buttonHtml;
}
    

    /**
     * Helper to safely encode data for the Livewire dispatch call.
     * Avoids breaking the oneliner due to quotes.
     */
    protected function jsonEncodeForEvent($data): string
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        return $json ?: '{}';
    }



}

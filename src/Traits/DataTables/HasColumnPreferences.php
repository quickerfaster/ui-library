<?php

namespace QuickerFaster\UILibrary\Traits\DataTables;

trait HasColumnPreferences
{
    /**
     * Load visible columns from session or use defaults.
     *
     * @param string $configKey
     * @param array $allColumns  All available columns (keys)
     * @return array
     */
    protected function loadVisibleColumns(string $configKey, array $allColumns): array
    {
        $preferenceKey = 'datatable.columns.' . $configKey;
        $saved = session($preferenceKey);

        if (!is_null($saved) && is_array($saved)) {
            // Ensure saved columns are actually available (in case config changed)
            return array_intersect($saved, $allColumns);
        }

        // Default: all columns visible
        return $allColumns;
    }

    /**
     * Save visible columns to session.
     *
     * @param string $configKey
     * @param array $visibleColumns
     * @return void
     */
    protected function saveVisibleColumns(string $configKey, array $visibleColumns): void
    {
        $preferenceKey = 'datatable.columns.' . $configKey;
        session([$preferenceKey => $visibleColumns]);
    }
}
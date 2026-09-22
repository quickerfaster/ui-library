<?php

namespace QuickerFaster\UILibrary\Traits\DataTables;

trait HasColumnPreferences
{
    /**
     * Load visible columns from session or use fallback defaults.
     *
     * @param string $configKey
     * @param array  $allColumns  All available columns (used for intersection to filter out stale columns)
     * @param array|null $fallback  Default columns when no session data exists (defaults to $allColumns)
     * @return array
     */
    protected function loadVisibleColumns(string $configKey, array $allColumns, ?array $fallback = null): array
    {
        $preferenceKey = 'datatable.columns.' . $configKey;
        $saved = session($preferenceKey);

        if (!is_null($saved) && is_array($saved)) {
            // Intersect with ALL available columns so user-added columns survive refresh.
            // Previously intersected with $allColumns which was often $defaultColumns (first 6),
            // causing any columns beyond the default set to be stripped on page reload.
            return array_values(array_intersect($saved, $allColumns));
        }

        // No session data: use fallback (typically config-defined defaults), or all columns
        return $fallback ?? $allColumns;
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
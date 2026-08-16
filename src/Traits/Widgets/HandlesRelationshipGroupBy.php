<?php

namespace QuickerFaster\UILibrary\Traits\Widgets;

use Illuminate\Support\Str;

trait HandlesRelationshipGroupBy
{
    /**
     * Apply a group by expression that supports dot notation for relationships.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $groupBy  e.g. 'record.company.name'
     * @param string $alias    Alias for the grouped column (default 'group_label')
     * @return string The final column expression (with alias) for SELECT
     */
    protected function applyGroupByWithRelations($query, string $groupBy, string $alias = 'group_label'): string
    {
        $parts = explode('.', $groupBy);
        $table = $query->getModel()->getTable();
        $lastPart = array_pop($parts);

        if (empty($parts)) {
            // Simple column on the main table
            $expression = "$table.$lastPart as $alias";
            $query->groupBy("$table.$lastPart");
            return $expression;
        }

        // Build joins for relationships
        $currentModel = $query->getModel();
        $currentAlias = $table;

        foreach ($parts as $relationName) {
            // Get the related model via the relation method
            $relation = $currentModel->$relationName();
            $relatedModel = $relation->getRelated();
            $relatedTable = $relatedModel->getTable();

            // Determine join condition based on relation type
            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                // BelongsTo: related table's primary key = current table's foreign key
                $foreignKey = $relation->getForeignKeyName(); // column on current table
                $ownerKey = $relation->getOwnerKeyName();     // column on related table (usually 'id')
                $joinOn = "$relatedTable.$ownerKey = $currentAlias.$foreignKey";
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOneOrMany) {
                // HasOne/HasMany: current table's primary key = related table's foreign key
                $localKey = $relation->getLocalKeyName();     // column on current table (usually 'id')
                $foreignKey = $relation->getForeignKeyName(); // column on related table
                $joinOn = "$currentAlias.$localKey = $relatedTable.$foreignKey";
            } else {
                // Fallback – assume BelongsTo style
                $foreignKey = Str::singular($currentAlias) . '_id';
                $ownerKey = 'id';
                $joinOn = "$relatedTable.$ownerKey = $currentAlias.$foreignKey";
            }

            // Perform the left join with a raw ON condition
            $query->leftJoin($relatedTable, function ($join) use ($joinOn) {
                $join->whereRaw($joinOn);
            });

            $currentModel = $relatedModel;
            $currentAlias = $relatedTable;
        }

        // Final column from the last joined table
        $expression = "$currentAlias.$lastPart as $alias";
        $query->groupBy("$currentAlias.$lastPart");
        return $expression;
    }
}
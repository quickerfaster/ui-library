<?php


namespace QuickerFaster\UILibrary\Services\Search;

class SearchEngine
{
    /**
     * Apply search conditions to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @param array $fields  Array of field names (strings) or field definitions with 'relationship' key.
     * @param array $fieldDefs  Optional full field definitions keyed by field name (for relationship resolution).
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function apply($query, string $search, array $fields, array $fieldDefs = [])
    {
        if (empty($search) || empty($fields)) {
            return $query;
        }

        return $query->where(function ($q) use ($search, $fields, $fieldDefs) {
            foreach ($fields as $field) {
                // If $field is a string, look up its definition in $fieldDefs
                $def = is_string($field) ? ($fieldDefs[$field] ?? []) : $field;

                // Handle relationship fields via whereHas
                if (isset($def['relationship'])) {
                    $relationMethod = $def['relationship']['dynamic_property']
                        ?? (str_ends_with($field, '_id') ? substr($field, 0, -3) : null);

                    $searchableFields = $def['relationship']['searchable_fields']
                        ?? [$def['relationship']['display_field'] ?? 'name'];

                    if ($relationMethod && !empty($searchableFields)) {
                        $q->orWhereHas($relationMethod, function ($subQ) use ($searchableFields, $search) {
                            $subQ->where(function ($innerQ) use ($searchableFields, $search) {
                                foreach ($searchableFields as $sf) {
                                    $innerQ->orWhere($sf, 'like', $search . '%');
                                }
                            });
                        });
                    }
                    continue;
                }

                // Direct field
                $fieldName = is_string($field) ? $field : ($def['field'] ?? '');
                if (!empty($fieldName)) {
                    $q->orWhere($fieldName, 'like', $search . '%');
                }
            }
        });
    }

    public static function get($modelClass, string $search, array $fields, int $limit = 20)
    {
        if (empty($search) || empty($fields)) {
            return collect();
        }

        $query = $modelClass::query();

        self::apply($query, $search, $fields);

        return $query->limit($limit)->get();
    }
}
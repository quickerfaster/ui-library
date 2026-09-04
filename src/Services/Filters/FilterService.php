<?php

namespace QuickerFaster\UILibrary\Services\Filters;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;


class FilterService
{
    /**
     * Apply a set of simple [field, operator, value] filters.
     * Supports dot notation relationships.
     *
     * @param Builder $query
     * @param array   $filters           Array of [field, operator, value] triplets.
     * @param array   $fieldDefinitions  Optional field definitions for validation.
     *                                   If omitted, no validation is performed.
     */
    public function applySimpleFilters(Builder $query, array $filters, array $fieldDefinitions = []): void
    {
        $modelInstance = $query->getModel();

        foreach ($filters as $filter) {
            if (!is_array($filter) || count($filter) !== 3) {
                continue;
            }
            [$field, $operator, $value] = $filter;

            if (str_contains($field, '.')) {
                $parts = explode('.', $field);
                $column = array_pop($parts);

                if ($this->validateRelationChain($modelInstance, $parts)) {
                    $relation = implode('.', $parts);
                    $query->whereHas($relation, function ($q) use ($column, $operator, $value) {
                        if ($operator === 'between' && is_array($value) && count($value) === 2) {
                            $q->whereBetween($column, $value);
                        } else {
                            $q->where($column, $operator, $value);
                        }
                    });
                }
            } else {
                // Optional field validation if definitions are provided
                if (!empty($fieldDefinitions) && !array_key_exists($field, $fieldDefinitions)) {
                    continue;
                }

                if ($operator === 'between' && is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                } else {
                    $query->where($field, $operator, $value);
                }
            }
        }
    }

    /**
     * Validate a relationship chain (e.g. ['profile', 'address']) exists.
     */
    private function validateRelationChain(Model $model, array $relations): bool
    {
        $currentModel = $model;

        foreach ($relations as $relation) {
            if (!method_exists($currentModel, $relation)) {
                return false;
            }

            try {
                $relationInstance = $currentModel->$relation();
                if (!$relationInstance instanceof Relation) {
                    return false;
                }
                $currentModel = $relationInstance->getRelated();
            } catch (\Throwable $e) {
                return false;
            }
        }

        return true;
    }
}
<?php


namespace QuickerFaster\UILibrary\Services\Search;

class SearchEngine
{
public static function apply($query, string $search, array $fields)
{
    if (empty($search) || empty($fields)) {
        return $query;
    }

    return $query->where(function ($q) use ($search, $fields) {
        foreach ($fields as $field) {
            $q->orWhere($field, 'like', $search . '%');
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
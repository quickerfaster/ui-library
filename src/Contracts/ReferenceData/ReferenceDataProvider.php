<?php

namespace QuickerFaster\UILibrary\Contracts\ReferenceData;

use Illuminate\Support\Collection;

interface ReferenceDataProvider
{
    /** Get all items of a given type. */
    public function getAll(string $type): Collection;

    /** Get a single item by type and ID. */
    public function getById(string $type, int|string $id): ?array;

    /** Get all registered reference data types. */
    public function getTypes(): array;

    /** Create a new reference data item. */
    public function create(string $type, string $key, mixed $value, array $meta = []): array;

    /** Update an existing reference data item. */
    public function update(int|string $id, array $data): array;

    /** Delete a reference data item. */
    public function delete(int|string $id): bool;
}
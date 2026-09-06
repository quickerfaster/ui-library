<?php

namespace QuickerFaster\UILibrary\Services\ReferenceData;

use QuickerFaster\UILibrary\Contracts\ReferenceData\ReferenceDataProvider;
use QuickerFaster\UILibrary\Models\ReferenceDataItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ReferenceDataService implements ReferenceDataProvider
{
    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('ui-library.reference_data.cache_ttl', 3600);
    }

    public function getAll(string $type): Collection
    {
        return Cache::remember("reference_data:{$type}", $this->cacheTtl, function () use ($type) {
            return ReferenceDataItem::ofType($type)->active()
                ->orderBy('key')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'key' => $item->key,
                    'value' => $item->value,
                    'meta' => $item->meta,
                    'is_active' => $item->is_active,
                ]);
        });
    }

    public function getById(string $type, int|string $id): ?array
    {
        $item = ReferenceDataItem::ofType($type)->find($id);
        if (!$item) return null;

        return [
            'id' => $item->id, 'type' => $item->type, 'key' => $item->key,
            'value' => $item->value, 'meta' => $item->meta, 'is_active' => $item->is_active,
        ];
    }

    public function getTypes(): array
    {
        return array_keys(config('ui-library.reference_data.types', []));
    }

    public function create(string $type, string $key, mixed $value, array $meta = []): array
    {
        $item = ReferenceDataItem::create([
            'type' => $type, 'key' => $key, 'value' => $value, 'meta' => $meta,
        ]);
        Cache::forget("reference_data:{$type}");
        return $item->toArray();
    }

    public function update(int|string $id, array $data): array
    {
        $item = ReferenceDataItem::findOrFail($id);
        $item->update($data);
        Cache::forget("reference_data:{$item->type}");
        return $item->fresh()->toArray();
    }

    public function delete(int|string $id): bool
    {
        $item = ReferenceDataItem::findOrFail($id);
        $type = $item->type;
        $result = $item->delete();
        Cache::forget("reference_data:{$type}");
        return $result;
    }

    /** Flush all reference data cache. */
    public function flushCache(): void
    {
        foreach ($this->getTypes() as $type) {
            Cache::forget("reference_data:{$type}");
        }
    }
}
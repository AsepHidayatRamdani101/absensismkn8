<?php

namespace App\Repositories\Pancawaluya\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AppliesMasterFilters
{
    private function applyBaseFilters(Builder $query, array $filters, array $searchColumns): Builder
    {
        $status = $filters['status'] ?? '';
        $search = trim((string) ($filters['search'] ?? ''));

        if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }

        if (!empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($searchColumns, $search): void {
                foreach ($searchColumns as $index => $column) {
                    if ($index === 0) {
                        $builder->where($column, 'like', "%{$search}%");

                        continue;
                    }

                    $builder->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }
}

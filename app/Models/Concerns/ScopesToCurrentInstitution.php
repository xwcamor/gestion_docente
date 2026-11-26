<?php

namespace App\Models\Concerns;

use App\Support\CurrentInstitution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ScopesToCurrentInstitution
{
    protected static function bootScopesToCurrentInstitution(): void
    {
        $modelClass = static::class;

        static::addGlobalScope('current_institution', function (Builder $builder) use ($modelClass): void {
            if (!auth()->check()) {
                return;
            }

            $institutionId = (int) app(CurrentInstitution::class)->get()->getKey();
            $modelClass::applyInstitutionScope($builder, $institutionId);
        });

        static::creating(function (Model $model) use ($modelClass): void {
            $column = $modelClass::institutionIdColumn();

            if ($column === null || !auth()->check() || filled($model->getAttribute($column))) {
                return;
            }

            $model->setAttribute(
                $column,
                app(CurrentInstitution::class)->get()->getKey()
            );
        });
    }

    protected static function institutionScopeRelation(): string
    {
        return 'institution';
    }

    protected static function institutionIdColumn(): ?string
    {
        return null;
    }

    protected static function institutionScopeColumn(): ?string
    {
        return static::institutionIdColumn();
    }

    protected static function applyInstitutionScope(Builder $builder, int $institutionId): void
    {
        $column = static::institutionScopeColumn();

        if ($column !== null) {
            $builder->where(
                $builder->getModel()->qualifyColumn($column),
                $institutionId
            );

            return;
        }

        $relation = static::institutionScopeRelation();

        $builder->whereHas($relation, function (Builder $related) use ($institutionId): void {
            $related->where(
                $related->getModel()->qualifyColumn('institution_id'),
                $institutionId
            );
        });
    }
}

<?php

declare(strict_types=1);

namespace Zarbin\IranLocations\Http\Controllers\Admin;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Zarbin\IranLocations\Builders\LocationBuilder;
use Zarbin\IranLocations\Http\Controllers\Admin\Concerns\AuthorizesIranLocationsAdmin;
use Zarbin\IranLocations\Support\LocationModelResolver;

abstract class AdminController extends Controller
{
    use AuthorizesIranLocationsAdmin;

    protected function perPage(?int $perPage = null): int
    {
        $perPage ??= (int) request('per_page', config('iran-locations.admin.per_page', 25));

        return max(1, min($perPage, 100));
    }

    /**
     * @return class-string<Model>
     */
    protected function modelClass(string $key): string
    {
        /** @var class-string<Model> $class */
        $class = LocationModelResolver::model($key);

        return $class;
    }

    protected function newModel(string $key): Model
    {
        $class = $this->modelClass($key);

        return new $class;
    }

    protected function findModel(string $key, int|string $id): Model
    {
        return $this->newModel($key)->newQuery()->whereKey($id)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function adminView(string $view, array $data = []): View
    {
        return app(ViewFactory::class)->make('iran-locations::admin.'.$view, $data);
    }

    /**
     * @param  array<int, string>  $columns
     * @return Collection<int, Model>
     */
    protected function optionRecords(string $modelKey, array $columns = ['id', 'name_fa', 'code']): Collection
    {
        $model = $this->newModel($modelKey);
        $query = $model->newQuery();

        if ($query instanceof LocationBuilder) {
            $query->active()->ordered();
        } else {
            $table = $model->getTable();

            if (Schema::hasColumn($table, 'is_active')) {
                $query->where($model->qualifyColumn('is_active'), true);
            }

            if (Schema::hasColumn($table, 'deprecated_at')) {
                $query->whereNull($model->qualifyColumn('deprecated_at'));
            }

            if (Schema::hasColumn($table, 'name_fa')) {
                $query->orderBy($model->qualifyColumn('name_fa'));
            }

            $query->orderBy($model->getQualifiedKeyName());
        }

        return $query->get($columns);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function payload(array $data, bool $creating = false): array
    {
        if ($creating && blank($data['source'] ?? null)) {
            $data['source'] = 'custom';
        }

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        return $data;
    }

    protected function allowsPackageRecordDirectEdit(): bool
    {
        return (bool) config('iran-locations.data.allow_package_record_direct_edit', false);
    }

    protected function guardPackageRecordDirectEdit(Model $model, string $resourceName): void
    {
        if ($this->allowsPackageRecordDirectEdit() || $model->getAttribute('source') !== 'package') {
            return;
        }

        abort(403, "{$resourceName} is package-owned and cannot be changed from the admin UI unless package record direct edit is explicitly enabled.");
    }

    protected function safeDestroy(Model $model, string $resourceName): RedirectResponse
    {
        $this->guardPackageRecordDirectEdit($model, $resourceName);

        if ($model->getAttribute('source') === 'package') {
            if (method_exists($model, 'markDeprecated')) {
                $model->markDeprecated();
                $model->save();

                return back()->with('status', "{$resourceName} is package-owned and was deprecated instead of deleted.");
            }

            return back()->with('status', "{$resourceName} is package-owned and was left unchanged because it cannot be deprecated safely.");
        }

        try {
            $model->delete();

            return back()->with('status', "{$resourceName} was deleted.");
        } catch (Throwable $exception) {
            if (method_exists($model, 'markInactive')) {
                $model->markInactive();
                $model->save();

                return back()->with('status', "{$resourceName} could not be deleted because related records exist, so it was deactivated.");
            }

            return back()->withErrors(['delete' => "{$resourceName} could not be deleted: {$exception->getMessage()}"]);
        }
    }
}

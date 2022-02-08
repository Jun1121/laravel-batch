<?php

namespace Jun1121\LaravelBatch;

use Illuminate\Database\Eloquent;
use Illuminate\Database\Query;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;


class AppServiceProvider extends LaravelServiceProvider
{

    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerSimpleBatch();

        Eloquent\Builder::macro('createMany', function (iterable $records): Eloquent\Collection {
            $instances = $this->model->newCollection();
            foreach ($records as $record) {
                $instances->push($this->create($record));
            }
            return $instances;
        });
    }

    /**
     * @return void
     */
    private function registerSimpleBatch(): void
    {
        Eloquent\Builder::macro('simpleBatch', function (string $column, array $values = [], array $option = []): int {
            return SimpleBatch::builder($this, $option)->update($column, $values);
        });

        Query\Builder::macro('simpleBatch', function (string $column, array $values = [], array $option = []): int {
            return SimpleBatch::builder($this, $option)->update($column, $values);
        });
    }
}

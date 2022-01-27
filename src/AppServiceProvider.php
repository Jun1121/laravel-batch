<?php

namespace Jun1121\LaravelBatch;

use Illuminate\Database\Eloquent;
use Illuminate\Database\Query;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use InvalidArgumentException;
use JsonException;


class AppServiceProvider extends LaravelServiceProvider
{

    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerEloquentSimpleBatch();
        $this->registerQuerySimpleBatch();

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
    private function registerEloquentSimpleBatch(): void
    {

        Eloquent\Builder::macro('simpleBatch', function (string $column, array $values = [], array $option = []) {
            $filed      = $this->qualifyColumn($column);
            $identifier = $this->qualifyColumn($option['key'] ?? $this->getModel()->getKeyName());
            return AppServiceProvider::runSimpleBatch($this, $values, $option, $filed, $identifier);
        });
    }


    private function registerQuerySimpleBatch(): void
    {
        Query\Builder::macro('simpleBatch', function (string $column, array $values = [], array $option = []) {
            $table      = last(preg_split('/\s+as\s+/i', $this->from));
            $filed      = $table . '.' . $column;
            $identifier = $table . '.' . ($option['key'] ?? 'id');
            return call_user_func([__CLASS__, 'runSimpleBatch'], $this, $values, $option, $filed, $identifier);
        });
    }

    /**
     * @param $value
     * @return array|false|string|string[]
     * @throws JsonException
     */
    private static function formatValue($value)
    {
        if (is_array($value)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        }

        return $value;
    }


    /**
     * @param Query\Builder| Eloquent\Builder $builder
     * @param array                           $values
     * @param array                           $option
     * @param string                          $filed
     * @param string                          $identifier
     * @return int
     * @throws JsonException
     */
    final public static function runSimpleBatch($builder, array $values, array $option, string $filed, string $identifier): int
    {
        $whens   = [];
        $grammar = $builder->getGrammar();

        foreach ($values as $key => $value) {
            if (isset($option['type']) && in_array($option['type'], ['+', '-', '*', '/', '%'])) {
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException('Non-numeric value passed to increment/decrement method.');
                }

                $whens[] = sprintf("when %s then %s", $key, implode(' ', [$grammar->wrap($filed), $option['type'], $value]));
            } else {
                $whens[] = sprintf("when %s then '%s'", $key, self::formatValue($value));
            }
        }

        if (!isset($option['other'])) {
            return $builder->whereIn($identifier, array_keys($values))->update([
                $filed => DB::raw(sprintf("( case %s %s end )", $grammar->wrap($identifier), implode(' ', $whens)))
            ]);
        }

        return $builder->update([
            $filed => DB::raw(sprintf("( case %s %s else '%s' end )", $grammar->wrap($identifier), implode(' ', $whens), self::formatValue($option['other'])))
        ]);
    }

}

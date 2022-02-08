<?php
declare(strict_types=1);

namespace Jun1121\LaravelBatch\Abstracts;

use Illuminate\Database\Eloquent;
use Illuminate\Database\Query;
use JsonException;

abstract class BatchAbstract
{
    /**
     * @var Eloquent\Builder|Query\Builder
     */
    protected $builder;

    /**
     * @var array
     */
    protected array $option = [];

    /**
     * @param Query\Builder|Eloquent\Builder $builder
     * @param array                          $option
     */
    protected function __construct($builder, array $option = [])
    {
        $this->builder = $builder;
        $this->option  = $option;
    }

    /**
     * @param string $column
     * @param array  $values
     * @return int
     */
    abstract public function update(string $column, array $values = []): int;

    /**
     * @param Query\Builder|Eloquent\Builder $builder
     * @param array                          $option
     * @return $this
     */
    final public static function builder($builder, array $option = []): self
    {
        return new (static::class)($builder, $option);
    }

    /**
     * @param string $column
     * @return string
     */
    protected function wrap(string $column): string
    {
        return $this->builder->getGrammar()->wrap($column);
    }


    /**
     * @param string $column
     * @return string
     */
    protected function qualifyColumn(string $column): string
    {
        if ($this->builder instanceof Eloquent\Builder) {
            return $this->builder->qualifyColumn($column);
        }

        return last(preg_split('/\s+as\s+/i', $this->builder->from)) . '.' . $column;
    }


    /**
     * @param $value
     * @return array|false|string|string[]
     * @throws JsonException
     */
    protected function formatValue($value)
    {
        if (is_array($value)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        }

        return $value;
    }

    /**
     * @return string
     */
    protected function getKeyName(): string
    {
        if (isset($this->option['key'])) {
            return $this->qualifyColumn($this->option['key']);
        }

        if ($this->builder instanceof Eloquent\Builder) {
            return $this->qualifyColumn($this->builder->getModel()->getKeyName());
        }

        return $this->qualifyColumn('id');
    }
}

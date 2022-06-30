<?php
declare(strict_types=1);

namespace Jun1121\LaravelBatch;

use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Jun1121\LaravelBatch\Abstracts\BatchAbstract;

class SimpleBatch extends BatchAbstract
{
    /**
     * @inheritdoc
     * @throws JsonException
     */
    public function update(string $column, $values = []): int
    {
        if ($values instanceof Collection) {
            $values = $values->pluck($column, $this->getKeyName())->toArray();
        }

        if (count($values) === 0) {
            return 0;
        }
        $filed = $this->qualifyColumn($column);
        $whens = [];

        foreach ($values as $key => $value) {
            if (isset($this->option['type']) && in_array($this->option['type'], ['+', '-', '*', '/', '%'])) {
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException('Non-numeric value passed to increment/decrement method.');
                }

                $whens[] = sprintf("when %s then %s", $key, implode(' ', [$this->wrap($filed), $this->option['type'], $value]));
            } else {
                $whens[] = sprintf("when %s then '%s'", $key, $this->formatValue($value));
            }
        }

        return $this->builder->when(!isset($this->option['other']), fn($query) => $query->whereIn($this->getKeyName(), array_keys($values)))->update([$filed => $this->getRaw($whens)]);
    }


    /**
     * @param array $whens
     * @return Expression
     * @throws JsonException
     */
    private function getRaw(array $whens): Expression
    {
        $whereRaw = implode(' ', $whens);

        if (isset($this->option['other'])) {
            return DB::raw(sprintf("( case %s %s else '%s' end )", $this->wrap($this->getKeyName()), $whereRaw, $this->formatValue($this->option['other'])));
        }

        return DB::raw(sprintf("( case %s %s end )", $this->wrap($this->getKeyName()), $whereRaw));
    }
}

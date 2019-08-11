<?php

namespace Snap\Database\Concerns;

use DateTimeInterface;
use Snap\Database\Query;
use WP_Post;

trait QueriesMeta
{
    /**
     * Holds the current meta query params.
     *
     * @var array
     */
    protected $meta_query = [];

    /**
     * Add a meta query.
     *
     * @param string|callable $key          Custom field key, or Callable for nested queries.
     * @param mixed           $value        Custom field value. It can be an array only when compare is 'IN',
     *                                      'NOT IN', 'BETWEEN', or 'NOT BETWEEN'.
     * @param string          $operator     Operator to test. Possible values are '=', '!=', '>', '>=', '<', '<=',
     *                                      'LIKE','NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS'
     *                                      and 'NOT EXISTS'.
     * @param string          $type         Custom field type. Possible values are 'NUMERIC', 'BINARY', 'CHAR', 'DATE',
     *                                      'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'.
     * @return $this
     *
     * @throws \InvalidArgumentException If the supplied operator is not valid.
     */
    public function where($key, $value, string $operator = '=', string $type = 'CHAR')
    {
        if (!\in_array($operator, Query::QUERY_OPERATORS)) {
            throw new \InvalidArgumentException("The operator '$operator' is not valid for a meta query.");
        }

        if (\is_callable($key)) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $child_query = new static($this->name);
            \call_user_func($key, $child_query);
            $this->meta_query[] = $child_query->getMetaQuery();
            return $this;
        }

        $operator = \strtoupper($operator);
        $type = \strtoupper($type);

        $args = [
            'key' => $key,
            'value' => $this->maybeCastValue($value),
        ];

        if ($operator !== '=') {
            $args['compare'] = $operator;
        }

        $guessed = $this->guessComparison($type, $value, $operator);

        if ($guessed !== $type) {
            $args['type'] = $guessed;
        }

        if ($type !== 'CHAR') {
            $args['type'] = $type;
        }

        // They only work without a value being passed.
        if ($operator === 'EXISTS' || $operator === 'NOT EXISTS') {
            unset($args['value']);
        }

        $this->meta_query[] = $args;

        return $this;
    }

    /**
     * Add a meta query, and make the meta query an OR relation.
     *
     * @param string|callable   $key      Custom field key.
     * @param null|string|array $value    Custom field value.
     * @param string            $operator Operator to test.
     * @param string            $type     Custom field type.
     * @return $this
     */
    public function orWhere($key, $value = null, string $operator = '=', string $type = 'CHAR')
    {
        $this->meta_query = ['relation' => 'OR'] + $this->meta_query;
        $this->where($key, $value, $operator, $type);
        return $this;
    }

    /**
     * Perform a EXISTS meta query.
     *
     * @param string $key Meta key.
     * @return $this
     */
    public function whereExists(string $key)
    {
        return $this->where($key, null, 'EXISTS');
    }

    /**
     * Perform a EXISTS meta query as an OR relation.
     *
     * @param string $key Meta key.
     * @return $this
     */
    public function orWhereExists(string $key)
    {
        return $this->orWhere($key, null, 'EXISTS');
    }

    /**
     * Perform a NOT EXISTS meta query.
     *
     * @param string $key Meta key.
     * @return $this
     */
    public function whereNotExists(string $key)
    {
        return $this->where($key, null, 'NOT EXISTS');
    }

    /**
     * Perform a NOT EXISTS meta query as an OR relation.
     *
     * @param string $key Meta key.
     * @return $this
     */
    public function orWhereNotExists(string $key)
    {
        return $this->orWhere($key, null, 'NOT EXISTS');
    }

    /**
     * Get the current meta query params.
     *
     * @return array
     */
    public function getMetaQuery(): array
    {
        return $this->meta_query;
    }

    /**
     * Try to guess the comparison type for a meta query.
     *
     * @param string $comparison The supplied comparison type.
     * @param mixed  $value      The current value.
     * @param string $operator   The current operator.
     * @return string
     */
    private function guessComparison(string $comparison, $value, string $operator): string
    {
        if (\is_array($value) && \in_array($operator, ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN']) && !empty($value)) {
            return $this->guessComparison($comparison, \current($value), $operator);
        }

        if (\is_numeric($value)) {
            return 'NUMERIC';
        }

        if ($value instanceof \DateTimeInterface) {
            return 'DATETIME';
        }

        if (\is_resource($comparison)) {
            return 'BINARY';
        }

        return $comparison;
    }

    /**
     * Sanitize and cast a where value.
     *
     * @param mixed $original_value
     * @return mixed
     */
    private function maybeCastValue($original_value)
    {
        if ($original_value instanceof \DateTimeInterface) {
            return $original_value->format('Y-m-d H:i:s');
        }

        if (\is_bool($original_value)) {
            return (int)$original_value;
        }

        if (\is_array($original_value)) {
            foreach ($original_value as $key => $value) {
                $original_value[$key] = $this->maybeCastValue($value);
            }
        }

        return $original_value;
    }
}

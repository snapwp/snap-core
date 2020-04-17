<?php

namespace Snap\Database\Concerns;

use DateTimeInterface;
use Snap\Database\Query;
use WP_Post;

trait QueriesDate
{
    /**
     * Holds the current date query.
     *
     * @var array
     */
    protected $date_query = ['relation' => 'AND'];

    /**
     *Return posts from the provided date.
     *
     * @param \WP_Post|DateTimeInterface|int $date Date to query against.
     * @return $this
     *
     * @throws \Exception When a bad value for $date is passed.
     */
    public function whereDate($date)
    {
        if (\is_callable($date)) {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $child_query = new static($this->name);
            \call_user_func($date, $child_query);
            $this->date_query[] = $child_query->getDateQuery();
            return $this;
        }

        $date = $this->parseDateParameter($date);

        $this->date_query[] = [
            'year' => (int)$date->format('Y'),
            'month' => (int)$date->format('n'),
            'day' => (int)$date->format('j'),
        ];

        return $this;
    }

    /**
     * Perform a whereDate as an OR relation.
     *
     * @param \WP_Post|DateTimeInterface|int $date Date to query against.
     * @return $this
     * @throws \Exception When a bad value for $date is passed.
     */
    public function orWhereDate($date)
    {
        $this->date_query = ['relation' => 'OR'] + $this->date_query;
        return $this->whereDate($date);
    }

    /**
     * Return posts posted between the provided dates.
     *
     * @param \WP_Post|DateTimeInterface|int $start The starting date.
     * @param \WP_Post|DateTimeInterface|int $end   The end date.
     * @return $this
     *
     * @throws \Exception When a bad date parameter is passed.
     */
    public function whereDateBetween($start, $end)
    {
        $this->whereDateAfter($start);
        return $this->whereDateBefore($end);
    }

    /**
     * Perform a whereDateBetween query with an OR relation.
     *
     * @param WP_Post|DateTimeInterface|int $start The starting date.
     * @param WP_Post|DateTimeInterface|int $end   The end date.
     * @return $this
     *
     * @throws \Exception When a bad date parameter is passed.
     */
    public function orWhereDateBetween($start, $end)
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereDateBetween($start, $end);
    }

    /**
     * Return posts NOT posted between the provided dates.
     *
     * @param \WP_Post|DateTimeInterface|int $start The starting date.
     * @param \WP_Post|DateTimeInterface|int $end   The end date.
     * @return $this
     *
     * @throws \Exception
     */
    public function whereDateNotBetween($start, $end)
    {
        $this->whereDateAfter($end);
        return $this->orWhereDateBefore($start);
    }

    /**
     * Perform a whereDateNotBetween query with an OR relation.
     *
     * @param \WP_Post|\DateTimeInterface|int $start The starting date.
     * @param \WP_Post|\DateTimeInterface|int $end   The end date.
     * @return $this
     *
     * @throws \Exception When a bad date parameter is passed.
     */
    public function orWhereDateNotBetween($start, $end)
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereDateNotBetween($start, $end);
    }

    /**
     * Returns posts created before (not inclusive of) the supplied date.
     *
     * @param \WP_Post|\DateTimeInterface|int $date The date.
     * @return $this
     *
     * @throws \Exception When a bad date parameter is passed.
     */
    public function whereDateBefore($date)
    {
        $this->date_query[] = [
            'before' => $this->parseDateParameter($date)->format('Y-m-d H:i:s'),
        ];

        return $this;
    }

    /**
     * Perform a whereDateBefore query with an OR relation.
     *
     * @param \WP_Post|\DateTimeInterface|int $date The date.
     * @return $this
     *
     * @throws \Exception When a bad date parameter is passed.
     */
    public function orWhereDateBefore($date)
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereDateBefore($date);
    }

    /**
     * Returns posts created after (inclusive of) the supplied date.
     *
     * @param \WP_Post|\DateTimeInterface|int $date The date.
     * @return $this
     *
     * @throws \Exception When a bad date parameter is passed.
     */
    public function whereDateAfter($date)
    {
        $this->date_query[] = [
            'after' => $this->parseDateParameter($date)->format('Y-m-d H:i:s'),
        ];

        return $this;
    }

    /**
     * Perform a whereDateAfter query with an OR relation.
     *
     * @param \WP_Post|\DateTimeInterface|int $date The date.
     * @return $this
     *
     * @throws \Exception When a bad date parameter is passed.
     */
    public function orWhereDateAfter($date)
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereDateAfter($date);
    }

    /**
     * Perform a year query.
     *
     * @param int    $year     The year to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function whereYear(int $year, string $operator = '=')
    {
        return $this->addToCurrentDateQuery($operator, ['year' => $year]);
    }

    /**
     * Perform a whereYear query as an OR relation.
     *
     * @param int    $year     The year to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function orWhereYear(int $year, string $operator = '=')
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereYear($year, $operator);
    }

    /**
     * Perform a month query.
     *
     * @param int    $month    The month to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function whereMonth(int $month, string $operator = '=')
    {
        return $this->addToCurrentDateQuery($operator, ['month' => $month]);
    }

    /**
     * Perform a whereMonth query as an OR relation.
     *
     * @param int    $month    The month to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function orWhereMonth(int $month, string $operator = '=')
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereMonth($month, $operator);
    }

    /**
     * Perform a day query.
     *
     * @param int    $day      The day to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function whereDay(int $day, string $operator = '=')
    {
        return $this->addToCurrentDateQuery($operator, ['day' => $day]);
    }

    /**
     * Perform a whereDay query as an OR relation.
     *
     * @param int    $day      The day to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function orWhereDay(int $day, string $operator = '=')
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereDay($day, $operator);
    }

    /**
     * Perform a hour query.
     *
     * @param int    $hour     The hour to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function whereHour(int $hour, string $operator = '=')
    {
        return $this->addToCurrentDateQuery($operator, ['hour' => $hour]);
    }

    /**
     * Perform a whereHour query as an OR relation.
     *
     * @param int    $hour     The hour to check against.
     * @param string $operator The operator.
     * @return $this
     */
    public function orWhereHour(int $hour, string $operator = '=')
    {
        $this->date_query = ['relation' => 'OR'] + [$this->date_query];
        return $this->whereHour($hour, $operator);
    }

    /**
     * Returns the current date_query.
     *
     * @return array
     */
    public function getDateQuery()
    {
        return $this->date_query;
    }

    /**
     * Parses a $date param.
     *
     * @param \WP_Post|\DateTimeInterface|int $date Date to parse.
     * @return \DateTime
     *
     * @throws \Exception When a bad value for $date is passed.
     */
    private function parseDateParameter($date): \DateTime
    {
        if ($date instanceof WP_Post) {
            $date = new \DateTime($date->post_date);
        } elseif (\is_numeric($date) && ($date <= PHP_INT_MAX) && ($date >= ~PHP_INT_MAX)) {
            $date = new \DateTime('@' . $date);
        }

        if (!\is_a($date, DateTimeInterface::class)) {
            throw new \BadMethodCallException('PostQuery::createdOn expects a valid DateTimeInterface, WP_Post, or a valid timestamp.');
        }
        return $date;
    }

    /**
     * Shorthand for adding a year/month etc args array tot he current date query.
     *
     * @param string $operator The current operator.
     * @param array  $args     Args to add.
     * @return $this
     */
    private function addToCurrentDateQuery(string $operator, array $args)
    {
        if ($operator !== '=' && \in_array($operator, Query::QUERY_OPERATORS)) {
            $args['compare'] = $operator;
        }

        $this->date_query[] = $args;

        return $this;
    }
}

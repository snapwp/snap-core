<?php

namespace Snap\Database;

abstract class Query
{
    /**
     * The query parameters.
     *
     * @var array
     */
    protected $params = [];

    /**
     * All valid meta query operators.
     */
    public const QUERY_OPERATORS = [
        '=',
        '!=',
        '>',
        '>=',
        '<',
        '<=',
        'LIKE',
        'NOT LIKE',
        'IN',
        'NOT IN',
        'BETWEEN',
        'NOT BETWEEN',
        'EXISTS',
        'NOT EXISTS',
    ];

    /**
     * The object name(s) being queried.
     *
     * @var string|string[]
     */
    protected $name;

    /**
     * PostQuery constructor.
     *
     * @param string $type The object name being queried.
     */
    public function __construct($type)
    {
        $this->name = $type;
    }

    /**
     * Set ordering params.
     *
     * @param string $order_by What field to order by.
     * @param string $order    Direction of ordering: ASC or DESC.
     * @return $this
     */
    public function orderBy(string $order_by, string $order = 'ASC')
    {
        $this->params['orderby'] = $order_by;
        $this->params['order'] = \strtoupper($order);
        return $this;
    }
}

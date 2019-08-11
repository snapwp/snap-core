<?php

namespace Snap\Services;

/**
 * PostQuery service facade.
 *
 * @method static false|\WP_Post first()
 * @method static \Snap\Utils\Collection get()
 * @method static \WP_Query getWPQuery()
 * @method static \Snap\Utils\Collection all()
 * @method static int count()
 * @method static false|\WP_Term|\Snap\Utils\Collection find(string|string[]|int|int[] $search)
 *
 * @method static \Snap\Database\PostQuery withStatus(string|string[]|int|int[] $status)
 * @method static \Snap\Database\PostQuery withSticky()
 *
 * @method static \Snap\Database\PostQuery whereTaxonomy(string|callable $key, int|string|array $terms = '', string $operator = 'IN', bool $include_children = true)
 * @method static \Snap\Database\PostQuery orWhereTaxonomy(string|callable $key, int|string|array $terms = '', string $operator = 'IN', bool $include_children = true)
 * @method static \Snap\Database\PostQuery whereTerms($objects, string $operator = 'IN', bool $include_children = true)
 * @method static \Snap\Database\PostQuery orWhereTerms($objects, string $operator = 'IN', bool $include_children = true)
 *
 * @method static \Snap\Database\PostQuery whereAuthor(int|int[]|\WP_User|\WP_User[] $author)
 * @method static \Snap\Database\PostQuery whereAuthorNot(int|int[]|\WP_User|\WP_User[] $author)
 * @method static \Snap\Database\PostQuery whereLike(string $search)
 * @method static \Snap\Database\PostQuery whereExact(string $search)
 * @method static \Snap\Database\PostQuery whereSlug(string|string[] $slug)
 *
 * @method static \Snap\Database\PostQuery WhereDate(\WP_Post|\DateTimeInterface|int $date)
 * @method static \Snap\Database\PostQuery orWhereDate(\WP_Post|\DateTimeInterface|int $date)
 * @method static \Snap\Database\PostQuery whereDateBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static \Snap\Database\PostQuery orWhereDateBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static \Snap\Database\PostQuery whereDateNotBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static \Snap\Database\PostQuery orWhereDateNotBetween(\WP_Post|\DateTimeInterface|int $start, \WP_Post|\DateTimeInterface|int$end)
 * @method static \Snap\Database\PostQuery whereDateBefore(\WP_Post|\DateTimeInterface|int $date)
 * @method static \Snap\Database\PostQuery orWhereDateBefore(\WP_Post|\DateTimeInterface|int $date)
 * @method static \Snap\Database\PostQuery whereDateAfter(\WP_Post|\DateTimeInterface|int $date)
 * @method static \Snap\Database\PostQuery orWhereDateAfter(\WP_Post|\DateTimeInterface|int $date)
 * @method static \Snap\Database\PostQuery whereYear(int $year, string $operator = '=')
 * @method static \Snap\Database\PostQuery orWhereYear(int $year, string $operator = '=')
 * @method static \Snap\Database\PostQuery whereMonth(int $month, string $operator = '=')
 * @method static \Snap\Database\PostQuery orWhereMonth(int $month, string $operator = '=')
 * @method static \Snap\Database\PostQuery whereDay(int $day, string $operator = '=')
 * @method static \Snap\Database\PostQuery orWhereDay(int $day, string $operator = '=')
 * @method static \Snap\Database\PostQuery whereHour(int $hour, string $operator = '=')
 * @method static \Snap\Database\PostQuery orWhereHour(int $hour, string $operator = '=')
 *
 * @method static \Snap\Database\PostQuery childOf(int|int[]|\WP_Post|\WP_Post[]$post_ids)
 * @method static \Snap\Database\PostQuery notChildOf(int|int[]|\WP_Post|\WP_Post[]$post_ids)
 * @method static \Snap\Database\PostQuery in(int|int[] $ids)
 * @method static \Snap\Database\PostQuery exclude(int|int[] $ids)
 *
 * @method static \Snap\Database\PostQuery orderBy(string $order_by, string $order = 'ASC')
 * @method static \Snap\Database\PostQuery limit(int $amount)
 * @method static \Snap\Database\PostQuery offset(int $amount)
 * @method static \Snap\Database\PostQuery page(int $page)
 */
class PostQuery
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Database\PostQuery::class;
    }
}

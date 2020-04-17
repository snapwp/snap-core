<?php

namespace Snap\Services;

/**
 * TaxQuery service facade.
 *
 * @method static \Snap\Database\TaxQuery tax(array|int $type)
 *
 * @method static \Tightenco\Collect\Support\Collection all()
 * @method static \Tightenco\Collect\Support\Collection get()
 * @method static \Tightenco\Collect\Support\Collection|false|\WP_Term find(int|int[]|string|string[] $ids)
 * @method static array getNames()
 * @method static array getIds()
 * @method static array getSlugs()
 * @method static \WP_Term_Query getQueryObject()
 * @method static false|\WP_Term first()
 * @method static int count()
 *
 * @method static \Snap\Database\TaxQuery for(int|\WP_Post|int[]|\WP_Post[] $object_ids)
 * @method static \Snap\Database\TaxQuery hideEmpty()
 * @method static \Snap\Database\TaxQuery includeEmpty()
 *
 * @method static \Snap\Database\TaxQuery childOf(int|\WP_Term $parent)
 * @method static \Snap\Database\TaxQuery notChildOf(int|int[]|\WP_Term|\WP_Term[] $term_ids)
 * @method static \Snap\Database\TaxQuery directChildOf(int|\WP_Term $parent)
 * @method static \Snap\Database\TaxQuery childless()
 *
 * @method static \Snap\Database\TaxQuery in(int|int[] $term_ids)
 * @method static \Snap\Database\TaxQuery exclude(int|int[] $term_ids)
 *
 * @method static \Snap\Database\TaxQuery where(string|callable $key, $value, string $operator = '=', string $type = 'CHAR')
 * @method static \Snap\Database\TaxQuery orWhere(string|callable $key, $value, string $operator = '=', string $type = 'CHAR')
 * @method static \Snap\Database\TaxQuery whereExists(string $key)
 * @method static \Snap\Database\TaxQuery orWhereExists(string $key)
 * @method static \Snap\Database\TaxQuery whereNotExists(string $key)
 * @method static \Snap\Database\TaxQuery orWhereNotExists(string $key)
 *
 * @method static \Snap\Database\TaxQuery whereName(string|string[] $names)
 * @method static \Snap\Database\TaxQuery whereNameLike(string $name)
 * @method static \Snap\Database\TaxQuery whereSlug(string|string[] $slugs)
 * @method static \Snap\Database\TaxQuery whereTaxonomyId(int|int[] $ids)
 * @method static \Snap\Database\TaxQuery whereLike(string $search)
 * @method static \Snap\Database\TaxQuery whereDescriptionLike(string $name)
 *
 * @method static \Snap\Database\TaxQuery orderBy(string $order_by, string $order = 'ASC')
 * @method static \Snap\Database\TaxQuery limit(int $amount)
 * @method static \Snap\Database\TaxQuery offset(int $amount)
 */
class TaxQuery
{
    use ProvidesServiceFacade;

    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Database\TaxQuery::class;
    }
}

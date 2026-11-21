<?php

namespace Snap\Hookables\Content\Concerns;

/**
 * Trait to interact with ACF meta.
 *
 * Loads ACF values one field at a time, as they are asked for, and remembers them per object and key for the rest of
 * the request. Loading every field on an object with get_fields() for a single key, and only remembering the last
 * object, made each magic attribute read in a loop over posts cost a full ACF load.
 */
trait InteractsWithAcf
{
    /**
     * Formatted ACF values, keyed by object ID then meta key. A key holding null was looked up and is not an ACF
     * field with a value on that object.
     *
     * @var array<int, array<string, array{0: mixed}|null>>
     */
    protected static array $acfValues = [];

    /**
     * Object ID + key pairs currently loading, so formatting a value can't recurse into itself.
     *
     * @var array<string, true>
     */
    protected static array $acfLoading = [];

    /**
     * Whether the cache-clearing hooks are in place.
     */
    protected static bool $acfCacheHooked = false;

    /**
     * The formatted ACF value of $meta_key on a post, as get_fields() would return it, loaded for that key alone.
     *
     * Returns null (the key isn't found) when ACF isn't active, the post has no value for the key, or the key isn't
     * the name of a top-level ACF field: the same rules get_fields() uses (a `_{$meta_key}` reference to a field key
     * whose field name matches).
     *
     * @return array{0: mixed}|null The value wrapped in an array (it may itself be null or false), or null if not found.
     */
    protected function findAcfValue(int $object_id, string $meta_key): ?array
    {
        if (!\class_exists('ACF')) {
            return null;
        }

        $this->hookAcfCacheClearing();

        if (\array_key_exists($meta_key, static::$acfValues[$object_id] ?? [])) {
            return static::$acfValues[$object_id][$meta_key];
        }

        $loading = $object_id . ':' . $meta_key;

        if (isset(static::$acfLoading[$loading])) {
            return null;
        }

        static::$acfLoading[$loading] = true;

        try {
            $found = null;

            // All of the post's meta, from the meta cache. The empty key returns before the accessors run.
            $meta = \get_post_meta($object_id);
            $reference = $meta["_{$meta_key}"][0] ?? null;

            if (isset($meta[$meta_key]) && (\is_string($reference) || \is_numeric($reference))) {
                $field = \acf_get_field($reference);

                if ($field && $field['name'] === $meta_key) {
                    $found = [\acf_format_value(\acf_get_value($object_id, $field), $object_id, $field)];
                }
            }

            static::$acfValues[$object_id][$meta_key] = $found;

            return $found;
        } finally {
            unset(static::$acfLoading[$loading]);
        }
    }

    /**
     * Shape an ACF value for returning from a magic attribute: associative arrays become objects, lists Collections.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function prepareAcfValue($value)
    {
        $objectified = $this->convertToObject($value);

        if (\is_array($objectified)) {
            return collect($objectified);
        }

        return $objectified;
    }

    /**
     * Forget an object's cached ACF values.
     */
    public static function forgetAcfValues($object_id): void
    {
        if (\is_numeric($object_id)) {
            unset(static::$acfValues[(int) $object_id]);
        }
    }

    /**
     * Forget cached values whenever an object's meta changes or it is saved through ACF.
     */
    private function hookAcfCacheClearing(): void
    {
        if (static::$acfCacheHooked) {
            return;
        }

        static::$acfCacheHooked = true;

        $forget = [static::class, 'forgetAcfValues'];

        foreach (['added_post_meta', 'updated_post_meta', 'deleted_post_meta'] as $hook) {
            \add_action($hook, fn ($meta_ids, $object_id) => \call_user_func($forget, $object_id), 10, 2);
        }

        \add_action('clean_post_cache', $forget);
        \add_action('acf/save_post', $forget, 1);
    }

    /**
     * Convert ACF data structure returned from get_field into objects where possible.
     *
     * @param mixed $data
     * @return array|mixed|object
     */
    private function convertToObject($data)
    {
        if (!\is_array($data)) {
            return $data;
        }

        if ($data === [] || \is_numeric(\key($data))) {
            return \array_map([$this, 'convertToObject'], $data);
        }

        return (object)\array_map([$this, 'convertToObject'], $data);
    }
}

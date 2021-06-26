<?php

namespace Snap\Hookables\Content\Concerns;

/**
 * Trait to interact with ACF meta.
 */
trait InteractsWithAcf
{
    /**
     * Cached ACF field data.
     */
    protected ?array $acfData = null;

    /**
     * Current object ID
     */
    protected ?int $acfObjectId = null;

    /**
     * Try to prime the ACF data cache.
     */
    protected function primeAcfCache(int $object_id): void
    {
        if ($this->acfObjectId !== $object_id && class_exists('ACF')) {
            $fields = get_fields($object_id);

            if (!empty($fields)) {
                $this->acfData = $fields;
            } else {
                $this->acfData = null;
            }

            $this->acfObjectId = $object_id;
        }
    }

    /**
     * Check if a meta key exists within the ACF cache.
     */
    protected function hasKeyInAcfCache(string $meta_key): bool
    {
        return $this->acfData && array_key_exists($meta_key, $this->acfData);
    }

    /**
     * Fetch data from the ACF cache for a given key.
     */
    protected function getKeyFromAcfCache(string $meta_key)
    {
        $objectified = $this->convertToObject($this->acfData[$meta_key]);

        if (is_array($objectified)) {
            return collect($objectified);
        }

        return $objectified;
    }

    /**
     * Convert ACF data structure returned from get_field into objects where possible.
     *
     * @param mixed $data
     * @return array|mixed|object
     */
    private function convertToObject($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        if (is_numeric(key($data))) {
            return array_map([$this, 'convertToObject'], $data);
        }

        return (object)array_map([$this, 'convertToObject'], $data);
    }
}

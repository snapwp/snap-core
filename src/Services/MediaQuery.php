<?php

namespace Snap\Services;

/**
 * MediaQuery service facade.
 *
 * @method static \Snap\Database\MediaQuery whereType(string $type)
 * @method static \Snap\Database\MediaQuery whereExtension(string|string[] $extensions)
 */
class MediaQuery extends PostQuery
{
    /**
     * Specify the underlying root class.
     *
     * @return string
     */
    protected static function getServiceName(): string
    {
        return \Snap\Database\MediaQuery::class;
    }
}

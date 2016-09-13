<?php

namespace SRL\Language\Helpers;

use SRL\Builder;

/**
 * Temporary cache for already built SRL queries to speed up loops.
 */
class Cache
{
    /** @var Builder[] */
    protected static $cache = [];

    /**
     * Add Builder for SRL to cache.
     *
     * @param string $srl
     * @param Builder $builder
     */
    public static function add(string $srl, Builder $builder)
    {
        static::$cache[$srl] = $builder;
    }

    /**
     * Validate if current SRL is already in cache.
     *
     * @param string $srl
     * @return bool
     */
    public static function has(string $srl) : bool
    {
        return isset(static::$cache[$srl]);
    }

    /**
     * Get SRL from cache, or return new Builder.
     *
     * @param string $srl
     * @return Builder
     */
    public static function get(string $srl) : Builder
    {
        return static::$cache[$srl] ?? new Builder;
    }
}

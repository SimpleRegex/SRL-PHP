<?php

namespace SRL;

use SRL\Exceptions\ImplementationException;

/**
 * @method static \SRL\Builder startsWith()
 * @method static \SRL\Builder literally($chars)
 * @mixin \SRL\Builder
 */
class SRL
{
    /**
     * Call each method on a new Builder object.
     *
     * @param $name
     * @param $arguments
     * @return mixed|Builder
     * @throws ImplementationException
     */
    public function __call($name, $arguments)
    {
        return static::__callStatic($name, $arguments);
    }

    /**
     * Call each method on a new Builder object.
     *
     * @param $name
     * @param $arguments
     * @return mixed|Builder
     * @throws ImplementationException
     */
    public static function __callStatic(string $name, array $arguments = [])
    {
        $builder = new Builder;

        if (!is_callable([$builder, $name])) {
            throw new ImplementationException(sprintf(
                'Call to undefined or invalid method %s:%s()',
                get_class($builder),
                $name
            ));
        }

        return $builder->$name(...$arguments);
    }
}
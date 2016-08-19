<?php

namespace Tests;

use ReflectionClass;
use SRL\Builder;
use SRL\SRL;

class ExceptionsTest extends TestCase
{
    /**
     * @expectedException  \SRL\Exceptions\ImplementationException
     * @expectedExceptionMessage Call to undefined or invalid method SRL\Builder:methodDoesNotExist()
     */
    public function testInvalidSRLMethod()
    {
        SRL::methodDoesNotExist();
    }

    /**
     * @expectedException  \SRL\Exceptions\PregException
     * @expectedExceptionMessage Internal PCRE error.
     */
    public function testInvalidGetMatches()
    {
        SRL::literally('foo')->getMatches('foo', 10);
    }

    /**
     * @expectedException  \SRL\Exceptions\PregException
     * @expectedExceptionMessage Internal PCRE error.
     */
    public function testInvalidMatches()
    {
        SRL::literally('foo')->once()->isMatching('foo', 0, 10);
    }

    /**
     * @expectedException  \SRL\Exceptions\BuilderException
     * @expectedExceptionMessage Unknown mapper.
     */
    public function testInvalidMapper()
    {
        $method = (new ReflectionClass(Builder::class))->getMethod('addFromMapper');
        $method->setAccessible(true);
        $method->invokeArgs(new Builder, ['invalid_mapper']);
    }

    /**
     * @expectedException  \SRL\Exceptions\ImplementationException
     * @expectedExceptionMessage Cannot apply laziness at this point.
     */
    public function testInvalidLaziness()
    {
        SRL::literally('foo')->lazy();
    }

    /**
     * @expectedException  \SRL\Exceptions\BuilderException
     * @expectedExceptionMessage Adding raw would invalidate this regular expression. Reverted.
     */
    public function testInvalidRaw()
    {
        SRL::literally('foo')->raw('ba)r');
    }
}
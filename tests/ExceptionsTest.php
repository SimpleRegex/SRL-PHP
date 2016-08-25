<?php

namespace SRLTests;

use ReflectionClass;
use SRL\Builder;
use SRL\Language\Helpers\ParenthesesParser;
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
        SRL::literally('foo')->firstMatch();
    }

    /**
     * @expectedException  \SRL\Exceptions\BuilderException
     * @expectedExceptionMessage Adding raw would invalidate this regular expression. Reverted.
     */
    public function testInvalidRaw()
    {
        SRL::literally('foo')->raw('ba)r');
    }

    /**
     * @expectedException  \SRL\Exceptions\SyntaxException
     * @expectedExceptionMessage Non-matching parenthesis found.
     */
    public function testInvalidParenthesis()
    {
        $parser = new ParenthesesParser('foo ( bar');
        $parser->parse();
    }

    /**
     * @expectedException  \SRL\Exceptions\SyntaxException
     * @expectedExceptionMessage Non-matching parenthesis found.
     */
    public function testOtherInvalidParenthesis()
    {
        $parser = new ParenthesesParser('foo ) bar');
        $parser->parse();
    }

    /**
     * @expectedException  \SRL\Exceptions\SyntaxException
     * @expectedExceptionMessage Invalid string ending found.
     */
    public function testInvalidStringEnding()
    {
        $parser = new ParenthesesParser('foo "bar');
        $parser->parse();
    }

    /**
     * @expectedException  \SRL\Exceptions\ImplementationException
     * @expectedExceptionMessage Method `onceOrMore` is not allowed at the beginning.
     */
    public function testInvalidMethodCallOne()
    {
        SRL::onceOrMore();
    }

    /**
     * @expectedException  \SRL\Exceptions\ImplementationException
     * @expectedExceptionMessage Method `neverOrMore` is not allowed after a quantifier.
     */
    public function testInvalidMethodCallTwo()
    {
        SRL::literally('foo')->twice()->neverOrMore();
    }
}
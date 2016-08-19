<?php

namespace Tests;

use SRL\Language\Helpers\ParenthesesParser;

class ParenthesesParserTest extends TestCase
{
    public function testDefault()
    {
        $this->assertEquals([
            'foo',
            ['bar'],
            'baz'
        ], (new ParenthesesParser('foo (bar) baz'))->parse());

        $this->assertEquals([
            'foo',
            ['bar'],
            'baz'
        ], (new ParenthesesParser('(foo (bar) baz)'))->parse());

        $this->assertEquals([
            'foo',
            [
                'bar',
                ['nested']
            ],
            'baz'
        ], (new ParenthesesParser('foo (bar (nested)) baz'))->parse());

        $this->assertEquals([
            'foo boo',
            ['bar', ['nested']],
            'baz',
            ['bar', ['foo foo']]
        ], (new ParenthesesParser('foo boo (bar (nested)) baz (bar (foo foo))'))->parse());
    }

    public function testEscaping()
    {
        $parser = new ParenthesesParser('foo (bar "(bla)") baz');

        $this->assertEquals([
            'foo',
            ['bar "(bla)"'],
            'baz'
        ], $parser->parse());

        $this->assertEquals([
            'bar "(b\"la)" baz'
        ], $parser->setString('bar "(b\"la)" baz')->parse());

        $this->assertEquals([
            'foo',
            ["bar '(b\\'la)'"],
            'baz'
        ], $parser->setString("foo (bar '(b\\'la)') baz")->parse());

        $this->assertEquals([
            'bar "b\\\"', ['la'], 'baz'
        ], $parser->setString('bar "b\\\" (la) baz')->parse());
    }

    public function testEmptyString()
    {
        $this->assertEquals([''], (new ParenthesesParser(''))->parse());
    }
}
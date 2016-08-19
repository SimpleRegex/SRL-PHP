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
            'foo',
            ['bar']
        ], (new ParenthesesParser('foo (bar)'))->parse());

        $this->assertEquals([
            ['foo'],
            'bar'
        ], (new ParenthesesParser('(foo)bar'))->parse());

        $this->assertEquals([
            'foo boo',
            ['bar', ['nested'], 'something'],
            'baz',
            ['bar', ['foo foo']]
        ], (new ParenthesesParser('foo boo (bar (nested) something) baz (bar (foo foo))'))->parse());

        $this->assertEquals(['foo', ['0']], (new ParenthesesParser('foo (0)'))->parse());
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

        $this->assertEquals([
            'foo boo',
            ['bar', ['nes"ted) s\"om\""'], 'ething'],
            'baz',
            ['bar', ['foo foo']]
        ], (new ParenthesesParser('foo boo (bar (nes"ted) s\"om\"")ething) baz (bar (foo foo))'))->parse());
    }

    public function testEmptyStrings()
    {
        $this->assertEquals([''], (new ParenthesesParser(''))->parse());
    }
}
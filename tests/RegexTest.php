<?php

namespace SRLTests;

use SRL\Builder;
use SRL\SRL;

class RegexTest extends TestCase
{
    public function testTrimmingWhitespaces()
    {
        $regEx = SRL::eitherOf(function (Builder $query) {
            $query->and(function (Builder $query) {
                $query->startsWith()->whitespace()->onceOrMore();
            })->and(function (Builder $query) {
                $query->whitespace()->onceOrMore()->mustEnd();
            });
        });

        $this->assertEquals('example', $regEx->replace('', ' example  '));
        $this->assertEquals('example', $regEx->replace('', 'example'));
        $this->assertEquals('exam ple', $regEx->replace('', ' exam ple  '));
    }

    public function testHtmlTagGrabber()
    {
        $regEx = SRL::literally('<')->capture(function (Builder $query) {
            $query->letter()->onceOrMore();
        }, 'name')->any()->neverOrMore()->until('>')->capture(function (Builder $query) {
            $query->any()->onceOrMore();
        }, 'content')->until('<')->caseInsensitive();

        $results = $regEx->getMatches('<foo bla="gedoens">bar</foo><baz>baz</baz>');

        $this->assertEquals('foo', $results[0]->get('name'));
        $this->assertEquals('bar', $results[0]->get('content'));
        $this->assertEquals('baz', $results[1]->get('name'));
        $this->assertEquals('baz', $results[1]->get('content'));
    }

    public function testAnonymousHtmlTagGrabber()
    {
        $regEx = SRL::literally('<')->capture(function (Builder $query) {
            $query->letter()->onceOrMore();
        })->any()->neverOrMore()->until('>')->capture(function (Builder $query) {
            $query->any()->onceOrMore();
        })->until('<')->caseInsensitive();

        $results = $regEx->getMatches('<foo bla="gedoens">bar</foo><baz>baz</baz>');

        $this->assertEquals(['foo', 'bar'], $results[0]->all());
        $this->assertEquals(['baz', 'baz'], $results[1]->all());
    }
}
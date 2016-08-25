<?php

namespace SRLTests;

use SRL\Builder;
use SRL\SRL;

class BuilderTest extends TestCase
{
    public function testSimplePhoneNumberFormat()
    {
        $regex = SRL::startsWith()
            ->literally('+')
            ->number()->between(1, 3)
            ->literally(' ')
            ->number()->between(3, 4)
            ->literally('-')
            ->number()->onceOrMore()
            ->mustEnd()->get();

        $this->assertEquals(1, preg_match($regex, '+49 123-45'));
        $this->assertEquals(1, preg_match($regex, '+492 1235-4'));
        $this->assertEquals(0, preg_match($regex, '+49 123 45'));
        $this->assertEquals(0, preg_match($regex, '49 123-45'));
        $this->assertEquals(0, preg_match($regex, 'a+49 123-45'));
        $this->assertEquals(0, preg_match($regex, '+49 123-45b'));
    }

    public function testSimpleEmailFormat()
    {
        $regex = SRL::startsWith()
            ->eitherOf(function (Builder $query) {
                $query->number()
                    ->letter()
                    ->oneOf('._%+-');
            })->onceOrMore()
            ->literally('@')
            ->eitherOf(function (Builder $query) {
                $query->number()
                    ->letter()
                    ->oneOf('.-');
            })->onceOrMore()
            ->literally('.')
            ->letter()->atLeast(2)
            ->mustEnd()
            ->caseInsensitive(); // Not using get to test __toString() method

        $this->assertTrue($regex->isValid());
        $this->assertEquals(1, preg_match($regex, 'sample@example.com'));
        $this->assertEquals(1, preg_match($regex, 'super-He4vy.add+ress@top-Le.ve1.domains'));
        $this->assertEquals(0, preg_match($regex, 'sample.example.com'));
        $this->assertEquals(0, preg_match($regex, 'missing@tld'));
        $this->assertEquals(0, preg_match($regex, 'hav ing@spac.es'));
        $this->assertEquals(0, preg_match($regex, 'no@pe.123'));
        $this->assertEquals(0, preg_match($regex, 'invalid@email.com123'));

        $this->assertTrue($regex->isMatching('super-He4vy.add+ress@top-Le.ve1.domains'));
        $this->assertFalse($regex->isMatching('sample.example.com'));
    }

    public function testCaptureGroup()
    {
        $query = SRL::literally('colo')
            ->optional('u')
            ->literally('r')
            ->eitherOf(function (Builder $query) {
                $query->literally(':')->and(function (Builder $query) {
                    $query->literally(' is');
                });
            })
            ->whitespace()
            ->capture(function (Builder $query) {
                $query->letter()->onceOrMore();
            }, 'color')
            ->literally('.');

        $this->assertTrue($query->isMatching('my favorite color: blue.'));
        $this->assertTrue($query->isMatching('my favorite colour is green.'));
        $this->assertFalse($query->isMatching('my favorite colour is green!'));

        $matches = $query->getMatches('my favorite colour is green. And my favorite color: yellow.');

        $this->assertCount(2, $matches);
        $this->assertEquals('green', $matches[0]->get('color'));
        $this->assertEquals('yellow', $matches[1]->get('color'));

        $this->assertEquals('green', $query->getMatch('my favorite colour is green. And my favorite color: yellow.')->get('color'));
    }

    public function testReplace()
    {
        $query = SRL::capture(function (Builder $query) {
            $query->anyCharacter()->onceOrMore();
        })->whitespace()->capture(function (Builder $query) {
            $query->number()->onceOrMore();
        })->literally(', ')->capture(function (Builder $query) {
            $query->number()->onceOrMore();
        })->caseInsensitive();

        $this->assertEquals('April 1, 2003', $query->replace('${1} 1, $3', 'April 15, 2003', -1, $count));
        $this->assertEquals(1, $count);
    }

    public function testFilter()
    {
        $this->assertEquals(
            [2 => 'A:A', 3 => 'A:B'],
            SRL::uppercaseLetter()->filter('A:$0', ['1', 'a', 'A', 'B'], -1, $count)
        );
        $this->assertEquals(2, $count);
    }

    public function testReplaceCallback()
    {
        $query = SRL::capture(function (Builder $query) {
            $query->anyCharacter()->onceOrMore();
        })->whitespace()->capture(function (Builder $query) {
            $query->number()->onceOrMore();
        })->literally(', ')->capture(function (Builder $query) {
            $query->number()->onceOrMore();
        })->caseInsensitive();

        $this->assertEquals('invoked', $query->replace(function ($params) {
            $this->assertEquals(['April 15, 2003', 'April', '15', '2003'], $params);

            return 'invoked';
        }, 'April 15, 2003', -1, $count));
        $this->assertEquals(1, $count);
    }

    public function testSplit()
    {
        $this->assertEquals(
            ['sample,one', 'two', 'three'],
            SRL::literally(',')->twice()->whitespace()->optional()->split('sample,one,, two,,three')
        );
    }

    public function testLaziness()
    {
        $this->assertEquals(
            ['sample,one', ' two', 'three'],
            SRL::literally(',')->twice()->whitespace()->optional()->firstMatch()->split('sample,one,, two,,three')
        );
    }

    public function testRaw()
    {
        $this->assertTrue(SRL::literally('foo')->raw('b[a-z]r')->isValid());
    }
}
<?php

namespace SRLTests;

use SRL\SRL;

class LanguageInterpreterTest extends TestCase
{
    public function testParser()
    {
        $srl = new SRL('aNy Character ONCE or more literAlly "fO/o"');
        $this->assertEquals('/\w+(?:fO\/o)/', $srl->get());

        $srl = new SRL('begin with literally "http", optional "s", literally "://", optional "www.",' .
            'anything once or more, literally ".com", must end');
        $this->assertEquals('/^(?:http)(?:(?:s))?(?:\:\/\/)(?:(?:www\.))?.+(?:\.com)$/', $srl->get());
        $this->assertTrue($srl->isMatching('http://www.ebay.com'));
        $this->assertTrue($srl->isMatching('https://google.com'));
        $this->assertFalse($srl->isMatching('htt://google.com'));
        $this->assertFalse($srl->isMatching('http://.com'));

        $srl = new SRL('begin with capture (digit from 0 to 8 once or more) as "number" if followed by "foo"');
        $this->assertEquals('/^(?<number>[0-8]+)(?=(?:foo))/', $srl->get());
        $this->assertTrue($srl->isMatching('142foo'));
        $this->assertFalse($srl->isMatching('149foo'));
        $this->assertFalse($srl->isMatching('14bar'));
        $this->assertEquals('142', $srl->getMatch('142foo')->get('number'));

        $srl = new SRL('literally "colo", optional "u", literally "r"');
        $this->assertEquals(1, preg_match($srl, 'color'));
        $this->assertTrue($srl->isMatching('colour'));

        $srl = new SRL('starts with number from 0 to 5 between 3 and 5 times, must end');
        $this->assertTrue($srl->isMatching('015'));
        $this->assertTrue($srl->isMatching('44444'));
        $this->assertFalse($srl->isMatching('444444'));
        $this->assertFalse($srl->isMatching('1'));
        $this->assertFalse($srl->isMatching('563'));

        $srl = new SRL('starts with digit exactly 2 times, letter at least 3 time');
        $this->assertEquals('/^[0-9]{2}[a-z]{3,}/', $srl->get());
        $this->assertTrue($srl->isMatching('12abc'));
        $this->assertTrue($srl->isMatching('12abcd'));
        $this->assertFalse($srl->isMatching('123abc'));
        $this->assertFalse($srl->isMatching('1a'));
        $this->assertFalse($srl->isMatching(''));

        $srl = new SRL('starts with not digit, not digit from 0 to 2, not number, not number from 3 to 5  ');
        $this->assertEquals('/^[^0-9][^0-2][^0-9][^3-5]/', $srl->get());

        $srl = new SRL('starts with not letter, not uppercase letter, not letter from f to o, not uppercase letter from O to z');
        $this->assertEquals('/^[^a-z][^A-Z][^f-o][^O-z]/', $srl->get());

        $srl = new SRL('starts with not one of "!@#/"');
        $this->assertEquals('/^[^\!@#\/]/', $srl->get());

        $srl = new SRL('backslash');
        $this->assertEquals('/\\\\/', $srl->get());
        $this->assertTrue($srl->isMatching('\\'));
        $this->assertFalse($srl->isMatching('/'));
    }

    public function testEmail()
    {
        $regex = new SRL('begin with any of (digit, letter, one of "._%+-") once or more,' .
            'literally "@", either of (digit, letter, one of ".-") once or more, literally ".",' .
            'letter at least 2, must end, case insensitive');

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
        $regEx = new SRL('literally "color:", whitespace, capture (letter once or more) as "color", literally "."');

        $matches = $regEx->getMatches('Favorite color: green. Another color: yellow.');

        $this->assertEquals('green', $matches[0]->get('color'));
        $this->assertEquals('yellow', $matches[1]->get('color'));
    }

    public function testParentheses()
    {
        $regEx = new SRL('begin with (literally "foo", literally "bar") twice must end');
        $this->assertEquals('/^(?:(?:foo)(?:bar)){2}$/', $regEx->get());
        $this->assertTrue($regEx->isMatching('foobarfoobar'));
        $this->assertFalse($regEx->isMatching('foobar'));

        $regEx = new SRL('begin with literally "bar", (literally "foo", literally "bar") twice must end');
        $this->assertEquals('/^(?:bar)(?:(?:foo)(?:bar)){2}$/', $regEx->get());
        $this->assertTrue($regEx->isMatching('barfoobarfoobar'));

        $regEx = new SRL('(literally "foo") twice');
        $this->assertEquals('/(?:(?:foo)){2}/', $regEx->get());
        $this->assertTrue($regEx->isMatching('foofoo'));
        $this->assertFalse($regEx->isMatching('foo'));
    }
}

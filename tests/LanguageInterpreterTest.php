<?php

namespace Tests;

use SRL\SRL;

class LanguageInterpreterTest extends TestCase
{
    public function testParser()
    {
        $srl = new SRL('aNy Letter ONCE or more literAlly "fO/o"');
        $this->assertEquals('/\w+fO\/o/', $srl->get());

        $srl = new SRL('BEGIN WITH LITERALLY "http" OPTIONAL "s" LITERALLY "://" OPTIONAL "www." ANYTHING ONCE OR MORE LITERALLY ".com" MUST END');
        $this->assertEquals('/^http(?:s)?:\/\/(?:www\.)?.+\.com$/', $srl->get());
        $this->assertTrue($srl->isMatching('http://www.ebay.com'));
        $this->assertTrue($srl->isMatching('https://google.com'));
        $this->assertFalse($srl->isMatching('htt://google.com'));
        $this->assertFalse($srl->isMatching('http://.com'));

        $srl = new SRL('BEGIN WITH CAPTURE (NUMBER BETWEEN 0 AND 8 ONCE OR MORE) AS "number" IF FOLLOWED BY "foo"');
        $this->assertEquals('/^(?<number>[0-8]+)(?=foo)/', $srl->get());
        $this->assertTrue($srl->isMatching('142foo'));
        $this->assertFalse($srl->isMatching('149foo'));
        $this->assertFalse($srl->isMatching('14bar'));
        $this->assertEquals('142', $srl->getMatches('142foo')[0]->get('number'));

        $srl = new SRL('LITERALLY "colo", OPTIONAL "u", LITERALLY "r"');
        $this->assertEquals(1, preg_match($srl, 'color'));
        $this->assertTrue($srl->isMatching('colour'));
    }

    public function testEmail()
    {
        $regex = new SRL('BEGIN WITH EITHER OF (NUMBER, LETTER, ONE OF "._%+-") ONCE OR MORE,' .
            'LITERALLY "@", EITHER OF (NUMBER, LETTER, ONE OF ".-") ONCE OR MORE, LITERALLY ".", LETTER AT LEAST 2, MUST END, CASE INSENSITIVE');

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
        $regEx = new SRL('LITERALLY "color:", WHITESPACE, CAPTURE (ANY LETTER ONCE OR MORE) AS "color", LITERALLY "."');

        $matches = $regEx->getMatches('Favorite color: green. Another color: yellow.');

        $this->assertEquals('green', $matches[0]->get('color'));
        $this->assertEquals('yellow', $matches[1]->get('color'));
    }
}
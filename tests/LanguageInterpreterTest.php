<?php

namespace Tests;


use SRL\SRL;

class LanguageInterpreterTest extends TestCase
{
    public function testParser()
    {
        $srl = new SRL('aNy Letter ONCE or more literAlly "fO/o"');
        $this->assertEquals('/\w+fO\/o/', $srl->get());

        $srl = new SRL('EITHER OF (LITERALLY "foo" LITERALLY "bar")');
        $this->assertEquals('/(?:foo|bar)/', $srl->get());
    }
}
<?php

namespace Tests;


use SRL\SRL;

class LanguageInterpreterTest extends TestCase
{
    public function testParser()
    {
        $srl = new SRL('aNy Letter ONCE or more literAlly "fOo"');
        $this->assertEquals('/\w+fOo/', $srl->get());
    }
}
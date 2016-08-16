<?php

namespace Tests;


use SRL\SRL;

class LanguageInterpreterTest extends TestCase
{
    public function testParser()
    {
        new SRL('foo (bar (baz) boo) ba bi');
    }
}
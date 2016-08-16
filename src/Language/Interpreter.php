<?php

namespace SRL\Language;

use SRL\Language\Helpers\ParenthesisParser;

class Interpreter
{
    /** @var string */
    protected $query;

    public function __construct(string $query)
    {
        $this->query = $query;
        $this->parse();
    }

    protected function parse()
    {
        $p = new ParenthesisParser($this->query);
        var_dump($p->getNesting());
    }
}
<?php

namespace SRL\Language;

use SRL\Language\Helpers\ParenthesesParser;

class Interpreter
{
    /** @var string */
    protected $query;

    /** @var string[] */
    protected $resolvedQuery = [];

    public function __construct(string $query)
    {
        $this->query = $query;
        $this->resolve();
    }

    protected function resolve()
    {
        $this->resolvedQuery = (new ParenthesesParser($this->query))->parse();
    }
}
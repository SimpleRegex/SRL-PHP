<?php

namespace SRL\Builder;

use SRL\Builder;

class EitherOf extends Builder
{
    /** @var string[] RegEx being built. */
    protected $regEx = [];

    /** @var string Desired match group. */
    protected $group = '(?:%s)';

    /**
     * @inheritdoc
     */
    protected function add(string $condition)
    {
        $this->regEx[] = $condition;
    }

    /**
     * @inheritdoc
     */
    public function get(string $delimiter = '/') : string
    {
        return $this->applyDelimiter(sprintf($this->group, implode('|', $this->regEx)), $delimiter) . $this->modifier;
    }
}
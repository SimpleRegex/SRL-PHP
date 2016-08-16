<?php

namespace SRL\Builder;

use SRL\Builder;

class EitherOf extends Capture
{
    /** @var string[] RegEx being built. */
    protected $regEx = [];

    /** @var string Desired match group. */
    protected $group = '(?:%s)';

    /**
     * @inheritdoc
     */
    protected function add(string $condition) : Builder
    {
        $this->regEx[] = $condition;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function getRawRegex() : string
    {
        return sprintf($this->group, implode('|', $this->regEx));
    }
}
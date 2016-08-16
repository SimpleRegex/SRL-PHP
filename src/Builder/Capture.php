<?php

namespace SRL\Builder;

use SRL\Builder;

class Capture extends Builder
{
    /** @var string Desired match group. */
    protected $group = '(%s)';

    /**
     * @inheritdoc
     */
    protected function getRawRegex() : string
    {
        return sprintf($this->group, $this->regEx);
    }

    /**
     * Set name for capture group.
     *
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->group = "(?<$name>%s)";
    }
}
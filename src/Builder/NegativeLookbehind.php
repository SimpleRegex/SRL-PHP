<?php

namespace SRL\Builder;

use SRL\Builder;

class NegativeLookbehind extends Builder
{
    /** @var string Desired lookbehind group. */
    protected $group = '(?<!%s)';
}
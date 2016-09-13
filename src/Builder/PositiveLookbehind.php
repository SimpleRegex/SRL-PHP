<?php

namespace SRL\Builder;

use SRL\Builder;

class PositiveLookbehind extends Builder
{
    /** @var string Desired lookbehind group. */
    protected $group = '(?<=%s)';
}

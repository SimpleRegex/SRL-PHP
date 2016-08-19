<?php

namespace SRL\Builder;

use SRL\Builder;

class NegativeLookahead extends Builder
{
    /** @var string Desired lookahead group. */
    protected $group = '(?!%s)';
}
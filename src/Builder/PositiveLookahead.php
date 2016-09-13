<?php

namespace SRL\Builder;

use SRL\Builder;

class PositiveLookahead extends Builder
{
    /** @var string Desired lookahead group. */
    protected $group = '(?=%s)';
}

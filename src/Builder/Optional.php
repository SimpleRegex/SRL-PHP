<?php

namespace SRL\Builder;

use SRL\Builder;

class Optional extends Builder
{
    /** @var string Desired match group. */
    protected $group = '(?:%s)?';
}
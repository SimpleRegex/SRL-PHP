<?php

namespace SRL\Builder;

class NegativeLookahead extends Capture
{
    /** @var string Desired lookahead group. */
    protected $group = '(?!%s)';
}
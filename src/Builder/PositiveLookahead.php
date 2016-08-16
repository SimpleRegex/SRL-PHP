<?php

namespace SRL\Builder;

class PositiveLookahead extends Capture
{
    /** @var string Desired lookahead group. */
    protected $group = '(?=%s)';
}
<?php

namespace SRL\Builder;

class Match extends EitherOf
{
    /** @var string Desired match group. */
    protected $group = '(%s)';
}
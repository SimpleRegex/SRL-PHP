<?php

namespace SRL\Builder;

use SRL\Builder;

class EitherOf extends Builder
{
    /** @var string Desired match group. */
    protected $group = '(?:%s)';

    /** @var string String to implode with. */
    protected $implodeString = '|';
}

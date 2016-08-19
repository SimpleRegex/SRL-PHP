<?php

namespace SRL\Builder;

use SRL\Builder;

class NonCapture extends Builder
{
    /** @var string Desired non capture group. */
    protected $group = '(?:%s)';
}
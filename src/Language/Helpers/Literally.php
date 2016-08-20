<?php

namespace SRL\Language\Helpers;

/**
 * Wrapper for literal strings that should not be split, tainted or interpreted in any way.
 */
class Literally
{
    /** @var string The literal string. */
    protected $string = '';

    public function __construct(string $string)
    {
        $this->string = trim(stripslashes($string));
    }

    /**
     * @return string
     */
    public function getString() : string
    {
        return $this->string;
    }
}
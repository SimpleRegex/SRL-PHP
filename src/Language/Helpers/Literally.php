<?php

namespace SRL\Language\Helpers;

class Literally
{
    protected $string = '';

    public function __construct(string $string)
    {
        $this->string = trim(stripslashes($string));
    }

    public function getString() : string
    {
        return $this->string;
    }
}
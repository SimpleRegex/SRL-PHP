<?php

namespace SRL\Exceptions;

use Exception;

class PregException extends SRLException
{
    const EXCEPTION_MESSAGES = [
        PREG_INTERNAL_ERROR => 'Internal PCRE error.',
        PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted.',
        PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted.',
        PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 data.',
        PREG_BAD_UTF8_OFFSET_ERROR => 'Offset did not correspond to the begin of a valid UTF-8 code point.',
        PREG_JIT_STACKLIMIT_ERROR => 'PCRE function failed due to limited JIT stack space.'
    ];

    public function __construct($code, Exception $previous = null)
    {
        parent::__construct(static::EXCEPTION_MESSAGES[$code] ?? 'Unknown preg error.', $code, $previous);
    }
}
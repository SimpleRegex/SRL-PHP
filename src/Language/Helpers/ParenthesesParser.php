<?php

namespace SRL\Language\Helpers;

use SRL\Exceptions\SyntaxException;

/**
 * Parse parentheses and return multidimensional array containing the structure of the input string.
 *
 * This parser will parse ( and ) and supports nesting, escaping using backslash and strings using ' or ".
 */
class ParenthesesParser
{
    /** @var string Input string */
    protected $string = '';

    /**
     * ParenthesesParser constructor.
     *
     * @param string $string
     */
    public function __construct(string $string)
    {
        $this->setString($string);
    }

    /**
     * Set new string to parse.
     *
     * @param string $string
     * @return ParenthesesParser
     */
    public function setString(string $string) : self
    {
        if (!$string) {
            return $this;
        }

        if ($string[0] === '(' && $string[strlen($string) - 1] === ')') {
            $string = substr($string, 1, -1);
        }

        $this->string = $string;

        return $this;
    }

    /**
     * Parse given string and return its structure.
     *
     * @return string[]
     * @throws SyntaxException
     */
    public function parse() : array
    {
        return $this->parseString($this->string);
    }

    /**
     * Internal parse method used for recursion.
     *
     * @param string $string
     * @return string[]
     * @throws SyntaxException
     */
    protected function parseString(string $string) : array
    {
        $openCount = $openPos = $closePos = 0;
        $inString = $backslash = false;

        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];

            if ($inString) {
                if (($char === '"' || $char === "'") && ($string[$i - 1] != '\\' || ($string[$i - 1] === '\\' && $string[$i - 2] === '\\'))) {
                    // We're no more in the string. Either the ' or " was not escaped, or it was but the backslash
                    // before was escaped as well.
                    $inString = false;
                }
                continue;
            }

            if ($backslash) {
                // Backslash was defined in the last char. Reset it and continue, since it only matches one character.
                $backslash = false;
                continue;
            }

            switch ($char) {
                case '\\':
                    // Set the backslash flag. This will skip one character.
                    $backslash = true;
                    break;
                case '"':
                case "'":
                    // Set the string flag. This will tell the parser to skip over this string.
                    $inString = true;
                    break;
                case '(':
                    // Opening parenthesis, increase the count and set the pointer if it's the first one.
                    $openCount++;
                    if ($openPos === 0) {
                        $openPos = $i;
                    }
                    break;
                case ')':
                    // Closing parenthesis, remove count
                    $openCount--;
                    if ($openCount === 0) {
                        // If this is the matching one, set the closing pointer and break the loop, since we don't
                        // want to match any following pairs. Those will be taken care of in a later recursion step.
                        $closePos = $i;
                        break 2;
                    }
                    break;
            }
        }

        if ($openCount !== 0) {
            throw new SyntaxException('Non-matching parenthesis found.');
        }

        if ($closePos === 0) {
            // No parenthesis found. Return trimmed string.
            return [trim($string)];
        }

        return array_filter(array_merge([
            // First part is definitely without parentheses, since we'll match the first pair.
            trim(substr($string, 0, $openPos)),
            // This is the inner part of the parentheses pair. Might be some more pairs, so we'll check.
            $this->parseString(substr($string, $openPos + 1, $closePos - $openPos - 1)),
            // Last part of the string wasn't checked at all, so we'll have to re-check it.
        ], $this->parseString(substr($string, $closePos + 1))));
    }
}
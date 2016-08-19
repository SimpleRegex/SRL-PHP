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
        $stringPositions = [];

        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];

            if ($inString) {
                if (($char === '"' || $char === "'") && ($string[$i - 1] != '\\' || ($string[$i - 1] === '\\' && $string[$i - 2] === '\\'))) {
                    // We're no more in the string. Either the ' or " was not escaped, or it was but the backslash
                    // before was escaped as well.
                    $inString = false;

                    // Also, to create a "Literally" object later on, save the string end position.
                    $stringPositions[count($stringPositions) - 1]['end'] = $i - 1;
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
                    // Also, to create a "Literally" object later on, save the string start position.
                    $stringPositions[] = ['start' => $i];
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
            // No parentheses found. Use end of string
            $openPos = $closePos = strlen($string);
        }

        $return = $this->createLiterallyObjects($string, $openPos, $stringPositions);

        if ($openPos !== $closePos) {
            // Parentheses found
            $return = array_merge(
                $return, // First part is definitely without parentheses, since we'll match the first pair.
                // This is the inner part of the parentheses pair. There may be some more nested pairs, so we'll check them.
                [$this->parseString(substr($string, $openPos + 1, $closePos - $openPos - 1))],
                // Last part of the string wasn't checked at all, so we'll have to re-check it.
                $this->parseString(substr($string, $closePos + 1))
            );
        }

        return array_values(array_filter($return, function ($val) {
            // This callback is required to keep '0' in the response, since this may be a parameter
            return !is_string($val) || strlen($val);
        }));
    }

    /**
     * Replace all "literal strings" with a Literally object to simplify parsing later on.
     *
     * @param string $string
     * @param int $openPos
     * @param array $stringPositions
     * @return array
     */
    protected function createLiterallyObjects(string $string, int $openPos, array $stringPositions) : array
    {
        $firstRaw = substr($string, 0, $openPos);
        $return = [trim($firstRaw)];
        $pointer = 0;

        foreach ($stringPositions as $stringPosition) {
            if ($stringPosition['end'] < strlen($firstRaw)) {
                // At least one string exists in first part, create a new object.

                // Remove the last part, since this wasn't parsed.
                array_pop($return);

                // Add part between pointer and string occurrence.
                $return[] = trim(substr($firstRaw, $pointer, $stringPosition['start']));

                // Add the string as object.
                $return[] = new Literally(substr(
                    $firstRaw,
                    $stringPosition['start'] + 1,
                    $stringPosition['end'] - $stringPosition['start']
                ));

                // Add everything else. If a string is in there, we'll take care of it in the next run.
                $return[] = trim(substr($firstRaw, $stringPosition['end'] + 2));

                $pointer = $stringPosition['end'];
            }
        }

        return $return;
    }
}
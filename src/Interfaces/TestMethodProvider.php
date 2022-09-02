<?php

namespace SRL\Interfaces;

use SRL\Exceptions\PregException;
use SRL\SRLMatch;

/**
 * Provider for methods that can be applied to the built regular expression by the user.
 * Shared by Builder and Language\Interpreter.
 */
abstract class TestMethodProvider
{
    /**
     * Build and return the resulting regular expression. This will apply the given delimiter and all modifiers.
     *
     * @param string $delimiter The delimiter to use. Defaults to '/'. If left empty, avoid using modifiers,
     *                          since they then will be ignored.
     * @param bool $ignoreInvalid Ignore invalid regular expressions.
     * @return string The resulting regular expression.
     */
    abstract public function get(string $delimiter = '/', bool $ignoreInvalid = false) : string;

    /**
     * Test if regular expression matches given string.
     *
     * @see preg_match()
     * @param string $string
     * @param int $flags
     * @param int $offset
     * @throws PregException
     * @return bool
     */
    public function isMatching(string $string, int $flags = 0, int $offset = 0) : bool
    {
        $result = preg_match($this->get(), $string, $matches, $flags, $offset);

        if ($result === false) {
            throw new PregException(preg_last_error());
        }

        return $result !== 0;
    }

    /**
     * Apply preg_replace with the regular expression.
     *
     * @see preg_replace()
     * @see preg_replace_callback()
     * @param string|array|\Closure $replacement If a callback is supplied, preg_replace_callback will be called.
     * @param string|array $haystack
     * @param int $limit
     * @param null $count
     * @return string|array
     */
    public function replace($replacement, $haystack, int $limit = -1, &$count = null)
    {
        if (is_callable($replacement)) {
            return preg_replace_callback($this->get(), $replacement, $haystack, $limit, $count);
        }

        return preg_replace($this->get(), $replacement, $haystack, $limit, $count);
    }

    /**
     * Apply preg_split with the regular expression.
     *
     * @param string $string
     * @param int $limit
     * @param int $flags
     * @return array
     */
    public function split(string $string, int $limit = -1, int $flags = 0) : array
    {
        return preg_split($this->get(), $string, $limit, $flags);
    }

    /**
     * Apply preg_filter with the regular expression.
     *
     * @see preg_filter()
     * @param string|array $replacement
     * @param string|array $haystack
     * @param int $limit
     * @param null $count
     * @return string|array
     */
    public function filter($replacement, $haystack, int $limit = -1, &$count = null)
    {
        return preg_filter($this->get(), $replacement, $haystack, $limit, $count);
    }

    /**
     * Match regular expression against string and return all matches.
     * @param string $string
     * @param int $offset
     * @return SRLMatch[]|array
     *@throws PregException
     */
    public function getMatches(string $string, int $offset = 0) : array
    {
        if (preg_match_all($this->get(), $string, $matches, PREG_SET_ORDER, $offset) === false) {
            throw new PregException(preg_last_error());
        }

        $matchObjects = [];

        foreach ($matches as $match) {
            $matchObjects[] = new SRLMatch($match);
        }

        return $matchObjects;
    }

    /**
     * Match regular expression against string and return first match object.
     * @param string $string
     * @param int $offset
     * @return null|SRLMatch
     *@throws PregException
     */
    public function getMatch(string $string, int $offset = 0)
    {
        return $this->getMatches($string, $offset)[0] ?? null;
    }

    /**
     * Validate regular expression.
     *
     * @param string $expression Validate given expression instead of current object.
     * @return bool
     */
    public function isValid(string $expression = null) : bool
    {
        return @preg_match($expression ?: $this->get('/', true), null) !== false;
    }
}

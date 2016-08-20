<?php

namespace SRL\Interfaces;

use SRL\Exceptions\PregException;
use SRL\Match;

abstract class TestMethodProvider
{
    /**
     * Get the raw regular expression without delimiter or modifiers.
     *
     * @return string
     */
    abstract protected function getRawRegex() : string;

    /**
     * Get all set modifiers.
     *
     * @return string
     */
    abstract public function getModifiers() : string;

    /**
     * Build and return the resulting regular expression. This will apply the given delimiter and all modifiers.
     *
     * @param string $delimiter The delimiter to use. Defaults to '/'. If left empty, avoid using modifiers,
     *                          since they then will be ignored.
     * @return string The resulting regular expression.
     */
    public function get(string $delimiter = '/') : string
    {
        if (empty($delimiter)) {
            return $this->getRawRegex();
        }

        return sprintf(
            '%s%s%s%s',
            $delimiter,
            str_replace($delimiter, '\\' . $delimiter, $this->getRawRegex()),
            $delimiter,
            $this->getModifiers()
        );
    }

    /**
     * Test if regular expression matches given string.
     *
     * @see preg_match()
     * @param string $string
     * @param int $flags
     * @param int $offset
     * @return bool
     * @throws PregException
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
     *
     * @param string $string
     * @param int $offset
     * @return Match[]|array
     * @throws PregException
     */
    public function getMatches(string $string, int $offset = 0) : array
    {
        if (preg_match_all($this->get(), $string, $matches, PREG_SET_ORDER, $offset) === false) {
            throw new PregException(preg_last_error());
        }

        $matchObjects = [];

        foreach ($matches as $match) {
            $matchObjects[] = new Match($match);
        }

        return $matchObjects;
    }

    /**
     * Validate regular expression.
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return @preg_match($this->get(), null) !== false;
    }
}
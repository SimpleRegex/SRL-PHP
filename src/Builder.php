<?php

namespace SRL;

use Closure;
use SRL\Builder\EitherOf;
use SRL\Builder\Capture;
use SRL\Exceptions\BuilderException;
use SRL\Exceptions\ImplementationException;
use SRL\Exceptions\PregException;

/**
 * @method $this all() Apply the 'g' modifier
 * @method $this multiLine() Apply the 'm' modifier
 * @method $this singleLine() Apply the 's' modifier
 * @method $this caseInsensitive() Apply the 'i' modifier
 * @method $this unicode() Apply the 'u' modifier
 * @method $this allLazy() Apply the 'U' modifier
 * @method $this startsWith() Expect the string to start with the following pattern.
 * @method $this mustEnd() Expect the string to end after the given pattern.
 * @method $this onceOrMore() Previous match must occur at least once.
 * @method $this any() Match any character.
 * @method $this tab() Match tab character.
 * @method $this newLine() Match new line character.
 * @method $this whitespace() Match any whitespace character.
 * @method $this noWhitespace() Match any non-whitespace character.
 * @method $this anyLetter() Match any word character.
 * @method $this noLetter() Match any non-word character.
 */
class Builder
{
    const NON_LITERAL_CHARACTERS = '[\\^$.|?*+()';

    /** @var string RegEx being built. */
    protected $regEx = '';

    /** @var string Raw modifiers to apply on get(). */
    protected $modifier = '';

    /** @var string[] Map method names to actual modifiers. */
    protected $modifierMapper = [
        'all' => 'g',
        'multiLine' => 'm',
        'singleLine' => 's',
        'caseInsensitive' => 'i',
        'unicode' => 'u',
        'allLazy' => 'U'
    ];

    /** @var string[] Map method names to simple 'add' conditions. */
    protected $simpleMapper = [
        'startsWith' => '^',
        'mustEnd' => '$',
        'onceOrMore' => '+',
        'any' => '.',
        'tab' => '\\t',
        'newLine' => '\\n',
        'whitespace' => '\s',
        'noWhitespace' => '\S',
        'anyLetter' => '\w',
        'noLetter' => '\W'
    ];

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
            $this->modifier
        );
    }

    /**
     * Get the raw regular expression without delimiter or modifiers.
     *
     * @return string
     */
    protected function getRawRegex() : string
    {
        return $this->regEx;
    }

    /**
     * Build and return the resulting regular expression.
     *
     * @see \SRL\Builder::get() For more information.
     * @return string The resulting regular expression.
     */
    public function __toString() : string
    {
        return $this->get();
    }

    /**
     * Add condition to the expression query.
     *
     * @param string $condition
     * @return Builder
     */
    protected function add(string $condition) : self
    {
        $this->regEx .= $condition;

        return $this;
    }

    /**
     * Literally match one of these characters.
     *
     * @param string $chars One or more characters.
     * @return Builder
     */
    public function literally(string $chars) : self
    {
        $chars = str_split($chars);

        foreach ($chars as $char) {
            if (strpos(static::NON_LITERAL_CHARACTERS, $char) !== false) {
                $char = '\\' . $char;
            }

            $this->add($char);
        }

        return $this;
    }

    /**
     * Make the last or given condition optional.
     *
     * @param string|null $chars
     * @return Builder
     */
    public function optional(string $chars = null) : self
    {
        if (!$chars) {
            return $this->add('?');
        }

        return $this->add("(?:$chars)?");
    }

    /**
     * Match either of these conditions.
     *
     * @param Closure $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function eitherOf(Closure $conditions) : self
    {
        $builder = new EitherOf;

        $conditions($builder);

        return $this->add($builder->get(''));
    }

    public function and (Closure $condition)
    {
        $builder = new Builder;

        $condition($builder);

        return $this->add($builder->get(''));
    }

    /**
     * Create capture group for given conditions.
     *
     * @param Closure $conditions Anonymous function with its Builder as a first parameter.
     * @param string $name Name for capture group, if any.
     * @return Builder
     */
    public function capture(Closure $conditions, string $name = null) : self
    {
        $builder = new Capture;

        if ($name) {
            $builder->setName($name);
        }

        $conditions($builder);

        return $this->add($builder->get(''));
    }

    /**
     * Previous match must occur so often.
     *
     * @param int $min
     * @param int $max
     * @return Builder
     */
    public function between(int $min, int $max) : self
    {
        return $this->add(sprintf('{%d,%d}', $min, $max));
    }

    /**
     * Previous match must occur at least this often.
     *
     * @param int $min
     * @return Builder
     */
    public function atLeast(int $min) : self
    {
        return $this->add(sprintf('{%d,}', $min));
    }

    /**
     * Previous match must occur exactly once.
     *
     * @return Builder
     */
    public function once() : self
    {
        return $this->exactly(1);
    }

    /**
     * Previous match must occur exactly twice.
     *
     * @return Builder
     */
    public function twice() : self
    {
        return $this->exactly(2);
    }

    /**
     * Previous match must occur exactly this often.
     *
     * @param int $count
     * @return Builder
     */
    public function exactly(int $count) : self
    {
        return $this->add(sprintf('{%d}', $count));
    }

    /**
     * Match any number (in given span). Default will be a number between 0 and 9.
     *
     * @param int $min
     * @param int $max
     * @return Builder
     */
    public function number(int $min = 0, int $max = 9) : self
    {
        return $this->add("[$min-$max]");
    }

    /**
     * Match any uppercase letter (between A to Z).
     *
     * @param string $min
     * @param string $max
     * @return Builder
     */
    public function uppercaseLetter(string $min = 'A', string $max = 'Z') : self
    {
        return $this->letter($min, $max);
    }

    /**
     * Match any lowercase letter (between a to z).
     *
     * @param string $min
     * @param string $max
     * @return Builder
     */
    public function letter(string $min = 'a', string $max = 'z') : self
    {
        return $this->add("[$min-$max]");
    }

    /**
     * Apply laziness to last match.
     *
     * @return Builder
     * @throws ImplementationException
     */
    public function lazy() : self
    {
        if (strpos('+*}?', substr($this->getRawRegex(), -1)) === false) {
            throw new ImplementationException('Cannot apply laziness at this point.');
        }

        return $this->add('?');
    }

    /**
     * Add a specific unique modifier. This will ignore all modifiers already set.
     *
     * @param string $modifier
     * @return Builder
     */
    protected function addUniqueModifier(string $modifier) : self
    {
        if (strpos($this->modifier, $modifier) === false) {
            $this->modifier .= $modifier;
        }

        return $this;
    }

    /**
     * Add the value from the simple mapper array to the regular expression.
     *
     * @param string $name
     * @return Builder
     * @throws BuilderException
     */
    protected function addFromMapper(string $name) : self
    {
        if (!isset($this->simpleMapper[$name])) {
            throw new BuilderException('Unknown mapper.');
        }

        return $this->add($this->simpleMapper[$name]);
    }

    /**
     * Try adding modifiers if their methods are defined in the modifierMapper attribute.
     *
     * @param $name
     * @param $arguments
     * @return Builder
     * @throws ImplementationException
     */
    public function __call($name, $arguments) : self
    {
        if (isset($this->simpleMapper[$name])) {
            // Simple mapper exists, add its character to the regex
            return $this->addFromMapper($name);
        }

        if (isset($this->modifierMapper[$name])) {
            // Modifier exists, add it
            return $this->addUniqueModifier($this->modifierMapper[$name]);
        }

        throw new ImplementationException(sprintf(
            'Call to undefined or invalid method %s:%s()',
            get_class($this),
            $name
        ));
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
    public function matches(string $string, int $flags = 0, int $offset = 0) : bool
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
     * @param string|array|Closure $replacement If a callback is supplied, preg_replace_callback will be called.
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
}
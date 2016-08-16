<?php

namespace SRL;

use Closure;
use SRL\Builder\EitherOf;
use SRL\Builder\Match;
use SRL\Exceptions\BuilderException;
use SRL\Exceptions\ImplementationException;

/**
 * @method $this all() Apply the 'g' modifier
 * @method $this multiLine() Apply the 'm' modifier
 * @method $this singleLine() Apply the 's' modifier
 * @method $this caseInsensitive() Apply the 'i' modifier
 * @method $this unicode() Apply the 'u' modifier
 * @method $this lazy() Apply the 'U' modifier
 */
class Builder
{
    /** @var string RegEx being built. */
    protected $regEx = null;

    /** @var string Raw modifiers to apply on get(). */
    protected $modifier = null;

    /** @var string[] Map method names to actual modifiers. */
    protected $modifierMapper = [
        'all' => 'g',
        'multiLine' => 'm',
        'singleLine' => 's',
        'caseInsensitive' => 'i',
        'unicode' => 'u',
        'lazy' => 'U'
    ];

    /**
     * Build and return the resulting regular expression. This will apply the given delimiter and all modifiers.
     *
     * @param string $delimiter The delimiter to use. Defaults to '/'. If left empty, avoid using modifiers,
     *                          since they then will be appended to the expression.
     * @return string The resulting regular expression.
     */
    public function get(string $delimiter = '/') : string
    {
        return $this->applyDelimiter($this->regEx, $delimiter) . $this->modifier;
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
     * Apply any given delimiter to given regular expression.
     *
     * @param string $regex
     * @param string $delimiter
     * @return string
     */
    protected function applyDelimiter(string $regex, string $delimiter = '/') : string
    {
        if (empty($delimiter)) {
            return $regex;
        }

        return $delimiter . str_replace($delimiter, '\\' . $delimiter, $regex) . $delimiter;
    }

    /**
     * Add condition to the expression query.
     *
     * @param string $condition
     */
    protected function add(string $condition)
    {
        $this->regEx .= $condition;
    }

    /**
     * Expect the string to start with the following pattern.
     *
     * @return Builder
     */
    public function startsWith(): self
    {
        $this->add('^');

        return $this;
    }

    /**
     * Expect the string to end after the given pattern.
     *
     * @return Builder
     */
    public function mustEnd(): self
    {
        $this->add('$');

        return $this;
    }

    /**
     * Literally match one of these characters.
     *
     * @param string $chars One or more characters.
     * @return Builder
     */
    public function literally(string $chars): self
    {
        foreach (str_split($chars) as $char) {
            $this->add('\\' . $char);
        }

        return $this;
    }

    /**
     * Match either of these conditions.
     *
     * @param Closure $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function eitherOf(Closure $conditions): self
    {
        $builder = new EitherOf;
        $conditions($builder);

        $this->add($builder->get(''));

        return $this;
    }

    /**
     * Create match group for given conditions.
     *
     * @param Closure $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function matchGroup(Closure $conditions): self
    {
        $builder = new Match;
        $conditions($builder);

        $this->add($builder->get(''));

        return $this;
    }

    /**
     * Previous match must occur so often.
     *
     * @param int $min
     * @param int $max
     * @return Builder
     */
    public function between(int $min, int $max): self
    {
        $this->add(sprintf('{%d,%d}', $min, $max));

        return $this;
    }

    /**
     * Previous match must occur at least this often.
     *
     * @param int $min
     * @return Builder
     */
    public function atLeast(int $min): self
    {
        $this->add(sprintf('{%d,}', $min));

        return $this;
    }

    /**
     * Previous match must occur at least once.
     *
     * @return Builder
     */
    public function onceOrMore() : self
    {
        return $this->atLeast(1);
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
    public function exactly(int $count): self
    {
        $this->add(sprintf('{%d}', $count));

        return $this;
    }

    /**
     * Match any number (in given span). Default will be a number between 0 and 9.
     *
     * @param int $min
     * @param int $max
     * @return Builder
     */
    public function number(int $min = 0, int $max = 9): self
    {
        $this->add("[$min-$max]");

        return $this;
    }

    /**
     * Match any uppercase letter (between A to Z).
     *
     * @param string $min
     * @param string $max
     * @return Builder
     */
    public function uppercaseLetter(string $min = 'A', string $max = 'Z'): self
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
    public function letter(string $min = 'a', string $max = 'z'): self
    {
        $this->add("[$min-$max]");

        return $this;
    }

    /**
     * Add a specific unique modifier. This will ignore all modifiers already set.
     *
     * @param string $modifier
     * @return Builder
     */
    protected function addUniqueModifier(string $modifier): self
    {
        if (strpos($this->modifier, $modifier) === false) {
            $this->modifier .= $modifier;
        }

        return $this;
    }

    /**
     * Try adding modifiers if their methods are defined in the modifierMapper attribute.
     *
     * @param $name
     * @param $arguments
     * @return Builder
     * @throws ImplementationException
     */
    public function __call($name, $arguments): self
    {
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
     * @param string $string
     * @return bool
     * @throws BuilderException
     */
    public function matches(string $string) : bool
    {
        $result = preg_match($this->get(), $string);

        if ($result === false) {
            throw new BuilderException('Invalid regular expression. I am sorry :(');
        }

        return $result == 1;
    }

    /**
     * Match regular expression against string and return all matches.
     *
     * @see preg_match_all()
     * @param string $string
     * @param int $flags Use preg_match_all() flags
     * @return array
     * @throws BuilderException
     */
    public function matchAll(string $string, int $flags = PREG_PATTERN_ORDER) : array
    {
        if (preg_match_all($this->get(), $string, $matches, $flags) === false) {
            throw new BuilderException('Invalid regular expression. I am sorry :(');
        }

        return $matches;
    }
}
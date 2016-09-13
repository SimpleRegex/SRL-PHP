<?php

namespace SRL;

use Closure;
use SRL\Builder\Capture;
use SRL\Builder\EitherOf;
use SRL\Builder\NegativeLookahead;
use SRL\Builder\NegativeLookbehind;
use SRL\Builder\NonCapture;
use SRL\Builder\Optional;
use SRL\Builder\PositiveLookahead;
use SRL\Builder\PositiveLookbehind;
use SRL\Exceptions\BuilderException;
use SRL\Exceptions\ImplementationException;
use SRL\Exceptions\SyntaxException;
use SRL\Interfaces\TestMethodProvider;

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
 * @method $this neverOrMore() Previous match must occur zero to infinite times.
 * @method $this any() Match any character.
 * @method $this tab() Match tab character.
 * @method $this newLine() Match new line character.
 * @method $this whitespace() Match any whitespace character.
 * @method $this noWhitespace() Match any non-whitespace character.
 * @method $this anyCharacter() Match any word character.
 * @method $this noCharacter() Match any non-word character.
 */
class Builder extends TestMethodProvider
{
    const NON_LITERAL_CHARACTERS = '[\\^$.|?*+()';
    const METHOD_TYPE_BEGIN = 0b00001;
    const METHOD_TYPE_CHARACTER = 0b00010;
    const METHOD_TYPE_GROUP = 0b00100;
    const METHOD_TYPE_QUANTIFIER = 0b01000;
    const METHOD_TYPE_ANCHOR = 0b10000;
    const METHOD_TYPE_UNKNOWN = 0b11111;
    const METHOD_TYPES_ALLOWED_FOR_CHARACTERS = self::METHOD_TYPE_BEGIN | self::METHOD_TYPE_ANCHOR | self::METHOD_TYPE_GROUP | self::METHOD_TYPE_QUANTIFIER | self::METHOD_TYPE_CHARACTER;

    /** @var string[] RegEx being built. */
    protected $regEx = [];

    /** @var string Raw modifiers to apply on get(). */
    protected $modifiers = '';

    /** @var int Type of last method, to avoid invalid builds. */
    protected $lastMethodType = self::METHOD_TYPE_BEGIN;

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
        'startsWith' => [
            'add' => '^',
            'type' => self::METHOD_TYPE_ANCHOR,
            'allowed' => self::METHOD_TYPE_BEGIN
        ],
        'mustEnd' => [
            'add' => '$',
            'type' => self::METHOD_TYPE_ANCHOR,
            'allowed' => self::METHOD_TYPE_CHARACTER | self::METHOD_TYPE_QUANTIFIER | self::METHOD_TYPE_GROUP
        ],
        'onceOrMore' => [
            'add' => '+',
            'type' => self::METHOD_TYPE_QUANTIFIER,
            'allowed' => self::METHOD_TYPE_CHARACTER | self::METHOD_TYPE_GROUP
        ],
        'neverOrMore' => [
            'add' => '*',
            'type' => self::METHOD_TYPE_QUANTIFIER,
            'allowed' => self::METHOD_TYPE_CHARACTER | self::METHOD_TYPE_GROUP
        ],
        'any' => [
            'add' => '.',
            'type' => self::METHOD_TYPE_CHARACTER,
            'allowed' => self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS
        ],
        'tab' => [
            'add' => '\\t',
            'type' => self::METHOD_TYPE_CHARACTER,
            'allowed' => self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS
        ],
        'newLine' => [
            'add' => '\\n',
            'type' => self::METHOD_TYPE_CHARACTER,
            'allowed' => self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS
        ],
        'whitespace' => [
            'add' => '\s',
            'type' => self::METHOD_TYPE_CHARACTER,
            'allowed' => self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS
        ],
        'noWhitespace' => [
            'add' => '\S',
            'type' => self::METHOD_TYPE_CHARACTER,
            'allowed' => self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS
        ],
        'anyCharacter' => [
            'add' => '\w',
            'type' => self::METHOD_TYPE_CHARACTER,
            'allowed' => self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS
        ],
        'noCharacter' => [
            'add' => '\W',
            'type' => self::METHOD_TYPE_CHARACTER,
            'allowed' => self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS
        ]
    ];

    /** @var string Desired group, if any. */
    protected $group = '%s';

    /** @var string String to implode with. */
    protected $implodeString = '';

    /**********************************************************/
    /*                     CHARACTERS                         */
    /**********************************************************/

    /**
     * Add raw Regular Expression to current expression.
     *
     * @param string $regularExpression
     * @throws BuilderException
     * @return Builder
     */
    public function raw(string $regularExpression) : self
    {
        $this->lastMethodType = self::METHOD_TYPE_UNKNOWN;

        $this->add($regularExpression);

        if (!$this->isValid()) {
            $this->revertLast();
            throw new BuilderException('Adding raw would invalidate this regular expression. Reverted.');
        }

        return $this;
    }

    /**
     * Literally match one of these characters.
     *
     * @param string $chars
     * @return Builder
     */
    public function oneOf(string $chars)
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_CHARACTER, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        $chars = implode('', array_map([$this, 'escape'], str_split($chars)));
        $chars = str_replace(['-', ']'], ['\\-', '\\]'], $chars);

        return $this->add('[' . $chars . ']');
    }

    /**
     * Literally match all of these characters in that order.
     *
     * @param string $chars One or more characters.
     * @return Builder
     */
    public function literally(string $chars) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_CHARACTER, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->add('(?:' . implode('', array_map([$this, 'escape'], str_split($chars))) . ')');
    }

    /**
     * Match any digit (in given span). Default will be a digit between 0 and 9.
     *
     * @deprecated
     * @see Builder::digit()
     * @param int $min
     * @param int $max
     * @return Builder
     */
    public function number(int $min = 0, int $max = 9) : self
    {
        return $this->digit($min, $max);
    }

    /**
     * Match any digit (in given span). Default will be a digit between 0 and 9.
     *
     * @param int $min
     * @param int $max
     * @return Builder
     */
    public function digit(int $min = 0, int $max = 9) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_CHARACTER, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

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
        $this->validateAndAddMethodType(self::METHOD_TYPE_CHARACTER, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->add("[$min-$max]");
    }

    /**********************************************************/
    /*                        GROUPS                          */
    /**********************************************************/

    /**
     * Match any of these conditions.
     *
     * @deprecated
     * @see Builder::anyOf()
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function eitherOf($conditions) : self
    {
        return $this->anyOf($conditions);
    }

    /**
     * Match any of these conditions.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function anyOf($conditions) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->addClosure(new EitherOf, $conditions);
    }

    /**
     * Match all of these conditions, but in a non capture group.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function group($conditions) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->addClosure(new NonCapture, $conditions);
    }

    /**
     * Match all of these conditions. Basically reverts back to the default mode, if coming from anyOf, etc.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function and($conditions) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->addClosure(new self, $conditions);
    }

    /**
     * Positive lookbehind. Match the previous condition only if given conditions already occurred.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function ifAlreadyHad($conditions) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        $condition = $this->revertLast();

        $this->addClosure(new PositiveLookbehind, $conditions);

        return $this->add($condition);
    }

    /**
     * Negative lookbehind. Match the previous condition only if given conditions did not already occur.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function ifNotAlreadyHad($conditions) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        $condition = $this->revertLast();

        $this->addClosure(new NegativeLookbehind, $conditions);

        return $this->add($condition);
    }

    /**
     * Positive lookahead. Match the previous condition only if followed by given conditions.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function ifFollowedBy($conditions) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->addClosure(new PositiveLookahead, $conditions);
    }

    /**
     * Negative lookahead. Match the previous condition only if NOT followed by given conditions.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function ifNotFollowedBy($conditions) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->addClosure(new NegativeLookahead, $conditions);
    }

    /**
     * Create capture group for given conditions.
     *
     * @param Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @param string $name Name for capture group, if any.
     * @return Builder
     */
    public function capture($conditions, string $name = null) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        $builder = new Capture;

        if ($name) {
            $builder->setName($name);
        }

        return $this->addClosure($builder, $conditions);
    }

    /**********************************************************/
    /*                      QUANTIFIERS                       */
    /**********************************************************/

    /**
     * Make the last or given condition optional.
     *
     * @param null|Closure|Builder|string $conditions Anonymous function with its Builder as a first parameter.
     * @return Builder
     */
    public function optional($conditions = null) : self
    {
        $this->validateAndAddMethodType(self::METHOD_TYPE_QUANTIFIER, self::METHOD_TYPE_CHARACTER | self::METHOD_TYPE_GROUP);

        if (!$conditions) {
            return $this->add('?');
        }

        return $this->addClosure(new Optional, $conditions);
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
        $this->validateAndAddMethodType(self::METHOD_TYPE_QUANTIFIER, self::METHOD_TYPE_CHARACTER | self::METHOD_TYPE_GROUP);

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
        $this->validateAndAddMethodType(self::METHOD_TYPE_QUANTIFIER, self::METHOD_TYPE_CHARACTER | self::METHOD_TYPE_GROUP);

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
        $this->validateAndAddMethodType(self::METHOD_TYPE_QUANTIFIER, self::METHOD_TYPE_CHARACTER | self::METHOD_TYPE_GROUP);

        return $this->add(sprintf('{%d}', $count));
    }

    /**
     * Get the first match instead of the last one (lazy).
     *
     * @return Builder
     */
    public function lazy() : self
    {
        return $this->firstMatch();
    }

    /**
     * Apply laziness to last match.
     *
     * @throws ImplementationException
     * @return Builder
     */
    public function firstMatch() : self
    {
        $this->lastMethodType = self::METHOD_TYPE_QUANTIFIER;

        if (strpos('+*}?', substr($this->getRawRegex(), -1)) === false) {
            if (substr(end($this->regEx), -1) === ')' && strpos('+*}?', substr($this->getRawRegex(), -2, 1)) !== false) {
                return $this->add(substr($this->revertLast(), 0, -1) . '?)');
            }

            throw new ImplementationException('Cannot apply laziness at this point. Only applicable after quantifiers.');
        }

        return $this->add('?');
    }

    /**
     * Match up to the given condition.
     *
     * @param $toCondition
     * @return Builder
     */
    public function until($toCondition) : self
    {
        try {
            $this->lazy();
        } catch (ImplementationException $e) {
        }

        $this->validateAndAddMethodType(self::METHOD_TYPE_GROUP, self::METHOD_TYPES_ALLOWED_FOR_CHARACTERS);

        return $this->addClosure(new self, $toCondition);
    }

    /**********************************************************/
    /*                   INTERNAL METHODS                     */
    /**********************************************************/

    /**
     * Escape specific character.
     *
     * @param string $char
     * @return string
     */
    protected function escape(string $char)
    {
        return (strpos(static::NON_LITERAL_CHARACTERS, $char) !== false ? '\\' : '') . $char;
    }

    /**
     * Get the raw regular expression without delimiter or modifiers.
     *
     * @return string
     */
    protected function getRawRegex() : string
    {
        return sprintf($this->group, implode($this->implodeString, $this->regEx));
    }

    /**
     * Get all set modifiers.
     *
     * @return string
     */
    public function getModifiers() : string
    {
        return $this->modifiers;
    }

    /**
     * Add condition to the expression query.
     *
     * @param string $condition
     * @return Builder
     */
    protected function add(string $condition) : self
    {
        $this->regEx[] = $condition;

        return $this;
    }

    /**
     * Validate method call. This will throw an exception if the called method makes no sense at this point.
     * Will add the current type as the last method type.
     *
     * @param int $type
     * @param int $allowed
     * @param string|null $methodName Optional. If not supplied, the calling method name will be used.
     * @throws ImplementationException
     */
    protected function validateAndAddMethodType(int $type, int $allowed, string $methodName = null)
    {
        if ($allowed & $this->lastMethodType) {
            $this->lastMethodType = $type;

            return;
        }

        switch ($this->lastMethodType) {
            case self::METHOD_TYPE_BEGIN:
                $humanText = 'at the beginning';
                break;
            case self::METHOD_TYPE_CHARACTER:
                $humanText = 'after a literal character';
                break;
            case self::METHOD_TYPE_GROUP:
                $humanText = 'after a group';
                break;
            case self::METHOD_TYPE_QUANTIFIER:
                $humanText = 'after a quantifier';
                break;
            case self::METHOD_TYPE_ANCHOR:
                $humanText = 'after an anchor';
                break;
        }

        throw new ImplementationException(sprintf(
            'Method `%s` is not allowed %s.',
            $methodName ?? debug_backtrace()[1]['function'],
            $humanText ?? 'here'
        ));
    }

    /**
     * Add the value from the simple mapper array to the regular expression.
     *
     * @param string $name
     * @throws BuilderException
     * @return Builder
     */
    protected function addFromMapper(string $name) : self
    {
        if (!isset($this->simpleMapper[$name])) {
            throw new BuilderException('Unknown mapper.');
        }

        $this->validateAndAddMethodType(
            $this->simpleMapper[$name]['type'],
            $this->simpleMapper[$name]['allowed'],
            $name
        );

        return $this->add($this->simpleMapper[$name]['add']);
    }

    /**
     * Add a specific unique modifier. This will ignore all modifiers already set.
     *
     * @param string $modifier
     * @return Builder
     */
    protected function addUniqueModifier(string $modifier) : self
    {
        if (strpos($this->modifiers, $modifier) === false) {
            $this->modifiers .= $modifier;
        }

        return $this;
    }

    /**
     * Build the given Closure or string and append it to the current expression.
     *
     * @param Builder $builder
     * @param Closure|Builder|string $conditions Either a closure, literal character string or another Builder instance.
     * @return Builder
     */
    protected function addClosure(Builder $builder, $conditions) : self
    {
        if (is_string($conditions)) {
            // Assuming literal characters if conditions are of type string
            $builder->literally($conditions);
        } elseif ($conditions instanceof self) {
            $builder->raw($conditions->get(''));
        } else {
            $conditions($builder);
        }

        return $this->add($builder->get(''));
    }

    /**
     * Get and remove last added element.
     *
     * @return string
     */
    protected function revertLast()
    {
        return array_pop($this->regEx);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $delimiter = '/', bool $ignoreInvalid = false) : string
    {
        if (empty($delimiter)) {
            return $this->getRawRegex();
        }

        $regEx = sprintf(
            '%s%s%s%s',
            $delimiter,
            str_replace($delimiter, '\\' . $delimiter, $this->getRawRegex()),
            $delimiter,
            $this->getModifiers()
        );

        if (!$ignoreInvalid && !$this->isValid($regEx)) {
            throw new SyntaxException('Generated expression seems to be inalid.');
        }

        return $regEx;
    }

    /**********************************************************/
    /*                     MAGIC METHODS                      */
    /**********************************************************/

    /**
     * Try adding modifiers if their methods are defined in the modifierMapper attribute.
     *
     * @param $name
     * @param $arguments
     * @throws ImplementationException
     * @return Builder
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
     * Build and return the resulting regular expression.
     *
     * @see \SRL\Builder::get() For more information.
     * @return string The resulting regular expression.
     */
    public function __toString() : string
    {
        return $this->get();
    }
}

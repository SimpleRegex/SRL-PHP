<?php

namespace SRL;

use SRL\Exceptions\ImplementationException;
use SRL\Language\Interpreter;

/**
 * SRL facade for SRL Builder and SRL Language.
 *
 * @method static \SRL\Builder oneOf(string $chars)
 * @method static \SRL\Builder literally(string $chars)
 * @method static \SRL\Builder optional(string $chars = null)
 * @method static \SRL\Builder anyOf(\Closure|string|\SRL\Builder $conditions)
 * @method static \SRL\Builder capture(\Closure|string|\SRL\Builder $conditions, string $name = null)
 * @method static \SRL\Builder between(int $min, int $max)
 * @method static \SRL\Builder digit(int $min = 0, int $max = 9)
 * @method static \SRL\Builder uppercaseLetter(string $min = 'A', string $max = 'Z')
 * @method static \SRL\Builder letter(string $min = 'a', string $max = 'z')
 * @method static \SRL\Builder multiLine() Apply the 'm' modifier
 * @method static \SRL\Builder singleLine() Apply the 's' modifier
 * @method static \SRL\Builder caseInsensitive() Apply the 'i' modifier
 * @method static \SRL\Builder unicode() Apply the 'u' modifier
 * @method static \SRL\Builder allLazy() Apply the 'U' modifier
 * @method static \SRL\Builder startsWith() Expect the string to start with the following pattern.
 * @method static \SRL\Builder mustEnd() Expect the string to end after the given pattern.
 * @method static \SRL\Builder onceOrMore() Previous match must occur at least once.
 * @method static \SRL\Builder any() Match any character.
 * @method static \SRL\Builder tab() Match tab character.
 * @method static \SRL\Builder newLine() Match new line character.
 * @method static \SRL\Builder whitespace() Match any whitespace character.
 * @method static \SRL\Builder noWhitespace() Match any non-whitespace character.
 * @method static \SRL\Builder anyCharacter() Match any word character.
 * @method static \SRL\Builder noCharacter() Match any non-word character.
 * @method static \SRL\Builder backslash() Match a backslash (\).
 *
 * @mixin \SRL\Language\Interpreter
 */
class SRL
{
    /** @var Interpreter */
    protected $language;

    public function __construct(string $query)
    {
        $this->language = new Interpreter($query);
    }

    public function __call($name, $arguments)
    {
        return $this->language->$name(...$arguments);
    }

    /**
     * Call each method on a new Builder object.
     *
     * @param $name
     * @param $arguments
     * @throws ImplementationException
     * @return mixed|Builder
     */
    public static function __callStatic(string $name, array $arguments = [])
    {
        return (new Builder)->$name(...$arguments);
    }

    public function __toString()
    {
        return $this->language->get();
    }
}

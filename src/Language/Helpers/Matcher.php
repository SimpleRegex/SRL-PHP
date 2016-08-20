<?php

namespace SRL\Language\Helpers;

use SRL\Exceptions\SyntaxException;

class Matcher
{
    /** @var static */
    protected static $instance;

    /** @var string[] Contains all possible commands. Initialized with commands without parameters. */
    protected $mapper = [
        'any letter' => ['params' => 0, 'method' => 'anyLetter'],
        'no letter' => ['params' => 0, 'method' => 'noLetter'],
        'multi line' => ['params' => 0, 'method' => 'multiLine'],
        'single line' => ['params' => 0, 'method' => 'singleLine'],
        'case insensitive' => ['params' => 0, 'method' => 'caseInsensitive'],
        'all lazy' => ['params' => 0, 'method' => 'allLazy'],
        'starts with' => ['params' => 0, 'method' => 'startsWith'],
        'must end' => ['params' => 0, 'method' => 'mustEnd'],
        'once or more' => ['params' => 0, 'method' => 'onceOrMore'],
        'never or more' => ['params' => 0, 'method' => 'neverOrMore'],
        'new line' => ['params' => 0, 'method' => 'newLine'],
        'whitespace' => ['params' => 0, 'method' => 'whitespace'],
        'no whitespace' => ['params' => 0, 'method' => 'noWhitespace'],
        'all' => ['params' => 0, 'method' => 'all'],
        'any' => ['params' => 0, 'method' => 'any'],
        'tab' => ['params' => 0, 'method' => 'tab'],
        'unicode' => ['params' => 0, 'method' => 'unicode'],
        'literally' => ['params' => 1, 'method' => 'literally']
    ];

    protected function __construct()
    {
        // Initialize additional commands with parameter regular expressions
        // TODO
    }

    /**
     * @return Matcher
     */
    public static function getInstance() : self
    {
        return static::$instance ?: static::$instance = new static();
    }

    /**
     * @param string $part
     * @return Method
     * @throws SyntaxException
     */
    public function match(string $part) : Method
    {
        $maxMatchCount = 0;

        foreach ($this->mapper as $key => $value) {
            $matches = [];
            preg_match_all('/^(' . str_replace(' ', ') (', $key) . ')/i', $part, $matches, PREG_SET_ORDER);
            $count = empty($matches) ? 0 : count($matches[0]);
            if ($count > $maxMatchCount) {
                $maxMatchCount = $count;
                $maxMatch = $key;
            }
        }

        if (isset($maxMatch)) {
            $method = $this->mapper[$maxMatch];

            return new Method($maxMatch, $method['method'], $method['params'], $method['optional'] ?? 0);
        }

        throw new SyntaxException("Invalid method: `$part`");
    }
}
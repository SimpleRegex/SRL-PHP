<?php

namespace SRL\Language\Helpers;

class ParenthesisParser
{
    /** @var array Keeping track of current nesting */
    protected $stack = [];

    /** @var array Current level */
    protected $current = [];

    /** @var string Input string */
    protected $string;

    /** @var int Character offset in string */
    protected $position = 0;

    /** @var int Start of text buffer */
    protected $buffer_start = null;

    /** @var int Length of string */
    protected $length = 0;

    public function __construct(string $string)
    {
        if (!$string) {
            return;
        }

        if ($string[0] == '(') {
            // kill outer parenthesis, as they're unnecessary
            $string = substr($string, 1, -1);
        }

        $this->string = $string;
        $this->length = strlen($this->string);
        // look at each character
        for (; $this->position < $this->length; $this->position++) {
            switch ($this->string[$this->position]) {
                case '(':
                    $this->push();
                    // push current scope to the stack an begin a new scope
                    array_push($this->stack, $this->current);
                    $this->current = [];
                    break;

                case ')':
                    $this->push();
                    // save current scope
                    $t = $this->current;
                    // get the last scope from stack
                    $this->current = array_pop($this->stack);
                    // add just saved scope to current scope
                    $this->current[] = $t;
                    break;
                /*
                 case ' ':
                     // make each word its own token
                     $this->push();
                     break;
                 */
                default:
                    // remember the offset to do a string capture later
                    // could've also done $buffer .= $string[$position]
                    // but that would just be wasting resources…
                    if ($this->buffer_start === null) {
                        $this->buffer_start = $this->position;
                    }
                    break;
            }
        }
    }

    public function getNesting()
    {
        return $this->current;
    }

    protected function push()
    {
        if ($this->buffer_start !== null) {
            // extract string from buffer start to current position
            $buffer = substr($this->string, $this->buffer_start, $this->position - $this->buffer_start);
            // clean buffer
            $this->buffer_start = null;
            // throw token into current scope
            $this->current[] = $buffer;
        }
    }
}
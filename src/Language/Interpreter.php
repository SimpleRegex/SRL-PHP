<?php

namespace SRL\Language;

use SRL\Builder;
use SRL\Exceptions\InterpreterException;
use SRL\Exceptions\SyntaxException;
use SRL\Interfaces\TestMethodProvider;
use SRL\Language\Helpers\Literally;
use SRL\Language\Helpers\Matcher;
use SRL\Language\Helpers\Method;
use SRL\Language\Helpers\ParenthesesParser;

class Interpreter extends TestMethodProvider
{
    /** @var string */
    protected $query;

    /** @var string[] */
    protected $resolvedQuery = [];

    /** @var Matcher */
    protected $matcher;

    /** @var Builder */
    protected $builder;

    public function __construct(string $query)
    {
        $this->query = trim($query);
        $this->matcher = Matcher::getInstance();

        $this->build();
    }

    public function build()
    {
        $this->resolve();

        $this->builder = $this->buildQuery($this->resolvedQuery);
    }

    protected function resolve()
    {
        $this->resolvedQuery = $this->resolveQuery((new ParenthesesParser($this->query))->parse());
    }

    protected function resolveQuery(array $query) : array
    {
        // Using for, since the array will be altered. Foreach would change behaviour
        for ($i = 0; $i < count($query); $i++) {
            if (is_string($query[$i])) {
                try {
                    $method = $this->matcher->match($query[$i]);
                    $leftOver = str_ireplace($method->getOriginal(), '', $query[$i]);
                    $query[$i] = $method;
                    if (!empty($leftOver)) {
                        array_splice($query, $i + 1, 0, trim($leftOver));
                    }
                } catch (SyntaxException $e) {
                    // TODO: May be a parameter
                }
            } elseif (is_array($query[$i])) {
                // TODO
                $query[$i] = $this->resolvePart($query[$i]);
            } elseif ($query[$i] instanceof Literally) {
                // TODO
            } else {
                throw new InterpreterException('Invalid type of part.');
            }
        }

        return $query;
    }

    protected function buildQuery(array $query, Builder $builder = null) : Builder
    {
        $builder = $builder ?: new Builder;

        for ($i = 0; $i < count($query); $i++) {
            $method = $query[$i];

            if (!$method instanceof Method) {
                // At this point, there should only be methods, since all parameters are already taken care of.
                // If that's not the case, something didn't work out.
                throw new SyntaxException("Unexpected keyword: `$method`");
            }

            // Get all required parameters for the method from the current query.
            $parameters = array_splice($query, $i + 1, $method->getParameterCount());

            // If there are optional parameters, walk through them and apply them if they don't start a new method.
            for (
                $j = 0;
                $j < $method->getOptionalParameterCount() && isset($query[$i + 1]) && !($query[$i + 1] instanceof Method);
                $j++
            ) {
                $parameters[] = $query[$i + 1];
                array_splice($query, $i + 1, 1); // don't unset to keep keys incrementing
            }

            // Now, append that method to the builder object
            $method->callMethodOn($builder, $parameters);
        }

        return $builder;
    }

    /**
     * @inheritdoc
     */
    protected function getRawRegex() : string
    {
        return $this->builder->get('');
    }

    /**
     * @inheritdoc
     */
    public function getModifiers() : string
    {
        return $this->builder->getModifiers();
    }

}
<?php

namespace SRL\Interfaces;

use SRL\Builder;
use SRL\Exceptions\SyntaxException;
use SRL\Language\Helpers\Literally;
use SRL\Language\Interpreter;
use Throwable;

/**
 * Abstract Method class. Method strings recognized by the Matcher will result in an object of type Method.
 */
abstract class Method
{
    /** @var string Contains the original method name (case-sensitive). */
    protected $original;

    /** @var string Contains the method name to execute. */
    protected $methodName;

    /** @var array Contains the parsed parameters to pass on execution. */
    protected $parameters = [];

    /** @var int[] Contains all executed callbacks for that method. Helps finding "lost" groups. */
    private $executedCallbacks = [];

    public function __construct(string $original, string $methodName)
    {
        $this->original = $original;
        $this->methodName = $methodName;
    }

    /**
     * Get original method name.
     *
     * @return string
     */
    public function getOriginal() : string
    {
        return $this->original;
    }

    /**
     * Call method with parameters on given builder object.
     *
     * @param Builder $builder
     * @return Builder|mixed
     * @throws SyntaxException
     */
    public function callMethodOn(Builder $builder)
    {
        try {
            $response = $builder->{$this->methodName}(...$this->parameters);

            foreach ($this->parameters as $k => $parameter) {
                if (is_callable($parameter) && !in_array($k, $this->executedCallbacks)) {
                    // Callback wasn't executed, but expected to. Assuming parentheses without method, so let's "and" it.
                    $builder->group($parameter);
                }
            }

            return $response;
        } catch (Throwable $e) {
            throw new SyntaxException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Set and parse raw parameters for method.
     *
     * @param array $params
     * @throws SyntaxException
     * @return Method
     */
    public function setParameters(array $params) : self
    {
        foreach ($params as $k => &$parameter) {
            if ($parameter instanceof Literally) {
                $parameter = $parameter->getString();
            } elseif (is_array($parameter)) {
                // Assuming the user wanted to start a sub-query. This means, we'll create a callback for them.
                $cb = function (Builder $query) use ($parameter, $k) {
                    $this->executedCallbacks[] = $k;
                    Interpreter::buildQuery($parameter, $query);
                };
                $parameter = $cb;
            }
        }

        $this->parameters = $params;

        return $this;
    }
}
<?php

namespace SRL\Language\Helpers;

use SRL\Builder;
use SRL\Exceptions\SyntaxException;

class Method
{
    protected $original;
    protected $methodName;
    protected $parameters;
    protected $optionalParameters;

    public function __construct(string $original, string $methodName, int $parameters, int $optional = 0)
    {
        $this->original = $original;
        $this->methodName = $methodName;
        $this->parameters = $parameters;
        $this->optionalParameters = $optional;
    }

    /**
     * @return string
     */
    public function getOriginal() : string
    {
        return $this->original;
    }

    /**
     * @param Builder $builder
     * @param array $parameters
     * @return Builder|mixed
     * @throws SyntaxException
     */
    public function callMethodOn(Builder $builder, array $parameters = [])
    {
        foreach ($parameters as &$parameter) {
            if ($parameter instanceof Method) {
                throw new SyntaxException('Missing parameter for method ' . $this->getOriginal());
            } elseif ($parameter instanceof Literally) {
                $parameter = $parameter->getString();
            } elseif (is_array($parameter)) {
                // TODO
            }
        }

        return call_user_func([$builder, $this->methodName], ...($this->parameters ? $parameters : []));
    }

    /**
     * @return int
     */
    public function getParameterCount() : int
    {
        return $this->parameters;
    }

    /**
     * @return int
     */
    public function getOptionalParameterCount() : int
    {
        return $this->optionalParameters;
    }
}
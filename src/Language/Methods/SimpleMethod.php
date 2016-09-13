<?php

namespace SRL\Language\Methods;

use SRL\Exceptions\SyntaxException;
use SRL\Interfaces\Method;

/**
 * Method having no parameters. Will throw SyntaxException if a parameter is provided.
 */
class SimpleMethod extends Method
{
    /**
     * {@inheritdoc}
     */
    public function setParameters(array $params) : Method
    {
        if (!empty($params)) {
            throw new SyntaxException('Invalid parameter.');
        }

        return $this;
    }
}

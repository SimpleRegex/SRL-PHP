<?php

namespace SRL\Language\Methods;

use SRL\Interfaces\Method;

/**
 * Method having simple parameter(s) ignoring "to".
 */
class ToMethod extends Method
{
    public function setParameters(array $params) : Method
    {
        $params = array_filter($params, function ($item) {
            return !is_string($item) || strtolower($item) != 'to';
        });

        return parent::setParameters($params);
    }
}

<?php

namespace SRL\Language\Methods;

use SRL\Interfaces\Method;

/**
 * Method having simple parameter(s) ignoring "and" and "times".
 */
class AndMethod extends Method
{
    public function setParameters(array $params) : Method
    {
        $params = array_filter($params, function ($item) {
            if (!is_string($item)) {
                return true;
            }

            $lower = strtolower($item);

            return $lower != 'and' && $lower != 'times' && $lower != 'time';
        });

        return parent::setParameters($params);
    }
}

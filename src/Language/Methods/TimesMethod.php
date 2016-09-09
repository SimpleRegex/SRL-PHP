<?php

namespace SRL\Language\Methods;

use SRL\Exceptions\SyntaxException;
use SRL\Interfaces\Method;

/**
 * Method having one or two parameters. First is simple, ignoring second "time" or "times". Will throw SyntaxException if more parameters provided.
 */
class TimesMethod extends Method
{
    public function setParameters(array $params) : Method
    {
        $params = array_filter($params, function ($item) {
            if (!is_string($item)) {
                return true;
            }

            $lower = strtolower($item);
            return $lower != 'times' && $lower != 'time';
        });

		if( count( $params ) > 1 )
		{
			throw new SyntaxException('Invalid parameter.');			
		}

        return parent::setParameters($params);
    }
}
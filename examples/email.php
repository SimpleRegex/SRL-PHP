<?php

use SRL\Builder;
use SRL\SRL;

require_once __DIR__ . '/../vendor/autoload.php';

$regex = SRL::startsWith()
    ->eitherOf(function (Builder $query) {
        $query->number()
            ->letter()
            ->literally('._%+-');
    })->onceOrMore()
    ->literally('@')
    ->eitherOf(function (Builder $query) {
        $query->number()
            ->letter()
            ->literally('.-');
    })->onceOrMore()
    ->literally('.')
    ->letter()->atLeast(2)
    ->mustEnd()
    ->caseInsensitive();

var_dump($regex->matches('email@example.com'));
var_dump($regex->matches('invalid email@example.com'));

var_dump(preg_match($regex, 'sample@with.new.tlds'));
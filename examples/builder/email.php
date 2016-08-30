<?php

use SRL\Builder;
use SRL\SRL;

require_once __DIR__ . '/../../vendor/autoload.php';

$regex = SRL::startsWith()
    ->anyOf(function (Builder $query) {
        $query->digit()
            ->letter()
            ->oneOf('._%+-');
    })->onceOrMore()
    ->literally('@')
    ->anyOf(function (Builder $query) {
        $query->digit()
            ->letter()
            ->oneOf('.-');
    })->onceOrMore()
    ->literally('.')
    ->letter()->atLeast(2)
    ->mustEnd()
    ->caseInsensitive();

var_dump($regex->isMatching('email@example.com'));
var_dump($regex->isMatching('invalid email@example.com'));

var_dump(preg_match($regex, 'sample@with.new.tlds'));
<?php

use SRL\SRL;

require_once __DIR__ . '/../../vendor/autoload.php';

$regex = new SRL('BEGIN WITH EITHER OF (NUMBER, LETTER, ONE OF "._%+-") ONCE OR MORE,' .
    'LITERALLY "@", EITHER OF (NUMBER, LETTER, ONE OF ".-") ONCE OR MORE, LITERALLY ".",' .
    'LETTER AT LEAST 2, MUST END, CASE INSENSITIVE');

var_dump($regex->isMatching('email@example.com'));
var_dump($regex->isMatching('invalid email@example.com'));

var_dump(preg_match($regex, 'sample@with.new.tlds'));
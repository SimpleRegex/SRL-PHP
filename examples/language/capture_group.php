<?php

use SRL\SRL;

require_once __DIR__ . '/../../vendor/autoload.php';

$query = new SRL('LITERALLY "colo", OPTIONAL "u", LITERALLY "r:", WHITESPACE,' .
    'CAPTURE (ANY LETTER ONCE OR MORE) AS "color" LITERALLY "."');

var_dump($query->isMatching('my favorite color: blue.')); // true
var_dump($query->isMatching('my favorite colour: green!')); // false

$matches = $query->getMatches('my favorite colour: green. And my favorite color: yellow.');

foreach ($matches as $match) {
    echo 'color: ' . $match->get('color') . "\n";
}
// color: green
// color: yellow

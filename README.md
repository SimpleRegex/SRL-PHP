# Simple Regex Language

[![codecov](https://codecov.io/gh/TYVRNET/SRL/branch/master/graph/badge.svg)](https://codecov.io/gh/TYVRNET/SRL)
[![Build Status](https://travis-ci.org/TYVRNET/SRL.svg?branch=master)](https://travis-ci.org/TYVRNET/SRL)

We all know Regular Expressions are hard to read. Once written you're
happy if you never ever have to touch this line of code again because
you're going to have a hard time understanding what you've written there.

Let's change that! Regular Expressions don't have to be that bulky.

## An Example

They don't have to be bulky? - No, they don't! Just have a look at this:

```
begin with either of (number, letter, one of "._%+-") once or more,
literally "@",
either of (number, letter, one of ".-") once or more,
literally ".",
letter at least 2,
must end, case insensitive
```

Or, if you like, a implementation in code itself:

```php
$query = SRL::startsWith()
    ->eitherOf(function (Builder $query) {
        $query->number()
            ->letter()
            ->oneOf('._%+-');
    })->onceOrMore()
    ->literally('@')
    ->eitherOf(function (Builder $query) {
        $query->number()
            ->letter()
            ->oneOf('.-');
    })->onceOrMore()
    ->literally('.')
    ->letter()->atLeast(2)
    ->mustEnd()->caseInsensitive();
```

Yes, indeed, both examples are definitely longer than the corresponding
regular expression:

```
/^([A-Z0-9._%+-])+@[A-Z0-9.-]+\.[A-Z]{2,}$/i
```

But, however, the above is quite better to read and definitely better
to maintain, isn't it?

Let's go through it real quick:

1. First, we require the matching string to start. This way, we make sure
the match won't begin in the middle of something.
2. Now, we're matching either a number, a letter, or one of the literal
characters ., _, %, + or -. We expect there to be one or more of them.
3. We now expect exactly one @ - Looks like an email address.
4. Again, either numbers, letters or . or -, once or multiple times.
5. A dot. Seems to be the end of the TLDs name
6. To the end, we'll expect two or more letters, for the TLD.
7. We require the string to end now, to avoid matching stuff like 
`invalid@email.com123`.
8. And of course, all of that should be case insensitive, since it's
an email address.

## Features

### Using the Language

Above you can see two examples. The first one uses the language itself,
the second one the Query Builder. Since using a language is more fluent
than a builder, we wanted to make things as easy as possible for you.

```php
$srl = new SRL('literally "colo", optional "u", literally "r"');
preg_match($srl, 'color') // 1
$srl->isMatching('colour') // true
$srl->isMatching('soup') // false
```

Everything below applies to both, the SRL itself and the Query Builder.

### Matching

SRL is as simple as the example above states. To retrieve
the built Regular Expression which can be used by external tools like
[preg_match](http://php.net/manual/en/function.preg-match.php), either
use the `->get()` method, or just let it cast to a string:

```php
preg_match($query, 'sample@email.com');
```

Of course, you may use the builtin match methods for an even easier
approach:

```php
$query->isMatching('sample@email.com'); // true
$query->isMatching('invalid-email.com'); // false
```

### Capture Groups

Since regular expressions aren't only used for validation, capturing
groups is supported by SRL as well. After defining the Regular
Expression just like before, simply add a `capture`-group which will
match the query defined in the lambda function. Optionally, a name for
that capture group (`color`) can be set as well:

```php
// Using SRL
$regEx = new SRL('literally "color:", whitespace, capture (letter once or more) as "color", literally "."');

// Using the query builder
$regEx = SRL::literally('color:')->whitespace()->capture(function (Builder $query) {
    $query->letter()->onceOrMore();
}, 'color')->literally('.');

$matches = $regEx->getMatches('Favorite color: green. Another color: yellow.');

echo $matches[0]->get('color'); // green
echo $matches[1]->get('color'); // yellow
```

Each match will be passed to a `SRL\Match` object, which will return the
matches found.

### Additional PCRE functions

Feel free to use all the available [PCRE PHP functions](http://php.net/manual/en/ref.pcre.php)
in combination with SRL. Although, why bother? We've got wrappers for
all common functions with additional features. Just like above, just
apply one of the following methods directly on the SRL or Builder:

* `isMatching()` - Validate if the expression matches the given string.
* `getMatches()` - Get all matches for supplied capture groups.
* `getMatch()` - Get first match for supplied capture groups.
* `replace()` - Replace data using the expression.
* `split()` - Split string into array through expression.
* `filter()` - Filter items using the expression.

### Lookarounds

In case you want some regular expressions to only apply in certain
conditions, lookarounds are probably what you're searching for.

With queries like:

```php
// SRL:
new SRL('capture (literally "foo") if followed by (literally "bar")');

// Query Builder:
SRL::capture(function (Builder $query) {
    $query->literally('foo');
})->ifFollowedBy(function (Builder $query) {
    $query->literally('bar');
});
```

you can easily capture 'foo', but only if this match is followed by
'bar'.

But to be honest, the Query Builder version is quite much code for
such a simple thing, right? No problem! Not only are we supporting
anonymous functions for sub-expressions, strings and Builder objects
are supported as well.
Isn't that great? Just have a look at one possible example:

```php
SRL::capture('foo')->ifFollowedBy(SRL::literally('bar'));
```

If desired, lookbehinds are possible as well. Using `ifAlreadyHad()`
you can validate a certain condition only if the previous string
contained a specific pattern.

## Performance

The built Regular Expression will be cached, so you don't have to worry
about it being created every time you call the `match`-method. And,
since it's a normal Regular Expression under the hood, performance
won't be an issue.

Of course, building the expression may take some time, but in real life
applications this shouldn't be noticeable. But if you like, you can
build the expression somewhere else and just use the result in your app.
If you do that, please keep the code for that query somewhere and link
to it, otherwise the Regular Expression will be unreadable just as before.

## Usage

Add the package to your ``require`` section in the ``composer.json``-file
and update your project.

```json
"require": {
    "tyvrnet/srl": "0.1.x-dev"
}
```

```sh
composer update
```

## Things to do

We're definitely not done yet. There's much to come. A short list of
stuff that's planned would contain:

* More functionality
* More documentation
* Variable support
* Rule the world

## License

    Copyright (C) 2016 Karim Geiger <karim@tyvr.net>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

## Contribution

Like this project? Want to contribute? Awesome! Feel free to open some
pull requests or just open an issue.
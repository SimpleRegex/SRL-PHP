# Simple Regex Language

[![codecov](https://codecov.io/gh/TYVRNET/SRL/branch/master/graph/badge.svg)](https://codecov.io/gh/TYVRNET/SRL)
[![Build Status](https://travis-ci.org/TYVRNET/SRL.svg?branch=master)](https://travis-ci.org/TYVRNET/SRL)

We all know Regular Expressions are hard to read. Once written you're
happy if you never ever have to touch this line of code again because
you're going to have a hard time understanding what you've written there.

Let's change that! Regular Expressions don't have to be that bulky.

## An Example

They don't have to be bulky? - No, they don't! Just have a look at this:

```php
$query = SRL::startsWith()
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
    ->mustEnd()->caseInsensitive();
```

Yes, indeed, this is definitely longer than the corresponding regular expression:

```
/^([A-Z]|[0-9._%+-])+@[A-Z0-9.-]+\.[A-Z]{2,}$/i
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

### Matching

The SRL Builder is as simple as the example above states. To retrieve
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
$query = SRL::literally('color:')
    ->whitespace()->capture(function (Builder $query) {
        $query->anyLetter()->onceOrMore();
    }, 'color')->literally('.');

$matches = $query->getMatches('Favorite color: green. Another color: yellow.');

echo $matches[0]->getMatch(); // green
echo $matches[1]->getMatch(); // yellow
echo $matches[0]->getName(); // color
```

Each match will be passed to a `SRL\Match` object, which will return the
matches found.

### Additional PCRE function

Feel free to use all the available [PCRE PHP functions](http://php.net/manual/en/ref.pcre.php)
in combination with SRL. Although, why bother? We've got wrappers for
all common functions with additional features. Just like above, just
apply one of the following methods directly on the SRL Builder:

* `isMatching()` - Validate if the expression matches the given string.
* `getMatches()` - Get all matches for supplied capture groups.
* `replace()` - Replace data using the expression.
* `split()` - Split string into array through expression.
* `filter()` - Filter items using the expression.

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

* SQL-Like syntax: `BEGIN WITH EITHER (NUMBER, LETTER, ._%+-) ...`
* More functionality
* More documentation
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
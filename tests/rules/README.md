# Test Rules

Test rules are made to verify that your implementation of SRL is valid.
These files contain simple tests to validate the SRL and the
corresponding results. The structure is easy to understand and implement.

## Structure of a .rule File

These rules are required to build valid test rules:

* All files used for testing must end with the extension `.rule` and at
least contain one valid assertion along with the SRL query.
* The query is defined through `srl: ` on the beginning of a line.
* All strings that should match are defined through `match: ` on the
beginning of a line.
  * There can be unlimited `match: ` lines per rule.
  * Each match must be surrounded by `"`.
* All strings that should **not** match are defined through `no match: `
on the beginning of a line.
  * There can be unlimited `no match: ` lines per rule.
  * Each match must be surrounded by `"`.
* If a capture group is defined, its result can be defined as follows:
  * The line must begin with `capture for `.
  * Surrounded by `"`, the test string to match must be provided, followed by a `: `.
  * If a named group is desired, use the following syntax: `name: "result"`
  * If a anonymous group is desired, just supply `"result"`.
  * Separate multiple captures using `, `.
  * If one expression returns multiple matches, supply the same test string in the second line.
* The query as well as the expectations must not exceed one line.
If required, new lines can be forced using `\n`. Tabs using `\t`.
* Comments must be on a separate line and start with a `#`.

## Example .rule Files

```
# This is a sample rule with a named capture group
srl: capture (letter twice) as "foo"
capture for "aa1":
- 0: foo: "aa"
match: "example"
match: "aa2"
no match: "a"
```

```
# This is a sample rule with an anonymous capture group and multiple results
srl: capture (digit)
capture for "123":
- 0: 0: "1"
- 1: 0: "2"
- 2: 0: "3"

capture for "01":
- 0: 0: "0"
- 1: 0: "1"
```
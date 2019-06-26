## PHP Shunting Yard Implementation

### Fork Changes
The general goal of this fork is to take the existing framework for safe formula evaluation and add the needed functionality for more general inputs (whereas the original is fairly heavily focused on numbers alone).

To this end, the following changes are made:
  * Constants can be any PHP value, not just numeric values. (Internally, they are represented as `T_NATIVE`, and subsume `T_NULL`.)
    * The `||` operator was added for concatenation. (This operator is given precedence above equality but below addition and subtraction. Thus, `2 + 3 || 3 + 4 = "57"`.)
    * The `+` operator acts as concatenation if either side is non-numeric. (Thus, `"2" + "3" = 5`, but `"2 " + "3" = "2 3"`)
    * String literals are supported -- either as `"string"` or `'string'`. (Escaping is not supported.)
    
  * Variable names have been expanded:
    * Now, any string matching `/\p{L}\p{N}\.]+/` (that is, containing only unicode letters, numbers, and the symbol `.`) will be treated as a variable name. This means that `littérature + 手紙` is a valid formula that is adding two variables.
    * A special syntax, `${xyz}` is allotted for more complex variable references -- this supports any character in the variable name other than `}`.
    * Unregistered variables evaluate to 0 instead of causing an exception. A strict mode flag on Context can re-enable the old behaviour.
    
  * A handful of more opinionated changes were made to make the overall syntax more user-friendly:
    * No default constants are set. At construction time, an array can be passed to set all constants at once.
    * `and`, `or`, and `not` are now synonyms for `&`, `|`, and `!` respectively.
    * `if`, `coalesce`, `min`, and `max` functions are registered by default.
    * The xor operator was removed to avoid possible confusion (it was `><`).

Unit tests have been written for all of these changes. All existing and new tests pass.

Finally, while not included, I personally recommend using this package by overloading the Context like so:
```
new class($array) extends Context
{
    public function cs($name) {
        return your_array_getter($this->constants, $name);
    }
}
```

While the default class only performs normal array lookups, there are many opportunities for more advanced array lookups. Laravel's array_get provides basic dot lookups, or https://github.com/Galbar/JsonPath-PHP could be used for full JSONPath lookups.

### Example

Simple equation parsing
```php
use RR\Shunt\Parser;

$equation = '3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3';
$result = Parser::parse($equation);
echo $result; //3.0001220703125
```

Equation with constants and functions
```php
use RR\Shunt\Parser;
use RR\Shunt\Context;

$ctx = new Context();
$ctx->def('abs'); // wrapper for PHP "abs" function
$ctx->def('foo', 5); // constant "foo" with value "5"
$ctx->def('bar', function($a, $b) { return $a * $b; }); // define function

$equation = '3 + bar(4, 2) / (abs(-1) - foo) ^ 2 ^ 3';
$result = Parser::parse($equation, $ctx);
echo $result; //3.0001220703125
```

Test a condition
```php
use RR\Shunt\Parser;
use RR\Shunt\Context;

$ctx = new Context();
$ctx->def('foo', 5); // constant "foo" with value "5"

$equation = '(foo > 3) & (foo < 6)';
$result = Parser::parse($equation, $ctx);
echo $result; //true
```

Re-run parsed expression on multiple inputs
```php
use RR\Shunt\Parser;
use RR\Shunt\Context;

$counter = 1;
$ctx = new Context();
$ctx->def('data', function() { global $counter; return $counter++; }); // define function
$ctx->def('bar', function($a) { return 2*$a; }); // define function

$equation = 'bar(data())';
$parser = new Parser(new Scanner($equation));

$result = $parser->reduce($this->ctx); // first result
echo $result; // 2
$result = $parser->reduce($this->ctx); // second result
echo $result; // 4
```

### Installation

Define the following requirement in your composer.json file:

```json
{
    "require": {
        "freezewarp/php-shunting-yard": "dev-master"
    }
}
```

### Authors

This is a fork of https://github.com/andig/php-shunting-yard, which in turn used code from:

  - https://github.com/droptable/php-shunting-yard
  - https://github.com/andig/php-shunting-yard
  - https://github.com/pmishev/php-shunting-yard
  - https://github.com/falahati/php-shunting-yard
  - https://github.com/sergej-kurakin/php-shunting-yard

## PHP Shunting Yard Implementation

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

### Authors

Originally source code taken from https://github.com/droptable/php-shunting-yard, some changes from:

  - https://github.com/andig/php-shunting-yard
  - https://github.com/pmishev/php-shunting-yard 
  - https://github.com/falahati/php-shunting-yard

Test cases and refactoring for composer/packagist by https://github.com/sergej-kurakin/php-shunting-yard.


### Installation Via Composer

Define the following requirement in your composer.json file:

```json
{
    "require": {
        "andig/php-shunting-yard": "dev-master"
    }
}
```

Class loading

```php
require_once "vendor/autoload.php";
```


### Manual Installation

Class loading

```php
require_once "RR/Shunt/Context.php";
require_once "RR/Shunt/Parser.php";
require_once "RR/Shunt/Scanner.php";
require_once "RR/Shunt/Token.php";
require_once "RR/Shunt/Exception/ParseError.php";
require_once "RR/Shunt/Exception/RuntimeError.php";
require_once "RR/Shunt/Exception/SyntaxError.php";
```


### MIT Licence

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

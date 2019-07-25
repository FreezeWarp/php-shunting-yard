<?php

/*!
 * PHP Shunting-yard Implementation
 * Copyright 2012 - droptable <murdoc@raidrush.org>
 *
 * PHP 5.3 required
 *
 * Reference: <http://en.wikipedia.org/wiki/Shunting-yard_algorithm>
 *
 * ----------------------------------------------------------------
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without
 * limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to
 * whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * <http://opensource.org/licenses/mit-license.php>
 */

namespace RR\Shunt;

use RR\Shunt\Exception\SyntaxError;

class Token
{
    const T_NUMBER          = 1,  // a number (integer / double)
          T_IDENT           = 2,  // constant
          T_NATIVE          = 3,  // a string
          T_FUNCTION        = 4,  // function
          T_POPEN           = 8,  // (
          T_ARRAY_OPEN      = 9,  // [
          T_PCLOSE          = 16, // )
          T_ARRAY_CLOSE     = 17, // ]
          T_COMMA           = 32, // ,
          T_PAIR            = 33,  // an array pair (a -> b)
          T_OPERATOR        = 64, // operator (currently unused)
          T_PLUS            = 65, // +
          T_MINUS           = 66, // -
          T_TIMES           = 67, // *
          T_DIV             = 68, // /
          T_MOD             = 69, // %
          T_POW             = 70, // ^
          T_UNARY_PLUS      = 71, // + unsigned number (determined during parsing)
          T_UNARY_MINUS     = 72, // - signed number (determined during parsing)
          T_NOT             = 73, // !
          T_CONCAT          = 74, // ||
          T_NULL            = 128, // null
          T_GREATER_EQUAL   = 256, // >=
          T_LESS_EQUAL      = 512, // <=
          T_GREATER         = 1024, // >
          T_LESS            = 2048, // <
          T_EQUAL           = 4096, // =
          T_NOT_EQUAL       = 8192, // <>
          T_AND             = 16384, // &
          T_OR              = 32768, // |
          T_XOR             = 65536; // ><

    public $type;
    public $value;
    public $argc = 0;

    public function __construct($type, $value)
    {
        $this->type  = $type;
        $this->value = $value;
    }

    public static function auto($value)
    {
        if (is_numeric($value)) {
            return new self(self::T_NUMBER, $value);
        } else {
            return new self(self::T_NATIVE, $value);
        }
    }
}

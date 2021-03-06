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

class Scanner
{

    const PATTERN = '/^('
        . '[<>]=|<>|-\>|\|\||[!,><=&\|\+\-\*\/\^%\(\)\[\]]' // operator
        . '|'
        . '\d*\.\d+|\d+\.\d*|\d+' // number
        . '|'
        . '[\p{L}\p{N}\._]+|\$\{[^\}]+\}' // word (variable reference)
        . '|'
        . '"[^"]*"|\'[^\']*\'' // string literal
        . '|'
        . '\s+' // space
        . '|'
        . '#.*(\n|$)' // comment
    . ')/um';

    const ERR_EMPTY = 'nothing found! (endless loop) near: `%s`';
    const ERR_MATCH = 'syntax error near `%s`';

    protected $tokens = array( 0 );

    protected $lookup = array(
        '>=' => Token::T_GREATER_EQUAL,
        '<=' => Token::T_LESS_EQUAL,
        '<>' => Token::T_NOT_EQUAL,
        '>' =>	Token::T_GREATER,
        '<' =>	Token::T_LESS,
        '=' =>	Token::T_EQUAL,
        '&' =>	Token::T_AND,
        'and' => Token::T_AND,
        '|' =>	Token::T_OR,
        'or' => Token::T_OR,

        '+' => Token::T_PLUS,
        '-' => Token::T_MINUS,
        '/' => Token::T_DIV,
        '%' => Token::T_MOD,
        '^' => Token::T_POW,
        '*' => Token::T_TIMES,
        '(' => Token::T_POPEN,
        ')' => Token::T_PCLOSE,
        '!' => Token::T_NOT,
        'not' => Token::T_NOT,
        ',' => Token::T_COMMA,

        '[' => Token::T_ARRAY_OPEN,
        ']' => Token::T_ARRAY_CLOSE,
        '->' => TOKEN::T_PAIR,

        '||' =>	Token::T_CONCAT,
        'in' => Token::T_IN
    );

    public function __construct($input)
    {

        $prev = new Token(Token::T_OPERATOR, 'noop');

        while (trim($input) !== '') {

            if (!preg_match(self::PATTERN, $input, $match)) {
                // syntax error
                throw new SyntaxError(sprintf(self::ERR_MATCH, $input));
            }

            if (empty($match[1]) && $match[1] !== '0') {
                // nothing found -> avoid endless loop
                throw new SyntaxError(sprintf(self::ERR_EMPTY, substr($input, 0, 10)));
            }

            // Remove the first matched token from the input, for the next iteration
            $input = substr($input, strlen($match[1]));

            // Get the value of the matched token
            $value = trim($match[1]);

            /*
             * While a bit... or possibly very... silly, the idea here is that we allow an extending class the ability to easily intercept our scanner at any time, which we do by passing it:
             * 1.) the current list of tokens
             * 2.) the value just obtained, which will become its own token
             * 3.) the remaining input after the value just obtained
             *
             * We pass all three by reference, letting an extending class modify them to its liking, hopefully in a memory-efficient way as a bonus.
             *
             * In my specific case, I wanted to be able to intercept a -> token that isn't contained by brackets, which I could do by checking to see if the currently parsed tokens includes more opening brackets than closing brackets. If both of these are true, I then modify the value and input to be empty, resulting in the scanner stopping here, returning a value, which I would then use the leftover input I captured to process further.
             */
            $this->intercept($this->tokens, $value, $input);

            // Ignore whitespace and comment matches
            if ($value === '' || preg_match('/^#.*$/m', $value)) {
                continue;
            }

            // Numeric values are automatically treated as such.
            else if (is_numeric($value)) {
                if ($prev->type === Token::T_PCLOSE) { // Support the form (n)(m) or n(m), e.g. (2)(3) or 2(3)
                    $this->tokens[] = new Token(Token::T_TIMES, '*');
                }

                $this->tokens[] = $prev = new Token(Token::T_NUMBER, (float) $value);
                continue;
            }

            // Strings are automatically treated as such (escaping is not currently possible)
            else if ($value[0] === '"' || $value[0] === "'") {
                $this->tokens[] = $prev = new Token(Token::T_NATIVE, substr($value, 1, -1));
                continue;
            }

            else {

                // Unless token is one of the predefined symbols, consider it an identifier token
                $tokenType = isset($this->lookup[$value]) ? $this->lookup[$value] : Token::T_IDENT;

                switch ($tokenType) {
                    case Token::T_PLUS:
                        if ($prev->type & Token::T_OPERATOR || $prev->type & Token::T_POPEN || $prev->type & Token::T_COMMA) {
                            $tokenType = Token::T_UNARY_PLUS;
                        }
                        break;

                    case Token::T_MINUS:
                        if ($prev->type & Token::T_OPERATOR || $prev->type & Token::T_POPEN || $prev->type & Token::T_COMMA) {
                            $tokenType = Token::T_UNARY_MINUS;
                        }
                        break;

                    case Token::T_POPEN:
                        switch ($prev->type) {
                            case Token::T_IDENT:
                                $prev->type = Token::T_FUNCTION; // An identify followed by an opening paren becomes a function call, e.g. a(b) becomes the function a with the input b
                                break;

                            case Token::T_NUMBER:
                            case Token::T_PCLOSE:
                                // allowed 2(2) -> 2 * 2 | (2)(2) -> 2 * 2
                                $this->tokens[] = new Token(Token::T_TIMES, '*');
                                break;
                        }
                        break;

                    case Token::T_IDENT:
                        if (strtolower($value) === 'null') {
                            $tokenType = Token::T_NATIVE;
                            $value = null;
                        }

                        else if (strtolower($value) === 'true') {
                            $tokenType = Token::T_NATIVE;
                            $value = true;
                        }

                        else if (strtolower($value) === 'false') {
                            $tokenType = Token::T_NATIVE;
                            $value = false;
                        }

                        else if ($value[0] === '$' && $value[1] === '{') { // Explicit variable names possible in the format ${xyz}
                            $value = substr($value, 2, -1);
                        }


                        // Two identity tokens next to each other will be concatenated.
                        if (
                            $tokenType === Token::T_IDENT
                            && $prev->type === Token::T_IDENT
                        ) {
                            $this->tokens[] = new Token(Token::T_PLUS, '+');
                        }
                }

                $this->tokens[] = $prev = new Token($tokenType, $value);

            }

        }

    }

    /**
     * This is designed to be overridden, allowing extending classes to intercept tokens as they are being scanned.
     */
    protected function intercept(&$tokens, &$value, &$input)
    {
        return;
    }

    public function reset()
    {
        reset($this->tokens);
    } // call before reusing Scanner instance

    public function curr()
    {
        return current($this->tokens);
    }
    
    public function next()
    {
        return next($this->tokens);
    }

    public function prev()
    {
        return prev($this->tokens);
    }

    public function dump()
    {
        print_r($this->tokens);
    }

    public function peek()
    {
        $v = next($this->tokens);
        prev($this->tokens);

        return $v;
    }
}
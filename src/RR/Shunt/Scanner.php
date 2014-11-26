<?php

/**
 * PHP Shunting-yard Implementation
 * Copyright 2012 - droptable <murdoc@raidrush.org>
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
	//              operator_________________________________|number_______________|word____________________|space_
	const PATTERN = '/^([<>]=|<>|><|[!,><=&\|\+\-\*\/\^%\(\)]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Zπ]+[a-z_A-Z0-9]*|[ \t]+)/';

	const ERR_EMPTY = 'nothing found! (endless loop) near: `%s`';
	const ERR_MATCH = 'syntax error near `%s`';

	protected $tokens = array( 0 );

	protected $lookup = array(
		'>=' => Token::T_GREATER_EQUAL,
		'<=' => Token::T_LESS_EQUAL,
		'<>' => Token::T_NOT_EQUAL,
		'><' => Token::T_XOR,
		'>' =>	Token::T_GREATER,
		'<' =>	Token::T_LESS,
		'=' =>	Token::T_EQUAL,
		'&' =>	Token::T_AND,
		'|' =>	Token::T_OR,

		'+' => Token::T_PLUS,
		'-' => Token::T_MINUS,
		'/' => Token::T_DIV,
		'%' => Token::T_MOD,
		'^' => Token::T_POW,
		'*' => Token::T_TIMES,
		'(' => Token::T_POPEN,
		')' => Token::T_PCLOSE,
		'!' => Token::T_NOT,
		',' => Token::T_COMMA
	);

	public function __construct($input)
	{
		$prev = new Token(Token::T_OPERATOR, 'noop');

		while (trim($input) !== '') {

			if (!preg_match(self::PATTERN, $input, $match)) {
				// syntax error
				throw new SyntaxError(sprintf(self::ERR_MATCH, substr($input, 0, 10)));
			}

			if (empty($match[1]) && $match[1] !== '0') {
				// nothing found -> avoid endless loop
				throw new SyntaxError(sprintf(self::ERR_EMPTY, substr($input, 0, 10)));
			}

			// Remove the first matched token from the input, for the next iteration
			$input = substr($input, strlen($match[1]));

			// Get the value of the matched token
			$value = trim($match[1]);

			// Ignore whitespace matches
			if ($value === '') {
				continue;
			}

			if (is_numeric($value)) {
				if ($prev->type === Token::T_PCLOSE)
					$this->tokens[] = new Token(Token::T_TIMES, '*');

				$this->tokens[] = $prev = new Token(Token::T_NUMBER, (float) $value);
				continue;
			}

			// Unless token is one of the predefined symbols, consider it an identifier token
			$tokenType = isset($this->lookup[$value]) ? $this->lookup[$value] : Token::T_IDENT;

			switch ($tokenType) {
				case Token::T_PLUS:
					if ($prev->type & Token::T_OPERATOR || $prev->type == Token::T_POPEN || $prev->type == Token::T_COMMA) {
						$tokenType = Token::T_UNARY_PLUS;
					}
					break;

				case Token::T_MINUS:
					if ($prev->type & Token::T_OPERATOR || $prev->type == Token::T_POPEN || $prev->type == Token::T_COMMA) {
						$tokenType = Token::T_UNARY_MINUS;
					}
					break;

				case Token::T_POPEN:
					switch ($prev->type) {
						case Token::T_IDENT:
							$prev->type = Token::T_FUNCTION;
							break;

						case Token::T_NUMBER:
						case Token::T_PCLOSE:
							// allowed 2(2) -> 2 * 2 | (2)(2) -> 2 * 2
							$this->tokens[] = new Token(Token::T_TIMES, '*');
							break;
					}
					break;

				case Token::T_IDENT:
					if (strcasecmp($value, 'null') == 0) {
						$tokenType = Token::T_NULL;
						$value = null;
					}
			}

			$this->tokens[] = $prev = new Token($tokenType, $value);
		}
	}

	public function reset() { reset($this->tokens); } // call before reusing Scanner instance
	public function curr() { return current($this->tokens); }
	public function next() { return next($this->tokens); }
	public function prev() { return prev($this->tokens); }

	public function peek()
	{
		$v = next($this->tokens);
		prev($this->tokens);

		return $v;
	}

	public function dump($formatted = false) {

		if (!$formatted) {
			var_dump($this->tokens);
		} else {
			$clonedTokens = array();
			foreach ($this->tokens as $token) {
				if (is_object($token)) {
					$cloned_token = clone($token);
					$cloned_token->type .= ' ('.$cloned_token->getTypeName().')';
					$clonedTokens[] = $cloned_token;
				} else {
					$clonedTokens[] = $token;
				}
			}
			var_dump($clonedTokens);die;
		}
	}
}

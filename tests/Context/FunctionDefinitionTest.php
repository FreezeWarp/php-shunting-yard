<?php

namespace tests\Context;

use RR\Shunt\Context;
use RR\Shunt\Exception\RuntimeError;
use Exception;
use RR\Shunt\Token;

class FunctionDefinitionTest extends \PHPUnit\Framework\TestCase
{
    public function testFunctionDefinitionAndCall()
    {
        $context = new Context();

        $context->def('func', function ($param1) {
            return $param1;
        });

        $actual = $context->fn('func', array(Token::auto(3)));

        $this->assertEquals(3.0, $actual);
    }

    public function testSystemFunctionDefinition()
    {
        $context = new Context();

        $context->def('abs');

        $actual = $context->fn('abs', array(Token::auto(-3)));

        $this->assertEquals(3.0, $actual);
    }

    public function testCallNotsetFunctionCausesException()
    {
        $this->expectException(\RR\Shunt\Exception\RuntimeError::class);

        $context = new Context();

        $context->fn('notdefinedfunction', array(Token::auto(-3)));
    }

    public function testFunctionDefinitionWithOptionalParams()
    {
        $context = new Context();

        $context->def('func', function ($param1, $param2 = 100) {
            return ($param1 + $param2);
        });

        $actual = $context->fn('func', array(Token::auto(3)));

        $this->assertEquals(103.0, $actual);

        $actual = $context->fn('func', array(Token::auto(3), Token::auto(200)));

        $this->assertEquals(203.0, $actual);
    }
}

<?php

namespace tests\Context;

use RR\Shunt\Context;
use RR\Shunt\Exception\RuntimeError;
use Exception;

class ConstantDefinitionTest extends \PHPUnit\Framework\TestCase
{
    public function constantsProvider()
    {
        return array(
            array(
                3,
                3.0,
            ),
            array(
                '3',
                3.0,
            ),
            array(
                3.2,
                3.2,
            ),
            array(
                '3.2',
                3.2,
            ),
            array(
                0.2,
                0.2,
            ),
            array(
                '0.2',
                0.2,
            ),
            array(
                '.2',
                0.2,
            ),
        );
    }

    /**
     * @param $value
     * @param $expected
     *
     * @dataProvider constantsProvider
     */
    public function testConstantDefinitionAndCall($value, $expected)
    {
        $context = new Context();
        $context->def('const', $value);
        $actual = $context->cs('const');
        $this->assertEquals($expected, $actual);
    }

    public function testNonNumericConstantDefinition()
    {
        $context = new Context();
        $context->def('const', 'string constant', 'string');

        $this->assertEquals($context->cs('const'), 'string constant');
    }

    public function testCallNotSetConstantCausesException()
    {
        $this->expectException(\RR\Shunt\Exception\RuntimeError::class);

        $context = new Context();
        $context->setStrictMode(true);
        $context->cs('notdefinedfunction');
    }
}

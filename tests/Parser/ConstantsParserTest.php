<?php

namespace tests\Parser;

use RR\Shunt\Context;
use RR\Shunt\Parser;

class ConstantsParserTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @param $equation
     * @param array $constants
     * @param $expected
     *
     * @dataProvider equationAndConstantsProvider
     */
    public function testParserWithConstants($equation, array $constants, $expected)
    {
        $context = new Context();

        foreach ($constants as $key => $val) {
            $context->def($key, $val);
        }

        $actual = Parser::parse($equation, $context);

        $this->assertEquals($expected, $actual);
    }

    public function equationAndConstantsProvider()
    {
        return array(
            array(
                'a+b',
                array(
                    'a' => 4,
                    'b' => 3,
                ),
                (4+3),
            ),
            array(
                '-a',
                array(
                    'a' => 1,
                ),
                (-1),
            ),
            array(
                'a-b',
                array(
                    'a' => 4,
                    'b' => 3,
                ),
                (4-3),
            ),
            array(
                'a-b',
                array(
                    'a' => 3,
                    'b' => 4,
                ),
                (3-4),
            ),
            array(
                'cc*dr',
                array(
                    'cc' => 3,
                    'dr' => 4,
                ),
                (3*4),
            ),
            array(
                'cc/dr',
                array(
                    'cc' => 3,
                    'dr' => 4,
                ),
                (3/4),
            ),
            array(
                'cc^dr',
                array(
                    'cc' => 3,
                    'dr' => 4,
                ),
                pow(3, 4),
            ),
            array(
                'a+b-c^d',
                array(
                    'a' => 1,
                    'b' => 3,
                    'c' => 8,
                    'd' => 4,
                ),
                (1+3-pow(8, 4))
            ),
            array(
                '2(a+b)^c',
                array(
                    'a' => 3,
                    'b' => 1,
                    'c' => -2,
                ),
                0.125
            ),
        );
    }


    /**
     * @param $equation
     * @param array $constants
     * @param $expected
     *
     * @dataProvider equationAndConstantsForkedProvider
     */
    public function testForkedParserWithConstants($equation, array $constants, $expected)
    {
        $context = new Context($constants);

        $actual = Parser::parse($equation, $context);

        $this->assertEquals($expected, $actual);
    }

    public function equationAndConstantsForkedProvider()
    {
        return array(
            array(
                'a+${b}',
                array(
                    'a' => "4",
                    'b' => "3",
                ),
                (4+3),
            ),
            array(
                '-${a}',
                array(
                    'a' => "1",
                ),
                (-1),
            ),
            array(
                '${a}-b.c',
                array(
                    'a' => "4",
                    'b.c' => "7",
                ),
                (4-7),
            ),
            array( // arguably invalid
                '${a} ${b}',
                array(
                    'a' => 4,
                    'b' => 3,
                ),
                7,
            ),
            array( // should maybe include some extra space?
                '${a} ${b.c}',
                array(
                    'a' => "hello",
                    'b.c' => "my honey",
                ),
                "hellomy honey",
            ),
            array( // should maybe include some extra space?
                '${a} || " " || b.c',
                array(
                    'a' => "hello",
                    'b.c' => "my honey",
                ),
                "hello my honey",
            ),
            array(
                'if(${a}, ${b}, ${c})',
                array(
                    'a' => true,
                    'b' => 100,
                    'c' => 1000,
                ),
                100,
            ),
            array(
                'if(${a}, ${b}, ${c})',
                array(
                    'a' => 0,
                    'b' => 100,
                    'c' => 1000,
                ),
                1000,
            ),
            array(
                'coalesce(${a}, ${b}, ${c})',
                array(
                    'a' => 0,
                    'b' => 100,
                    'c' => 1000,
                ),
                100,
            ),
            array(
                'coalesce(${a}, ${c}, ${b})',
                array(
                    'a' => 0,
                    'b' => 100,
                    'c' => 1000,
                ),
                1000,
            ),
            array(
                '${hello my honey}*${hello my darling!}',
                array(
                    'hello my honey' => 4,
                    'hello my darling!' => 3,
                ),
                12,
            ),
            array(
                '手紙+${123}',
                array(
                    '手紙' => 3,
                    '123' => 4,
                ),
                (3+4),
            ),
            array(
                '(手紙)(${la littérature})',
                array(
                    '手紙' => 3,
                    'la littérature' => 4,
                ),
                12,
            ),
            array(
                '手紙+${howdy}',
                array(
                    '手紙' => 7
                ),
                7,
            ),
            array(
                'if(a, b, c)',
                array(
                    'a' => false,
                    'b' => "hello",
                    'c' => "goodbye"
                ),
                "goodbye",
            ),
            array(
                'if(a, b, c)',
                array(
                    'a' => "true",
                    'b' => "hello",
                    'c' => "goodbye"
                ),
                "hello",
            ),
            array(
                'if(a, b, c)',
                array(
                    'a' => [],
                    'b' => ['a', 'b'],
                    'c' => ['a', 'c']
                ),
                ['a', 'c'],
            ),
        );
    }

    public function testParserWithStringConstants()
    {
        $context = new Context();
        $const = 'string constant';
        $context->def('const', $const, 'string');

        $actual = Parser::parse('const', $context);

        $this->assertEquals($const, $actual);
    }
}

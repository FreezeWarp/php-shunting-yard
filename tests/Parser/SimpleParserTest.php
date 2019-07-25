<?php

namespace tests\Parser;

use RR\Shunt\Parser;
use RR\Shunt\Exception\RuntimeError;

class SimpleParserTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @param $equation
     * @param $expected
     *
     * @dataProvider simpleEquations
     */
    public function testParserWithSimpleEquations($equation, $expected)
    {
        $actual = Parser::parse($equation);

        $this->assertEquals($expected, $actual);
    }

    public function simpleEquations()
    {
        return array(
            array(
                '2+3',
                5.0,
            ),
            array(
                '2-3',
                -1.0,
            ),
            array(
                '2*3',
                6.0,
            ),
            array(
                '2/3',
                (2/3),
            ),
            array(
                '3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3',
                3.0001220703125,
            ),
            array(
                '3*(3+4)^(1+2)',
                1029.0,
            ),
            array(
                '4^(-2)',
                0.0625,
            ),
            array(
                '4^-2',
                0.0625,
            ),

            // exclamation / not
            array(
                '!1',
                0,
            ),
            array(
                '!0',
                1,
            ),
        );
    }

    /**
     * @param $equation
     * @param $expected
     *
     * @dataProvider simpleForkedEquations
     */
    public function testForkModifications($equation, $expected)
    {
        $actual = Parser::parse($equation);

        $this->assertEquals($expected, $actual);
    }

    public function simpleForkedEquations()
    {
        return array(
            array(
                '"2"+"3"',
                "5",
            ),
            array(
                '2 || 3',
                "23",
            ),
            array(
                '"2"+3',
                "5",
            ),
            array(
                '"2 "+3',
                "2 3",
            ),
            array(
                '"2" || 3',
                "23",
            ),
            array(
                '2 + 3 || 3 + 4',
                "57",
            ),
            array(
                '2 + 3 || 3 + 4 = "57"',
                true,
            ),
            array(
                '"2a"+"3"',
                "2a3",
            ),
            array(
                '"2a"+3',
                "2a3",
            ),
            array(
                '"hello" + "my honey"',
                "hellomy honey",
            ),
            array(
                '"2" + "my honey"',
                "2my honey",
            ),
            array(
                '${2}+3',
                3,
            ),
            array(
                'min(1,2)',
                1,
            ),
            array(
                'min(1,2,3,-1)',
                -1,
            ),
            array(
                'max(1,2,3)',
                3,
            ),
            array(
                'if(-1+1,3,4)',
                4,
            ),
            array(
                'if(-1+2,3,4)',
                3,
            ),
            array(
                'if(true,1)',
                1,
            ),
            array(
                'if(false,1)',
                0,
            ),
            array(
                'false',
                false,
            ),
            array(
                'not false',
                true,
            ),
            array(
                'true',
                true,
            ),
            array(
                'not true',
                false,
            ),
            array(
                'null',
                null,
            ),
            array(
                'true and false',
                false,
            ),
            array(
                'true or false',
                true,
            ),
            array(
                'true and not false',
                true,
            ),
            array(
                '[1, 2, 3]',
                [1, 2, 3]
            ),
            array(
                '[1, 1, 1]',
                [1, 1, 1]
            ),
            array(
                '[1, "b", 1 + 1]',
                [1, "b", 2]
            ),
            array(
                '[1, "b", 1 + 1, [1, 2, 1 + 2]]',
                [1, "b", 2, [1, 2, 3]]
            ),
            array(
                '[1, "b", 1 + 1, [1, 2, 1 + 2, [-7, -9, -11 * 9]]]',
                [1, "b", 2, [1, 2, 3, [-7, -9, -99]]]
            ),
            array(
                '[1, "b", 4 -> 2]',
                [1, "b", 4 => 2]
            ),
            array(
                '[1, "b", 2 + 2 -> 1 + 1]',
                [1, "b", 4 => 2]
            ),
            array(
                '[1, "b", 4 -> 2] || [1, "b", 4 -> 3]',
                [1, "b", 2, 1, "b", 3]
            ),
            array(
                '[1, "b", "4a" -> 2] || [1, "b", "4a" -> 3]',
                [1, "b", "4a" => 3, 1, "b"]
            ),
            array(
                '[
                    1 - 2 + 5  -> "hello",
                    "b" || "c" -> 3 * 7,
                    2 + 22     -> 1 + 1
                ]',
                [4 => "hello", "bc" => 21, 24 => 2]
            ),
            array(
                '[
                    7 -> [
                      8 -> 8 + 1,
                      7 + 2 -> 9 + 1
                    ],
                    8 -> [
                      6 + 3 -> 10 + 1
                    ]
                ]',
                [7 => [8 => 9, 9 => 10], 8 => [9 => 11]]
            ),
        );
    }

    public function testDivisionFromZero()
    {
        $this->expectException(\RR\Shunt\Exception\RuntimeError::class);

        $equation = '100/0';

        Parser::parse($equation);
    }

    public function testModulusFromZero()
    {
        $this->expectException(\RR\Shunt\Exception\RuntimeError::class);

        $equation = '100%0';

        Parser::parse($equation);
    }
}

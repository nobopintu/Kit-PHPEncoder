<?php

namespace Riimu\Kit\PHPEncoder;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class ObjectEncodingTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $encoder = new PHPEncoder(['object.format' => 'serialize']);
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $obj->baz = true;

        $code = $encoder->encode($obj);
        $this->assertSame(
            'unserialize(\'O:8:"stdClass":2:{s:3:"foo";s:3:"bar";s:3:"baz";b:1;}\')',
            $code
        );

        $evaluated = eval("return $code;");
        $this->assertSame(get_class($obj), get_class($evaluated));
        $this->assertSame((array) $obj, (array) $evaluated);
    }

    public function testStringConversion()
    {
        $encoder = new PHPEncoder([
            'object.format' => 'string',
            'object.method' => false,
        ]);

        $mock = $this->getMock('Test\StringObject', ['toPHP']);
        $mock->expects($this->exactly(0))->method('toPHP');

        $this->assertSame('Stringed', eval('return ' . $encoder->encode($mock) . ';'));
    }

    public function testPHPValue()
    {
        $encoder = new PHPEncoder();
        $mock = $this->getMock('TestMockObjectWithPHPValue', ['toPHPValue']);
        $mock->expects($this->once())->method('toPHPValue')->will($this->returnValue([]));
        $this->assertSame([], eval('return ' . $encoder->encode($mock) . ';'));
    }

    public function testPHP()
    {
        $encoder = new PHPEncoder();
        $mock = $this->getMock('TestMockObjectWithPHP', ['toPHP']);
        $mock->expects($this->once())->method('toPHP')->will($this->returnValue('"Mocked"'));
        $this->assertSame('"Mocked"', $encoder->encode($mock));
    }

    public function testObjectVarsArray()
    {
        $encoder = new PHPEncoder([
            'whitespace'    => false,
            'object.format' => 'vars',
            'object.cast'   => false,
        ]);

        $std = new \stdClass();
        $std->baz = 'C';
        $this->assertSame("['baz'=>'C']", $encoder->encode($std));
        $this->assertSame("(object) [\n 'baz' => 'C',\n]", $encoder->encode($std, [
            'object.cast'  => true,
            'whitespace'   => true,
            'array.eol'    => "\n",
            'array.indent' => ' ',
        ]));
    }

    public function testObjectExport()
    {
        $encoder = new PHPEncoder([
            'object.format' => 'export',
            'whitespace'    => false,
        ]);

        $obj = new \Test\ExtendsTestMockObject();
        $obj->var = true;
        $this->assertSame(
            "\\Test\\ExtendsTestMockObject::__set_state(['bazC'=>'E','baz'=>'C'," .
            "'var'=>true,'fooC'=>'D','bar'=>'B','foo'=>'A'])",
            $encoder->encode($obj)
        );
    }

    public function testIteratingArray()
    {
        $encoder = new PHPEncoder([
            'whitespace'    => false,
            'object.format' => 'iterate',
            'object.cast'   => false,
        ]);

        $array = ['foo' => 'bar', [1, 2], 3, 10 => 1337, 11 => 7, 6 => 6];
        $this->assertSame("['foo'=>'bar',[1,2],3,10=>1337,7,6=>6]", $encoder->encode(new \ArrayObject($array)));
        $this->assertSame($array, eval('return ' . $encoder->encode(new \ArrayObject($array)) . ';'));
    }

    public function testArrayCasting()
    {
        $encoder = new PHPEncoder([
            'whitespace'    => false,
            'object.format' => 'array',
            'object.cast'   => false,
        ]);

        $obj = new \stdClass();
        $this->assertSame('[]', $encoder->encode($obj));
        $mock = new \Test\TestMockObject();
        $this->assertSame(
            '["\x00Test\\\\TestMockObject\x00foo"=>\'A\',"\x00*\x00bar"=>\'B\',\'baz\'=>\'C\']',
            $encoder->encode($mock)
        );
        $this->assertSame(
            ["\0Test\\TestMockObject\0foo" => 'A', "\0*\0bar" => 'B', 'baz' => 'C'],
            eval('return ' . $encoder->encode($mock) . ';')
        );
    }

    public function testInvalidFormat()
    {
        $encoder = new PHPEncoder(['object.format' => 'invalid']);
        $this->setExpectedException('RuntimeException');
        $encoder->encode(new \stdClass());
    }

    public function testInvalidFormatValueType()
    {
        $encoder = new PHPEncoder(['object.format' => true]);
        $this->setExpectedException('RuntimeException');
        $encoder->encode(new \stdClass());
    }
}

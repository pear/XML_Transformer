<?php
require_once 'PHPUnit/PHPUnit.php';
require_once 'XML/Transformer/OutputBuffer.php';
require_once 'XML/Transformer/Namespace.php';

class Main extends XML_Transformer_Namespace {
    function start_bold($attributes) {
        return '<b>';
    }

    function end_bold($cdata) {
        return $cdata . '</b>';
    }

    function start_boldbold($attributes) {
        return '<bold>';
    }

    function end_boldbold($cdata) {
        return $cdata . '</bold>';
    }
}

class XML_Transformer_Test extends PHPUnit_TestCase {
    function testMainNamespace() {
        $t = new XML_Transformer;

        $t->overloadNamespace(
          '&MAIN',
          new Main
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $t->transform(
            '<p><bold>text</bold></p>'
          )
        );
    }

    function testMainNamespaceRecursion() {
        $t = new XML_Transformer;

        $t->overloadNamespace(
          '&MAIN',
          new Main
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $t->transform(
            '<p><boldbold>text</boldbold></p>'
          )
        );
    }
}

$result = PHPUnit::run(new PHPUnit_TestSuite('XML_Transformer_Test'));
echo $result->toString();
?>

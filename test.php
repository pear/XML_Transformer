<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                                |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2003-2003 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                         Kristian K�hntopp <kris@koehntopp.de>.            |
// +---------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,           |
// | that is available at http://www.php.net/license/3_0.txt.                  |
// | If you did not receive a copy of the PHP license and are unable to        |
// | obtain it through the world-wide-web, please send a note to               |
// | license@php.net so we can mail you a copy immediately.                    |
// +---------------------------------------------------------------------------+
//
// $Id$
//

require_once 'PHPUnit.php';
require_once 'XML/Transformer.php';
require_once 'XML/Transformer/Namespace.php';

class TestNamespace extends XML_Transformer_Namespace {
    function start_body($attributes) {
        return '<body>text';
    }

    function end_body($cdata) {
        return $cdata . '</body>';
    }

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
    function testNoRecursion() {
        $t = new XML_Transformer;

        $t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $t->transform(
            '<p><bold>text</bold></p>'
          )
        );
    }

    function testRecursion() {
        $t = new XML_Transformer;

        $t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $t->transform(
            '<p><boldbold>text</boldbold></p>'
          )
        );
    }

    function testSelfReplacing() {
        $t = new XML_Transformer;

        $t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<html><body>text</body></html>',

          $t->transform(
            '<html><body/></html>'
          )
        );
    }
}

$result = PHPUnit::run(new PHPUnit_TestSuite('XML_Transformer_Test'));
echo $result->toString();
?>

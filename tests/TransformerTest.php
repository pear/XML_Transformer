<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                                |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
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

require_once 'TestNamespace.php';
require_once 'XML/Transformer.php';

/**
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author      Kristian K�hntopp <kris@koehntopp.de>
 * @copyright   Copyright &copy; 2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de> and Kristian K�hntopp <kris@koehntopp.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    XML
 * @package     XML_Transformer
 */
class XML_Transformer_Tests_TransformerTest extends PHPUnit_Framework_TestCase {
    private $t;

    public function  setUp() {
        $this->t = new XML_Transformer;
    }

    public function testNoRecursion() {
        $this->t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><bold>text</bold></p>'
          )
        );
    }

    public function testRecursion() {
        $this->t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><boldbold>text</boldbold></p>'
          )
        );
    }

    public function testSelfReplacing() {
        $this->t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<html><body>text</body></html>',

          $this->t->transform(
            '<html><body/></html>'
          )
        );
    }

    public function testNamespace() {
        $this->t->overloadNamespace(
          'test',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><test:bold>text</test:bold></p>'
          )
        );
    }

    public function testNamespaceURI() {
        $this->t->overloadNamespace(
          'test',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><test:bold>text</test:bold></p>'
          )
        );
    }
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */

<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                           |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                    Kristian Köhntopp <kris@koehntopp.de>.            |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,      |
// | that is available at http://www.php.net/license/3_0.txt.             |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'XML/Transformer.php';

/**
* Uses PHP's Output Buffering mechanism to catch the
* output of a script, transforms it, and outputs the
* result.
*
* Example
*
*   <?php
*   require_once 'XML/Transformer/OutputBuffer.php';
*
*   $t = new XML_Transformer_OutputBuffer(
*     array(
*       'overloadedElements' => array(
*         'bold' => array(
*           'start' => 'startElementBold',
*           'end'   => 'endElementBold'
*         )
*       )
*     )
*   );
*
*   function startElementBold($attributes) {
*     return '<b>';
*   }
*
*   function endElementBold($cdata) {
*     return $cdata . '</b>';
*   }
*   ?>
*   <bold>text</bold>
*
* Output
*
*   <b>text</b>
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_OutputBuffer extends XML_Transformer {
    // {{{ Members

    /**
    * @var    boolean
    * @access private
    */
    var $_started = false;

    // }}}
    // {{{ function XML_Transformer_OutputBuffer($parameters = array())

    /**
    * Constructor.
    *
    * @param  array
    * @access public
    */
    function XML_Transformer_OutputBuffer($parameters = array()) {
        $this->XML_Transformer($parameters);

        if (!empty($this->_callbackRegistry->overloadedElements) ||
            !empty($this->_callbackRegistry->overloadedNamespaces)) {
            $this->start();
        }
    }

    // }}}
    // {{{ function start()

    /**
    * Starts the output-buffering,
    * and thus the transformation.
    *
    * @access public
    */
    function start() {
        if (!$this->_started) {
            ob_start(
              array(
                $this, 'transform'
              )
            );

            $this->_started = true;

            $this->_debug(
              'start: ' . serialize($this)
            );
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

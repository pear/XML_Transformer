<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: PHP Namespace Handler                  |
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

#require_once 'XML/Transformer/Namespace.php';

/**
* Handler for the PHP Namespace.
*
* Example
*
*   <?php
*   require_once 'XML/Transformer.php';
*   require_once 'XML/Transformer/PHP.php';
*
*   $t = new XML_Transformer;
*
*   $t->overloadNamespace(
*     'php',
*     new XML_Transformer_PHP 
*   );
*
*   $t->start();
*   ?>
*   <dl>
*     <dd>Current time: <php:expr>time()</php:expr></dd>
*     <php:setvariable name="foo">bar</php:setvariable>
*     <dd>foo = <php:getvariable name="foo"/></dd>
*   </dl>
*
* Output
*
*   <dl>
*     <dd>Current time: 1032158587</dd>
*     <dd>foo = bar</dd>
*   </dl>
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_PHP extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    string
    * @access private
    */
    var $_variable = '';

    // }}}
    // {{{ function start_expr($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_expr($attributes) {}

    // }}}
    // {{{ function end_expr($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_expr($cdata) {
        return eval('return ' . $cdata . ';');
    }

    // }}}
    // {{{ function start_logic($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_logic($attributes) {}

    // }}}
    // {{{ function end_logic($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_logic($cdata) {
        ob_start();
        eval($cdata);
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    // }}}
    // {{{ function start_getparameter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_getparameter($attributes) {
        return isset($_GET[$attributes['name']]) ? $_GET[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_getparameter($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_getparameter($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_postparameter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_postparameter($attributes) {
        return isset($_POST[$attributes['name']]) ? $_POST[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_postparameter($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_postparameter($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_sessionvariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_sessionvariable($attributes) {
        return isset($_SESSION[$attributes['name']]) ? $_SESSION[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_sessionvariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_sessionvariable($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_getvariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_getvariable($attributes) {
        return isset($GLOBALS[$attributes['name']]) ? $GLOBALS[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_getvariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_getvariable($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_setvariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_setvariable($attributes) {
        $this->_variable = isset($attributes['name']) ? $attributes['name'] : '';

        return '';
    }

    // }}}
    // {{{ function end_setvariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_setvariable($cdata) {
        if ($this->_variable != '') {
            $GLOBALS[$this->_variable] = $cdata;
            $this->_variable = '';
        }

        return '';
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

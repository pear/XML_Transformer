<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML Transformer                                              |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Sebastian Bergmann <sb@sebastian-bergmann.de>                |
// |         Kristian Köhntopp <kris@koehntopp.de>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

#require_once 'XML/Transformer/Namespace.php';

/**
* ...
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_PHP extends XML_Transformer_Namespace {
    var $_variable = '';

    function start_expr($attributes) {}

    function end_expr($cdata) {
        return eval('return ' . $cdata . ';');
    }

    function start_logic($attributes) {}

    function end_logic($cdata) {
        // ...
    }

    function start_getparameter($attributes) {
        return isset($_GET[$attributes['name']]) ? $_GET[$attributes['name']] : '';
    }

    function end_getparameter($cdata) {
        return $cdata;
    }

    function start_postparameter($attributes) {
        return isset($_POST[$attributes['name']]) ? $_POST[$attributes['name']] : '';
    }

    function end_postparameter($cdata) {
        return $cdata;
    }

    function start_sessionvariable($attributes) {
        return isset($_SESSION[$attributes['name']]) ? $_SESSION[$attributes['name']] : '';
    }

    function end_sessionvariable($cdata) {
        return $cdata;
    }

    function start_getvariable($attributes) {
        return isset($GLOBALS[$attributes['name']]) ? $GLOBALS[$attributes['name']] : '';
    }

    function end_getvariable($cdata) {
        return $cdata;
    }

    function start_setvariable($attributes) {
        $this->_variable = isset($attributes['name']) ? $attributes['name'] : '';

        return '';
    }

    function end_setvariable($cdata) {
        if ($this->_variable != '') {
            $GLOBALS[$this->_variable] = $cdata;
            $this->_variable = '';
        }

        return '';
    }
}
?>

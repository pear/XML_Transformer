<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: DocBook Namespace Handler              |
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

require_once 'XML/Transformer/Namespace.php';

/**
* DocBook Namespace Handler.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_DocBook extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    string
    * @access public
    */
    var $defaultNamespacePrefix = '&MAIN';

    /**
    * @var    array
    * @access private
    */
    var $_counter = array();

    /**
    * @var    string
    * @access private
    */
    var $_emphasizeRole = '';

    // }}}
    // {{{ function XML_Transformer_Namespace_DocBook()

    /**
    * @access public
    */
    function XML_Transformer_Namespace_DocBook() {
        $this->_counter['chapter'] = 1;
        $this->_counter['example'] = 1;
        $this->_counter['figure']  = 1;
        $this->_counter['section'] = 1;
    }

    // }}}
    // {{{ function start_article($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_article($attributes) {
        return '<html><body>';
    }

    // }}}
    // {{{ function end_article($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_article($cdata) {
        return $cdata . '</body></html>';
    }

    // }}}
    // {{{ function start_emphasis($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_emphasis($attributes) {
        $emphasisRole = isset($attributes['role']) ? $attributes['role'] : '';

        switch($emphasisRole) {
            case 'bold':
            case 'strong': {
                $this->_emphasisRole = 'b';
            }
            break;

            default: {
                $this->_emphasisRole = 'i';
            }
        }

        return '<' . $this->_emphasisRole . '>';
    }

    // }}}
    // {{{ function end_emphasis($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_emphasis($cdata) {
        $emphasisRole        = $this->_emphasisRole;
        $this->_emphasisRole = '';

        return $cdata . '</' . $emphasisRole . '>';
    }

    // }}}
    // {{{ function start_para($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_para($attributes) {
        return '<p>';
    }

    // }}}
    // {{{ function end_para($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_para($cdata) {
        return $cdata . '</p>';
    }

    // }}}
    // {{{ function start_section($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_section($attributes) {
        return '<h2 class="title" style="clear: both">' .
               $this->_counter['section']++ . '. ';
    }

    // }}}
    // {{{ function end_section($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_section($cdata) {
        return $cdata . '</h2>';
    }

    // }}}
    // {{{ function start_title($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_title($attributes) {
        return '';
    }

    // }}}
    // {{{ function end_title($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_title($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_ulink($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_ulink($attributes) {
        return '<a href="' . $attributes['url'] . '">';
    }

    // }}}
    // {{{ function end_ulink($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_ulink($cdata) {
        return $cdata . '</a>';
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Anchor Namespace Handler               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                    Kristian K�hntopp <kris@koehntopp.de>.            |
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
* Handler for the Anchor Namespace.
*
* This namespace maintains an anchor database, a database of
* named links. These links can be referenced using the iref
* tag within this namespace.
*
* This allows for a central storage of links, changing links
* need only be changed in one locations. Designers can reference
* the link through the symbolic name.
*
* Example:
*
* ...
*   $n = XML_Transformer_Namespace_Anchor;
*   $t->overloadNamespace("a", $n);
*
*   $n->setDatabase(
*         array(
*           "pear" => array(
*             "href"  => "http://pear.php.net",
*             "title" => "PEAR Homepage"
*           )
*         )
*   );
* ?>
* <p>The <a:iref iref="pear">PEAR Homepage</a:iref> is now online.</p>
*
*
* Output:
* <p>The <a href="http://www.pear.net" title="PEAR Homepage">PEAR
* Homepage</a> is now online.</p>
*
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian K�hntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_Anchor extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    array
    * @access private
    */
    var $_anchorDatabase = array();

    /**
    * @var    array
    * @access private
    */
    var $_irefAttributes = array();

    // {{{ function setDatabase($db)

    /**
    * Install a complete link database array.
    *
    * @param  array
    * @return boolean
    * @access public
    */
    function setDatabase($db) {
        $this->_anchorDatabase = $db;

        return true;
    }

    // }}}
    // {{{ function getDatabase($db)

    /**
    * Return the link database array.
    *
    * @return array
    * @access public
    */
    function getDatabase() {
        return $this->_anchorDatabase;
    }

    // }}}
    // {{{ function addItem($item, $attr)

    /**
    * Add an item $item with the attributes $attr to the link database array.
    *
    * @param  string
    * @param  array
    * @return boolean
    * @access public
    */
    function addItem($item, $attr) {
        $this->_anchorDatabase[$item] = $attr;

        return true;
    }

    // }}}
    // {{{ function dropItem($item)

    /**
    * Drop an item $item drom the link database array.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function dropItem($item) {
        if (!isset($this->_anchorDatabase[$item]))
            return false;

        unset($this->_anchorDatabase[$item]);

        return true;
    }

    // }}}
    // {{{ function getItem($item)

    /**
    * Get an item $item from the link database array.
    *
    * @param  string
    * @return mixed
    * @access public
    */
    function getItem($item) {
        if (!isset($this->_anchorDatabase[$item])) {
            return false;
        }

        return $this->_anchorDatabase[$item];
    }

    // }}}
    // {{{ function start_iref($attributes)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function start_iref($attributes) {
        $this->_irefAttributes = $attributes;

        return '';
    }

    // }}}
    // {{{ function end_iref($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_iref($cdata) {
        if (!isset($this->_irefAttributes)) {
            return '';
        }
        
        $name = $this->_irefAttributes['iref'];

        return sprintf(
          '<a href='%s'>%s</a>',
          XML_Transformer_Util::attributesToString($this->_anchorDatabase[$name]),
          $cdata
        );
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Anchor Namespace Handler                    |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                         Kristian Köhntopp <kris@koehntopp.de>.            |
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

require_once 'XML/Transformer/Namespace.php';
require_once 'XML/Util.php';

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
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_Anchor extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    boolean
    * @access public
    */
    public $defaultNamespacePrefix = 'a';

    /**
    * @var    array
    * @access private
    */
    private $anchorDatabase = array();

    /**
    * @var    array
    * @access private
    */
    private $irefAttributes = array();

    // {{{ public function setDatabase($db)

    /**
    * Install a complete link database array.
    *
    * @param  array
    * @return boolean
    * @access public
    */
    public function setDatabase($db) {
        $this->anchorDatabase = $db;

        return true;
    }

    // }}}
    // {{{ public function getDatabase($db)

    /**
    * Return the link database array.
    *
    * @return array
    * @access public
    */
    public function getDatabase() {
        return $this->anchorDatabase;
    }

    // }}}
    // {{{ public function addItem($item, $attr)

    /**
    * Add an item $item with the attributes $attr to the link database array.
    *
    * @param  string
    * @param  array
    * @return boolean
    * @access public
    */
    public function addItem($item, $attr) {
        $this->anchorDatabase[$item] = $attr;

        return true;
    }

    // }}}
    // {{{ public function dropItem($item)

    /**
    * Drop an item $item drom the link database array.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function public dropItem($item) {
        if (!isset($this->anchorDatabase[$item]))
            return false;

        unset($this->anchorDatabase[$item]);

        return true;
    }

    // }}}
    // {{{ public function getItem($item)

    /**
    * Get an item $item from the link database array.
    *
    * @param  string
    * @return mixed
    * @access public
    */
    public function getItem($item) {
        if (!isset($this->anchorDatabase[$item])) {
            return false;
        }

        return $this->anchorDatabase[$item];
    }

    // }}}
    // {{{ public function start_iref($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_iref($attributes) {
        $this->irefAttributes = $attributes;

        return '';
    }

    // }}}
    // {{{ public function end_iref($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_iref($cdata) {
        if (!isset($this->irefAttributes['iref']))
            return '';

        $name = $this->irefAttributes['iref'];
        if (!isset($this->anchorDatabase[$name]))
            return sprintf('<span>(undefined reference %s)%s</span>',
                $name,
                $cdata
            );

        return sprintf('<a %s>%s</a>',
            XML_Util::attributesToString($this->anchorDatabase[$name]),
            $cdata
        );
    }

    // }}}
    // {{{ public function start_random($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_random($attributes) {
        return '';
    }

    // }}}
    // {{{ public function end_random($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_random($cdata) {
        srand((double)microtime()*1000000);

        $keys = array_keys($this->anchorDatabase);
        $pos  = rand(0, count($keys)-1);
        $name = $keys[$pos];

        return sprintf('<a %s>%s</a>',
            XML_Util::attributesToString($this->anchorDatabase[$name]),
            $cdata
        );
    }

    // }}}
    // {{{ public function start_link($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_link($attributes) {
        if (!isset($attributes['name']))
            return '';

        $name = $attributes['name'];
        unset($attributes['name']);

        $this->addItem($name, $attributes);
        return '';
    }

    // }}}
    // {{{ public function end_link($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_link($cdata) {
        return '';
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

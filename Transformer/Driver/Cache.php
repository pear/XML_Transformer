<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Driver :: Cache                             |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2003-2003 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
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

require_once 'Cache/Lite.php';
require_once 'XML/Transformer.php';

/**
* Caching Transformer.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Driver_Cache extends XML_Transformer {
    // {{{ Members

    /**
    * @var    object
    * @access private
    */
    var $_cache = false;

    // }}}
    // {{{ function XML_Transformer_Driver_Cache($parameters = array())

    /**
    * Constructor.
    *
    * @param  array
    * @access public
    */
    function XML_Transformer_Driver_Cache($parameters = array()) {
        $this->XML_Transformer($parameters);
        $this->_cache = new Cache_Lite($parameters);
    }

    // }}}
    // {{{ function transform($xml, $cacheID = '')

    /**
    * Cached transformation a given XML string using
    * the registered PHP callbacks for overloaded tags.
    *
    * @param  string
    * @param  string
    * @return string
    * @access public
    */
    function transform($xml, $cacheID = '') {
        $cacheID = ($cacheID != '') ? $cacheID : md5($xml);

        $cachedResult = $this->_cache->get($cacheID, 'XML_Transformer');

        if ($cachedResult !== false) {
            return $cachedResult;
        }

        $result = parent::transform($xml);
        $this->_cache->save($result, $cacheID, 'XML_Transformer');

        return $result;
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

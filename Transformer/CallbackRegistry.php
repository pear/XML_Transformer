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

/**
* Callback Registry.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_CallbackRegistry {
    // {{{ Members

    /**
    * @var    array
    * @access public
    */
    var $overloadedNamespaces = array();

    /**
    * @var    boolean
    * @access private
    */
    var $_locked = false;

    /**
    * If true, the transformation will continue recursively
    * until the XML contains no more overloaded elements.
    * Can be overrided on a per-element basis.
    *
    * @var    boolean
    * @access private
    */
    var $_recursiveOperation = true;

    // }}}
    // {{{ function XML_Transformer_CallbackRegistry($recursiveOperation)

    /**
    * Constructor.
    *
    * @param  boolean
    * @access public
    */
    function XML_Transformer_CallbackRegistry($recursiveOperation) {
        $this->_recursiveOperation = $recursiveOperation;
    }

    // }}}
    // {{{ function &singleton($recursiveOperation)

    /**
    * Singleton.
    *
    * @param  boolean
    * @access public
    */
    function &singleton($recursiveOperation) {
        static $instance;

        if (!isset($instance)) {
            $instance = new XML_Transformer_CallbackRegistry($recursiveOperation);
        }

        return $instance;
    }

    // }}}
    // {{{ function overloadNamespace($namespacePrefix, &$object, $recursiveOperation = '')

    /**
    * Overloads an XML Namespace.
    *
    * @param  string
    * @param  object
    * @param  boolean
    * @return mixed
    * @access public
    */
    function overloadNamespace($namespacePrefix, &$object, $recursiveOperation = '') {
        if (is_object($object) &&
            method_exists($object, 'startElement') &&
            method_exists($object, 'endElement')) {
            $this->overloadedNamespaces[$namespacePrefix]['active']             = true;
            $this->overloadedNamespaces[$namespacePrefix]['object']             = &$object;
            $this->overloadedNamespaces[$namespacePrefix]['recursiveOperation'] = is_bool($recursiveOperation) ? $recursiveOperation : $this->_recursiveOperation;
        } else {
            return 'Cannot overload namespace "' .
                   $namespacePrefix .
                   '", method(s) "startElement" and/or "endElement" ' .
                   'are missing on given object.';
        }

        return true;
    }

    // }}}
    // {{{ function unOverloadNamespace($namespacePrefix)

    /**
    * Reverts overloading of a given XML Namespace.
    *
    * @param  string
    * @access public
    */
    function unOverloadNamespace($namespacePrefix) {
        if (isset($this->overloadedNamespaces[$namespacePrefix])) {
            unset($this->overloadedNamespaces[$namespacePrefix]);
        }
    }

    // }}}
    // {{{ function isOverloadedNamespace($namespacePrefix)

    /**
    * Returns true if a given namespace is overloaded,
    * false otherwise.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function isOverloadedNamespace($namespacePrefix) {
        return isset(
          $this->overloadedNamespaces[$namespacePrefix]
        );
    }

    // }}}
    // {{{ function setRecursiveOperation($recursiveOperation)

    /**
    * Enables or disables the recursive operation.
    *
    * @param  boolean
    * @access public
    */
    function setRecursiveOperation($recursiveOperation) {
        if (is_bool($recursiveOperation)) {
            $this->_recursiveOperation = $recursiveOperation;
        }
    }

    // }}}
    // {{{ function function getLock($namespace)

    /**
    * Lock all namespace handlers except a given one.
    *
    * @string namespace
    * @return boolean
    * @access public
    * @see    releaseLock()
    */
    function getLock($namespace) {
        if (!$this->_locked) {
            $namespacePrefixes = array_keys($this->overloadedNamespaces);

            foreach ($namespacePrefixes as $namespacePrefix) {
                if ($namespacePrefix != $namespace) {
                    unset($this->overloadedNamespaces[$namespacePrefix]['active']);
                }
            }
            
            $this->_locked = true;
            return true;
        }

        return false;
    }

    // }}}
    // {{{ function releaseLock()

    /**
    * Releases a lock.
    *
    * @access public
    * @see    getLock()
    */
    function releaseLock() {
        $namespacePrefixes = array_keys($this->overloadedNamespaces);

        foreach ($namespacePrefixes as $namespacePrefix) {
            $this->overloadedNamespaces[$namespacePrefix]['active'] = true;
        }

        $this->_locked = false;
    }

    // }}}
}
?>

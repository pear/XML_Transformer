<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                                |
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

/**
* Callback Registry.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_CallbackRegistry {
    // {{{ Members

    /**
    * @var    array
    * @access public
    */
    public $overloadedNamespaces = array();

    /**
    * @var    boolean
    * @access private
    */
    private $locked = false;

    /**
    * If true, the transformation will continue recursively
    * until the XML contains no more overloaded elements.
    * Can be overrided on a per-element basis.
    *
    * @var    boolean
    * @access private
    */
    private $recursiveOperation = true;

    /**
    * @var    boolean
    * @access private
    * @static
    */
    private static $instance = null;

    // }}}
    // {{{ public function __construct($recursiveOperation)

    /**
    * Constructor.
    *
    * @param  boolean
    * @access public
    */
    public function __construct($recursiveOperation) {
        $this->recursiveOperation = $recursiveOperation;
    }

    // }}}
    // {{{ public function getInstance($recursiveOperation)

    /**
    * Returns an instance of XML_Transformer_CallbackRegistry.
    *
    * @param  boolean
    * @return XML_Transformer_CallbackRegistry
    * @access public
    */
    public function getInstance($recursiveOperation) {
        if (self::$instance == null) {
            self::$instance = new XML_Transformer_CallbackRegistry($recursiveOperation);
        }

        return self::$instance;
    }

    // }}}
    // {{{ public function overloadNamespace($namespacePrefix, $object, $recursiveOperation = '')

    /**
    * Overloads an XML Namespace.
    *
    * @param  string
    * @param  object
    * @param  boolean
    * @return mixed
    * @access public
    */
    public function overloadNamespace($namespacePrefix, $object, $recursiveOperation = '') {
        if (!is_object($object)) {
            return sprintf(
              'Cannot overload namespace "%s", ' .
              'second parameter is not an object.',

              $namespacePrefix
            );
        }

        if (!is_subclass_of($object, 'XML_Transformer_Namespace')) {
            return sprintf(
              'Cannot overload namespace "%s", ' .
              'provided object was not instantiated from ' .
              'a class that inherits XML_Transformer_Namespace.',

              $namespacePrefix
            );
        }

        if (!method_exists($object, 'startElement') ||
            !method_exists($object, 'endElement')) {
            return sprintf(
              'Cannot overload namespace "%s", ' .
              'method(s) "startElement" and/or "endElement" ' .
              'are missing on given object.',

              $namespacePrefix
            );
        }

        $this->overloadedNamespaces[$namespacePrefix]['active']             = true;
        $this->overloadedNamespaces[$namespacePrefix]['object']             = $object;
        $this->overloadedNamespaces[$namespacePrefix]['recursiveOperation'] = is_bool($recursiveOperation) ? $recursiveOperation : $this->recursiveOperation;

        return true;
    }

    // }}}
    // {{{ public function unOverloadNamespace($namespacePrefix)

    /**
    * Reverts overloading of a given XML Namespace.
    *
    * @param  string
    * @access public
    */
    public function unOverloadNamespace($namespacePrefix) {
        if (isset($this->overloadedNamespaces[$namespacePrefix])) {
            unset($this->overloadedNamespaces[$namespacePrefix]);
        }
    }

    // }}}
    // {{{ public function isOverloadedNamespace($namespacePrefix)

    /**
    * Returns true if a given namespace is overloaded,
    * false otherwise.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    public function isOverloadedNamespace($namespacePrefix) {
        return isset(
          $this->overloadedNamespaces[$namespacePrefix]
        );
    }

    // }}}
    // {{{ public function setRecursiveOperation($recursiveOperation)

    /**
    * Enables or disables the recursive operation.
    *
    * @param  boolean
    * @access public
    */
    public function setRecursiveOperation($recursiveOperation) {
        if (is_bool($recursiveOperation)) {
            $this->recursiveOperation = $recursiveOperation;
        }
    }

    // }}}
    // {{{ public function public function getLock($namespace)

    /**
    * Lock all namespace handlers except a given one.
    *
    * @string namespace
    * @return boolean
    * @access public
    * @see    releaseLock()
    */
    public function getLock($namespace) {
        if (!$this->locked) {
            $namespacePrefixes = array_keys($this->overloadedNamespaces);

            foreach ($namespacePrefixes as $namespacePrefix) {
                if ($namespacePrefix != $namespace) {
                    unset($this->overloadedNamespaces[$namespacePrefix]['active']);
                }
            }

            $this->locked = true;

            return true;
        }

        return false;
    }

    // }}}
    // {{{ public function releaseLock()

    /**
    * Releases a lock.
    *
    * @access public
    * @see    getLock()
    */
    public function releaseLock() {
        $namespacePrefixes = array_keys($this->overloadedNamespaces);

        foreach ($namespacePrefixes as $namespacePrefix) {
            $this->overloadedNamespaces[$namespacePrefix]['active'] = true;
        }

        $this->locked = false;
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

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
    * @access private
    */
    var $overloadedElements = array();

    /**
    * @var    array
    * @access private
    */
    var $overloadedNamespaces = array();

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

    function XML_Transformer_CallbackRegistry($recursiveOperation) {
        $this->_recursiveOperation = $recursiveOperation;
    }

    function &singleton($recursiveOperation) {
        static $instance;

        if (!isset($instance)) {
            $instance = new XML_Transformer_CallbackRegistry($recursiveOperation);
        }

        return $instance;
    }

    // {{{ function overloadElement($element, $startHandler, $endHandler, $recursiveOperation = '')

    /**
    * Overloads an XML element and binds its
    * opening and closing tags to a PHP callback.
    *
    * @param  string
    * @param  string
    * @param  string
    * @param  boolean
    * @return mixed
    * @access public
    */
    function overloadElement($element, $startHandler, $endHandler, $recursiveOperation = '') {
        $result = $this->_registerElementCallback(
          $element,
          'start',
          $startHandler
        );

        if ($result !== true) {
            return $result;
        }

        $result = $this->_registerElementCallback(
          $element,
          'end',
          $endHandler
        );

        if ($result !== true) {
            return $result;
        }

        $this->overloadedElements[$element]['recursiveOperation'] = is_bool($recursiveOperation) ? $recursiveOperation : $this->_recursiveOperation;

        return true;
    }

    // }}}
    // {{{ function unOverloadElement($element)

    /**
    * Reverts overloading of a given element.
    *
    * @param  string
    * @access public
    */
    function unOverloadElement($element) {
        if (isset($this->overloadedElements[$element])) {
            unset($this->overloadedElements[$element]);
        }
    }

    // }}}
    // {{{ function isOverloadedElement($element)

    /**
    * Returns true if a given element is overloaded,
    * false otherwise.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function isOverloadedElement($element) {
        return isset(
          $this->overloadedElements[$element]
        );
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
        if (isset($this->overloadedNamespaces[$namespacePrefix]['object'])) {
            unset($this->overloadedNamespaces[$namespacePrefix]['object']);
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
    // {{{ function setDefaultCallback($startHandler, $endHandler)

    /**
    * Registers default start and end handlers for elements that
    * have no registered callbacks.
    *
    * @param  string
    * @param  string
    * @access public
    */
    function setDefaultCallback($startHandler, $endHandler) {
        $startHandler = $this->_parseCallback($startHandler);
        $endHandler   = $this->_parseCallback($endHandler);

        if ($startHandler && $endHandler) {
            $this->overloadedElements['&DEFAULT']['start'] = $startHandler;
            $this->overloadedElements['&DEFAULT']['end']   = $endHandler;
        }
    }

    // }}}
    // {{{ function unsetDefaultCallback()

    /**
    * Unsets default handlers for elements that have no
    * registered callbacks.
    *
    * @access public
    */
    function unsetDefaultCallback() {
        if (isset($this->overloadedElements['&DEFAULT'])) {
            unset($this->overloadedElements['&DEFAULT']);
        }
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
    // {{{ function _parseCallback($callback)

    /**
    * Parses a PHP callback.
    *
    * @param  string
    * @return mixed
    * @access private
    */
    function _parseCallback($callback) {
        $parsedCallback = false;

        // classname::staticMethod
        if (strstr($callback, '::')) {
            list($class, $method) = explode('::', $callback);

            if (class_exists($class) &&
                in_array(strtolower($method), get_class_methods($class))) {
                $parsedCallback = array($class, $method);
            }
        }

        // object->method
        else if (strstr($callback, '->')) {
            list($object, $method) = explode('->', $callback);

            if (isset($GLOBALS[$object]) &&
                is_object($GLOBALS[$object]) &&
                method_exists($GLOBALS[$object], $method)) {
                $parsedCallback = array($GLOBALS[$object], $method);
            }
        }

        // function
        else if (function_exists($callback)) {
            $parsedCallback = $callback;
        }

        if ($parsedCallback) {
            return $parsedCallback;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ function _registerElementCallback($element, $event, $callback)

    /**
    * Registers a PHP callback for a given event of a XML element.
    *
    * @param  string
    * @param  string
    * @param  string
    * @return mixed
    * @access private
    */
    function _registerElementCallback($element, $event, $callback) {
        if ($parsedCallback = $this->_parseCallback($callback)) {
            $this->overloadedElements[$element][$event] = $parsedCallback;
        } else {
            return sprintf(
              'Callback %s for <%s%s> does not exist.',
              $callback,
              ($event == 'end') ? '/' : '',
              $element
            );
        }

        return true;
    }

    // }}}
}
?>

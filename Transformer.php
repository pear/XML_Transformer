<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
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

/**
* XML Transformations in PHP
*
* With this class one can easily bind PHP functionality to XML tags,
* thus transforming an XML input tree into another XML tree without
* the need for XSLT.
*
* Single XML elements can be overloaded with PHP functions, methods
* and static method calls, XML namespaces can be registered to be
* handled by PHP classes.
*
* Example
*
*   <?php
*   require_once 'XML/Transformer.php';
*
*   $t = new XML_Transformer(
*     array(
*       'overloadedElements' => array(
*         'bold' => array('start' => 'startElementBold',
*                         'end'   => 'endElementBold'
*                        )
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
class XML_Transformer {
    /**
    * @var    boolean
    * @access private
    */
    var $_caseFolding;

    /**
    * @var    array
    * @access private
    */
    var $_attributesStack = array();

    /**
    * @var    array
    * @access private
    */
    var $_cdataStack = array('');

    /**
    * @var    array
    * @access private
    */
    var $_elementStack = array();

    /**
    * @var    integer
    * @access private
    */
    var $_level = 0;

    /**
    * @var    array
    * @access private
    */
    var $_overloadedElements = array();

    /**
    * @var    array
    * @access private
    */
    var $_overloadedNamespaces = array();

    /**
    * @var    boolean
    * @access private
    */
    var $_recursiveOperation = true;

    /**
    * @var    boolean
    * @access private
    */
    var $_started = false;

    /**
    * Constructor.
    *
    * @param  array
    * @access public
    */
    function XML_Transformer($parameters = array()) {
        // Parse parameters array.

        $startup                     = isset($parameters['startup'])              ? $parameters['startup']              : true;
        $this->_caseFolding          = isset($parameters['caseFolding'])          ? $parameters['caseFolding']          : false;
        $overloadedElements          = isset($parameters['overloadedElements'])   ? $parameters['overloadedElements']   : array();
        $this->_overloadedNamespaces = isset($parameters['overloadedNamespaces']) ? $parameters['overloadedNamespaces'] : array();
        $this->_recursiveOperation   = isset($parameters['recursiveOperation'])   ? $parameters['recursiveOperation']   : true;

        if ($startup) {
            // Walk through overloadedElements array,
            // that was passed to the constructor and
            // register the element callbacks it contains.

            foreach ($overloadedElements as $element => $overloadedElement) {
                $overloadedElement['start']              = isset($overloadedElement['start'])              ? $overloadedElement['start']              : '';
                $overloadedElement['end']                = isset($overloadedElement['end'])                ? $overloadedElement['end']                : '';
                $overloadedElement['recursiveOperation'] = isset($overloadedElement['recursiveOperation']) ? $overloadedElement['recursiveOperation'] : $this->_recursiveOperation;

                $this->overloadElement(
                  $element,
                  $overloadedElement['start'],
                  $overloadedElement['end'],
                  $overloadedElement['recursiveOperation']
                );
            }

            // Start transformation.

            $this->start();
        } else {
            $this->_overloadedElements = $overloadedElements;
        }
    }

    /**
    * Returns string representation of attributes array.
    *
    * @param  array
    * @return string
    * @access public
    * @static
    */
    function attributesToString($attributes) {
        $string = '';

        if ($this->_caseFolding) {
            $attributes = array_change_key_case($attributes, CASE_UPPER);
        }

        ksort($attributes);

        foreach ($attributes as $key => $value) {
            $string .= ' ' . $key . '="' . $value . '"';
        }

        return $string;
    }

    /**
    * Returns the canonical name of a given element.
    *
    * @param  string
    * @return string
    * @access public
    */
    function canonicalName($name) {
        return $this->_caseFolding ? strtoupper($name) : $name;
    }

    /**
    * Overloads an XML element and binds its
    * opening and closing tags to a PHP callback.
    *
    * @param  string
    * @param  string
    * @param  string
    * @param  boolean
    * @access public
    */
    function overloadElement($element, $startHandler, $endHandler, $recursiveOperation = '') {
        $element = $this->canonicalName($element);

        $this->_registerElementCallback($element, 'start', $startHandler);
        $this->_registerElementCallback($element, 'end',   $endHandler);

        $this->_overloadedElements[$element]['recursiveOperation'] = !empty($recursiveOperation) ? $recursiveOperation : $this->_recursiveOperation;
    }

    /**
    * Reverts overloading of a given element.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function unOverloadElement($element) {
        $element = $this->canonicalName($element);

        if (isset($this->_overloadedElements[$element])) {
            unset($this->_overloadedElements[$element]);

            return true;
        } else {
            return false;
        }
    }

    /**
    * Returns true if a given element is overloaded,
    * false otherwise.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function isOverloadedElement($element) {
        return isset($this->_overloadedElements[$this->canonicalName($element)]);
    }

    /**
    * Overloads an XML Namespace.
    *
    * @param  string
    * @param  object
    * @access public
    */
    function overloadNamespace($namespacePrefix, &$object) {
        $namespacePrefix = $this->canonicalName($namespacePrefix);

        if (is_object($object) &&
            method_exists($object, 'startElement') &&
            method_exists($object, 'endElement')
           )
        {
            $this->_overloadedNamespaces[$namespacePrefix] = $object;
        } else {
            $this->_handle_error(
              'Cannot overload namespace "' .
              $namespacePrefix .
              '", method(s) "startElement" and/or "endElement" ' .
              'are missing on given object.'
            );
        }
    }

    /**
    * Reverts overloading of a given XML Namespace.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function unOverloadNamespace($namespacePrefix) {
        $namespacePrefix = $this->canonicalName($namespacePrefix);

        if (isset($this->_overloadedNamespaces[$namespacePrefix])) {
            unset($this->_overloadedNamespaces[$namespacePrefix]);

            return true;
        } else {
            return false;
        }
    }

    /**
    * Returns true if a given namespace is overloaded,
    * false otherwise.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function isOverloadedNamespace($namespacePrefix) {
        return isset($this->_overloadedNamespaces[$this->canonicalName($namespacePrefix)]);
    }

    /**
    * Sets the XML parser's case-folding option.
    *
    * @param  boolean
    * @access public
    */
    function setCaseFolding($caseFolding) {
        if (is_bool($caseFolding)) {
            $this->_caseFolding = $caseFolding;
        }
    }

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

    /**
    * Starts the output-buffering,
    * and thus the transformation.
    *
    * @access public
    */
    function start() {
        if (!$this->_started) {
          ob_start(array($this, 'transform'));
          $this->_started = true;
        }
    }

    /**
    * Transforms a given XML string using the registered
    * PHP callbacks for overloaded tags.
    *
    * @param  string
    * @return string
    * @access public
    */
    function transform($xml) {
        // Don't process input when it contains no XML elements.

        if (strpos($xml, '<') === false) {
            return $xml;
        }

        // Create XML parser, set parser options.

        $parser = xml_parser_create();

        xml_set_object($parser, $this);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, $this->_caseFolding);

        // Register SAX callbacks.

        xml_set_element_handler($parser, '_startElement', '_endElement');
        xml_set_character_data_handler($parser, '_characterData');

        // Parse input.

        if (!xml_parse($parser, $xml, true)) {
            return sprintf(
              "<!-- XML Error: %s at line %d -->\n",
              xml_error_string(xml_get_error_code($parser)),
              xml_get_current_line_number($parser)
            );
        }

        xml_parser_free($parser);

        // Return result of the transformation.

        return $this->_cdataStack[0];
    }

    /**
    * SAX callback for 'startElement' event.
    *
    * @param  resource
    * @param  string
    * @param  array
    * @access private
    */
    function _startElement($parser, $element, $attributes) {
        $element         = $this->canonicalName($element);
        $namespacePrefix = '';

        if (strstr($element, ':')) {
            list($namespacePrefix, $_element) = explode(':', $element);
        }

        // Push element's name and attributes onto the stack.

        $this->_level++;

        $this->_elementStack[$this->_level]    = $element;
        $this->_attributesStack[$this->_level] = $attributes;

        if (isset($this->_overloadedNamespaces[$namespacePrefix])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->_overloadedNamespaces[$namespacePrefix]->startElement($_element, $attributes);
        }

        else if (isset($this->_overloadedElements[$element]['start'])) {
            // The event is handled by a callback
            // that is registered for this element.

            $cdata = call_user_func(
                $this->_overloadedElements[$element]['start'],
                $attributes
            );
        }

        else {
            // No callback was registered for this element's
            // opening tag, copy it.

            $cdata = sprintf(
              '<%s%s>',
              $element,
              $this->attributesToString($attributes)
            );
        }

        $this->_cdataStack[$this->_level] = $cdata;
    }

    /**
    * SAX callback for 'endElement' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    function _endElement($parser, $element) {
        $cdata           = $this->_cdataStack[$this->_level];
        $element         = $this->canonicalName($element);
        $namespacePrefix = '';
        $recursion       = false;

        if (strstr($element, ':')) {
            list($namespacePrefix, $_element) = explode(':', $element);
        }

        if (isset($this->_overloadedNamespaces[$namespacePrefix])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->_overloadedNamespaces[$namespacePrefix]->endElement($_element, $cdata);

            $recursion = true;
        }

        else if (isset($this->_overloadedElements[$element]['end'])) {
            // The event is handled by a callback
            // that is registered for this element.

            $cdata = call_user_func(
                $this->_overloadedElements[$element]['end'],
                $cdata
            );

            $recursion = true;
        }

        else {
            // No callback was registered for this element's
            // closing tag, copy it.

            $cdata .= '</' . $element . '>';
        }

        if ($resursion &&
            $this->_overloadedElements[$element]['recursiveOperation']
           )
        {
            // Recursively process this transformation's result.

            $transformer = new XML_Transformer(
              array(
                'caseFolding'          => $this->_caseFolding,
                'overloadedElements'   => $this->_overloadedElements,
                'overloadedNamespaces' => $this->_overloadedNamespaces,
                'recursiveOperation'   =  $this->_recursiveOperation,
                'startup'              => false
              )
            );

            $cdata = $transformer->transform($cdata);
        }

        // Move result of this transformation step to
        // the parent's CDATA section.

        $this->_cdataStack[--$this->_level] .= $cdata;
    }

    /**
    * SAX callback for 'characterData' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    function _characterData($parser, $cdata) {
        $this->_cdataStack[$this->_level] .= $cdata;
    }

    /**
    * Parses a PHP callback.
    *
    * @param  string
    * @param  string
    * @param  string
    * @return mixed
    * @access private
    */
    function _parseCallback($element, $event, $callback) {
        $parsedCallback = false;

        // classname::staticMethod
        if (strstr($callback, '::')) {
            list($class, $method) = explode('::', $callback);

            if (class_exists($class) &&
                in_array(strtolower($method),
                         get_class_methods($class)
                        )
               )
            {
                $parsedCallback = array($class, $method);
            }
        }

        // object->method
        else if (strstr($callback, '->')) {
            list($object, $method) = explode('->', $callback);

            if (isset($GLOBALS[$object]) &&
                is_object($GLOBALS[$object]) &&
                method_exists($GLOBALS[$object], $method)
               )
            {
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
            $this->_handleError(
              sprintf(
                'Callback %s for <%s%s> does not exist.',
                $callback,
                ($event == 'end') ? '/' : '',
                $element
              )
            );

            return false;
        }
    }

    /**
    * Registers a PHP callback for a given event of a XML element.
    *
    * @param  string
    * @param  string
    * @param  string
    * @access private
    */
    function _registerElementCallback($element, $event, $callback) {
        if ($parsedCallback = $this->_parseCallback($element, $event, $callback)) {
            $this->_overloadedElements[$element][$event] = $parsedCallback;
        }
    }

    /**
    * Inserts an error message into the output.
    *
    * @param  string
    * @access private
    */
    function _handleError($errorMessage) {
        $this->_cdataStack[$this->_level] .= '<!-- Transformer Error: ' .
                                             $errorMessage .
                                             " -->\n";
    }
}
?>

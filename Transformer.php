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

require_once 'XML/Transformer/CallbackRegistry.php';

/**
* XML Transformations in PHP.
*
* With this class one can easily bind PHP functionality to XML tags,
* thus transforming an XML input tree into another XML tree without
* the need for XSLT.
*
* Single XML elements can be overloaded with PHP functions, methods
* and static method calls, XML namespaces can be registered to be
* handled by PHP classes.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer {
    // {{{ Members

    /**
    * @var    object
    * @access private
    */
    var $_callbackRegistry = null;

    /**
    * If true, XML attribute and element names will be
    * case-folded.
    * 
    * @var    boolean
    * @access private
    * @see    $_caseFoldingTo
    */
    var $_caseFolding = false;

    /**
    * Can be set to either CASE_UPPER or CASE_LOWER
    * and sets the target case for the case-folding.
    *
    * @var    integer
    * @access private
    * @see    $_caseFolding
    */
    var $_caseFoldingTo = CASE_UPPER;

    /**
    * If true, debugging information will be sent to
    * the error.log.
    *
    * @var    boolean
    * @access private
    * @see    $_debugFilter
    */
    var $_debug = false;

    /**
    * If not empty, debugging information will only be generated
    * for XML elements whose names are in this array.
    *
    * @var    array
    * @access private
    * @see    $_debug
    */
    var $_debugFilter = array();

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
    * @var    string
    * @access private
    */
    var $_lastProcessed = '';

    // }}}
    // {{{ function XML_Transformer($parameters = array())

    /**
    * Constructor.
    *
    * @param  array
    * @access public
    */
    function XML_Transformer($parameters = array()) {
        // Parse parameters array.

        if (isset($parameters['debug'])) {
            $this->setDebug($parameters['debug']);
        }

        $this->_caseFolding   = isset($parameters['caseFolding'])   ? $parameters['caseFolding']   : false;
        $this->_caseFoldingTo = isset($parameters['caseFoldingTo']) ? $parameters['caseFoldingTo'] : CASE_UPPER;
        $this->_lastProcessed = isset($parameters['lastProcessed']) ? $parameters['lastProcessed'] : '';

        $overloadedElements   = isset($parameters['overloadedElements'])   ? $parameters['overloadedElements']   : array();
        $overloadedNamespaces = isset($parameters['overloadedNamespaces']) ? $parameters['overloadedNamespaces'] : array();
        $recursiveOperation   = isset($parameters['recursiveOperation'])   ? $parameters['recursiveOperation']   : true;

        // Initialize callback registry.

        $this->_callbackRegistry = XML_Transformer_CallbackRegistry::singleton(
          $recursiveOperation
        );

        foreach ($overloadedElements as $element => $overloadedElement) {
            $overloadedElement['start']              = isset($overloadedElement['start'])              ? $overloadedElement['start']              : '';
            $overloadedElement['end']                = isset($overloadedElement['end'])                ? $overloadedElement['end']                : '';
            $overloadedElement['recursiveOperation'] = isset($overloadedElement['recursiveOperation']) ? $overloadedElement['recursiveOperation'] : $recursiveOperation;

            $this->overloadElement(
              $element,
              $overloadedElement['start'],
              $overloadedElement['end'],
              $overloadedElement['recursiveOperation']
            );
        }

        foreach ($overloadedNamespaces as $namespacePrefix => $object) {
            $this->overloadNamespace(
              $namespacePrefix,
              $object
            );
        }
    }

    // }}}
    // {{{ function attributesToString($attributes)

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

        if (is_array($attributes)) {
            ksort($attributes);

            foreach ($attributes as $key => $value) {
                $string .= ' ' . $key . '="' . $value . '"';
            }
        }

        return $string;
    }

    // }}}
    // {{{ function canonicalize($target)

    /**
    * Canonicalizes a given attributes array or element name.
    *
    * @param  mixed
    * @return mixed
    * @access public
    */
    function canonicalize($target) {
        if ($this->_caseFolding) {
            if (is_string($target)) {
                return ($this->_caseFoldingTo == CASE_UPPER) ? strtoupper($target) : strtolower($target);
            } else {
                return array_change_key_case(
                  $target,
                  $this->_caseFoldingTo
                );
            }
        }

        return $target;
    }

    // }}}
    // {{{ function stackdump()

    /**
    * Returns a stack dump as a debugging aid.
    *
    * @param
    * @return string
    * @access public
    */
    function stackdump() {
        $stackdump = sprintf(
          "Stackdump (level: %s) follows:\n",
          $this->_level
        );

        for ($i = $this->_level; $i >= 0; $i--) {
          $attributes = $this->attributesToString($this->_attributesStack[$i]);

          $stackdump .= sprintf(
            "level=%d\nelement=%s:%s\ncdata=%s\n\n",
            $i,
            $this->_elementStack[$i],
            (php_sapi_name() == 'cli') ? $attributes            : htmlspecialchars($attributes),
            (php_sapi_name() == 'cli') ? $this->_cdataStack[$i] : htmlspecialchars($this->_cdataStack[$i])
          );
        }

        return $stackdump;
    }

    // }}}
    // {{{ function overloadElement($element, $startHandler, $endHandler, $recursiveOperation = '')

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
        $result = $this->_callbackRegistry->overloadElement(
          $this->canonicalize($element),
          $startHandler,
          $endHandler,
          $recursiveOperation
        );

        if ($result !== true) {
            $this->_handleError($result);
        }
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
        $this->_callbackRegistry->unOverloadElement(
          $this->canonicalize($element)
        );
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
        return $this->_callbackRegistry->isOverloadedElement(
          $this->canonicalize($element)
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
    * @access public
    */
    function overloadNamespace($namespacePrefix, &$object, $recursiveOperation = '') {
        $namespacePrefix = $this->canonicalize($namespacePrefix);

        $result = $this->_callbackRegistry->overloadNamespace(
          $namespacePrefix,
          $object,
          $recursiveOperation
        );

        if ($result === true) {
            // Call initObserver() on the object, if it exists.

            if (is_object($object) && method_exists($object, 'initObserver')) {
                $object->initObserver(
                  $namespacePrefix,
                  $this
                );
            }
        } else {
            $this->_handleError($result);
        }
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
        $this->_callbackRegistry->unOverloadNamespace($namespacePrefix);
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
        return $this->_callbackRegistry->isOverloadedNamespace(
          $this->canonicalize($namespacePrefix)
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
        $this->_callbackRegistry->setDefaultCallback(
          $startHandler,
          $endHandler
        );
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
        $this->_callbackRegistry->unsetDefaultCallback();
    }

    // }}}
    // {{{ function setCaseFolding($caseFolding)

    /**
    * Sets the XML parser's case-folding option.
    *
    * @param  boolean
    * @param  integer
    * @access public
    */
    function setCaseFolding($caseFolding, $caseFoldingTo = CASE_UPPER) {
        if (is_bool($caseFolding) &&
            ($caseFoldingTo == CASE_LOWER || $caseFoldingTo == CASE_UPPER)) {
            $this->_caseFolding   = $caseFolding;
            $this->_caseFoldingTo = $caseFoldingTo;
        }
    }

    // }}}
    // {{{ function setDebug($debug)

    /**
    * Enables or disables debugging to error.log.
    *
    * @param  mixed
    * @access public
    */
    function setDebug($debug) {
        if (is_array($debug)) {
            $this->_debug       = true;
            $this->_debugFilter = array_flip($debug);
        }

        else if (is_bool($debug)) {
            $this->_debug = $debug;
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
        $this->_callbackRegistry->setRecursiveOperation($recursiveOperation);
    }

    // }}}
    // {{{ function transform($xml)

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
            $errmsg = sprintf(
              "<!-- Transformer: XML Error: %s at line %d\n",
              xml_error_string(xml_get_error_code($parser)),
              xml_get_current_line_number($parser)
            );

            $errmsg .= $this->stackdump() . " -->\n";

            return $errmsg;
        }

        xml_parser_free($parser);

        // Return result of the transformation.

        return $this->_cdataStack[0];
    }

    // }}}
    // {{{ function _startElement($parser, $element, $attributes)

    /**
    * SAX callback for 'startElement' event.
    *
    * @param  resource
    * @param  string
    * @param  array
    * @access private
    */
    function _startElement($parser, $element, $attributes) {
        $attributes = $this->canonicalize($attributes);
        $element    = $this->canonicalize($element);

        if (strstr($element, ':')) {
            list($namespacePrefix, $qElement) = explode(':', $element);
        } else {
            $namespacePrefix = '';
        }

        // Push element's name and attributes onto the stack.

        $this->_level++;
        $this->_elementStack[$this->_level]    = $element;
        $this->_attributesStack[$this->_level] = $attributes;

        $this->_debug(
          sprintf(
            'startElement[%d]: %s %s',
            $this->_level,
            $element,
            $this->attributesToString($attributes)
          ),
          $element
        );

        if ($this->_lastProcessed != $element &&
            isset($this->_callbackRegistry->overloadedNamespaces[$namespacePrefix]['object'])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->_callbackRegistry->overloadedNamespaces[$namespacePrefix]['object']->startElement(
              $qElement,
              $attributes
            );
        }

        else if ($this->_lastProcessed != $element &&
                 isset($this->_callbackRegistry->overloadedElements[$element]['start'])) {
            // The event is handled by a callback
            // that is registered for this element.

            $cdata = call_user_func(
              $this->_callbackRegistry->overloadedElements[$element]['start'],
              $attributes
            );
        }

        else if (isset($this->_callbackRegistry->overloadedElements['&DEFAULT']['start'])) {
            // The event is handled by the default callback.

            $cdata = call_user_func(
              $this->_callbackRegistry->overloadedElements['&DEFAULT']['start'],
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

    // }}}
    // {{{ function _endElement($parser, $element)

    /**
    * SAX callback for 'endElement' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    function _endElement($parser, $element) {
        $cdata     = $this->_cdataStack[$this->_level];
        $element   = $this->canonicalize($element);
        $recursion = false;

        if (strstr($element, ':')) {
            list($namespacePrefix, $qElement) = explode(':', $element);
        } else {
            $namespacePrefix = '';
        }

        $this->_debug(
          sprintf(
            'endElement[%d]: %s (with cdata=%s)',
            $this->_level,
            $element,
            $this->_cdataStack[$this->_level]
          ),
          $element
        );

        if ($this->_lastProcessed != $element &&
            isset($this->_callbackRegistry->overloadedNamespaces[$namespacePrefix]['object'])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->_callbackRegistry->overloadedNamespaces[$namespacePrefix]['object']->endElement(
              $qElement,
              $cdata
            );

            if ($this->_callbackRegistry->overloadedNamespaces[$namespacePrefix]['recursiveOperation']) {
                $recursion = true;
            }
        }

        else if ($this->_lastProcessed != $element &&
                 isset($this->_callbackRegistry->overloadedElements[$element]['end'])) {
            // The event is handled by a callback
            // that is registered for this element.

            $cdata = call_user_func(
              $this->_callbackRegistry->overloadedElements[$element]['end'],
              $cdata
            );

            if ($this->_callbackRegistry->overloadedElements[$element]['recursiveOperation']) {
                $recursion = true;
            }
        }

        else if (isset($this->_callbackRegistry->overloadedElements['&DEFAULT']['end'])) {
            // The event is handled by the default callback.

            $cdata = call_user_func(
              $this->_callbackRegistry->overloadedElements['&DEFAULT']['end'],
              $cdata
            );

            $recursion = true;
        }

        else {
            // No callback was registered for this element's
            // closing tag, copy it.

            $cdata .= '</' . $element . '>';
        }

        if ($recursion) {
            // Recursively process this transformation's result.

            $this->_debug(
              sprintf(
                'recursion[%d]: %s',
                $this->_level,
                $cdata
              ),
              '&RECURSE'
            );

            // Note: Recursive debugging creates monstrous output.

            $transformer = new XML_Transformer(
              array(
                'caseFolding'   => $this->_caseFolding,
                'caseFoldingTo' => $this->_caseFoldingTo,
                'debug'         => false,
                'lastProcessed' => $element
              )
            );

            $cdata = $transformer->transform($cdata);

            $this->_debug(
              sprintf(
                'end recursion[%d]: %s',
                $this->_level,
                $cdata
              ),
              '&RECURSE'
            );
        }

        // Move result of this transformation step to
        // the parent's CDATA section.

        $this->_cdataStack[--$this->_level] .= $cdata;
    }

    // }}}
    // {{{ function _characterData($parser, $cdata)

    /**
    * SAX callback for 'characterData' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    function _characterData($parser, $cdata) {
      $this->_debug(
        sprintf(
          'cdata [%d]: %s + %s',
          $this->_level,
          $this->_cdataStack[$this->_level],
          $cdata
        ),
        '&CDATA'
      );

      $this->_cdataStack[$this->_level] .= $cdata;
    }

    // }}}
    // {{{ function _debug($debugMessage, $currentElement = '')

    /**
    * Sends a debug message to error.log, if debugging is enabled.
    *
    * @param  string
    * @access private
    */
    function _debug($debugMessage, $currentElement = '') {
        if ($this->_debug &&
            (empty($this->_debugFilter) ||
             isset($this->_debugFilter[$currentElement]))) {
            error_log($debugMessage);
        }
    }

    // }}}
    // {{{ function _handleError($errorMessage)

    /**
    * Inserts an error message into the output.
    *
    * @param  string
    * @access private
    */
    function _handleError($errorMessage) {
        $this->_cdataStack[$this->_level] .= sprintf(
          "<!-- Transformer Error: %s -->\n",
          $errorMessage
        );
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

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
    // {{{ Members

    /**
    * @var    boolean
    * @access private
    */
    var $_caseFolding = false;

    /**
    * @var    integer
    * @access private
    */
    var $_caseFoldingTo = CASE_UPPER;

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
    * @var    boolean
    * @access private
    */
    var $_debug = false;

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

        $this->_caseFolding          = isset($parameters['caseFolding'])          ? $parameters['caseFolding']          : false;
        $this->_caseFoldingTo        = isset($parameters['caseFoldingTo'])        ? $parameters['caseFoldingTo']        : CASE_UPPER;
        $this->_recursiveOperation   = isset($parameters['recursiveOperation'])   ? $parameters['recursiveOperation']   : true;
        $this->_started              = isset($parameters['started'])              ? $parameters['started']              : false;

        $overloadedElements   = isset($parameters['overloadedElements'])   ? $parameters['overloadedElements']   : array();
        $overloadedNamespaces = isset($parameters['overloadedNamespaces']) ? $parameters['overloadedNamespaces'] : array();

        // Perform startup operations and begin transformation
        // if the transformation wasn't started before and
        // overloaded elements or namespaces were passed to the
        // constructor.

        if (!$this->_started &&
            (!empty($overloadedElements) ||
             !empty($overloadedNamespaces)
            )
           ) {
            // Check overloaded elements.

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

            // Check overloaded namespaces.

            foreach ($overloadedNamespaces as $namespacePrefix => $object) {
                $this->overloadNamespace(
                  $namespacePrefix,
                  $object
                );
            }

            // Start transformation.

            $this->_debug(
              'ctor (will start):' . serialize($this) . "\n"
            );

            $this->start();
        } else {
            $this->_overloadedElements   = $overloadedElements;
            $this->_overloadedNamespaces = $overloadedNamespaces;

            $this->_debug(
              "ctor (won't start):" . serialize($this) . "\n"
            );
        }
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
          $stackdump .= sprintf(
            "level=%d\nelement=%s:%s\ncdata=%s\n\n",
            $i,
            $this->_elementStack[$i],
            htmlspecialchars(
              $this->attributesToString(
                $this->_attributesStack[$i]
              )
            ),
            htmlspecialchars(
              $this->_cdataStack[$i]
            )
          );
        }

        return $stackdump;
    }

    // }}}
    // {{{ function attributesToString($attributes)

    /**
    * Returns string representation of attributes array.
    *
    * @param  array
    * @return string
    * @access public
    */
    function attributesToString($attributes) {
        $string = '';

        if ($this->_caseFolding) {
            $attributes = array_change_key_case(
              $attributes,
              $this->_caseFoldingTo
            );
        }

        ksort($attributes);

        foreach ($attributes as $key => $value) {
            $string .= ' ' . $key . '="' . $value . '"';
        }

        return $string;
    }

    // }}}
    // {{{ function canonicalName($name)

    /**
    * Returns the canonical name of a given element.
    *
    * @param  string
    * @return string
    * @access public
    */
    function canonicalName($name) {
        if ($this->caseFoldingTo == CASE_UPPER) {
            return $this->_caseFolding ? strtoupper($name) : $name;
        } else {
            return $this->_caseFolding ? strtolower($name) : $name;
        }
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
        $element = $this->canonicalName($element);

        $this->_registerElementCallback($element, 'start', $startHandler);
        $this->_registerElementCallback($element, 'end',   $endHandler);

        $this->_overloadedElements[$element]['recursiveOperation'] = !empty($recursiveOperation) ? $recursiveOperation : $this->_recursiveOperation;
    }

    // }}}
    // {{{ function unOverloadElement($element)

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
          $this->_overloadedElements[$this->canonicalName($element)]
        );
    }

    // }}}
    // {{{ function overloadNamespace($namespacePrefix, &$object)

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
            $this->_overloadedNamespaces[$namespacePrefix] = &$object;
        } else {
            $this->_handle_error(
              'Cannot overload namespace "' .
              $namespacePrefix .
              '", method(s) "startElement" and/or "endElement" ' .
              'are missing on given object.');
        }

        // if the namespace object has a method initObserver,
        // that method is automatically called with the
        // registered namespace prefix and with a backpointer
        // to the transformer, both of which are commonly
        // needed in a namespace object.

        if (is_object($object) && method_exists($object, 'initObserver')) {
          $object->initObserver(
            $namespacePrefix,
            $this
          );
        }
    }

    // }}}
    // {{{ function unOverloadNamespace($namespacePrefix)

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
          $this->_overloadedNamespaces[$this->canonicalName($namespacePrefix)]
        );
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
            ($caseFoldingTo == CASE_LOWER || $caseFoldingTo == CASE_UPPER)
           )
        {
            $this->_caseFolding   = $caseFolding;
            $this->_caseFoldingTo = $caseFoldingTo;
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
    // {{{ function setDebug($debug)

    /**
    * Enables or disables Debugging to syslog
    *
    * @param  boolean
    * @access public
    */
    function setDebug($debug) {
        if (is_bool($debug)) {
            $this->_debug = $debug;

            if ($debug) {
                define_syslog_variables();

                openlog(
                  $_SERVER['PHP_SELF'],
                  LOG_PID | LOG_PERROR, LOG_LOCAL0
                );

                syslog(
                  LOG_DEBUG,
                  "debug enabled\n"
                );
            } else {
                syslog(
                  LOG_DEBUG,
                  "debug disabled\n"
                );

                closelog();
            }
        }
    }

    // }}}
    // {{{ function start()

    /**
    * Starts the output-buffering,
    * and thus the transformation.
    *
    * @access public
    */
    function start() {
        $this->_debug(
          sprintf(
            "start: %s\n",
            serialize($this)
          )
        );

        if (!$this->_started) {
            ob_start(
              array(
                $this, 'transform'
              )
            );

            $this->_started = true;
        }
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
        $element         = $this->canonicalName($element);
        $namespacePrefix = '';

        if (strstr($element, ':')) {
            list($namespacePrefix, $qElement) = explode(':', $element);
        }

        // Push element's name and attributes onto the stack.

        $this->_level++;

        $this->_debug(
          sprintf(
            "startElement[%d]: %s %s\n",
            $this->_level,
            $element,
            $this->attributesToString($attributes)
          )
        );

        $this->_elementStack[$this->_level]    = $element;
        $this->_attributesStack[$this->_level] = $attributes;

        if (isset($this->_overloadedNamespaces[$namespacePrefix])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->_overloadedNamespaces[$namespacePrefix]->startElement(
              $qElement,
              $attributes
            );
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
        $this->_debug(
          sprintf(
            "endElement[%d]: %s (with cdata=%s)\n",
            $this->_level,
            $element,
            $this->_cdataStack[$this->_level]
          )
        );

        $cdata           = $this->_cdataStack[$this->_level];
        $element         = $this->canonicalName($element);
        $namespacePrefix = '';
        $recursion       = false;

        if (strstr($element, ':')) {
            list($namespacePrefix, $qElement) = explode(':', $element);
        }

        if (isset($this->_overloadedNamespaces[$namespacePrefix])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->_overloadedNamespaces[$namespacePrefix]->endElement(
              $qElement,
              $cdata
            );

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

        if ($recursion &&
            $this->_overloadedElements[$element]['recursiveOperation']
           )
        {
            // Recursively process this transformation's result.

            $this->_debug(
              sprintf(
                "recursion[%d]: %s\n",
                $this->_level,
                $cdata
              )
            );

            // Note: Recursive debugging creates monstrous output.

            $transformer = new XML_Transformer(
              array(
                'caseFolding'          => $this->_caseFolding,
                'overloadedElements'   => $this->_overloadedElements,
                'overloadedNamespaces' => $this->_overloadedNamespaces,
                'recursiveOperation'   => $this->_recursiveOperation,
                'debug'                => false,
                'started'              => true
              )
            );

            $cdata = $transformer->transform($cdata);

            $this->_debug(
              sprintf(
                "end recursion[%d]: %s\n",
                $this->_level,
                $cdata
              )
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
          "cdata [%d]: %s + %s\n",
          $this->_level,
          $this->_cdataStack[$this->_level],
          $cdata
        )
      );

      $this->_cdataStack[$this->_level] .= $cdata;
    }

    // }}}
    // {{{ function _parseCallback($element, $event, $callback)

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

    // }}}
    // {{{ function _registerElementCallback($element, $event, $callback)

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
    // {{{ function _debug($debugMessage) {

    /**
    * Sends a debug message to syslog, if debugging is enabled.
    *
    * @param  string
    * @access private
    */

    function _debug($debugMessage) {
        if ($this->_debug) {
            syslog(
              LOG_DEBUG,
              $debugMessage
            );
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

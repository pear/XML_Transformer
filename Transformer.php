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

require_once 'XML/Transformer/CallbackRegistry.php';
require_once 'XML/Util.php';

/**
* XML Transformations in PHP.
*
* With this class one can easily bind PHP functionality to XML tags,
* thus transforming an XML input tree into another XML tree without
* the need for XSLT.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer {
    // {{{ Members

    /**
    * @var    object
    * @access protected
    */
    protected $callbackRegistry = null;

    /**
    * If true, XML attribute and element names will be
    * case-folded.
    * 
    * @var    boolean
    * @access private
    * @see    $_caseFoldingTo
    */
    private $caseFolding = false;

    /**
    * Can be set to either CASE_UPPER or CASE_LOWER
    * and sets the target case for the case-folding.
    *
    * @var    integer
    * @access private
    * @see    $_caseFolding
    */
    private $caseFoldingTo = CASE_UPPER;

    /**
    * When set to TRUE empty XML tags (<foo></foo>) are
    * collapsed to their short-tag (<foo/>) equivalent.
    *
    * @var    boolean
    * @access private
    */
    private $collapseEmptyTags = false;

    /**
    * If true, debugging information will be sent to
    * the error.log.
    *
    * @var    boolean
    * @access private
    * @see    $_debugFilter
    */
    private $debug = false;

    /**
    * If not empty, debugging information will only be generated
    * for XML elements whose names are in this array.
    *
    * @var    array
    * @access private
    * @see    $_debug
    */
    private $debugFilter = array();

    /**
    * Specifies the target to which error messages and
    * debugging messages are sent.
    *
    * @var    string
    * @access private
    * @see    $_debug
    */
    private $logTarget = 'error_log';

    /**
    * @var    array
    * @access private
    */
    private $attributesStack = array();

    /**
    * @var    array
    * @access private
    */
    private $cdataStack = array('');

    /**
    * @var    array
    * @access private
    */
    private $elementStack = array();

    /**
    * @var    integer
    * @access private
    */
    private $level = 0;

    /**
    * @var    string
    * @access private
    */
    private $lastProcessed = '';

    /**
    * @var    boolean
    * @access public
    */
    private $secondPassRequired = false;

    /**
    * @var    integer
    * @access private
    */
    private $depth = 0;

    // }}}
    // {{{ public function __construct($parameters = array())

    /**
    * Constructor.
    *
    * @param  array
    * @access public
    */
    public function __construct($parameters = array()) {
        // Parse parameters array.

        if (isset($parameters['debug'])) {
            $this->setDebug($parameters['debug']);
        }

        $this->caseFolding       = isset($parameters['caseFolding'])          ? $parameters['caseFolding']          : false;
        $this->collapseEmptyTags = isset($parameters['collapseEmptyTags'])    ? $parameters['collapseEmptyTags']    : false;
        $this->caseFoldingTo     = isset($parameters['caseFoldingTo'])        ? $parameters['caseFoldingTo']        : CASE_UPPER;
        $this->lastProcessed     = isset($parameters['lastProcessed'])        ? $parameters['lastProcessed']        : '';
        $this->logTarget         = isset($parameters['logTarget'])            ? $parameters['logTarget']            : 'error_log';

        $autoload                = isset($parameters['autoload'])             ? $parameters['autoload']             : false;
        $overloadedNamespaces    = isset($parameters['overloadedNamespaces']) ? $parameters['overloadedNamespaces'] : array();
        $recursiveOperation      = isset($parameters['recursiveOperation'])   ? $parameters['recursiveOperation']   : true;

        // Initialize callback registry.

        $this->callbackRegistry = XML_Transformer_CallbackRegistry::getInstance(
          $recursiveOperation
        );

        foreach ($overloadedNamespaces as $namespacePrefix => $object) {
            $this->overloadNamespace(
              $namespacePrefix,
              $object
            );
        }

        if ($autoload !== false) {
            $this->autoload($autoload);
        }
    }

    // }}}
    // {{{ public function canonicalize($target)

    /**
    * Canonicalizes a given attributes array or element name.
    *
    * @param  mixed
    * @return mixed
    * @access public
    */
    public function canonicalize($target) {
        if ($this->caseFolding) {
            if (is_string($target)) {
                return ($this->caseFoldingTo == CASE_UPPER) ? strtoupper($target) : strtolower($target);
            } else {
                return array_change_key_case(
                  $target,
                  $this->caseFoldingTo
                );
            }
        }

        return $target;
    }

    // }}}
    // {{{ public function overloadNamespace($namespacePrefix, $object, $recursiveOperation = '')

    /**
    * Overloads an XML Namespace.
    *
    * @param  string
    * @param  object
    * @param  boolean
    * @access public
    */
    public function overloadNamespace($namespacePrefix, $object, $recursiveOperation = '') {
        if (empty($namespacePrefix) ||
            $namespacePrefix == '&MAIN') {
            $namespacePrefix = '&MAIN';
        } else {
            $namespacePrefix = $this->canonicalize($namespacePrefix);
        }

        $result = $this->callbackRegistry->overloadNamespace(
          $namespacePrefix,
          $object,
          $recursiveOperation
        );

        if ($result === true) {
            if ($object->secondPassRequired) {
                $this->secondPassRequired = true;
            }

            // Call initObserver() on the object, if it exists.

            if (method_exists($object, 'initObserver')) {
                $object->initObserver(
                  $namespacePrefix,
                  $this
                );
            }
        } else {
            $this->sendMessage(
              $result,
              $this->logTarget
            );
        }
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
        $this->callbackRegistry->unOverloadNamespace($namespacePrefix);
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
        return $this->callbackRegistry->isOverloadedNamespace(
          $this->canonicalize($namespacePrefix)
        );
    }

    // }}}
    // {{{ public function sendMessage($message, $target = 'error_log')

    /**
    * Sends a message to a given target.
    *
    * @param  string
    * @param  string
    * @access public
    */
    public function sendMessage($message, $target = 'error_log') {
        switch ($target) {
            case 'echo':
            case 'print': {
                print $message;
            }
            break;

            default: {
                error_log($message);
            }
        }
    }

    // }}}
    // {{{ public function setCaseFolding($caseFolding)

    /**
    * Sets the XML parser's case-folding option.
    *
    * @param  boolean
    * @param  integer
    * @access public
    */
    public function setCaseFolding($caseFolding, $caseFoldingTo = CASE_UPPER) {
        if (is_bool($caseFolding) &&
            ($caseFoldingTo == CASE_LOWER || $caseFoldingTo == CASE_UPPER)) {
            $this->caseFolding   = $caseFolding;
            $this->caseFoldingTo = $caseFoldingTo;
        }
    }

    // }}}
    // {{{ function setCollapsingOfEmptyTags($collapseEmptyTags)

    /**
    * Sets the XML parser's case-folding option.
    *
    * @param  boolean
    * @access public
    */
    function setCollapsingOfEmptyTags($collapseEmptyTags) {
        if (is_bool($collapseEmptyTags)) {
            $this->collapseEmptyTags = $collapseEmptyTags;
        }
    }

    // }}}
    // {{{ public function setDebug($debug)

    /**
    * Enables or disables debugging information.
    *
    * @param  mixed
    * @access public
    */
    public function setDebug($debug) {
        if (is_array($debug)) {
            $this->debug       = true;
            $this->debugFilter = array_flip($debug);
        }

        else if (is_bool($debug)) {
            $this->debug = $debug;
        }
    }

    // }}}
    // {{{ public function setLogTarget($logTarget)

    /**
    * Sets the target to which error messages and
    * debugging messages are sent.
    *
    * @param  string
    * @access public
    */
    public function setLogTarget($logTarget) {
        $this->logTarget = $logTarget;
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
        $this->callbackRegistry->setRecursiveOperation($recursiveOperation);
    }

    // }}}
    // {{{ public function stackdump()

    /**
    * Returns a stack dump as a debugging aid.
    *
    * @return string
    * @access public
    */
    public function stackdump() {
        $stackdump = sprintf(
          "Stackdump (level: %s) follows:\n",
          $this->level
        );

        for ($i = $this->level; $i >= 0; $i--) {
          $stackdump .= sprintf(
            "level=%d\nelement=%s:%s\ncdata=%s\n\n",
            $i,
            isset($this->elementStack[$i])    ? $this->elementStack[$i]                                  : '',
            isset($this->attributesStack[$i]) ? XML_Util::attributesToString($this->attributesStack[$i]) : '',
            isset($this->cdataStack[$i])      ? $this->cdataStack[$i]                                    : ''
          );
        }

        return $stackdump;
    }

    // }}}
    // {{{ public function transform($xml)

    /**
    * Transforms a given XML string using the registered
    * PHP callbacks for overloaded tags.
    *
    * @param  string
    * @return string
    * @access public
    */
    public function transform($xml) {
        // Don't process input when it contains no XML elements.

        if (strpos($xml, '<') === false) {
            return $xml;
        }

        $xml = str_replace('&', '&amp;', $xml);

        // Create XML parser, set parser options.

        $parser = xml_parser_create();

        xml_set_object($parser, $this);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, $this->caseFolding);

        // Register SAX callbacks.

        xml_set_element_handler($parser, '_startElement', '_endElement');
        xml_set_character_data_handler($parser, '_characterData');
        xml_set_default_handler($parser, '_characterData');

        // Parse input.

        if (!xml_parse($parser, $xml, true)) {
            $line = xml_get_current_line_number($parser);

            $errorMessage = sprintf(
              "Transformer: XML Error: %s at line %d:%d\n",
              xml_error_string(xml_get_error_code($parser)),
              $line,
              xml_get_current_column_number($parser)
            );

            $exml = preg_split('/\n/', $xml);

            $start = ($line - 3 > 0)             ? $line - 3 : 0;
            $end   = ($line + 3 < sizeof($exml)) ? $line + 3 : sizeof($exml);

            for ($i = $start; $i < $end; $i++) {
                $errorMessage .= sprintf(
                  "line %d: %s\n",
                  $i+1,
                  $exml[$i]
                );
            }

            $this->sendMessage(
              $errorMessage . "\n" . $this->stackdump(),
              $this->logTarget
            );

            return '';
        }

        $result = $this->cdataStack[0];

        // Clean up.

        xml_parser_free($parser);

        $this->attributesStack = array();
        $this->cdataStack      = array('');
        $this->elementStack    = array();
        $this->level           = 0;
        $this->lastProcessed   = '';

        // Perform second transformation pass, if required.

        $secondPassRequired = $this->secondPassRequired;

        if ($secondPassRequired) {
            $this->depth++;
            $this->secondPassRequired = false;
            $result = $this->transform($result);
            $this->depth--;
        }

        if ($this->collapseEmptyTags &&
            $this->depth == 0) {
            $result = preg_replace(
              '/<(\w+)([^>]*)><\/\\1>/s',
              '<\\1\\2 />',
              $result
            );
        }

        $this->secondPassRequired = $secondPassRequired;

        // Return result of the transformation.

        return $result;
    }

    // }}}
    // {{{ private function startElement($parser, $element, $attributes)

    /**
    * SAX callback for 'startElement' event.
    *
    * @param  resource
    * @param  string
    * @param  array
    * @access private
    */
    private function startElement($parser, $element, $attributes) {
        $attributes = $this->canonicalize($attributes);
        $element    = $this->canonicalize($element);
        $qElement   = XML_Util::splitQualifiedName($element, '&MAIN');
        $process    = $this->lastProcessed != $element;

        // Push element's name and attributes onto the stack.

        $this->level++;
        $this->elementStack[$this->level]    = $element;
        $this->attributesStack[$this->level] = $attributes;

        if ($this->checkDebug($element)) {
            $this->sendMessage(
              sprintf(
                'startElement[%d]: %s %s',
                $this->level,
                $element,
                XML_Util::attributesToString($attributes)
              )
            );
        }

        if ($process &&
            isset($this->callbackRegistry->overloadedNamespaces[$qElement['namespace']]['active'])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $cdata = $this->callbackRegistry->overloadedNamespaces[$qElement['namespace']]['object']->startElement(
              $qElement['localPart'],
              $attributes
            );
        } else {
            // No callback was registered for this element's
            // opening tag, copy it.

            $cdata = sprintf(
              '<%s%s>',
              $element,
              XML_Util::attributesToString($attributes)
            );
        }

        $this->cdataStack[$this->level] = $cdata;
    }

    // }}}
    // {{{ private function endElement($parser, $element)

    /**
    * SAX callback for 'endElement' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    private function endElement($parser, $element) {
        $cdata     = $this->cdataStack[$this->level];
        $element   = $this->canonicalize($element);
        $qElement  = XML_Util::splitQualifiedName($element, '&MAIN');
        $process   = $this->lastProcessed != $element;
        $recursion = false;

        if ($process &&
            isset($this->callbackRegistry->overloadedNamespaces[$qElement['namespace']]['active'])) {
            // The event is handled by a callback
            // that is registered for this namespace.

            $result = $this->callbackRegistry->overloadedNamespaces[$qElement['namespace']]['object']->endElement(
              $qElement['localPart'],
              $cdata
            );

            if (is_array($result)) {
                $cdata   = &$result[0];
                $reparse = $result[1];
            } else {
                $cdata   = &$result;
                $reparse = true;
            }

            $recursion = $reparse &&
                         isset($this->elementStack[$this->level-1]) &&
                         $this->callbackRegistry->overloadedNamespaces[$qElement['namespace']]['recursiveOperation'];
        } else {
            // No callback was registered for this element's
            // closing tag, copy it.

            $cdata .= '</' . $element . '>';
        }

        if ($recursion) {
            // Recursively process this transformation's result.

            if ($this->checkDebug('&RECURSE')) {
                $this->sendMessage(
                  sprintf(
                    'start recursion[%d]: %s',
                    $this->level,
                    $cdata
                  )
                );
            }

            $transformer = new XML_Transformer(
              array(
                'caseFolding'   => $this->caseFolding,
                'caseFoldingTo' => $this->caseFoldingTo,
                'lastProcessed' => $element
              )
            );

            $cdata = substr($transformer->transform("<_>$cdata</_>"),3,-4);

            if ($this->checkDebug('&RECURSE')) {
                $this->sendMessage(
                  sprintf(
                    'end recursion[%d]: %s',
                    $this->level,
                    $cdata
                  )
                );
            }
        }

        if ($this->checkDebug($element)) {
            $this->sendMessage(
              sprintf(
                'endElement[%d]: %s (with cdata=%s)',
                $this->level,
                $element,
                $this->cdataStack[$this->level]
              )
            );
        }

        // Move result of this transformation step to
        // the parent's CDATA section.

        $this->cdataStack[--$this->level] .= $cdata;
    }

    // }}}
    // {{{ private function characterData($parser, $cdata)

    /**
    * SAX callback for 'characterData' event.
    *
    * @param  resource
    * @param  string
    * @access private
    */
    private function characterData($parser, $cdata) {
      if ($this->checkDebug('&CDATA')) {
          $this->sendMessage(
            sprintf(
              'cdata [%d]: %s + %s',
              $this->level,
              $this->cdataStack[$this->level],
              $cdata
            )
          );
      }

      $this->cdataStack[$this->level] .= $cdata;
    }

    // }}}
    // {{{ private function autoload($namespaces)

    /**
    * Loads either all (true) or a selection of namespace
    * handlers from XML/Transformer/Namespace/.
    *
    * @param  mixed
    * @access private
    */
    private function autoload($namespaces) {
        $path = dirname(__FILE__) . '/Transformer/Namespace/';

        if ($namespaces === true) {
            $namespaces = array();

            if ($dir = @opendir($path)) {
                while (($file = @readdir($dir)) !== false) {
                    if (strstr($file, '.php')) {
                        $namespaces[] = $this->canonicalize(
                          strtolower(
                            substr($file, 0, -4)
                          )
                        );
                    }
                }
            }
        }

        else if (is_string($namespaces)) {
            $namespaces = array($namespaces);
        }

        foreach ($namespaces as $namespace) {
            if (@include_once($path . $namespace . '.php')) {
                $className = 'XML_Transformer_Namespace_' . $namespace;
                $object    = new $className;

                $this->overloadNamespace(
                  !empty($object->defaultNamespacePrefix) ? $object->defaultNamespacePrefix : $namespace,
                  $object
                );
            }
        }
    }

    // }}}
    // {{{ protected function checkDebug($currentElement = '')

    /**
    * Checks whether a debug message should be printed
    * for the current event.
    *
    * @param  string
    * @return boolean
    * @access protected
    */
    protected function checkDebug($currentElement = '') {
        if ($this->debug &&
            (empty($this->debugFilter) ||
             isset($this->debugFilter[$currentElement]))) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

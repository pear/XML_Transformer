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

require_once 'XML/Util.php';

/**
* Convenience Base Class for Namespace Transformers.
*
* Example
*
*   <?php
*   require_once 'XML/Transformer.php';
*   require_once 'XML/Transformer/Namespace.php';
*
*   class Image extends XML_Transformer_Namespace {
*       var $imageAttributes = array();
*
*       public function truePath($path) {
*           if (php_sapi_name() == 'apache') {
*               $r    = apache_lookup_uri($path);
*               $path = $r->filename;
*           } else {
*               $path = $_SERVER['DOCUMENT_ROOT'] . "/$path";
*           }
*
*           return $path;
*       }
*
*       public function start_img($attributes) {
*           $this->imageAttributes = $attributes;
*           return '';
*       }
*
*       public function end_img($cdata) {
*           $src = $this->truePath($this->imageAttributes['src']);
*           list($w, $h, $t, $whs) = getimagesize($src);
*
*           $this->imageAttributes['height'] = $w;
*           $this->imageAttributes['width']  = $h;
*
*           return sprintf(
*             '<img %s/>',
*             XML_Transformer::attributesToString($this->imageAttributes)
*           );
*       }
*   }
*   ?>
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
abstract class XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    string
    * @access public
    */
    public $defaultNamespacePrefix = '';

    /**
    * @var    boolean
    * @access public
    */
    public $secondPassRequired = false;

    /**
    * @var    array
    * @access private
    */
    private $prefix = array();

    /**
    * @var    string
    * @access private
    */
    private $transformer = '';

    // }}}
    // {{{ public function initObserver($prefix, $object)

    /**
    * Called by XML_Transformer at initialization time.
    * We use this to remember our namespace prefixes
    * (there can be multiple) and a pointer to the
    * Transformer object.
    *
    * @param  string
    * @param  object
    * @access public
    */
    public function initObserver($prefix, $object) {
        $this->prefix[]    = $prefix;
        $this->transformer = $object;
    }

    // }}}
    // {{{ public function startElement($element, $attributes)

    /**
    * Wrapper for startElement handler.
    *
    * @param  string
    * @param  array
    * @return string
    * @access public
    */
    public function startElement($element, $attributes) {
        $do = 'start_' . $element;

        if (method_exists($this, $do)) {
            return $this->$do($attributes);
        }

        return sprintf(
          "<%s%s>",

          $element,
          XML_Util::attributesToString($attributes)
        );
    }

    // }}}
    // {{{ public function endElement($element, $cdata)

    /**
    * Wrapper for endElement handler.
    *
    * @param  string
    * @param  string
    * @return array
    * @access public
    */
    public function endElement($element, $cdata) {
        $do = 'end_' . $element;

        if (method_exists($this, $do)) {
            return $this->$do($cdata);
        }

        return array(
          sprintf(
            '%s</%s>',

            $cdata,
            $element
          ),
          false
        );
    }

    // }}}
    // {{{ public function public function getLock()

    /**
    * Lock all other namespace handlers.
    *
    * @return boolean
    * @access public
    * @see    releaseLock()
    */
    public function getLock() {
        return $this->transformer->callbackRegistry->getLock($this->prefix[0]);
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
        $this->transformer->callbackRegistry->releaseLock();
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

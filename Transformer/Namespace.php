<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Namespace Hander Base Class            |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                    Kristian K�hntopp <kris@koehntopp.de>.            |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,      |
// | that is available through the world-wide-web at                      |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
//
// $Id$
//

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
*       function truePath($path) {
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
*       function start_img($attributes) {
*           $this->imageAttributes = $attributes;
*           return '';
*       }
*
*       function end_img($cdata) {
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
*          Kristian K�hntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace {
    // {{{ function startElement($element, $attributes)

    /**
    * Wrapper for startElement handler.
    *
    * @param  string
    * @param  array
    * @return string
    * @access public
    */
    function startElement($element, $attributes) {
        $do = 'start_' . $element;

        if (method_exists($this, $do)) {
            return $this->$do($attributes);
        }

        return sprintf(
          "<!-- undefined: %s --><%s %s>",

          $element,
          $element,
          XML_Transformer::attributesToString($attributes)
        );
    }

    // }}}
    // {{{ function endElement($element, $cdata)

    /**
    * Wrapper for endElement handler.
    *
    * @param  string
    * @param  string
    * @return string
    * @access public
    */
    function endElement($element, $cdata) {
        $do = 'end_' . $element;

        if (method_exists($this, $do)) {
            return $this->$do($cdata);
        }

        return sprintf(
          '%s</%s>',

          $cdata,
          $element
        );
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

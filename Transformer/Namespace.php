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
* Convenience Base Class for Namespace Transformers.
*
* Example
*
*   <?php
*   require_once 'XML/Transformer.php';
*   require_once 'XML/Transformer/Namespace.php';
*
*   class Image extends XML_Transformer_Namespace {
*       var $imageAttributes;
*
*       function start_img($imageAttributes) {
*           $this->imageAttributes = $imageAttributes;
*           return "";
*       }
*
*       function end_img($cdata) {
*           // do something
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
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace {
    function startElement($element, $attributes) {
        $do = "start_$element";

        if (method_exists($this, $do)) {
            return $this->$do($attributes);
        }

        return "<!-- undefined: $element --><$element>";
    }

    function endElement($element, $cdata) {
        $do = "end_$element";

        if (method_exists($this, $do)) {
            return $this->$do($cdata);
        }

        return "$cdata</$element>";
    }
}
?>

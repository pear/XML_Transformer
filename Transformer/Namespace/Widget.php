<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Widget Namespace Handler                    |
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

require_once 'XML/Transformer/Namespace.php';

/**
* Handler for the Widget Namespace.
*
* Implements <widget:obox /> similar to http://docs.roxen.com/roxen/2.2/creator/text/obox.tag.
* Implements <widget:oboxtitle> as counterpart to <obox><title>..</title></obox> in Roxen.
*
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_Widget extends XML_Transformer_Namespace {
    // {{{ Members
    
    /**
    * @var    boolean
    * @access public
    */
    public $defaultNamespacePrefix = 'widget';

    /**
    * @var    array
    * @access private
    */
    private $oboxAttributes = array();

    /**
    * @var    string
    * @access private
    */
    private $oboxUnitPngPath = '';

    /**
    * @var    string
    * @access private
    */
    private $oboxUnitPngURL = '/cache/unit.png';

    // }}}
    // {{{ public function start_obox($attributes)

    /**
    * <obox /> -- This container creates an outlined box.
    *
    * The outer Table is controlled by
    *   align=...
    *   width=...
    *
    * The title is controlled by
    *   title=...
    *   titlealign=...
    *   titlevalign=...
    *   titlecolor=...
    *
    * The outline is controlled by
    *   outlinecolor=...
    *   outlinewidth=...
    *   left=...
    *   leftskip=...
    *   right=...
    *   rightskip=...
    *
    * The inner table cell is controlled by
    *   contentalign=...
    *   contentvalign=...
    *   contentpadding=...
    *   contentwidth=...
    *   contentheight=...
    *   bgcolor=...
    *
    * @param  string
    * @return string
    * @access public
    */
    public function start_obox($attributes) {
        $this->oboxAttributes = $attributes;

        return '';
    }

    // }}}
    // {{{ public function end_obox($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_obox($cdata) {
        return $this->box($cdata);
    }

    // }}}
    // {{{ public function start_oboxtitle($attributes)

    /**
    * <oboxtitle /> -- Alternate method to set the obox title
    *
    * align=...
    * valign=...
    *
    * @param  string
    * @return string
    * @access public
    */
    public function start_oboxtitle($attributes) {
        if (isset($attributes['align'])) {
            $this->oboxAttributes['titlealign'] = $attributes['align'];
        }

        if (isset($attributes['valign'])) {
            $this->oboxAttributes['titlevalign'] = $attributes['valign'];
        }

        if (isset($attributes['bgcolor'])) {
            $this->oboxAttributes['titlecolor'] = $attributes['bgcolor'];
        }

        return '';
    }

    // }}}
    // {{{ public function end_oboxtitle($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_oboxtitle($cdata) {
        $this->oboxAttributes['title'] = $cdata;

        return '';
    }

    // }}}
    // {{{ private function makeUnitPngPath()

    /**
    * Create the filesystem pathname for the unitPng
    *
    * @return void
    * @access private
    */
    private function makeUnitPngPath() {
      $this->oboxUnitPngPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->oboxUnitPngURL;

      return;
    }

    // }}}
    // {{{ private function unitPng()

    /**
    * Create the transparent unitPng and return its URL
    *
    * @return string
    * @access private
    */
    private function unitpng() {
        if (file_exists($this->oboxUnitPngPath)) {
            return $this->oboxUnitPngURL;
        }

        $im    = ImageCreate(1, 1);
        $trans = ImageColorAllocate($im, 128, 128, 128);

        ImageColorTransparent($im, $trans);
        ImageFilledRectangle($im, 0,0,1,1,$trans);

        $this->makeUnitPngPath();

        ImagePNG($im, $this->oboxUnitPngPath);
        ImageDestroy($im);

        return $this->oboxUnitURL;
    }

    // }}}
    // {{{ private function imagePlaceholder($h = false, $w = false)

    /**
    * Create a placeholder image of $h pixel height and $w pixel width
    *
    * @param  integer
    * @param  integer
    * @return string
    * @access private
    */
    private function imagePlaceholder($h = false, $w = false) {
        if ($h === false) {
            $h = isset($this->oboxAttributes['outlinewidth']) ? $this->oboxAttributes['outlinewidth'] : 1;
        }

        if ($w === false) {
            $w = $h;
        }

        return sprintf(
          '<img src="%s" alt="" width="%s" height="%s" />',
          $this->unitpng(),
          $w,
          $h
        );
    }

    // }}}
    // {{{ private function oboxGetAttr($name)

    /**
    * Return value of $name suitable for attribute printing (name='value')
    * or an empty string ('')
    *
    * @param  string
    * @return string
    * @access private
    */
    private function oboxGetAttr($name) {
        if (isset($this->oboxAttributes[$name])) {
            return sprintf(
              " %s='%s'",
              $name,
              $this->oboxAttributes[$name]
            );
        } else {
            return '';
        }
    }

    // }}}
    // {{{ private function oboxGetAttrAs($name, $attributes)

    /**
    * Return value of $name suitable as printable attr $attr (attr='valueofname')
    * or an empty string ('')
    *
    * @param  string
    * @param  string
    * @return string
    * @access private
    */
    private function oboxGetAttrAs($name, $attributes) {
        if (isset($this->oboxAttributes[$name])) {
            return sprintf(
              " %s='%s'",
              $attributes,
              $this->oboxAttributes[$name]
            );
        } else {
            return '';
        }
    }

    // }}}
    // {{{ private function oboxGetValueWithDefault($name, $def)

    /**
    * Return value of $name as value or $def, if empty.
    *
    * @param  string
    * @param  string
    * @return string
    * @access private
    */
    private function oboxGetValueWithDefault($name, $def) {
        if (isset($this->oboxAttributes[$name])) {
            return $this->oboxAttributes[$name];
        } else {
            return $def;
        }
    }

    // }}}
    // {{{ private function titlebox()

    /**
    * Create the obox titlebox. Ugly.
    *
    * @return string
    * @access private
    */
    private function titlebox() {
        if (!isset($this->oboxAttributes['title'])) {
            return sprintf(
              " <tr>\n  <td colspan='5'%s>%s</td>\n </tr>\n",
              $this->oboxGetAttrAs("outlinecolor", "bgcolor"),
              $this->imagePlaceholder()
            );
        }

        $left      = $this->oboxGetValueWithDefault('left',      20);
        $right     = $this->oboxGetValueWithDefault('right',     20);
        $leftskip  = $this->oboxGetValueWithDefault('leftskip',  10);
        $rightskip = $this->oboxGetValueWithDefault('rightskip', 10);

        if (!isset($this->oboxAttributes['titlecolor']) &&
             isset($this->oboxAttributes['bgcolor'])) {
            $this->oboxAttributes['titlecolor'] = $this->oboxAttributes['bgcolor'];
        }

        $r .= sprintf(
          " <tr>\n  <td>%s</td>\n  <td>%s</td>\n  <td nowrap='nowrap' rowspan='3'%s%s%s>%s%s%s</td>\n  <td>%s</td>\n  <td>%s</td>\n </tr>\n",
          $this->imagePlaceholder(1,1),
          $this->imagePlaceholder(1, $left),
          $this->oboxGetAttrAs('titlealign', 'align'),
          $this->oboxGetAttrAs('titlevalign', 'valign'),
          $this->oboxGetAttrAs('titlecolor', 'bgcolor'),
          $this->imagePlaceholder(1, $leftskip),
          $this->oboxAttributes['title'],
          $this->imagePlaceholder(1, $rightskip),
          $this->imagePlaceholder(1, $right),
          $this->imagePlaceholder(1,1)
        );

        $r .= sprintf(
          " <tr%s>\n  <td colspan='2' height='1'%s>%s</td>\n  <td colspan='2' height='1'%s>%s</td>\n </tr>\n",
          $this->oboxGetAttrAs("bgcolor", "bgcolor"),
          $this->oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->imagePlaceholder($this->oboxGetValueWithDefault("outlinewidth", 1), 1),
          $this->oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->imagePlaceholder($this->oboxGetValueWithDefault("outlinewidth", 1), 1)
        );

        $r .= sprintf(
          " <tr%s>\n  <td%s>%s</td>\n  <td>%s</td>\n  <td>%s</td>\n  <td%s>%s</td>\n </tr>\n",
          $this->oboxGetAttrAs("bgcolor", "bgcolor"),
          $this->oboxGetAttrAs('outlinecolor', 'bgcolor'),
          $this->imagePlaceholder(1, $this->oboxGetValueWithDefault("outlinewidth", 1)),
          $this->imagePlaceholder(1, 1),
          $this->imagePlaceholder(1, 1),
          $this->oboxGetAttrAs('outlinecolor', 'bgcolor'),
          $this->imagePlaceholder(1, $this->oboxGetValueWithDefault("outlinewidth", 1))
        );

        return $r;
    }

    // }}}
    // {{{ private function box($cdata)

    /**
    * Create the actual obox.
    *
    * @param  string
    * @return string
    * @access private
    */
    private function box($cdata) {
        /* Outer container */
        $r  = sprintf(
          "<table border='0' cellpadding='0' cellspacing='0'%s%s>\n",
          $this->oboxGetAttr("align"),
          $this->oboxGetAttr("width")
        );

        /* Title */
        $r .= $this->titlebox();

        /* Content container */
        $r .= sprintf(
          " <tr%s>\n",
          $this->oboxGetAttr("bgcolor")
        );

        $r .= sprintf(
          "  <td%s%s>%s</td>\n  <td colspan='3'>\n",
          $this->oboxGetAttrAs("outlinewidth", "width"),
          $this->oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->imagePlaceholder(1, $this->oboxGetValueWithDefault("outlinewidth", 1))
        );

        $r .= sprintf(
          "<table %s%s border='0' cellspacing='0' cellpadding='%s'><tr><td%s%s>%s</td></tr></table>\n  </td>\n",
          $this->oboxGetAttrAs("contentwidth", "width"),
          $this->oboxGetAttrAs("contentheight", "height"),
          $this->oboxGetValueWithDefault("contentpadding", 0),
          $this->oboxGetAttrAs("contentalign", "align"),
          $this->oboxGetAttrAs("contentvalign", "valign"),
          $cdata
        );

        $r .= sprintf(
          "  <td%s%s>%s</td>\n </tr>\n",
          $this->oboxGetAttrAs("outlinewidth", "width"),
          $this->oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->imagePlaceholder(1, $this->oboxGetValueWithDefault("outlinewidth", 1))
        );

        /* Footer line */
        $r .= sprintf(
          " <tr>\n  <td colspan='5'%s>%s</td>\n </tr>\n</table>\n",
          $this->oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->imagePlaceholder()
        );

        return $r;
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

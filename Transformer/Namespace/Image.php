<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Image Namespace Handler                |
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

require_once 'XML/Transformer/Namespace.php';
require_once 'XML/Transformer/Util.php';

define(PEAR_XML_TRANSFORMER_IMAGE_FONTPATH, '/usr/X11R6/lib/X11/fonts/truetype');
define(PEAR_XML_TRANSFORMER_IMAGE_cacheDir, '/cache/gtext');

/**
* Handler for the Image Namespace.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_Image extends XML_Transformer_Namespace {
    var $img_attributes          = array();
    var $gtext_attributes        = array();
    var $gtextdefault_attributes = array();

    function _truepath($p) {
        if (php_sapi_name() == 'apache') {
            $r    = apache_lookup_uri($p);
            $path = $r->filename;
        } else {
            $path = $_SERVER['DOCUMENT_ROOT'] . "/$p";
        }

        return $path;
    }

    function _createAlt($word) {
        if (isset($this->gtext_attributes['alt'])) {
            return $this->gtext_attributes['alt'];
        }

        return $word;
    }

    function _colorString($color) {
        if (substr($color, 0, 1) == '#') {
            $color = substr($color, 1);
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        return array($r, $g, $b);
    }

    function _baseline($font, $size) {
        $r = ImageTTFBBox(
          $size,
          0,
          $font,
          'Gg§_|ÖÄÜßQqPp'
        );

        return $r[1];
    }

    function _createImage($word, $baseline) {
        $font         = isset($this->gtext_attributes['font'])         ? $this->gtext_attributes['font']         : 'arial.ttf';
        $fh           = isset($this->gtext_attributes['fontsize'])     ? $this->gtext_attributes['fontsize']     : 12;
        $bgcolor      = isset($this->gtext_attributes['bgcolor'])      ? $this->gtext_attributes['bgcolor']      : '#ffffff';
        $fgcolor      = isset($this->gtext_attributes['fgcolor'])      ? $this->gtext_attributes['fgcolor']      : '#ffffff';

        $antialias    = isset($this->gtext_attributes['antialias'])    ? $this->gtext_attributes['antialias']    : 'yes';
        $transparency = isset($this->gtext_attributes['transparency']) ? $this->gtext_attributes['transparency'] : 'yes';
        $cacheable    = isset($this->gtext_attributes['cacheable'])    ? $this->gtext_attributes['cacheable']    : 'yes';

        $spacing      = isset($this->gtext_attributes['spacing'])      ? $this->gtext_attributes['spacing']      : 2;
        $border       = isset($this->gtext_attributes['border'])       ? $this->gtext_attributes['border']       : 0;
        $bordercolor  = isset($this->gtext_attributes['bordercolor'])  ? $this->gtext_attributes['bordercolor']  : '#ff0000';

        /* The cache name is derived from all attributes and cdata.
         * This is very conserative and may create to many cachefiles,
         * but better to err on the safe side.
         */
        $cachefile = md5(XML_Transformer_Util::attributesToString($this->gtext_attributes) . ':' . $word) . '.png';
        $cacheDir  = $_SERVER['DOCUMENT_ROOT']
                   . PEAR_XML_TRANSFORMER_IMAGE_cacheDir;
        $cacheName = "$cacheDir/$cachefile";
        $cacheURL  = PEAR_XML_TRANSFORMER_IMAGE_cacheDir. "/$cachefile";

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 01777);
        }

        /* Don't do the same work twice. */
        if (file_exists($cacheName) && $cacheable != 'no') {
            return $cacheURL;
        }

        $r = ImageTTFBBox(
          $fh,
          0,
          $font,
          $word
        );

        $w = max(1/10*$fh, abs($r[2] - $r[0]));
        $h = max(1, abs($r[7] - $r[1]));
        $x = $r[0];
        $y = $baseline;

        $www = $w  + 2*($spacing+$border);
        $hhh = $fh + 2*($spacing+$border);

        $im = ImageCreate($www, $hhh);

        list($r, $g, $b) = $this->_colorString($bgcolor);
        $bg = ImageColorAllocate($im, $r, $g, $b);

        if ($transparency != 'no') {
            ImageColorTransparent($im, $bg);
        }

        list($r, $g, $b) = $this->_colorString($fgcolor);
        $fg = ImageColorAllocate($im, $r, $g, $b);

        if ($antialias == 'no') {
            $fg = -$fg;
        }

        list($r, $g, $b) = $this->_colorString($bordercolor);
        $bo = ImageColorAllocate($im, $r, $g, $b);

        ImageFilledRectangle($im, 0, 0, $www, $hhh, $bg);

        if ($border > 0) {
            for ($i=$border; $i>=0; $i--) {
                $x1 = $y1 = $i;
                $x2 = $www-$i-1;
                $y2 = $hhh-$i-1;

                ImageRectangle($im, $x1, $y1, $x2, $y2, $bo);
            }
        }

        ImageTTFText(
          $im,
          $fh,
          0,
          -$x+$spacing+$border,
          $hhh-(2+$y+$spacing+$border),
          $fg,
          $font,
          $word
        );

        ImagePNG($im, $cacheName);
        ImageDestroy($im);

        return $cacheURL;
    }

    /* <img:img />
     *   src=...  -- src of the image, must be getimagesize() compatible
     *            -- all other attributes are copied
     */
    function start_img($attributes) {
        $this->img_attributes = $attributes;

        return '';
    }

    function end_img($cdata) {
        $src = $this->_truepath($this->img_attributes['src']);

        list($w, $h, $t, $whs) = getimagesize($src);
        $this->img_attributes['height'] = $h;
        $this->img_attributes['width']  = $w;

        return sprintf(
          '<img %s />',
          XML_Transformer_Util::attributesToString($this->img_attributes)
        );
    }

    /* <img:gtext  />
     *   alt=...        -- set alt attribute of generated img (default: automatic)
     *   split= ...     -- set to none, word, char
     *   antialias=...  -- yes or no (default: yes)
     *   transparency=..-- yes or no (default: yes)
     *   cacheable=...  -- yes or no (default: yes)
     *
     *   bgcolor=...    -- specifiy color to use as background
     *   fgcolor=...    -- specify color to use as textcolor
     *
     *   font=..        -- specify font to use
     *   fontsize=...   -- specify font size to use
     *
     *   spacing=...    -- specify x pixels of spacing around the text
     *   border=...     -- draw a border of x pixels around the text
     *   bordercolor=...-- color to use for border
     */
    function start_gtext($attributes) {
        foreach ($this->gtextdefault_attributes as $k => $v) {
            if (! isset($attributes[$k])) {
                $attributes[$k] = $v;
            }
        }

        if (!file_exists($attributes['font'])) {
            $attributes['font'] = PEAR_XML_TRANSFORMER_IMAGE_FONTPATH . '/' . $attributes['font'];
        }
  
        $this->gtext_attributes = $attributes;
  
        return '';
    }

    function end_gtext($cdata) {
        switch ($this->gtext_attributes['split']) {
            case 'word': {
                $text = preg_split('/\s+/', $cdata);

                foreach ($text as $index => $word) {
                    if ($index) {
                        $text[$index] = " $word";
                    }
                }
            }
            break;

            case 'char': {
                $text = preg_split('//', $cdata);

                foreach ($text as $index => $word) {
                    if ($word == ' ' || $word == '') {
                        $text[$index] = chr(160);
                    }
                }
            }
            break;

            default: {
                $text = array(0 => $cdata);
            }
        }

        $r = '';

        foreach ($text as $index => $word) {
            $baseline = $this->_baseline(
              $this->gtext_attributes['font'],
              $this->gtext_attributes['fontsize']
            );

            $src = $this->_createImage($word,$baseline);
            $alt = $this->_createAlt($word);

            $r .= sprintf(
              '<img:img src="%s" alt="%s" />',
              $src,
              addslashes($alt)
            );
        }

        return "<span>$r</span>";
    }

    /*
     * <img:gtextdefaults />
     *
     * as <img:gtext />, stores attributes as defaults for gtext.
     */
    function start_gtextdefault($attributes) {
        $this->gtextdefault_attributes = $attributes;

        return '';
    }

    function end_gtextdefault() {
        return '';
    }
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

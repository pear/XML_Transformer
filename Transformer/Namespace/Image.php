<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Image Namespace Handler                     |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                         Kristian K�hntopp <kris@koehntopp.de>.            |
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
require_once 'XML/Util.php';

define('PEAR_XML_TRANSFORMER_IMAGE_FONTPATH', '/usr/X11R6/lib/X11/fonts/truetype');
define('PEAR_XML_TRANSFORMER_IMAGE_cacheDir', '/cache/gtext');

/**
* Handler for the Image Namespace.
*
* Example:
*
*   <?php
*   require_once 'XML/Transformer_OutputBuffer.php';
*   require_once 'XML/Transformer/Namespace/Image.php';
*
*   $t = new XML_Transformer_OutputBuffer;
*   $t->overloadNamespace('img', new XML_Transformer_Namespace_Image);
*   $t->start();
*   ?>
*   <!-- Height and Width attributes are autogenerated -->
*   <img:img src="somepng.png" alt="A sample image" />
*
*   <!-- Set default for all subsequent <img:gtext /> -->
*   <img:gtextdefault bgcolor="888888" fgcolor="#000000"
*                     font="arial.ttf" fontsize="33"
*                     border="2" spacing="2"
*                     split="" cacheable="yes" />
*
*   <!-- Render Text as PNG image -->
*   <img:gtext>0123456789 �������</img:gtext><br />
*
* Output:
*
*   <img alt="A sample image" height="33" src="somepng.png" width="133" />
*
*   <span><img alt="0123456789 �������" height="41" width="338"
*              src="/cache/gtext/8b91aee0403c5cdccc1dd96bd4f49fbb.png" /></span>
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian K�hntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_Image extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    boolean
    * @access public
    */
    public $defaultNamespacePrefix = 'img';

    /**
    * @var    array
    * @access private
    */
    private $imgAttributes = array();

    /**
    * @var    array
    * @access private
    */
    private $gtextAttributes = array();

    /**
    * @var    array
    * @access private
    */
    private $gtextDefaultAttributes = array();

    // }}}
    // {{{ public function start_img($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_img($attributes) {
        $this->imgAttributes = $attributes;

        return '';
    }

    // }}}
    // {{{ public function end_img($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_img($cdata) {
        $src = $this->truePath($this->imgAttributes['src']);

        list($w, $h, $t, $whs) = getimagesize($src);

        $this->imgAttributes['height'] = $h;
        $this->imgAttributes['width']  = $w;

        return sprintf(
          '<img %s />',
          XML_Util::attributesToString($this->imgAttributes)
        );
    }

    // }}}
    // {{{ public function start_gtext($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_gtext($attributes) {
        foreach ($this->gtextDefaultAttributes as $k => $v) {
            if (! isset($attributes[$k])) {
                $attributes[$k] = $v;
            }
        }

        if (!file_exists($attributes['font'])) {
            $attributes['font'] = PEAR_XML_TRANSFORMER_IMAGE_FONTPATH . '/' . $attributes['font'];
        }
  
        $this->gtextAttributes = $attributes;
  
        return '';
    }

    // }}}
    // {{{ public function end_gtext($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_gtext($cdata) {
        if(!is_file($this->gtextAttributes['font'])) {
            return '<span>font \"' . $this->gtextAttributes['font'] . '" not found</span>';
        }

        switch ($this->gtextAttributes['split']) {
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
            $baseline = $this->baseline(
              $this->gtextAttributes['font'],
              $this->gtextAttributes['fontsize']
            );

            $src = $this->createImage($word,$baseline);
            $alt = $this->createAlt($word);

            $r .= sprintf(
              '<%simg src="%s" alt="%s" />',
              ($this->prefix[0] != '&MAIN') ? $this->prefix[0] . ':' : '',
              $src,
              addslashes($alt)
            );
        }

        return "<span>$r</span>";
    }

    // }}}
    // {{{ public function start_gtextdefault($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_gtextdefault($attributes) {
        $this->gtextDefaultAttributes = $attributes;

        return '';
    }

    // }}}
    // {{{ public function end_gtextdefault()

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_gtextdefault() {
        return '';
    }

    // }}}
    // {{{ private functionbaseline($font, $size)

    /**
    * @param  string
    * @param  integer
    * @return ImageTTFBBox
    * @access private
    */
    private functionbaseline($font, $size) {
        $r = ImageTTFBBox(
          $size,
          0,
          $font,
          'Gg�_|����QqPp'
        );

        return $r[1];
    }

    // }}}
    // {{{ private functioncolorString($color)

    /**
    * @param  integer
    * @return array
    * @access private
    */
    private functioncolorString($color) {
        if (substr($color, 0, 1) == '#') {
            $color = substr($color, 1);
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        return array($r, $g, $b);
    }

    // }}}
    // {{{ private functioncreateAlt($word)

    /**
    * @param  string
    * @return string
    * @access private
    */
    private functioncreateAlt($word) {
        if (isset($this->gtextAttributes['alt'])) {
            return $this->gtextAttributes['alt'];
        }

        return strip_tags($word);
    }

    // }}}
    // {{{ private functioncreateImage($word, $baseline)

    /**
    * @param  string
    * @param  integer
    * @return string
    * @access private
    */
    private functioncreateImage($word, $baseline) {
        $font         = isset($this->gtextAttributes['font'])         ? $this->gtextAttributes['font']         : 'arial.ttf';
        $fh           = isset($this->gtextAttributes['fontsize'])     ? $this->gtextAttributes['fontsize']     : 12;
        $bgcolor      = isset($this->gtextAttributes['bgcolor'])      ? $this->gtextAttributes['bgcolor']      : '#ffffff';
        $fgcolor      = isset($this->gtextAttributes['fgcolor'])      ? $this->gtextAttributes['fgcolor']      : '#ffffff';

        $antialias    = isset($this->gtextAttributes['antialias'])    ? $this->gtextAttributes['antialias']    : 'yes';
        $transparency = isset($this->gtextAttributes['transparency']) ? $this->gtextAttributes['transparency'] : 'yes';
        $cacheable    = isset($this->gtextAttributes['cacheable'])    ? $this->gtextAttributes['cacheable']    : 'yes';

        $spacing      = isset($this->gtextAttributes['spacing'])      ? $this->gtextAttributes['spacing']      : 2;
        $border       = isset($this->gtextAttributes['border'])       ? $this->gtextAttributes['border']       : 0;
        $bordercolor  = isset($this->gtextAttributes['bordercolor'])  ? $this->gtextAttributes['bordercolor']  : '#ff0000';

        /* The cache name is derived from all attributes and cdata.
         * This is very conserative and may create to many cachefiles,
         * but better to err on the safe side.
         */
        $cachefile = md5(XML_Util::attributesToString($this->gtextAttributes) . ':' . $word) . '.png';
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

        list($r, $g, $b) = $this->colorString($bgcolor);
        $bg = ImageColorAllocate($im, $r, $g, $b);

        if ($transparency != 'no') {
            ImageColorTransparent($im, $bg);
        }

        list($r, $g, $b) = $this->colorString($fgcolor);
        $fg = ImageColorAllocate($im, $r, $g, $b);

        if ($antialias == 'no') {
            $fg = -$fg;
        }

        list($r, $g, $b) = $this->colorString($bordercolor);
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

    // }}}
    // {{{ private functiontruePath($path)

    /**
    * @param  string
    * @return string
    * @access private
    */
    private functiontruePath($path) {
        if (php_sapi_name() == 'apache') {
            $uri = apache_lookup_uri($path);

            return $uri->filename;
        } else {
            return $_SERVER['DOCUMENT_ROOT'] . '/' . $path;
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

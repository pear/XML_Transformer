<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: DocBook Namespace Handler                   |
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
require_once 'XML/Util.php';

/**
* DocBook Namespace Handler.
*
* This namespace handler provides transformations to render a subset of
* the popular DocBook/XML markup (http://www.docbook.org/) into HTML.
*
* Transformations for the following DocBook tags are implemented:
*
*   * <artheader>
*
*   * <article>
*
*   * <author>
*
*   * <book>
*
*   * <chapter>
*
*   * <emphasis>
*
*   * <example>
*
*   * <figure>
*
*   * <filename>
*
*   * <firstname>
*
*   * <function>
*
*   * <graphic>
*
*   * <itemizedlist>
*
*   * <listitem>
*
*   * <orderedlist>
*
*   * <para>
*
*   * <programlisting>
*
*   * <section>
*
*   * <surname>
*
*   * <title>
*
*   * <ulink>
*
*   * <xref>
*
* Example
*
*   <?php
*   require_once 'XML/Transformer/Driver/OutputBuffer.php';
*   $t = new XML_Transformer_Driver_OutputBuffer(
*     array(
*       'autoload' => 'DocBook'
*     )
*   );
*   ?>
*   <article>
*     <artheader>
*       <title>
*         An Article
*       </title>
*       <author>
*         <firstname>
*           Sebastian
*         </firstname>
*         <surname>
*           Bergmann
*         </surname>
*       </author>
*     </artheader>
*
*     <section id="foo">
*       <title>
*         Section One
*       </title>
*     </section>
*
*     <section id="bar">
*       <title>
*         Section Two
*       </title>
*
*       <para>
*         <xref linkend="foo" />
*       </para>
*     </section>
*   </article>
*
* Output
*
*   <html>
*     <head>
*       <title>
*         Sebastian Bergmann: An Article
*       </title>
*     </head>
*     <body>
*       <h1 class="title">
*         Sebastian Bergmann: An Article
*       </h1>
*       <div class="section">
*         <a id="foo"></a>
*         <h2 class="title">
*           1. Section One
*         </h2>
*       </div>
*       <div class="section">
*         <a id="bar"></a>
*         <h2 class="title">
*           2. Section Two
*         </h2>
*         <p>
*           <a href="#foo">
*             1. Section One
*           </a>
*         </p>
*       </div>
*     </body>
*   </html>
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_DocBook extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    string
    * @access public
    */
    public $defaultNamespacePrefix = '&MAIN';

    /**
    * @var    boolean
    * @access public
    */
    public $secondPassRequired = true;

    /**
    * @var    string
    * @access private
    */
    private $author = '';

    /**
    * @var    array
    * @access private
    */
    private $context = array();

    /**
    * @var    string
    * @access private
    */
    private $currentExampleNumber = '';

    /**
    * @var    string
    * @access private
    */
    private $currentFigureNumber = '';

    /**
    * @var    string
    * @access private
    */
    private $currentSectionNumber = '';

    /**
    * @var    array
    * @access private
    */
    private $examples = array();

    /**
    * @var    array
    * @access private
    */
    private $figures = array();

    /**
    * @var    array
    * @access private
    */
    private $highlightColors = array(
      'bg'      => '#ffffff',
      'comment' => '#ba8370',
      'default' => '#113d73',
      'html'    => '#000000',
      'keyword' => '#005500',
      'string'  => '#550000'
    );

    /**
    * @var    array
    * @access private
    */
    private $ids = array();

    /**
    * @var    boolean
    * @access private
    */
    private $roles = array();

    /**
    * @var    array
    * @access private
    */
    private $secondPass = false;

    /**
    * @var    array
    * @access private
    */
    private $sections = array();

    /**
    * @var    string
    * @access private
    */
    private $title = '';

    /**
    * @var    array
    * @access private
    */
    private $xref = '';

    // }}}
    // {{{ public function __construct($parameters = array())

    /**
    * @param  array
    * @access public
    */
    public function __construct($parameters = array()) {
        if (isset($parameters['highlightColors'])) {
            $this->highlightColors = $parameters['highlightColors'];
        }

        foreach ($this->highlightColors as $highlight => $color) {
            ini_set('highlight.' . $highlight, $color);
        }
    }

    // }}}
    // {{{ public function start_artheader($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_artheader($attributes) {
        if (!$this->secondPass) {
            return sprintf(
              '<artheader%s>',
              XML_Util::attributesToString($attributes)
            );
        }
    }

    // }}}
    // {{{ public function end_artheader($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_artheader($cdata) {
        if (!$this->secondPass) {
            $cdata = $cdata . '</artheader>';

            return array(
              $cdata,
              false
            );
        }
    }

    // }}}
    // {{{ public function start_article($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_article($attributes) {
        return $this->startDocument('article', $attributes);
    }

    // }}}
    // {{{ public function end_article($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_article($cdata) {
        return $this->endDocument('article', $cdata);
    }

    // }}}
    // {{{ public function start_author($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_author($attributes) {}

    // }}}
    // {{{ public function end_author($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_author($cdata) {
        $this->author = trim(str_replace("\n", '', $cdata));
    }

    // }}}
    // {{{ public function start_book($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_book($attributes) {
        return $this->startDocument('book', $attributes);
    }

    // }}}
    // {{{ public function end_book($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_book($cdata) {
        return $this->endDocument('book', $cdata);
    }

    // }}}
    // {{{ public function start_chapter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_chapter($attributes) {
        $id = $this->startSection(
          'chapter',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="chapter">' . $id;
    }

    // }}}
    // {{{ public function end_chapter($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_chapter($cdata) {
        $this->endSection('chapter');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ public function start_emphasis($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_emphasis($attributes) {
        $emphasisRole = isset($attributes['role']) ? $attributes['role'] : '';

        switch($emphasisRole) {
            case 'bold':
            case 'strong': {
                $this->roles['emphasis'] = 'b';
            }
            break;

            default: {
                $this->roles['emphasis'] = 'i';
            }
        }

        return '<' . $this->roles['emphasis'] . '>';
    }

    // }}}
    // {{{ public function end_emphasis($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_emphasis($cdata) {
        $cdata = sprintf(
          '%s</%s>',
          $cdata,
          $this->roles['emphasis']
        );

        $this->roles['emphasis'] = '';

        return $cdata;
    }

    // }}}
    // {{{ public function start_example($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_example($attributes) {
        $id = $this->startSection(
          'example',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="example">' . $id;
    }

    // }}}
    // {{{ public function end_example($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_example($cdata) {
        $this->endSection('example');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ public function start_figure($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_figure($attributes) {
        $id = $this->startSection(
          'figure',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="figure">' . $id;
    }

    // }}}
    // {{{ public function end_figure($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_figure($cdata) {
        $this->endSection('figure');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ public function start_filename($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_filename($attributes) {
        return '<tt>';
    }

    // }}}
    // {{{ public function end_filename($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_filename($cdata) {
        return trim($cdata) . '</tt>';
    }

    // }}}
    // {{{ public function start_firstname($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_firstname($attributes) {}

    // }}}
    // {{{ public function end_firstname($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_firstname($cdata) {
        return trim($cdata);
    }

    // }}}
    // {{{ public function start_function($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_function($attributes) {
        return '<code><b>';
    }

    // }}}
    // {{{ public function end_function($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_function($cdata) {
        return array(
          trim($cdata) . '</b></code>',
          false
        );
    }

    // }}}
    // {{{ public function start_graphic($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_graphic($attributes) {
        return sprintf(
          '<img alt="%s" border="0" src="%s"%s%s/>',

          isset($attributes['srccredit']) ? $attributes['srccredit']                  : '',
          isset($attributes['fileref'])   ? $attributes['fileref']                    : '',
          isset($attributes['width'])     ? ' width="'  . $attributes['width']  . '"' : '',
          isset($attributes['height'])    ? ' height="' . $attributes['height'] . '"' : ''
        );
    }

    // }}}
    // {{{ public function end_graphic($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_graphic($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ public function start_itemizedlist($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_itemizedlist($attributes) {
        return '<ul>';
    }

    // }}}
    // {{{ public function end_itemizedlist($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_itemizedlist($cdata) {
        return $cdata . '</ul>';
    }

    // }}}
    // {{{ public function start_listitem($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_listitem($attributes) {
        return '<li>';
    }

    // }}}
    // {{{ public function end_listitem($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_listitem($cdata) {
        return $cdata . '</li>';
    }

    // }}}
    // {{{ public function start_orderedlist($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_orderedlist($attributes) {
        return '<ol>';
    }

    // }}}
    // {{{ public function end_orderedlist($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_orderedlist($cdata) {
        return $cdata . '</ol>';
    }

    // }}}
    // {{{ public function start_para($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_para($attributes) {
        return '<p>';
    }

    // }}}
    // {{{ public function end_para($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_para($cdata) {
        return $cdata . '</p>';
    }

    // }}}
    // {{{ public function start_programlisting($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_programlisting($attributes) {
        $this->roles['programlisting'] = isset($attributes['role']) ? $attributes['role'] : '';

        switch ($this->roles['programlisting']) {
            case 'php': {
                return '';
            }
            break;

            default: {
                return '<code>';
            }
        }
    }

    // }}}
    // {{{ public function end_programlisting($cdata)

    /**
    * @param  string
    * @return mixed
    * @access public
    */
    public function end_programlisting($cdata) {
        switch ($this->roles['programlisting']) {
            case 'php': {
                $cdata = array(
                  str_replace(
                    '&nbsp;',
                    ' ',
                    highlight_string($cdata, 1)
                  ),
                  false
                );
            }
            break;

            default: {
                $cdata = array(
                  $cdata . '</code>',
                  false
                );
            }
        }

        $this->roles['programlisting'] = '';

        return $cdata;
    }

    // }}}
    // {{{ public function start_section($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_section($attributes) {
        $id = $this->startSection(
          'section',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="section">' . $id;
    }

    // }}}
    // {{{ public function end_section($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_section($cdata) {
        $this->endSection('section');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ public function start_surname($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_surname($attributes) {}

    // }}}
    // {{{ public function end_surname($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_surname($cdata) {
        return trim($cdata);
    }

    // }}}
    // {{{ public function start_title($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_title($attributes) {
        switch ($this->context[sizeof($this->context)-1]) {
            case 'chapter':
            case 'section': {
                return '<h2 class="title">' . $this->currentSectionNumber . '. ';
            }
            break;

            case 'example': {
                return '<h3 class="title">Example ' . $this->currentExampleNumber;
            }
            break;

            case 'figure': {
                return '<h3 class="title">Figure ' . $this->currentFigureNumber;
            }
            break;
        }
    }

    // }}}
    // {{{ public function end_title($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_title($cdata) {
        $cdata = trim($cdata);

        if (!empty($this->ids[sizeof($this->ids)-1])) {
            $this->xref[$this->ids[sizeof($this->ids)-1]] = strip_tags($cdata);
        }

        switch ($this->context[sizeof($this->context)-1]) {
            case 'article':
            case 'book': {
                $this->title = $cdata;
            }
            break;

            case 'chapter':
            case 'section': {
                return $cdata . '</h2>';
            }
            break;

            case 'example':
            case 'figure': {
                return $cdata . '</h3>';
            }
            break;

            default: {
                return $cdata;
            }
        }
    }

    // }}}
    // {{{ public function start_ulink($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_ulink($attributes) {
        return '<a href="' . $attributes['url'] . '">';
    }

    // }}}
    // {{{ public function end_ulink($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_ulink($cdata) {
        return $cdata . '</a>';
    }

    // }}}
    // {{{ public function start_xref($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    public function start_xref($attributes) {
        if ($this->secondPass) {
            return sprintf(
              '<a href="#%s">%s</a>',

              isset($attributes['linkend'])               ? $attributes['linkend']               : '',
              isset($this->xref[$attributes['linkend']]) ? $this->xref[$attributes['linkend']] : ''
            );
        } else {
            return sprintf(
              '<xref%s>',
              XML_Util::attributesToString($attributes)
            );
        }
    }

    // }}}
    // {{{ public function end_xref($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    public function end_xref($cdata) {
        if (!$this->secondPass) {
            $cdata = $cdata . '</xref>';
        }

        return array(
          $cdata,
          false
        );
    }

    // }}}
    // {{{ private function startDocument($type, $attributes)

    /**
    * @param  string
    * @param  array
    * @return string
    * @access private
    */
    private function startDocument($type, $attributes) {
        if (!$this->secondPass) {
            $id = $this->startSection(
              $type,
              isset($attributes['id']) ? $attributes['id'] : ''
            );

            return sprintf(
              '<%s>%s',

              $type,
              $id
            );
        } else {
            return sprintf(
              '<html><head><title>%s: %s</title><body><h1 class="title">%s: %s</h1>',

              $this->author,
              $this->title,
              $this->author,
              $this->title
            );
        }
    }

    // }}}
    // {{{ private function endDocument($type, $cdata)

    /**
    * @param  string
    * @param  string
    * @return string
    * @access private
    */
    private function endDocument($type, $cdata) {
        if (!$this->secondPass) {
            $this->endSection($type);

            $this->secondPass = true;

            $cdata = sprintf(
              '%s</%s>',

              $cdata,
              $type
            );
        } else {
            $cdata = $cdata . '</body></html>';
        }

        return array(
          $cdata,
          false
        );
    }

    // }}}
    // {{{ private function startSection($type, $id)

    /**
    * @param  string
    * @return string
    * @access private
    */
    private function startSection($type, $id) {
        array_push($this->context, $type);
        array_push($this->ids,     $id);

        switch ($type) {
            case 'article':
            case 'book':
            case 'chapter':
            case 'section': {
                $this->currentSectionNumber = '';

                if (!isset($this->sections[$type]['open'])) {
                    $this->sections[$type]['open'] = 1;
                } else {
                    $this->sections[$type]['open']++;
                }

                if (!isset($this->sections[$type]['id'][$this->sections[$type]['open']])) {
                    $this->sections[$type]['id'][$this->sections[$type]['open']] = 1;
                } else {
                    $this->sections[$type]['id'][$this->sections[$type]['open']]++;
                }

                for ($i = 1; $i <= $this->sections[$type]['open']; $i++) {
                    if (!empty($this->currentSectionNumber)) {
                        $this->currentSectionNumber .= '.';
                    }

                    $this->currentSectionNumber .= $this->sections[$type]['id'][$i];
                }
            }
            break;

            case 'example': {
                if (!isset($this->examples[$this->currentSectionNumber])) {
                    $this->examples[$this->currentSectionNumber] = 1;
                } else {
                    $this->examples[$this->currentSectionNumber]++;
                }

                $this->currentExampleNumber =
                $this->currentSectionNumber . '.' . $this->examples[$this->currentSectionNumber];
            }
            break;

            case 'figure': {
                if (!isset($this->figures[$this->currentFigureNumber])) {
                    $this->figures[$this->currentSectionNumber] = 1;
                } else {
                    $this->figures[$this->currentSectionNumber]++;
                }

                $this->currentFigureNumber =
                $this->currentSectionNumber . '.' . $this->figures[$this->currentSectionNumber];
            }
            break;
        }

        if (!empty($id)) {
            $id = '<a id="' . $id . '" />';
        }

        return $id;
    }

    // }}}
    // {{{ private function endSection($type)

    /**
    * @param  string
    * @access private
    */
    private function endSection($type) {
        array_pop($this->context);

        switch ($type) {
            case 'article':
            case 'book':
            case 'chapter':
            case 'section': {
                $this->sections[$type]['open']--;
            }
            break;
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

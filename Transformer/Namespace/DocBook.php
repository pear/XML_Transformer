<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: DocBook Namespace Handler              |
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
*   * <chapter>
*
*   * <emphasis>
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
* Example
*
*   <?php
*   require_once 'XML/Transformer/Namespace/DocBook.php';
*   require_once 'XML/Transformer/OutputBuffer.php';
*
*   $t = new XML_Transformer_OutputBuffer(
*     array(
*       'autoload' => 'DocBook'
*     )
*   );
*
*   echo $t->transform(implode('', file('article.xml')));
*   ?>
*   <article>
*     <section>
*       <title>
*         Section One
*       </title>
*       <para>
*         <emphasis role="bold">
*           Bold Text.
*         </emphasis>
*       </para>
*       <para>
*         <ulink url="http://pear.php.net/">PEAR</ulink>
*       </para>
*     </section>
*   </article>
*
* Output
*
*   <html>
*     <body>
*       <h2 class="title" style="clear: both">
*         1. Section One
*       </h2>
*       <p>
*         <b>
*           Bold Text.
*         </b>
*       </p>
*       <p>
*         <a href="http://pear.php.net/">
*           PEAR
*         </a>
*       </p>
*     </body>
*   </html>
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
*          Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Namespace_DocBook extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    string
    * @access public
    */
    var $defaultNamespacePrefix = '&MAIN';

    /**
    * @var    array
    * @access private
    */
    var $_author = '';

    /**
    * @var    array
    * @access private
    */
    var $_context = array();

    /**
    * @var    array
    * @access private
    */
    var $_currentSectionNumber = '';

    /**
    * @var    array
    * @access private
    */
    var $_highlightColors = array(
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
    var $_roles = array();

    /**
    * @var    array
    * @access private
    */
    var $_sections = array();

    /**
    * @var    array
    * @access private
    */
    var $_title = '';

    // }}}
    // {{{ function XML_Transformer_Namespace_DocBook($parameters = array())

    /**
    * @param  array
    * @access public
    */
    function XML_Transformer_Namespace_DocBook($parameters = array()) {
        if (isset($parameters['highlightColors'])) {
            $this->_highlightColors = $parameters['highlightColors'];
        }

        foreach ($this->_highlightColors as $highlight => $color) {
            ini_set('highlight.' . $highlight, $color);
        }
    }

    // }}}
    // {{{ function start_artheader($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_artheader($attributes) {}

    // }}}
    // {{{ function end_artheader($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_artheader($cdata) {}

    // }}}
    // {{{ function start_article($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_article($attributes) {
        $this->_startSection('article');

        return '<html><body>';
    }

    // }}}
    // {{{ function end_article($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_article($cdata) {
        $this->_endSection('article');

        return $cdata . '</body></html>';
    }

    // }}}
    // {{{ function start_author($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_author($attributes) {}

    // }}}
    // {{{ function end_author($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_author($cdata) {
        $this->_author = trim(str_replace("\n", '', $cdata));
    }

    // }}}
    // {{{ function start_chapter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_chapter($attributes) {
        $this->_startSection('chapter');

        return '<div class="chapter">';
    }

    // }}}
    // {{{ function end_chapter($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_chapter($cdata) {
        $this->_endSection('chapter');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ function start_emphasis($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_emphasis($attributes) {
        $emphasisRole = isset($attributes['role']) ? $attributes['role'] : '';

        switch($emphasisRole) {
            case 'bold':
            case 'strong': {
                $this->_roles['emphasis'] = 'b';
            }
            break;

            default: {
                $this->_roles['emphasis'] = 'i';
            }
        }

        return '<' . $this->_roles['emphasis'] . '>';
    }

    // }}}
    // {{{ function end_emphasis($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_emphasis($cdata) {
        $cdata = sprintf(
          '%s</%s>',
          $cdata,
          $this->_roles['emphasis']
        );

        $this->_roles['emphasis'] = '';

        return $cdata;
    }

    // }}}
    // {{{ function start_filename($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_filename($attributes) {
        return '<tt>';
    }

    // }}}
    // {{{ function end_filename($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_filename($cdata) {
        return trim($cdata) . '</tt>';
    }

    // }}}
    // {{{ function start_firstname($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_firstname($attributes) {}

    // }}}
    // {{{ function end_firstname($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_firstname($cdata) {
        return trim($cdata);
    }

    // }}}
    // {{{ function start_function($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_function($attributes) {
        return '<code><b>';
    }

    // }}}
    // {{{ function end_function($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_function($cdata) {
        return array(
          trim($cdata) . '</b></code>',
          false
        );
    }

    // }}}
    // {{{ function start_graphic($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_graphic($attributes) {
        return sprintf(
          '<img alt="%s" border="0" src="%s" />',

          isset($attributes['srccredit']) ? $attributes['srccredit'] : '',
          isset($attributes['fileref'])   ? $attributes['fileref']   : ''
        );
    }

    // }}}
    // {{{ function end_graphic($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_graphic($cdata) {
        return '';
    }

    // }}}
    // {{{ function start_itemizedlist($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_itemizedlist($attributes) {
        return '<ul>';
    }

    // }}}
    // {{{ function end_itemizedlist($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_itemizedlist($cdata) {
        return $cdata . '</ul>';
    }

    // }}}
    // {{{ function start_listitem($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_listitem($attributes) {
        return '<li>';
    }

    // }}}
    // {{{ function end_listitem($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_listitem($cdata) {
        return $cdata . '</li>';
    }

    // }}}
    // {{{ function start_orderedlist($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_orderedlist($attributes) {
        return '<ol>';
    }

    // }}}
    // {{{ function end_orderedlist($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_orderedlist($cdata) {
        return $cdata . '</ol>';
    }

    // }}}
    // {{{ function start_para($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_para($attributes) {
        return '<p>';
    }

    // }}}
    // {{{ function end_para($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_para($cdata) {
        return $cdata . '</p>';
    }

    // }}}
    // {{{ function start_programlisting($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_programlisting($attributes) {
        $this->_roles['programlisting'] = isset($attributes['role']) ? $attributes['role'] : '';

        switch ($this->_roles['programlisting']) {
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
    // {{{ function end_programlisting($cdata)

    /**
    * @param  string
    * @return mixed
    * @access public
    */
    function end_programlisting($cdata) {
        switch ($this->_roles['programlisting']) {
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

        $this->_roles['programlisting'] = '';

        return $cdata;
    }

    // }}}
    // {{{ function start_section($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_section($attributes) {
        $this->_startSection('section');

        return '<div class="section">';
    }

    // }}}
    // {{{ function end_section($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_section($cdata) {
        $this->_endSection('section');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ function start_surname($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_surname($attributes) {}

    // }}}
    // {{{ function end_surname($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_surname($cdata) {
        return trim($cdata);
    }

    // }}}
    // {{{ function start_title($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_title($attributes) {
        switch ($this->_context[sizeof($this->_context)-1]) {
            case 'chapter':
            case 'section': {
                return '<h2>' . $this->_currentSectionNumber . '. ';
            }
            break;
        }
    }

    // }}}
    // {{{ function end_title($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_title($cdata) {
        switch ($this->_context[sizeof($this->_context)-1]) {
            case 'article':
            case 'book': {
                $this->_title = trim($cdata);
            }
            break;

            case 'chapter':
            case 'section': {
                return trim($cdata) . '</h2>';
            }
            break;

            default: {
                return trim($cdata);
            }
        }
    }

    // }}}
    // {{{ function start_ulink($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_ulink($attributes) {
        return '<a href="' . $attributes['url'] . '">';
    }

    // }}}
    // {{{ function end_ulink($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_ulink($cdata) {
        return $cdata . '</a>';
    }

    // }}}
    // {{{ function _startSection($type)

    /**
    * @param  string
    * @access public
    */
    function _startSection($type) {
        $this->_currentSectionNumber = '';

        array_push($this->_context, $type);

        if (!isset($this->_sections[$type]['open'])) {
            $this->_sections[$type]['open']  = 1;
        } else {
            $this->_sections[$type]['open']++;
        }

        if (!isset($this->_sections[$type]['id'][$this->_sections[$type]['open']])) {
            $this->_sections[$type]['id'][$this->_sections[$type]['open']] = 1;
        } else {
            $this->_sections[$type]['id'][$this->_sections[$type]['open']]++;
        }

        for ($i = 1; $i <= $this->_sections[$type]['open']; $i++) {
            if (!empty($this->_currentSectionNumber)) {
                $this->_currentSectionNumber .= '.';
            }

            $this->_currentSectionNumber .= $this->_sections[$type]['id'][$i];
        }
    }

    // }}}
    // {{{ function _endSection($type)

    /**
    * @param  string
    * @access private
    */
    function _endSection($type) {
        array_pop($this->_context);
        $this->_sections[$type]['open']--;
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

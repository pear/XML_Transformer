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

ini_set('highlight.bg',      '#ffffff');
ini_set('highlight.comment', '#ba8370');
ini_set('highlight.default', '#113d73');
ini_set('highlight.html',    '#000000');
ini_set('highlight.keyword', '#005500');
ini_set('highlight.string',  '#550000');

/**
* DocBook Namespace Handler.
*
* This namespace handler provides transformations to render a subset of
* the popular DocBook/XML markup (http://www.docbook.org/) into HTML.
*
* Transformations for the following DocBook tags are implemented:
*
*   * <article>
*
*   * <chapter>
*
*   * <section>
*
*   * <title>
*
*   * <emphasis>
*
*   * <itemizedlist>
*
*   * <orderedlist>
*
*   * <listitem>
*
*   * <para>
*
*   * <programlisting>
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
    * @var    string
    * @access private
    */
    var $_emphasizeRole = '';

    /**
    * @var    string
    * @access private
    */
    var $_programlistingRole = '';

    /**
    * @var    array
    * @access private
    */
    var $_sections = array();

    // }}}
    // {{{ function start_article($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_article($attributes) {
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
        return $cdata . '</body></html>';
    }

    // }}}
    // {{{ function start_chapter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_chapter($attributes) {
        return '<h2 class="title">' .
               $this->_startSection('chapter') . '. ';
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

        return $cdata . '</h2>';
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
                $this->_emphasisRole = 'b';
            }
            break;

            default: {
                $this->_emphasisRole = 'i';
            }
        }

        return '<' . $this->_emphasisRole . '>';
    }

    // }}}
    // {{{ function end_emphasis($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_emphasis($cdata) {
        $emphasisRole        = $this->_emphasisRole;
        $this->_emphasisRole = '';

        return $cdata . '</' . $emphasisRole . '>';
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
        $this->_programlistingRole = isset($attributes['role']) ? $attributes['role'] : '';

        return '';
    }

    // }}}
    // {{{ function end_programlisting($cdata)

    /**
    * @param  string
    * @return mixed
    * @access public
    */
    function end_programlisting($cdata) {
        switch ($this->_programlistingRole) {
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
        }

        $this->_programlistingRole = '';

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
        return '<h2 class="title" style="clear: both">' .
               $this->_startSection('section') . '. ';
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

        return $cdata . '</h2>';
    }

    // }}}
    // {{{ function start_title($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_title($attributes) {
        return '';
    }

    // }}}
    // {{{ function end_title($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_title($cdata) {
        return $cdata;
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
    * @return string
    * @access public
    */
    function _startSection($type) {
        $result = '';

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
            if (!empty($result)) {
                $result .= '.';
            }

            $result .= $this->_sections[$type]['id'][$i];
        }

        return $result;
    }

    // }}}
    // {{{ function _endSection($type)

    /**
    * @param  string
    * @access private
    */
    function _endSection($type) {
        $this->_sections[$type]['open']--;
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

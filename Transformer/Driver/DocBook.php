<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                           |
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

require_once 'XML/Transformer.php';
require_once 'XML/Transformer/Namespace/DocBook.php';

/**
* Example
*
*   <?php
*   require_once 'XML/Transformer/Driver/DocBook.php';
*   $t = new XML_Transformer_Driver_DocBook();
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
*     <body>
*       <div class="section">
*         <a id="foo"></a>
*         <h2>
*           1. Section One
*         </h2>
*       </div>
*       <div class="section">
*         <a id="bar"></a>
*         <h2>
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
class XML_Transformer_Driver_DocBook {
    // {{{ function XML_Transformer_Driver_DocBook()

    function XML_Transformer_Driver_DocBook() {
        ob_start(
          array(
            $this, 'transform'
          )
        );
    }

    // }}}
    // {{{ function transform($xml)

    /**
    * Transforms a given DocBook XML document
    * to HTML using the DocBook Namespace Handler.
    *
    * @access public
    */
    function transform($xml) {
        $t = new XML_Transformer(
          array(
            'autoload' => 'DocBook'
          )
        );

        return $t->transform($t->transform($xml));
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                                |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2003 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
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

/**
* Utility Methods.
*
* @author  Sebastian Bergmann <sb@sebastian-bergmann.de>
* @author  Kristian Köhntopp <kris@koehntopp.de>
* @version $Revision$
* @access  public
*/
class XML_Transformer_Util {
    // {{{ function logMessage($logMessage, $target = 'error_log')

    /**
    * Sends an error message to a given target.
    *
    * @param  string
    * @param  string
    * @access public
    * @static
    */
    function logMessage($logMessage, $target = 'error_log') {
        switch ($target) {
            case 'echo':
            case 'print': {
                print $logMessage;
            }
            break;

            default: {
                error_log($logMessage);
            }
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>

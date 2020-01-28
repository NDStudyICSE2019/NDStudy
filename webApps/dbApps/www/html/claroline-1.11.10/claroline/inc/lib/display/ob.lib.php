<?php // $Id: ob.lib.php 13034 2011-04-01 15:07:53Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Output buffering functions to provide output
 * buffering with error and exception handling
 *
 * @version     1.9 $Revision: 13034 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     display
 */

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

// load exception_error_handler
FromKernel::uses ( 'core/exception.lib' );

/**
 * Exception handler to be used inside an output buffer
 */
function claro_ob_exception_handler( $e )
{
    // get buffer contents
    $buffer = ob_get_contents();
    // close the output buffer
    ob_end_clean();
    // display the buffer contents
    echo $buffer;
    // display the exception
    if ( claro_debug_mode() )
    {
        echo '<pre>' . $e->__toString() . '</pre>';
    }
    else
    {
        echo '<p>' . $e->getMessage() . '</p>';
    }
}

/**
 * Start output buffering
 */
function claro_ob_start()
{
    // set error handlers for output buffering :
    set_error_handler('exception_error_handler', error_reporting() & ~E_STRICT);
    set_exception_handler('claro_ob_exception_handler');
    // start output buffering
    ob_start();
}

/**
 * Stop output buffering
 */
function claro_ob_end_clean()
{
    // end output buffering
    ob_end_clean();
    // restore original error handlers
    restore_exception_handler();
    restore_error_handler();
}

/**
 * Return buffer contents
 */
function claro_ob_get_contents()
{
    return ob_get_contents();
}
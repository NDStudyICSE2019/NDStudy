<?php // $Id: console.lib.php 14308 2012-10-31 14:08:16Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Console and debug bar. Display a log message to the console and send it to
 * the Claroline log table.
 *
 * @version     Claroline 1.11 Revision: 12923 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 */

require_once dirname(__FILE__) . '/debug.lib.php';

class Console
{
    const
        MESSAGE = 'message',
        DEBUG = 'debug',
        WARNING = 'warning',
        INFO = 'info',
        SUCCESS = 'success',
        ERROR = 'error';
    
    const
        REPORT_LEVEL_ERROR = 1,
        REPORT_LEVEL_WARNING = 2,
        REPORT_LEVEL_INFO = 3,
        REPORT_LEVEL_SUCCESS = 4,
        REPORT_LEVEL_ALL = 5;
    
    public static function message( $message )
    {
        pushClaroMessage( $message, self::MESSAGE );
        Claroline::log( self::MESSAGE, $message );
    }

    public static function debug( $message )
    {
        if ( claro_debug_mode() )
        {
            pushClaroMessage( $message, self::DEBUG );
            Claroline::log( self::DEBUG, $message );
        }
    }
    
    public static function warning( $message )
    {
        if ( claro_debug_mode() ) pushClaroMessage( $message, self::WARNING );    
        if ( get_conf( 'log_report_level', self::REPORT_LEVEL_ALL ) >= self::REPORT_LEVEL_WARNING ) Claroline::log( self::WARNING, $message );
    }

    public static function info( $message )
    {
        if ( claro_debug_mode() ) pushClaroMessage( $message, self::INFO );
        if ( get_conf( 'log_report_level', self::REPORT_LEVEL_ALL ) >= self::REPORT_LEVEL_INFO ) Claroline::log( self::INFO, $message );
    }

    public static function success( $message )
    {
        if ( claro_debug_mode() ) pushClaroMessage( $message, self::SUCCESS );
        if ( get_conf( 'log_report_level', self::REPORT_LEVEL_ALL ) >= self::REPORT_LEVEL_SUCCESS ) Claroline::log( self::SUCCESS, $message );
    }

    public static function error( $message )
    {
        // claro_failure::set_failure( $message );
        if ( claro_debug_mode() ) pushClaroMessage( $message, self::ERROR );
        if ( get_conf( 'log_report_level', self::REPORT_LEVEL_ALL ) >= self::REPORT_LEVEL_ERROR ) Claroline::log( self::ERROR, $message );
    }
    
    public static function log( $message, $type )
    {
        if ( claro_debug_mode() ) pushClaroMessage( $message, $type );
        Claroline::log( $type, $message );
    }
}

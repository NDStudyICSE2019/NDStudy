<?php // $Id: time.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Time library
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     utils
 */


class Claro_Utils_Time
{
    public static function isIso8601( $dateStr )
    {
        return preg_match( '/\d{4}-\d{2}-\d{2}T\d{2}\:\d{2}\:\d{2}\+\d{2}\:\d{2}/i', $dateStr );
    }
    
    public static function iso8601ToDatetime( $iso8601Str )
    {
        if ( ! self::isIso8601( $iso8601Str ) )
        {
            return false;
        }
        
        return preg_replace( '/(\d{4}-\d{2}-\d{2})T(\d{2}\:\d{2}\:\d{2})\+\d{2}\:\d{2}/i', "$1 $2", $iso8601Str ) ;
    }
    
    public static function timeToIso8601( $time = null )
    {
        if ( is_null( $time ) ) $time = time();

        return (date('c') == 'c') ? date('Y-m-d\TH:i:sO',$time) : date('c', $time );
    }

    public static function dateToIso8601( $date = null )
    {
        $time = is_null( $date )
            ? time()
            : strtotime( $date )
            ;
        
        return self::timeToIso8601( $time );
    }

    public static function timeToDatetime( $time = null )
    {
        if ( $time )
        {
            return date( "Y-m-d H:i:s", $time );
        }
        else
        {
            return date( "Y-m-d H:i:s" );
        }
    }

    public static function dateToDatetime( $date = null)
    {
        $time = is_null( $date )
            ? time()
            : strtotime( $date )
            ;

        return date('Y-m-d H:i:s',$time);
    }
}

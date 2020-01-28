<?php // $Id: ical.php 13707 2011-10-19 09:43:32Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Build iCal file for user in given course
 *
 * @version     $Revision: 13707 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2.0
 * @package     KERNEL
 */

$_course = array();
$siteName = '';
$is_courseAllowed = false;

require dirname(__FILE__) . '/../inc/claro_init_global.inc.php';

include_once claro_get_conf_repository() . 'ical.conf.php';
include_once get_path('includePath') . '/lib/ical.write.lib.php';

$formatList = array('ics'=>'iCalendar','xcs'=>'xCalendar (xml)','rdf'=>'rdf');

if ( ! get_conf('enableICalInCourse') )
{
    // Codes Status HTTP 404 for rss feeder
    header('HTTP/1.0 404 Not Found');
    exit;
}

$calType = ( array_key_exists( 'calFormat', $_REQUEST )
    && array_key_exists( $_REQUEST['calFormat'], $formatList ) )
    ? $_REQUEST['calFormat']
    : get_conf('calType','ics')
    ;

// need to be in a course
if( ! claro_is_in_a_course() )
{
    die( '<form >cidReq = <input name="cidReq" type="text"  /><input type="submit" /></form>');
}

if ( !$_course['visibility'] && !claro_is_course_allowed() )
{
    if (!isset($_SERVER['PHP_AUTH_USER']))
    {
        header('WWW-Authenticate: Basic realm="'
            . get_lang('iCal feed for %course'
                , array('%course' => $_course['name'] ) ) . '"');
        header('HTTP/1.0 401 Unauthorized');
        
        echo '<h2>'
            . get_lang('You need to be authenticated with your %sitename account'
                , array('%sitename'=>$siteName) ) . '</h2>'
            . '<a href="index.php?cidReq=' . claro_get_current_course_id()
            . '">' . get_lang('Retry') . '</a>'
            ;
            
        exit;
    }
    else
    {
        if ( get_magic_quotes_gpc() ) // claro_unquote_gpc don't wash
        {
            $_REQUEST['login']    = stripslashes($_SERVER['PHP_AUTH_USER']);
            $_REQUEST['password'] = stripslashes($_SERVER['PHP_AUTH_PW']);
        }
        else
        {
            $_REQUEST['login']    = $_SERVER['PHP_AUTH_USER'];
            $_REQUEST['password'] = $_SERVER['PHP_AUTH_PW'] ;
        }
        require '../inc/claro_init_local.inc.php';
        if (!$_course['visibility'] && !claro_is_course_allowed())
        {
            header('WWW-Authenticate: Basic realm="'
                . get_lang('iCal feed for %course'
                    , array('%course' => $_course['name']) ) .'"');
            header('HTTP/1.0 401 Unauthorized');
            echo '<h2>'
                . get_lang('You need to be authenticated with your %sitename account'
                    , array('%sitename'=>$siteName) ) . '</h2>'
                . '<a href="index.php?cidReq='
                . claro_get_current_course_id() . '">'
                . get_lang('Retry') . '</a>'
                ;
                
            exit;
        }
    }
}

// OK TO SEND FEED

claro_send_file ( buildICal( array( CLARO_CONTEXT_COURSE => claro_get_current_course_id() ), $calType) );
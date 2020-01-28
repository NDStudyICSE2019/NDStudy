<?php // $Id: rss.php 13348 2011-07-18 13:58:28Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2.0
 * @package     KERNEL
 */

$_course = array();

$siteName ='';

require dirname(__FILE__) . '/../inc/claro_init_global.inc.php';

include claro_get_conf_repository() . 'rss.conf.php';

// RSS enabled
if ( ! get_conf('enableRssInCourse') )
{
    // Codes Status HTTP 404 for rss feeder
    header('HTTP/1.0 404 Not Found');
    exit;
}

// need to be in a course
if( ! claro_is_in_a_course() )
{
    echo '<form >cidReq = <input name="cidReq" type="text" /><input type="submit" /></form>';
    exit;
}
else
{
    if ( !$_course['visibility'] && !claro_is_course_allowed() )
    {
        if (!isset($_SERVER['PHP_AUTH_USER']))
        {
            header('WWW-Authenticate: Basic realm="'. get_lang('Rss feed for %course', array('%course' => $_course['name']) ) . '"');
            header('HTTP/1.0 401 Unauthorized');
            echo '<h2>' . get_lang('You need to be authenticated with your %sitename account', array('%sitename'=>$siteName) ) . '</h2>'
            .    '<a href="index.php?cidReq=' . claro_get_current_course_id() . '">' . get_lang('Retry') . '</a>'
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
                header('WWW-Authenticate: Basic realm="'. get_lang('Rss feed for %course', array('%course' => $_course['name']) ) .'"');
                header('HTTP/1.0 401 Unauthorized');
                echo '<h2>' . get_lang('You need to be authenticated with your %sitename account', array('%sitename'=>$siteName) ) . '</h2>'
                .    '<a href="index.php?cidReq=' . claro_get_current_course_id() . '">' . get_lang('Retry') . '</a>'
                ;
                exit;
            }
        }
    }

    // end session to avoid lock
    session_write_close();

    // OK TO SEND FEED
    require_once get_path('incRepositorySys') . '/lib/rss.write.lib.php';

    echo build_rss( array('course' => claro_get_current_course_id() ) );
}
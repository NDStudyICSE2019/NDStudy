<?php // $Id: delete_course_stats.php 13708 2011-10-19 10:46:34Z abourguignon $

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <pir@cerdecam.be>
 * @author Christophe Gesche <moosh@claroline.net>
 */

/*
 * Kernel
 */
require_once dirname( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';

/*
 * Libraries
 */
require_once get_path('incRepositorySys') . '/lib/form.lib.php';
require_once dirname(__FILE__) . '/lib/trackingManager.class.php';
require_once dirname(__FILE__) . '/lib/trackingManagerRegistry.class.php';

/*
 * Init request vars
 */
// cmd
$acceptedCmdList = array( 'exDelete' );

if( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )
{
    $cmd = $_REQUEST['cmd'];
}
else
{
    $cmd = null;
}

// scope
$acceptedScopeList = array( 'ALL', 'BEFORE' );

if( isset($_REQUEST['scope']) && in_array($_REQUEST['scope'], $acceptedScopeList) )
{
    $scope = $_REQUEST['scope'];
}
else
{
    $scope = null;
}

// date
if ( isset($_REQUEST['beforeDate'])
    && is_array($_REQUEST['beforeDate'])
    && array_key_exists('day',$_REQUEST['beforeDate'])
    && array_key_exists('month',$_REQUEST['beforeDate'])
    && array_key_exists('year',$_REQUEST['beforeDate'])
    && (bool) checkdate( $_REQUEST['beforeDate']['month'], $_REQUEST['beforeDate']['day'], $_REQUEST['beforeDate']['year'] ))
{
    $beforeDate = mktime(0,0,0, $_REQUEST['beforeDate']['month'], $_REQUEST['beforeDate']['day'], $_REQUEST['beforeDate']['year'] );
}
else
{
    $beforeDate = null;
}


/*
 * Init other vars
 */

define('DISP_FORM', __LINE__);
define('DISP_FLUSH_RESULT', __LINE__);

$dialogBox = new DialogBox();

// default display
$display = DISP_FORM;

/*
 * Permissions
 */
if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);
if ( ! claro_is_course_manager() ) claro_die(get_lang('Not allowed'));

if( 'exDelete' == $cmd && 'BEFORE' == $scope )
{
    
    if( !is_null($beforeDate) )
    {
        // load all available managers
        $trackingManagerRegistry = TrackingManagerRegistry::getInstance();
        
        // get the loaded list
        $trackingManagerList = $trackingManagerRegistry->getManagerList();
        
        // perform delete on each manager found
        foreach( $trackingManagerList as $ctr )
        {
            $manager = new $ctr( claro_get_current_course_id() );
            
            $manager->deleteBefore( $beforeDate );
        }

        $dialogBox->success( get_block('All events before %date have been successfully deleted', array('%date' => claro_html_localised_date(get_locale('dateFormatLong'), $beforeDate))));

        Console::log( "In course "
            .  claro_get_current_course_id()
            . " : tracking events before "
            . date('Y-m-d', $beforeDate)
            . " deleted by " . claro_get_current_user_id(), 'COURSE_RESET_TRACKING_BEFORE' );
    }
    else
    {
        $dialogBox->error( get_block('%date not valid',array('%date'=>claro_html_localised_date(get_locale('dateFormatLong')))));
    }

    $display = DISP_FLUSH_RESULT;

}

if( 'exDelete' == $cmd && 'ALL' == $scope )
{
    // load all available managers
    $trackingManagerRegistry = TrackingManagerRegistry::getInstance();
    
    // get the loaded list
    $trackingManagerList = $trackingManagerRegistry->getManagerList();
    
    // perform delete on each manager found
    foreach( $trackingManagerList as $ctr )
    {
        $manager = new $ctr( claro_get_current_course_id() );
        
        $manager->deleteAll();
    }

    $dialogBox->success(get_lang('Course statistics are now empty'));

    Console::log( "In course "
        .  claro_get_current_course_id()
        . " : all tracking events deleted by user "
        . claro_get_current_user_id(), 'COURSE_RESET_ALL_TRACKING' );

    $display = DISP_FLUSH_RESULT;
}


/*
 * Prepare output
 */
$nameTools = get_lang('Delete all course statistics');


/*
 * Output
 */
$html = '';

$html .= claro_html_tool_title($nameTools);


if  ( DISP_FLUSH_RESULT == $display)
{
    // display confirm msg and back link
    $dialogBox->info( '<small>'
    .    '<a href="courseReport.php">'
    .    '&lt;&lt;&nbsp;'
    .    get_lang('Back')
    .    '</a>'
    .    '</small>' . "\n"
    );

} elseif  ( DISP_FORM == $display)
{
    $dialogBox->warning(get_lang('Delete is definitive.  There is no way to get your data back after delete.'));

    $dialogBox->form('<form action="' . $_SERVER['PHP_SELF'] . '">' . "\n"
    .    claro_form_relay_context()
    .    '<input type="hidden" name="cmd" value="exDelete" />' . "\n"
    .    '<input type="radio" name="scope" id="scope_all" value="ALL" />' . "\n"
    .    '<label for="scope_all">' . get_lang('All') . '</label>' . "\n"
    .    '<br />' . "\n"
    .    '<input type="radio" name="scope" id="scope_before" value="BEFORE" checked="checked" />' . "\n"
    .    '<label for="scope_before" >' . get_lang('Before') . '</label> ' . "\n"
    .    claro_html_date_form('beforeDate[day]', 'beforeDate[month]', 'beforeDate[year]', time(), 'short' )
    .    '<br /><br />' . "\n"
    .    '<input type="submit" name="action" value="' . get_lang('Ok') . '" />&nbsp; '
    .    claro_html_button('courseReport.php', get_lang('Cancel') )
    .    '</form>' . "\n"
    );

}        // end else if $delete

$html .= $dialogBox->render();

/*
 * Output rendering
 */
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Statistics'), 'courseReport.php' );

$claroline->display->body->setContent($html);

echo $claroline->display->render();

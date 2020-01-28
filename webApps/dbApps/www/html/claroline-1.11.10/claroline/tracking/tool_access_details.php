<?php // $Id: tool_access_details.php 13708 2011-10-19 10:46:34Z abourguignon $
/**
 * CLAROLINE
 *
 * @version 1.9 $Revision: 13708 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @author Sebastien Piraux <seb@claroline.net>
 *
 * @package CLTRACK
 */

/*
 * Kernel
 */
require_once dirname( __FILE__ ) . '/../inc/claro_init_global.inc.php';



/*
 * Permissions
 */
if( ! get_conf('is_trackingEnabled') ) claro_die(get_lang('Tracking has been disabled by system administrator.'));

if ( ! claro_is_user_authenticated() || ! claro_is_in_a_course()) claro_disp_auth_form(true);

/*
 * Libraries
 */
require_once get_path('incRepositorySys') . '/lib/statsUtils.lib.inc.php';

/*
 * DB tables definition
 */
$tbl_cdb_names    = claro_sql_get_course_tbl(claro_get_course_db_name_glued(claro_get_current_course_id()));
$tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];

/*
 * Input
 */
if( !empty($_REQUEST['displayType']) && in_array($_REQUEST['displayType'], array('month','day','hour')) )
{
    $displayType = $_REQUEST['displayType'];
}
else
{
    $displayType = '';
}

if( !empty($_REQUEST['period']) && in_array($_REQUEST['period'], array('year','month','day')) )
{
    $period = $_REQUEST['period'];
}
else
{
    $period = 'day';
}

if( !empty($_REQUEST['reqdate']) )
{
    $reqdate = (int) $_REQUEST['reqdate'];
}
else
{
    $reqdate = time();
}

// toolId is required, go to the tool list if it is missing
if( empty($_REQUEST['toolId']) )
{
    claro_redirect("./courseReport.php");
    exit();
}
else
{
    // FIXME what if tool do not exists anymore ? is not in course tool list ? is deactivated ?
    $toolId = (int)$_REQUEST['toolId'];
}

/*
 * Output
 */
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Statistics'), 'courseReport.php' );

$nameTools = get_lang('Statistics');

$html = '';

$langMonthNames = get_locale('langMonthNames');



if( claro_is_in_a_course()) //stats for the current course
{
    // to see stats of one course user must be courseAdmin of this course
    $is_allowedToTrack = claro_is_course_manager();
}
else
{
    // cid has to be set here else it probably means that the user has directly access this page by url
    $is_allowedToTrack = false;
}

if( $is_allowedToTrack )
{
    // Title parts
    $titleParts['mainTitle'] = $nameTools;
    $titleParts['subTitle'] = get_lang('Details for the tool')
                            . ': '
                            . claro_get_tool_name(claro_get_tool_id_from_course_tid($toolId));
    
    // Command list
    $cmdList = array();
    
    $cmdList[] = array(
        'name' => get_lang('View list of all tools'),
        'url' => './courseReport.php');
    
    $html .= claro_html_tool_title($titleParts, null, $cmdList);
    
    $langDay_of_weekNames = get_locale('langDay_of_weekNames');
    switch($period)
    {
        case "month" :
            $html .= $langMonthNames['long'][date("n", $reqdate)-1].date(" Y", $reqdate);
            break;
        case "week" :
            $weeklowreqdate = ($reqdate-(86400*date("w" , $reqdate)));
            $weekhighreqdate = ($reqdate+(86400*(6-date("w" , $reqdate)) ));
            $html .= '<b>'.get_lang('From').'</b> '.date('d ' , $weeklowreqdate).$langMonthNames['long'][date('n', $weeklowreqdate)-1].date(' Y' , $weeklowreqdate)."\n";
            $html .= ' <b>'.get_lang('to').'</b> '.date('d ' , $weekhighreqdate ).$langMonthNames['long'][date('n', $weekhighreqdate)-1].date(' Y' , $weekhighreqdate)."\n";
            break;
        // default == day
        default :
            $period = "day";
        case "day" :
            $html .= $langDay_of_weekNames['long'][date('w' , $reqdate)].date(' d ' , $reqdate).$langMonthNames['long'][date('n', $reqdate)-1].date(' Y' , $reqdate)."\n";
            break;
    }
    
    $html .= '<p>' . "\n"
    .   '[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=month&amp;reqdate='.$reqdate.'">'
    .   ( $period == 'month' ? '<strong>' . get_lang('Month') . '</strong>' : get_lang('Month') )
    .   '</a>]'."\n"
    .   '[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=week&amp;reqdate='.$reqdate.'">'
    .   ( $period == 'week' ? '<strong>' . get_lang('Week') . '</strong>' : get_lang('Week') )
    .   '</a>]'."\n"
    .   '[<a href="' . $_SERVER['PHP_SELF'] . '?toolId=' . $toolId . '&amp;period=day&amp;reqdate='.$reqdate.'">'
    .   ( $period == 'day' ? '<strong>' . get_lang('Day') . '</strong>' : get_lang('Day') )
    .   '</a>]'."\n"
    .   '&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;'."\n";
    
    switch($period)
    {
        case "month" :
            // previous and next date must be evaluated
            $previousReqDate = mktime(1,1,1,date("m",$reqdate)-1,1,date("Y",$reqdate));
            $nextReqDate = mktime(1,1,1,date("m",$reqdate)+1,1,date("Y",$reqdate));
            $html .= '[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=month&amp;reqdate='.$previousReqDate.'">'.get_lang('Previous month').'</a>]'."\n"
                .'[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=month&amp;reqdate='.$nextReqDate.'">'.get_lang('Next month').'</a>]'."\n";
            break;
        case "week" :
            // previous and next date must be evaluated
            $previousReqDate = $reqdate - 7*86400;
            $nextReqDate = $reqdate + 7*86400;
            $html .= '[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=week&amp;reqdate='.$previousReqDate.'">'.get_lang('Previous week').'</a>]'."\n"
                .'[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=week&amp;reqdate='.$nextReqDate.'">'.get_lang('Next week').'</a>]'."\n";
            break;
        case "day" :
            // previous and next date must be evaluated
            $previousReqDate = $reqdate - 86400;
            $nextReqDate = $reqdate + 86400;
            $html .= '[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=day&amp;reqdate='.$previousReqDate.'">'.get_lang('Previous day').'</a>]'."\n"
                .'[<a href="'.$_SERVER['PHP_SELF'].'?toolId='.$toolId.'&amp;period=day&amp;reqdate='.$nextReqDate.'">'.get_lang('Next day').'</a>]'."\n";
            break;
    }
    
    // display information about this period
    switch($period)
    {
        // all days
        case "month" :
            $sql = "SELECT UNIX_TIMESTAMP(`date`)
                    FROM `".$tbl_course_tracking_event."`
                    WHERE `type` = 'tool_access'
                      AND `tool_id` = '". (int) $toolId ."'
                      AND MONTH(`date`) = MONTH(FROM_UNIXTIME($reqdate))
                      AND YEAR(`date`) = YEAR(FROM_UNIXTIME($reqdate))
                    ORDER BY `date` ASC";

            $days_array = daysTab($sql);
            $html .= makeHitsTable($days_array,get_lang('Day'));
            break;
        // all days
        case "week" :
            $sql = "SELECT UNIX_TIMESTAMP(`date`)
                    FROM `".$tbl_course_tracking_event."`
                    WHERE `type` = 'tool_access'
                      AND `tool_id` = '". (int)$toolId ."'
                      AND WEEK(`date`) = WEEK(FROM_UNIXTIME($reqdate))
                      AND YEAR(`date`) = YEAR(FROM_UNIXTIME($reqdate))
                    ORDER BY `date` ASC";

            $days_array = daysTab($sql);
            $html .= makeHitsTable($days_array,get_lang('Day'));
            break;
        // all hours
        case "day"  :
            $sql = "SELECT UNIX_TIMESTAMP(`date`)
                        FROM `".$tbl_course_tracking_event."`
                        WHERE `type` = 'tool_access'
                          AND `tool_id` = '". $toolId ."'
                          AND DAYOFYEAR(`date`) = DAYOFYEAR(FROM_UNIXTIME($reqdate))
                          AND YEAR(`date`) = YEAR(FROM_UNIXTIME($reqdate))
                        ORDER BY `date` ASC";

            $hours_array = hoursTab($sql,$reqdate);
            $html .= makeHitsTable($hours_array,get_lang('Hour'));
            break;
    }
}
else // not allowed to track
{
    $html .= get_lang('Not allowed');
}

/*
 * Output rendering
 */
$claroline->display->body->setContent($html);

echo $claroline->display->render();

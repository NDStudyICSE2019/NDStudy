<?php // $Id: platform_access_details.php 13708 2011-10-19 10:46:34Z abourguignon $
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

if( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if( ! claro_is_platform_admin() ) claro_die( get_lang('Not allowed') );

/*
 * Libraries
 */
require_once get_path('incRepositorySys') . '/lib/statsUtils.lib.inc.php';

/*
 * DB tables definition
 */
$tbl_mdb_names    = claro_sql_get_main_tbl();
$tbl_tracking_event = $tbl_mdb_names['tracking_event'];

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

/*
 * Output
 */
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Platform statistics'),'platform_report.php' );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$nameTools = get_lang('Traffic Details');

$html = '';

$html .= claro_html_tool_title( $nameTools );

$html .= '<p><strong>';

switch($period)
{
    case 'year' :
    {
        $html .= date('Y', $reqdate);
    }   break;
    case 'month' :
    {
        $html .= claro_html_localised_date('%B %Y',$reqdate);
    }   break;
    case 'day' :
    {
        $html .= claro_html_localised_date('%A %d %B %Y',$reqdate);
    }   break;
}

$html .= '</strong></p>'."\n\n";

$html .= '<p><small>'."\n";
$html .= get_lang('Period').' : '
.   '[<a href="'.$_SERVER['PHP_SELF'].'?period=year&reqdate='.$reqdate.'&displayType=month">'
.   ( $period == 'year' ? '<strong>' . get_lang('Year') . '</strong>' : get_lang('Year') )
.    '</a>]'."\n"
.   '[<a href="'.$_SERVER['PHP_SELF'].'?period=month&reqdate='.$reqdate.'&displayType=day">'
.   ( $period == 'month' ? '<strong>' . get_lang('Month') . '</strong>' : get_lang('Month') )
.   '</a>]'."\n"
.   '[<a href="'.$_SERVER['PHP_SELF'].'?period=day&reqdate='.$reqdate.'">'
.   ( $period == 'day' ? '<strong>' . get_lang('Day') . '</strong>' : get_lang('Day') )
.   '</a>]'."\n"
.   '&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;'."\n"
.   get_lang('View by').' : ';

switch($period)
{
    case 'year' :
            //-- if period is "year" display can be by month, day or hour
            $html .= '  [<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$reqdate.'&displayType=month">'
            .   ( $displayType == 'month' ? '<strong>' . get_lang('Month') . '</strong>' : get_lang('Month') )
            .   '</a>]'."\n";
    case 'month' :
            //-- if period is "month" display can be by day or hour
            $html .= '  [<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$reqdate.'&displayType=day">'
            .   ( $displayType == 'day' ? '<strong>' . get_lang('Day') . '</strong>' : get_lang('Day') )
            .   '</a>]'."\n";
    case 'day' :
            //-- if period is "day" display can only be by hour
            $html .= '  [<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$reqdate.'&displayType=hour">'
            .   ( $displayType == 'hour' ? '<strong>' . get_lang('Hour') . '</strong>' : get_lang('Hour') )
            .   '</a>]'."\n";
            break;
}

$html .= '&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;'."\n";

switch($period)
{
    case 'year' :
        // previous and next date must be evaluated
        // 30 days should be a good approximation
        $previousReqDate = mktime(1,1,1,1,1,date('Y',$reqdate)-1);
        $nextReqDate = mktime(1,1,1,1,1,date('Y',$reqdate)+1);
        $html .= '[<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$previousReqDate.'&displayType='.$displayType.'">'.get_lang('Previous year').'</a>]'."\n"
            .'[<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$nextReqDate.'&displayType='.$displayType.'">'.get_lang('Next year').'</a>]'."\n";
        break;
    case 'month' :
        // previous and next date must be evaluated
        // 30 days should be a good approximation
        $previousReqDate = mktime(1,1,1,date("m",$reqdate)-1,1,date("Y",$reqdate));
        $nextReqDate = mktime(1,1,1,date("m",$reqdate)+1,1,date("Y",$reqdate));
        $html .= '[<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$previousReqDate.'&displayType='.$displayType.'">'.get_lang('Previous month').'</a>]'."\n"
            .'[<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$nextReqDate.'&displayType='.$displayType.'">'.get_lang('Next month').'</a>]'."\n";
        break;
    case 'day' :
        // previous and next date must be evaluated
        $previousReqDate = $reqdate - 86400;
        $nextReqDate = $reqdate + 86400;
        $html .= '[<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$previousReqDate.'&displayType='.$displayType.'">'.get_lang('Previous day').'</a>]'."\n"
            .'[<a href="'.$_SERVER['PHP_SELF'].'?period='.$period.'&reqdate='.$nextReqDate.'&displayType='.$displayType.'">'.get_lang('Next day').'</a>]'."\n";
        break;
}
$html .= '</small></p>' . "\n\n";


// display information about this period
switch($period)
{
    // all days
    case "year" :
        $sql = "SELECT UNIX_TIMESTAMP( `date` )
                            FROM `".$tbl_tracking_event."`
                            WHERE `type` = 'platform_access'
                              AND YEAR( `date` ) = YEAR( FROM_UNIXTIME( ".(int)$reqdate." ) ) ";
        if( $displayType == "month" )
        {
            $sql .= "ORDER BY UNIX_TIMESTAMP( `date`)";
            $month_array = monthTab($sql);
            $html .= makeHitsTable($month_array,get_lang('Month'));
        }
        elseif( $displayType == "day" )
        {
            $sql .= "ORDER BY DAYOFYEAR( `date`)";
            $days_array = daysTab($sql);
            $html .= makeHitsTable($days_array,get_lang('Day'));
        }
        else // by hours by default
        {
            $sql .= "ORDER BY HOUR( `date`)";
            $hours_array = hoursTab($sql);
            $html .= makeHitsTable($hours_array,get_lang('Hour'));
        }
        break;
    // all days
    case "month" :
        $sql = "SELECT UNIX_TIMESTAMP( `date` )
                            FROM `".$tbl_tracking_event."`
                            WHERE `type` = 'platform_access'
                              AND MONTH(`date`) = MONTH (FROM_UNIXTIME( $reqdate ) )
                                AND YEAR( `date` ) = YEAR( FROM_UNIXTIME( $reqdate ) ) ";
        if( $displayType == "day" )
        {
            $sql .= "ORDER BY DAYOFYEAR( `date`)";
            $days_array = daysTab($sql);
            $html .= makeHitsTable($days_array,get_lang('Day'));
        }
        else // by hours by default
        {
            $sql .= "ORDER BY HOUR( `date`)";
            $hours_array = hoursTab($sql);
            $html .= makeHitsTable($hours_array,get_lang('Hour'));
        }
        break;
    // all hours
    case "day"  :
        $sql = "SELECT UNIX_TIMESTAMP( `date` )
                            FROM `".$tbl_tracking_event."`
                            WHERE `type` = 'platform_access'
                              AND DAYOFMONTH(`date`) = DAYOFMONTH(FROM_UNIXTIME( $reqdate ) )
                                AND MONTH(`date`) = MONTH (FROM_UNIXTIME( $reqdate ) )
                                AND YEAR( `date` ) = YEAR( FROM_UNIXTIME( $reqdate ) )
                            ORDER BY HOUR( `date` )";
        $hours_array = hoursTab($sql,$reqdate);
        $html .= makeHitsTable($hours_array,get_lang('Hour'));
        break;
}

/*
 * Output rendering
 */
$claroline->display->body->setContent($html);

echo $claroline->display->render();

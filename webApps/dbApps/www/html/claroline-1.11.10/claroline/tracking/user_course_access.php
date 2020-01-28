<?php // $Id: user_course_access.php 14314 2012-11-07 09:09:19Z zefredz $
/**
 * CLAROLINE
 *
 * @version 1.9 $Revision: 14314 $
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
require_once dirname( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';



/*
 * Usual check
 */
if( ! get_conf('is_trackingEnabled') ) claro_die(get_lang('Tracking has been disabled by system administrator.'));

/*
 * Libraries
 */
FromKernel::uses('user.lib', 'display/userprofilebox.lib');

/*
 * Init request vars
 */

if( isset($_REQUEST['userId']) && is_numeric($_REQUEST['userId']) )
{
    $userId = (int) $_REQUEST['userId'];
}
else
{
    $userId = null;
}

if( isset($_REQUEST['courseId']) && !empty($_REQUEST['courseId']) )
{
    $courseId = $_REQUEST['courseId'];
}
else
{
    if( claro_is_in_a_course() )
    {
        $courseId = claro_get_current_course_id();
    }
    else
    {
       claro_disp_auth_form(true);
    }
}

        
if( !empty($_REQUEST['period']) && in_array($_REQUEST['period'], array('month','week')) )
{
    $period = $_REQUEST['period'];
}
else
{
    $period = 'month';
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
 * Permissions
 */
$userData = user_get_properties($userId);

if( is_null($userId) || empty($userData) )
{
    claro_die(get_lang('User not found'));
}

if( ! claro_is_platform_admin() && ! claro_is_course_manager() && $userId != claro_get_current_user_id() )
{
    claro_die(get_lang('Not allowed'));
}


/*
 * Prepare output
 */
$tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($courseId));
$tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];

// FIXME use userCard
 
if( $period == 'week' )
{

    $sqlAccessDates = "SELECT `date`
            FROM `" . $tbl_course_tracking_event . "`
            WHERE `user_id` = " . (int) $userId . "
              AND `type` = 'course_access'
              AND WEEK(`date`) = WEEK( FROM_UNIXTIME('" . $reqdate . "') )
              AND YEAR(`date`) = YEAR( FROM_UNIXTIME(" . $reqdate . ") )
            ORDER BY `date` ASC ";
    
    // used in links to move from one week to another
    $previousReqDate = $reqdate - 7 * 86400; // 86400=24*60*60
    $nextReqDate     = $reqdate + 7 * 86400;
    
    // prepare displayed date
    $weekStartDate = ($reqdate-(86400*date("w" , $reqdate)));
    $weekEndDate = ($reqdate+(86400*(6-date("w" , $reqdate)) ));
    
    $displayedDate =
    get_lang('From')
    .' '.claro_html_localised_date(get_locale('dateFormatLong'), $weekStartDate )
    ." ".get_lang('to')
    .' '.claro_html_localised_date(get_locale('dateFormatLong'), $weekEndDate );
}
else // month
{
    
    $sqlAccessDates = "SELECT `date`
                      FROM `".$tbl_course_tracking_event."`
                      WHERE `user_id` = ". (int) $userId ."
                        AND `type` = 'course_access'
                          AND MONTH(`date`) = MONTH( FROM_UNIXTIME('" . $reqdate . "') )
                          AND YEAR(`date`) = YEAR( FROM_UNIXTIME(" . $reqdate . ") )
                      ORDER BY `date` ASC ";
    
    // used in links to move from one month to another
    $previousReqDate = mktime(1,1,1,date('m',$reqdate)-1,1,date('Y',$reqdate));
    $nextReqDate = mktime(1,1,1,date('m',$reqdate)+1,1,date('Y',$reqdate));
    
    // prepare displayed date
    $displayedDate = claro_html_localised_date(get_locale('dateFormatCompact'), $reqdate );
}

$accessList = claro_sql_query_fetch_all($sqlAccessDates);


/*
 * Output
 */
CssLoader::getInstance()->load( 'tracking', 'screen');

// initialize output
$claroline->setDisplayType( Claroline::PAGE );

// FIXME (link + parameters)
$nameTools = get_lang('User access to course');
ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, $_SERVER['PHP_SELF'].'?userId=' . $userId );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Users statistics'), claro_htmlspecialchars( Url::Contextualize('userReport.php?userId=' . $userId) ) );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Users'), get_module_url('CLUSR').'/user.php' );


$output = '';

/*
 * Output of : user information
 */
$userProfileBox = new UserProfileBox(true);
$userProfileBox->setUserId($userId);

$output .= '<div id="rightSidebar">' . $userProfileBox->render() . '</div>';

$output .= '<div id="leftContent">' . "\n";

$output .= claro_html_tool_title($nameTools);

// menu
$output .= '<small>'."\n"
.   '[<a href="' . $_SERVER['PHP_SELF'].'?userId=' . $userId . '&amp;period=week&amp;reqdate='.$reqdate.'">'.get_lang('Week').'</a>]'."\n"
.   '[<a href="' . $_SERVER['PHP_SELF'].'?userId=' . $userId . '&amp;period=month&amp;reqdate='.$reqdate.'">'.get_lang('Month').'</a>]'."\n"
.   '&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;'."\n"
;

if( $period == 'week' )
{
    // previous and next date must be evaluated
    $output .= '[<a href="' . $_SERVER['PHP_SELF'] . '?userId=' . $userId . '&amp;period=week&amp;reqdate=' . $previousReqDate . '">' . get_lang('Previous week') . '</a>]' . "\n"
    .    '[<a href="' . $_SERVER['PHP_SELF'] . '?userId=' . $userId . '&amp;period=week&amp;reqdate=' . $nextReqDate . '">' . get_lang('Next week') . '</a>]' . "\n"
    ;
}
else // month
{
    $output .= '[<a href="' . $_SERVER['PHP_SELF'] . '?userId=' . $userId . '&amp;period=month&amp;reqdate=' . $previousReqDate . '">' . get_lang('Previous month') . '</a>]' . "\n"
    .    '[<a href="' . $_SERVER['PHP_SELF'] . '?userId=' . $userId . '&amp;period=month&amp;reqdate=' . $nextReqDate . '">' . get_lang('Next month') . '</a>]' . "\n"
    ;
}

$output .= '</small>' . "\n\n";

$output .= '<table class="claroTable" width="100%" cellpadding="4" cellspacing="1">' ."\n"
.   '<thead>'
.   '<tr class="headerX"><th>'.$displayedDate.'</th></tr>' . "\n"
.   '</thead>' . "\n"
.   '<tbody>';

if( !empty($accessList) && is_array($accessList) )
{
    $i = 0;
    while( $i < sizeof($accessList) )
    {
        $output .= '<tr>' . "\n"
        .    '<td><small>' . claro_html_localised_date( get_locale('dateTimeFormatLong'), strtotime($accessList[$i]['date']) ) . '</small></td>' . "\n"
        .    '</tr>' . "\n"
        ;
        // $limit is used to select only results between current login and next one
        if( $i == ( sizeof($accessList) - 1 ) || !isset($accessList[$i+1]['date']) )
        $limit = date("Y-m-d H:i:s",$nextReqDate);
        else
        $limit = $accessList[$i+1]['date'];

        // select all access in the displayed date range
        $sql = "SELECT `tool_id`, count(`id`) AS `nbr_access`
                FROM `".$tbl_course_tracking_event."`
                WHERE `user_id` = '". (int) $userId."'
                    AND `type` = 'tool_access'
                    AND `date` >= '" . $accessList[$i]['date'] . "'
                    AND `date` < '" . $limit . "'
                GROUP BY `tool_id`
                ORDER BY `tool_id` ASC";

        $toolAccess = claro_sql_query_fetch_all($sql);
        
        if( !empty($toolAccess) && is_array($toolAccess) )
        {
            $output .= '<tr>' . "\n"
            .    '<td colspan="2">' . "\n"
            .    '<table width="100%" cellpadding="0" cellspacing="0" border="0">' . "\n"
            ;
            foreach( $toolAccess as $aToolAccess )
            {
                $output .= '<tr>' . "\n"
                .    '<td width="70%"><small>' . claro_get_tool_name(claro_get_tool_id_from_course_tid($aToolAccess['tool_id'])) . '</small></td>' . "\n"
                .    '<td width="30%" align="right"><small>' . $aToolAccess['nbr_access'] . ' ' . get_lang('Visits').'</small></td>' . "\n"
                .    '</tr>' . "\n"
                ;

            }
            $output .= '</table>' . "\n"
            .    '</td></tr>' . "\n\n"
            ;
        }

        $i++;
    }

}
else
{
    $output .= '<tr>' . "\n"
    .    '<td colspan="2">'
    .    '<div align="center">' . get_lang('No result') . '</div>'
    .    '</td>'."\n"
    .    '</tr>' . "\n"
    ;
}
$output .= '</tbody></table>' . "\n";

$output .= "\n" . '</div>' . "\n";
/*
 * Output rendering
 */

$claroline->display->body->setContent($output);

echo $claroline->display->render();

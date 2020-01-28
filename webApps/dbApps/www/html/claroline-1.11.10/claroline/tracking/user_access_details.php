<?php // $Id: user_access_details.php 14314 2012-11-07 09:09:19Z zefredz $
/**
 * CLAROLINE
 *
 * @version 1.6 *
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author see CREDITS.txt
 *
 */
require '../inc/claro_init_global.inc.php';

$nameTools = get_lang('User access details');

$interbredcrump[]= array ("url"=>"courseReport.php", "name"=> get_lang('Statistics'));

$tbl_mdb_names       = claro_sql_get_main_tbl();
$tbl_user           = $tbl_mdb_names['user'  ];

$tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued(claro_get_current_course_id()));
$tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];


require_once get_path('incRepositorySys') . '/lib/statsUtils.lib.inc.php';

$toolTitle['mainTitle'] = $nameTools;

$is_allowedToTrack = claro_is_course_manager();

$out = '';

if( $is_allowedToTrack && get_conf('is_trackingEnabled') )
{
    if( isset($_REQUEST['cmd']) && ( $_REQUEST['cmd'] == 'tool' && !empty($_REQUEST['id']) ) )
    {
        $toolTitle['subTitle'] = claro_get_tool_name(claro_get_tool_id_from_course_tid((int)$_REQUEST['id']));


        // prepare SQL query
        $sql = "SELECT `U`.`nom` AS `lastName`,
                       `U`.`prenom` AS `firstName`,
                        MAX(UNIX_TIMESTAMP(`TE`.`date`)) AS `data`,
                        COUNT(`TE`.`date`) AS `nbr`
                  FROM `".$tbl_course_tracking_event."` AS `TE`
             LEFT JOIN `".$tbl_user."` AS `U`
                    ON `TE`.`user_id` = `U`.`user_id`
                 WHERE `TE`.`tool_id` = '". (int)$_REQUEST['id']."'
              GROUP BY `U`.`nom`, `U`.`prenom`
              ORDER BY `U`.`nom`, `U`.`prenom`";
    }
    elseif( isset($_REQUEST['cmd']) && ( $_REQUEST['cmd'] == 'doc' && !empty($_REQUEST['path']) ) )
    {
        // FIXME : fix query, probably not a good idea to use like to find a match inside serialized data
        // set the subtitle for the echo claro_html_tool_title function
        $toolTitle['subTitle'] = get_lang('Documents and Links')." : ". claro_htmlspecialchars($_REQUEST['path']);
        // prepare SQL query
        $sql = "SELECT `U`.`nom` as `lastName`,
                       `U`.`prenom` as `firstName`,
                        MAX(UNIX_TIMESTAMP(`TE`.`date`)) AS `data`,
                        COUNT(`TE`.`date`) AS `nbr`
                  FROM `".$tbl_course_tracking_event."` AS `TE`
             LEFT JOIN `".$tbl_user."` AS `U`
                    ON `U`.`user_id` = `TE`.`user_id`
                 WHERE `TE`.`data` LIKE '%". claro_sql_escape($_REQUEST['path']) ."%'
              GROUP BY `U`.`nom`, `U`.`prenom`
              ORDER BY `U`.`nom`, `U`.`prenom`";
    }
    else
    {
        claro_die( get_lang('Wrong operation') );
    }

    $out .= claro_html_tool_title($toolTitle);

    // TODO  use datagrid
    $out .= '<br />' . "\n\n"
    .    '<table class="claroTable" border="0" cellpadding="5" cellspacing="1">' . "\n"
    .    '<tr class="headerX">'."\n"
    .    '<th>' . get_lang('Username') . '</th>' . "\n"
    .    '<th>' . get_lang('Last access') . '</th>' . "\n"
    .    '<th>' . get_lang('Access count') . '</th>' . "\n"
    .    '</tr>' . "\n"
    .    '<tbody>' . "\n\n"
    ;

    $i = 0;
    $anonymousCount = 0;
    if( isset($sql) )
    {
        $accessList = claro_sql_query_fetch_all($sql);
        // display the list
        foreach ( $accessList as $userAccess )
        {
            $userName = $userAccess['lastName']." ".$userAccess['firstName'];
            if( empty($userAccess['lastName']) )
            {
                 $anonymousCount = $userAccess['nbr'];
                continue;
            }
            $i++;
            $out .= '<tr>' . "\n"
            .    '<td>' . $userName . '</td>' . "\n"
            .    '<td>' . claro_html_localised_date(get_locale('dateTimeFormatLong'), $userAccess['data']) . '</td>' . "\n"
            .    '<td>' . $userAccess['nbr'] . '</td>' . "\n"
            .    '</tr>' . "\n\n"
            ;
        }
    }
    // in case of error or no results to display
    if( $i == 0 || !isset($sql) )
    {
        $out .= '<td colspan="3">' . "\n"
        .    '<center>' . get_lang('No result') . '</center>' . "\n"
        .    '</td>' . "\n\n"
        ;
    }

    $out .= '</tbody>' . "\n\n"
    .    '</table>' . "\n\n"
    ;

    if( $anonymousCount != 0 )
    {
        $out .= '<p>'.get_lang('Anonymous users access count : ').' '.$anonymousCount.'</p>'."\n";
    }

}
// not allowed
else
{
    if(!get_conf('is_trackingEnabled'))
    {
        $out .= get_lang('Tracking has been disabled by system administrator.');
    }
    else
    {
        $out .= get_lang('Not allowed');
    }
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

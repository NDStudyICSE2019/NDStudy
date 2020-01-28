<?php // $Id: group_edit.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * This script edit userlist of a group and group propreties
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/CLGRP
 * @package     CLGRP
 * @author      Claro Team <cvs@claroline.net>
 */

$tlabelReq = 'CLGRP';

require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/form.lib.php';
require_once get_path('incRepositorySys') . '/lib/group.lib.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

$is_allowedToManage = claro_is_allowed_to_edit();

if ( ! $is_allowedToManage )
{
    claro_die(get_lang("Not allowed"));
}

$dialogBox = new DialogBox();
$nameTools = get_lang("Edit this group");

$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_rel_user_course         = $tbl_mdb_names['rel_course_user'  ];
$tbl_user                    = $tbl_mdb_names['user'             ];

$tbl_bb_forum                = $tbl_cdb_names['bb_forums'];
$tbl_group_rel_team_user     = $tbl_cdb_names['group_rel_team_user'];
$tbl_group_team              = $tbl_cdb_names['group_team'];

$currentCourseId        = claro_get_current_course_id();
$_groupProperties       = claro_get_current_group_properties_data();
$myStudentGroup         = claro_get_current_group_data();
$nbMaxGroupPerUser      = $_groupProperties ['nbGroupPerUser'];

if ( isset($_REQUEST['name']) ) $name = trim($_REQUEST['name']);
else                            $name = '';

if ( isset($_REQUEST['description']) ) $description = trim($_REQUEST['description']);
else                                   $description = '';

if ( isset($_REQUEST['maxMember'])
    && ctype_digit($_REQUEST['maxMember'])
    && (trim($_REQUEST['maxMember']) != ''
    && (int)$_REQUEST['maxMember'] > 0 ) ) $maxMember = (int)$_REQUEST['maxMember'];
else $maxMember = NULL;

if ( isset($_REQUEST['tutor']) ) $tutor = (int) $_REQUEST['tutor'];
else                             $tutor = 0;

if ( isset($_REQUEST['ingroup']) ) $ingroup = $_REQUEST['ingroup'];
else                               $ingroup = array();


################### IF MODIFY #######################################

// Once modifications have been done, the user validates and arrives here
if ( isset($_REQUEST['modify']) && $is_allowedToManage )
{
    $sql = "UPDATE`" . $tbl_group_team . "`
            SET `name`        = '" . claro_sql_escape( $name ) . "',
                `description` = '" . claro_sql_escape( $description ) . "',
                `maxStudent`  = " . ( is_null( $maxMember ) ? 'NULL' : "'" . (int) $maxMember . "'" ) .",
                `tutor`       = '" . (int) $tutor ."'
            WHERE `id`        = '" . (int) claro_get_current_group_id() . "'";


    // Update main group settings
    $updateStudentGroup = claro_sql_query( $sql );

    // UPDATE FORUM NAME
    $sql = 'UPDATE `' . $tbl_bb_forum . '`
            SET `forum_name` ="' . claro_sql_escape($name).'"
            WHERE `forum_id` ="' . $myStudentGroup['forumId'] . '"';

    claro_sql_query( $sql );

    // Count number of members
    $numberMembers = count( $ingroup );

    // every letter introduced in field drives to 0
    settype( $maxMember, 'integer' );

    // Insert new list of members
    if ( $maxMember < $numberMembers AND $maxMember != '0' )
    {
        // Too much members compared to max members allowed
        $dialogBox->error( get_lang('Number proposed exceeds max. that you allowed (you can modify it below). Group composition has not been modified') );
    }
    else
    {
        // Delete all members of this group
        $sql = 'DELETE FROM `' . $tbl_group_rel_team_user . '` WHERE `team` = "' . (int)claro_get_current_group_id() . '"';

        $delGroupUsers = claro_sql_query( $sql );
        $numberMembers--;

        for ( $i = 0; $i <= $numberMembers; $i++ )
        {
            $sql = "INSERT INTO `" . $tbl_group_rel_team_user . "`
                    SET user = " . (int) $ingroup[$i] . ",
                        team = " . (int) claro_get_current_group_id() ;

            $registerUserGroup = claro_sql_query( $sql );
        }

        $dialogBox->success( get_lang("Group settings modified")  
            . '<br />'
            . '<a href="'.claro_htmlspecialchars(Url::Contextualize('./group_space.php' ) ).'">'
            . get_lang("Group area")
            . '</a>' 
            . '&nbsp;-&nbsp;'
            . '<a href="'.claro_htmlspecialchars(Url::Contextualize('./group.php' ) ).'">'
            . get_lang("Groups")
            . '</a>'
        );

    }    // else

    $gidReset = TRUE;
    $gidReq   = claro_get_current_group_id();

    include get_path('incRepositorySys') . '/claro_init_local.inc.php';

    $myStudentGroup = claro_get_current_group_data();

}    // end if $modify
// SELECT TUTORS

$tutorList = get_course_tutor_list( $currentCourseId );

// AND student_group.id='claro_get_current_group_id()'    // This statement is DEACTIVATED

$tutor_list=array();

$tutor_list[get_lang("(none)")] = 0;

foreach ($tutorList as $myTutor)
{
    $tutor_list[claro_htmlspecialchars( $myTutor['name'] . ' ' . $myTutor['firstname'] )] = $myTutor['userId'];
}

// Student registered to the course but inserted in no group
$limitNumOfGroups = ( is_null($nbMaxGroupPerUser) || $nbMaxGroupPerUser == 0 )
    ? ""
    : " AND nbg < " . (int) $nbMaxGroupPerUser
    ;

// Get the users not in group
$sql = "SELECT `u`.`user_id`        AS `user_id`,
               `u`.`nom`            AS `lastName`,
               `u`.`prenom`         AS `firstName`,
               `cu`.`role`          AS `role`,
               COUNT(`ug`.`id`)     AS `nbg`,
               COUNT(`ugbloc`.`id`) AS `BLOCK`
        
        FROM (`" . $tbl_user . "`                     AS u
           , `" . $tbl_rel_user_course . "`          AS cu )
        
        LEFT JOIN `" . $tbl_group_rel_team_user . "` AS ug
        ON `u`.`user_id`=`ug`.`user`
        
        LEFT JOIN `" . $tbl_group_rel_team_user . "` AS `ugbloc`
        ON  `u`.`user_id`=`ugbloc`.`user` AND `ugbloc`.`team` = " . (int) claro_get_current_group_id() . "
        
        WHERE `cu`.`code_cours` = '" . $currentCourseId . "'
        AND   `cu`.`user_id`    = `u`.`user_id`
        AND ( `cu`.`isCourseManager` = 0 )
        AND   `cu`.`tutor`      = 0
        AND ( `ug`.`team`       <> " . (int) claro_get_current_group_id() . " OR `ug`.`team` IS NULL )
        
        GROUP BY `u`.`user_id`
        HAVING `BLOCK` = 0
        " . $limitNumOfGroups . "
        ORDER BY
        #`nbg`, #disabled because different of  right box
        UPPER(`u`.`nom`), UPPER(`u`.`prenom`), `u`.`user_id`";

$result = Claroline::getDatabase()->query($sql);
$result->setFetchMode(Database_ResultSet::FETCH_ASSOC);

// Create html options lists
$userNotInGroupListHtml = '';
foreach ( $result as $member )
{
    $label = claro_htmlspecialchars( ucwords( strtolower( $member['lastName']))
           . ' ' . ucwords(strtolower($member['firstName'] ))
           . ($member['role']!=''?' (' . $member['role'] . ')':'') )
           . ( $nbMaxGroupPerUser > 1 ?' (' . $member['nbg'] . ')' : '' );
    
    $userNotInGroupListHtml .= '<option value="'
                         . $member['user_id'] . '">' . $label
                         . '</option>' . "\n";
}

$usersInGroupList = get_group_member_list();

$usersInGroupListHtml = '';
foreach ( $usersInGroupList as $key => $val )
{
    $usersInGroupListHtml .= '<option value="'
                         . $key . '">' . $val
                         . '</option>' . "\n";
}

$thisGroupMaxMember = ( is_null($myStudentGroup['maxMember']) ? '-' : $myStudentGroup['maxMember']);

$template = new CoreTemplate('group_form.tpl.php');
$template->assign('formAction', claro_htmlspecialchars( $_SERVER['PHP_SELF'] . '?edit=yes&gidReq=' . claro_get_current_group_id() ) );
$template->assign('relayContext', claro_form_relay_context());
$template->assign('groupName', claro_htmlspecialchars($myStudentGroup['name']));
$template->assign('groupId', claro_get_current_group_id());
$template->assign('groupDescription', claro_htmlspecialchars($myStudentGroup['description']));
$template->assign('groupTutorId', $myStudentGroup['tutorId']);
$template->assign('groupUserLimit', claro_htmlspecialchars($thisGroupMaxMember));
$template->assign('tutorList', $tutor_list);
$template->assign('usersInGroupListHtml', $usersInGroupListHtml);
$template->assign('userNotInGroupListHtml', $userNotInGroupListHtml);

$out = '';

$out .= claro_html_tool_title(array('supraTitle' => get_lang("Groups"), 'mainTitle' => $nameTools));

$out .= $dialogBox->render();

$out .= $template->render();

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

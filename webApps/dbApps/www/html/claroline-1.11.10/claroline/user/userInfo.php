<?php // $Id: userInfo.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * Mangage personal user info in a course.
 *
 * @version 1.11 $Revision: 14314 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see http://www.claroline.net/CLUSR/
 * @package CLUSR
 * @author Claro Team <cvs@claroline.net>
 */


define ('DO_WRITE_EXTRA_FIELD','DO_WRITE_EXTRA_FIELD');
define ('DO_REMOVE_EXTRA_FIELD','DO_REMOVE_EXTRA_FIELD');
define ('DO_MOVE_DOWN_EXTRA_FIELD_RANK','DO_MOVE_DOWN_EXTRA_FIELD_RANK');
define ('DO_MOVE_UP_EXTRA_FIELD_RANK','DO_MOVE_UP_EXTRA_FIELD_RANK');
define ('DO_VIEW_EXTRA_FIELD_LIST','DO_VIEW_EXTRA_FIELD_LIST');
define ('DO_ADD_EXTRA_FIELD','DO_ADD_EXTRA_FIELD');
define ('DO_EDIT_EXTRA_FIELD','DO_EDIT_EXTRA_FIELD');

$tlabelReq = 'CLUSR';
$gidReset = true;

require '../inc/claro_init_global.inc.php';

$messageList = array();

$descSizeToPrupose = array(3,5,10,15,20); // size in lines for desc - don't add 1

require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php' ;
require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';
require_once get_path('incRepositorySys') . '/lib/user_info.lib.php';

require_once dirname(__FILE__) . '/../messaging/lib/permission.lib.php';

ClaroBreadCrumbs::getInstance()->prepend( 
    get_lang('Users'), 
    Url::Contextualize('user.php')
);

$nameTools = get_lang('User');

/** OUTPUT **/
claro_set_display_mode_available(TRUE);

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form();

/*
* data  found  in settings  are :
*    $uid
*    $isAdmin
*    $isAdminOfCourse
*
*/

if (isset($_REQUEST['uInfo'])) $userIdViewed = (int) $_REQUEST['uInfo']; // Id of the user we want to view coming from the user.php
else $userIdViewed = 0;

/*--------------------------------------------------------
Connection API between Claroline and the current script
--------------------------------------------------------*/

$userIdViewer = claro_get_current_user_id(); // id fo the user currently online
//$userIdViewed = $_GET['userIdViewed']; // Id of the user we want to view

$allowedToEditContent     = $userIdViewer == $userIdViewed || claro_is_allowed_to_edit();
$allowedToEditDef         = claro_is_allowed_to_edit();
$is_allowedToTrack        = ( claro_is_allowed_to_edit() || $userIdViewer == $userIdViewed ) && get_conf('is_trackingEnabled') ;

if ( ! claro_is_allowed_to_edit() && ! get_conf('linkToUserInfo') )
{
    claro_die(get_lang('Not allowed'));
}

// clean field submited by the user
if ($_POST)
{
    foreach($_POST as $key => $value)
    {
        $$key = replace_dangerous_char($value);
    }
}

/*======================================
COMMANDS SECTION
======================================*/

$displayMode = "viewContentList";

$cmdList= array('submitDef','removeDef','editDef','addDef' , 'moveUpDef' , 'moveDownDef', 'viewDefList','editMainUserInfo', 'exUpdateCourseUserProperties' );
$cmd = (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$cmdList))?$_REQUEST['cmd']:null;

$do = null;
if($cmd == 'submitDef' || (isset($_REQUEST['submitDef']) && $_REQUEST['submitDef']))
{
    $do = DO_WRITE_EXTRA_FIELD;
}
elseif ($cmd == 'removeDef' || (isset($_REQUEST['removeDef']) && $_REQUEST['removeDef']))
{
    $do = DO_REMOVE_EXTRA_FIELD;
}
elseif ($cmd == 'editDef' || (isset($_REQUEST['editDef']) && $_REQUEST['editDef']))
{
    $defToEdit = $_REQUEST['editDef'];
    $do = DO_EDIT_EXTRA_FIELD;
}
elseif ($cmd == 'addDef' || isset($_REQUEST['addDef']))
{
    $do = DO_ADD_EXTRA_FIELD;
    $displayMode = "viewDefEdit";
}
elseif ($cmd == 'moveUpDef' || isset($_REQUEST['moveUpDef']))
{
    $do = DO_MOVE_UP_EXTRA_FIELD_RANK;
}
elseif ($cmd == 'moveDownDef' || isset($_REQUEST['moveDownDef']))
{
    $do = DO_MOVE_DOWN_EXTRA_FIELD_RANK;
}
elseif($cmd == 'viewDefList' || isset($_REQUEST['viewDefList']))
{
    $do = DO_VIEW_EXTRA_FIELD_LIST;
}
elseif ($cmd == 'editMainUserInfo' || isset($_REQUEST['editMainUserInfo']))
{
    $do = null;
    $userIdViewed = (int) $_REQUEST['editMainUserInfo'];
    $displayMode = "viewMainInfoEdit";
}
elseif ( $cmd == 'exUpdateCourseUserProperties' )
{
    $do = null;
}
if ($allowedToEditDef)
{
    if ($do == DO_WRITE_EXTRA_FIELD)
    {
        if (isset($_REQUEST['id']) && $_REQUEST['id'] != '')
        {
            claro_user_info_edit_cat_def($_REQUEST['id'], $_REQUEST['title'], $_REQUEST['comment'], $_REQUEST['nbline']);
        }
        else
        {
            claro_user_info_create_cat_def($_REQUEST['title'], $_REQUEST['comment'], $_REQUEST['nbline']);
        }

        $displayMode = "viewDefList";
    }
    elseif ($do == DO_REMOVE_EXTRA_FIELD)
    {
        claro_user_info_remove_cat_def($_REQUEST['removeDef'], true);
        $displayMode = "viewDefList";
    }
    elseif ($do == DO_EDIT_EXTRA_FIELD)
    {
        $catToEdit = claro_user_info_get_cat_def($_REQUEST['editDef']);
        $displayMode = "viewDefEdit";
    }
    elseif (isset($_REQUEST['addDef']))
    {
        $displayMode = "viewDefEdit";
    }
    elseif ( $do == DO_MOVE_UP_EXTRA_FIELD_RANK )
    {
        claro_user_info_move_cat_rank($_REQUEST['moveUpDef'], "up");
        $displayMode = "viewDefList";
    }
    elseif ( $do == DO_MOVE_DOWN_EXTRA_FIELD_RANK )
    {
        claro_user_info_move_cat_rank($_REQUEST['moveDownDef'], "down");
        $displayMode = "viewDefList";
    }
    elseif($do == DO_VIEW_EXTRA_FIELD_LIST)
    {
        $displayMode = "viewDefList";
    }
    elseif (isset($_REQUEST['editMainUserInfo']))
    {
        $userIdViewed = (int) $_REQUEST['editMainUserInfo'];
        $displayMode = "viewMainInfoEdit";
    }
    elseif ( $cmd == 'exUpdateCourseUserProperties' )
    {
        $userIdViewed = $_REQUEST['submitMainUserInfo'];

        // Set variable for course manager or student status

        if ( !empty($_REQUEST['profileId']) && $userIdViewed != claro_get_current_user_id() )
        {
            $userProperties['profileId'] = $_REQUEST['profileId'];
        }

        // Set variable for tutor setting

        if (isset($_REQUEST['isTutor']))
        {
            // check first the user isn't registered to a group yet
            $tbl_crs_names           = claro_sql_get_course_tbl();
            $tbl_group_rel_team_user = $tbl_crs_names['group_rel_team_user'];

            $sql = "SELECT COUNT(user)
                    FROM `".$tbl_group_rel_team_user."`
                    WHERE user = ".(int) $userIdViewed;

            if ( claro_sql_query_get_single_value($sql) == 0 )
            {
                $userProperties['tutor' ] = 1;
            }
            else
            {
                $userProperties['tutor' ] = 0;
                $messageList['error'] = get_lang('Impossible to promote as a group tutor a student who is already registered in the group');
            }
        }
        else
        {
            $userProperties['tutor' ] = 0;
        }

        //set variable for role setting

        $userProperties['role'] =  $_REQUEST['role'];

        // apply changes in DB

        user_set_course_properties($userIdViewed, claro_get_current_course_id(), $userProperties);
        $displayMode = "viewContentList";
    }
}

// COMMON COMMANDS

if ($allowedToEditContent)
{
    if (isset($_REQUEST['submitContent']))
    {
        if ($_REQUEST['cntId'])    // submit a content change
        {
            claro_user_info_edit_cat_content($_REQUEST['catId'], $userIdViewed, $_REQUEST['content'], $_SERVER['REMOTE_ADDR']);
        }
        else        // submit a totally new content
        {
            claro_user_info_fill_new_cat_content($_REQUEST['catId'], $userIdViewed, $_REQUEST['content'], $_SERVER['REMOTE_ADDR']);
        }

        $displayMode = "viewContentList";
    }
    elseif (isset($_REQUEST['editContent']))
    {
        $displayMode = "viewContentEdit";
    }
}

//PREPARE DISPLAYS


if ($displayMode == "viewDefEdit")
{
    /* CATEGORIES DEFINITIONS : EDIT */

    if ($do != DO_EDIT_EXTRA_FIELD)
    {
        $catToEdit = array();
        $catToEdit['title'] = '';
        $catToEdit['comment'] = '';
        $catToEdit['nbline'] = 1;
        $catToEdit['id'] = '';
    }
}
elseif ($displayMode == 'viewDefList')
{
    $catList = claro_user_info_claro_user_info_get_cat_def_list();
}
elseif ($displayMode == 'viewMainInfoEdit')
{
    /*>>>>>>>>>>>> CATEGORIES MAIN INFO : EDIT <<<<<<<<<<<<*/
    $mainUserInfo = course_user_get_properties($userIdViewed, claro_get_current_course_id());

}
elseif ($displayMode == 'viewContentEdit' )
{
    $catToEdit = claro_user_info_get_cat_content($userIdViewed,$_REQUEST['editContent']);
}
elseif ($displayMode == 'viewContentList') // default display
{
    $mainUserInfo = course_user_get_properties($userIdViewed, claro_get_current_course_id());
}


if( $displayMode != "viewContentList" ) claro_set_display_mode_available(false);


$skypeName = get_user_property( $userIdViewed , 'skype' );

if ( $skypeName )
{
    $skypeStatus = '<a href="skype:' . $skypeName . '?call">' . "\n"
                   .'<img src="http://mystatus.skype.com/smallclassic/' . $skypeName . '"
                        style="border: none;" width="100" height="15" alt="" />' . "\n"
                   .'</a>';
}
else
{
    $skypeStatus = '<em>' . get_lang( 'None' ) . '</em>';
}

//////////////////////////////
// OUTPUT
//////////////////////////////

$out = '';

$out .= claro_html_tool_title($nameTools)
    // Back button for each display mode (Top)
    . '<p>' . "\n"
    . '<small>' . "\n"
    . '<a href="'. claro_htmlspecialchars(Url::Contextualize( get_module_entry_url('CLUSR') )) . '">'
    . '&lt;&lt;&nbsp;'
    . get_lang('Back to user list')
    . '</a>' . "\n"
    . '</small>' . "\n"
    . '</p>' . "\n"
    . claro_html_msg_list($messageList)
    ;

$userData = user_get_properties( $userIdViewed );
$picturePath = user_get_picture_path( $userData );

if ( $picturePath && file_exists( $picturePath ) )
{
    $pictureUrl = user_get_picture_url( $userData );
}
else
{
    $pictureUrl = get_icon_url('nopicture');
}

$out .= '<div id="rightSidebar"><img src="' . $pictureUrl . '" alt="' . get_lang('avatar') . '" /></div>';

if ($displayMode == "viewDefEdit")
{
    /* CATEGORIES DEFINITIONS : EDIT */
    $out .= '<form method="post" action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] . '?uInfo=' . $userIdViewed ) . '">' . "\n"
        . claro_form_relay_context()
        . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' . "\n"
        . '<input type="hidden" name="id" value="' . $catToEdit['id'] . '" />' . "\n"
        . '<table>' . "\n"
        . '<tr>' . "\n"
        . '<td>' . "\n"
        . '<label for="title" >' . get_lang('Heading') . '</label> :' . "\n"
        . '</td>' . "\n"
        . '<td>' . "\n"
        . '<input type="text" name="title" id="title" size="80" maxlength="80" value ="' . claro_htmlspecialchars($catToEdit['title']) . '" />' . "\n"
        . '</td>' . "\n"
        . '</tr>' . "\n"
        . '<tr>' . "\n"
        . '<td>' . "\n"
        . '<label for="comment" >' . get_lang('Comment') . '</label> :' . "\n"
        . '</td>' . "\n"
        . '<td>' . "\n"
        . '<textarea name="comment" id="comment" cols="60" rows="3" >' . $catToEdit['comment'] . '</textarea>' . "\n"
        . '</td>' . "\n"
        . '</tr>' . "\n"
        . '<tr>' . "\n"
        . '<td nowrap="nowrap">' . "\n"
        . '<label for="nbline" >' . get_lang('Line Number') . '</label> :' . "\n"
        . '' . "\n"
        . '</td>' . "\n"
        . '<td>' . "\n"
        . '<select name="nbline" id="nbline">' . "\n"
        ;
    
    if ($catToEdit['nbline'] && $catToEdit['nbline']!=1)
    {
        $out .= '<option value="' . $catToEdit['nbline'] . '" selected>' . $catToEdit['nbline'] . ' ' . get_lang('line(s)') . '</option>' . "\n"
        . '<option>---</option>' . "\n"
        ;
    }
    
    sort($descSizeToPrupose);
    
    $out .= '<option value="1">1 ' . get_lang('line') . '</option>' . "\n";
    
    foreach($descSizeToPrupose as $nblines)
    {
        $out .= '<option value="'.$nblines.'">'.$nblines.' '.get_lang('lines').'</option>';
    }

    $out .= '</select>' . "\n"
        . '</td>' . "\n"
        . '</tr><tr>' . "\n"
        . '<td>&nbsp;</td>' . "\n"
        . '<td align="center">' . "\n"
        . '<input type="submit" name="submitDef" value="' . get_lang('Ok') . '" />' . "\n"
        . '</td>' . "\n"
        . '</tr>' . "\n"
        . '</table>' . "\n"
        . '</form>' . "\n"
        ;
}
elseif ($displayMode == "viewDefList")
{
    /*>>>>>>>>>>>> CATEGORIES DEFINITIONS : LIST <<<<<<<<<<<<*/

    if ($catList)
    {
        foreach ($catList as $thisCat)
        {
            // displays Title and comments

            $out .= '<div class="userInfoExtraField" >' . "\n"
                . '<p>' . "\n"
                . '<b>'.htmlize($thisCat['title']).'</b><br />' . "\n"
                . '<i>'.htmlize($thisCat['comment']).'</i>' . "\n"
                . '</p>' . "\n"
                ;

            // displays lines

            $out .= '<blockquote>' . "\n"
                . '<font color="gray">' . "\n"
                ;

            for ($i=1;$i<=$thisCat['nbline'];$i++ )
            {
                $out .= '<br />__________________________________________' . "\n";
            }

            $out .= '</font>' . "\n"
                . '</blockquote>' . "\n"

                // displays commands

                . '<a href="'.claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] . '?removeDef=' . $thisCat['catId'] )) . '">'
                . '<img src="' . get_icon_url('delete') . '" alt="'.get_lang('Delete').'" />'
                . '</a>' . "\n"
                . '<a href="'.claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?editDef='.$thisCat['catId'])) . '">'
                . '<img src="' . get_icon_url('edit') . '" alt="'.get_lang('Edit').'" />'
                . '</a>' . "\n"
                . '<a href="'.claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?moveUpDef='.$thisCat['catId'])). '">'
                . '<img src="' . get_icon_url('move_up') . '" alt="'.get_lang('Move up').'" />'
                . '</a>' . "\n"
                . '<a href="'.claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?moveDownDef='.$thisCat['catId'])). '">'
                . '<img src="' . get_icon_url('move_down') . '" alt="'.get_lang('Move down').'" />'
                . '</a>' . "\n"
                . '</div>' . "\n"
                ;
        } // end for each

    } // end if ($catList)


    $out .= '<div align="center">' . "\n"
        . '<form method="post" action="'.claro_htmlspecialchars($_SERVER['PHP_SELF'].'?uInfo='.$userIdViewed).'">' . "\n"
        . claro_form_relay_context()
        . '<input type="submit" name="addDef" value="'.get_lang('Add new heading').'" />' . "\n"
        . '</form>' . "\n"
        . '</div>' . "\n"
        ;

}
elseif ($displayMode == 'viewContentEdit' )
{
    /*>>>>>>>>>>>> CATEGORIES CONTENTS : EDIT <<<<<<<<<<<<*/
    $out .= '<form method="post" action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] . '?uInfo=' . $userIdViewed ) . '">' . "\n"
        . claro_form_relay_context()
        . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' . "\n"
        . '<input type="hidden" name="cntId" value="' . $catToEdit['contentId'] . '" />' . "\n"
        . '<input type="hidden" name="catId" value="' . $catToEdit['catId'    ] . '" />' . "\n"
        . '<input type="hidden" name="uInfo"  value="' . $userIdViewed . '" />' . "\n"
        . '<p><label for="content" ><b>' . $catToEdit['title'] . '</b></label></p>' . "\n"
        . '<p><i>' . htmlize($catToEdit['comment']) . '</i></p>' . "\n"
        ;
    
    if ($catToEdit['nbline']==1)
    {
        $out .= '<input type="text" name="content" id="content" size="80" value="' . claro_htmlspecialchars($catToEdit['content']) . '" />';
    }
    else
    {
        $out .= '<textarea  cols="80" rows="' . $catToEdit['nbline'] . '" name="content" id="content" >' . $catToEdit['content'] . '</textarea>'
        ;
    }
    
    $out .= '<input type="submit" name="submitContent" value="' . get_lang('Ok') . '" />' . "\n"
        . '</form>'
        ;

}
elseif ($displayMode =="viewMainInfoEdit")
{
    if ($mainUserInfo)
    {
        $hidden_param = array ( 'submitMainUserInfo' => $userIdViewed,
        'uInfo' => $userIdViewed);
        $out .= course_user_html_form($mainUserInfo, claro_get_current_course_id(), $userIdViewed, $hidden_param);
    }
}
elseif ($displayMode == "viewContentList") // default display
{
    /*>>>>>>>>>>>> CATEGORIES CONTENTS : LIST <<<<<<<<<<<<*/

    if ($mainUserInfo)
    {
        $mainUserInfo['role'] = (!empty($mainUserInfo['role']) ? $mainUserInfo['role'] : ' - ');
        $mainUserInfo['tutor'] = ($mainUserInfo['isTutor'] == 1 ? get_lang('Group Tutor') : ' - ');
        $mainUserInfo['isCourseManager'] = ($mainUserInfo['isCourseManager'] == 1 ? get_lang('Course manager') : ' - ');

        $out .= '<table class="claroTable" width="80%" border="0">' . "\n"
            . '<thead>' . "\n"
            . '<tr class="headerX">' . "\n"
            . '<th align="left">'.get_lang('Name').'</th>' . "\n"
            . '<th align="left">'.get_lang('Profile').'</th>' . "\n"
            . '<th aling="left">'.get_lang( 'Skype account' ).'</th>' . "\n"
            . '<th align="left">'.get_lang('Role').'</th>' . "\n"
            . '<th>'.get_lang('Group Tutor').'</th>' . "\n"
            . '<th>'.get_lang('Course manager').'</th>' . "\n"
            . ($allowedToEditDef?'<th>'.get_lang('Edit').'</th>' . "\n":'')
            . ($is_allowedToTrack?"<th>".get_lang('Tracking').'</th>' . "\n":'')
            . '</tr>' . "\n"
            . '</thead>' . "\n"
            . '<tbody>' . "\n"
            . '<tr align="center">' . "\n"
            . '<td align="left"><b>'.htmlize($mainUserInfo['firstName']).' '.htmlize($mainUserInfo['lastName']).'</b></td>' . "\n"
            . '<td align="left">'.htmlize(claro_get_profile_name($mainUserInfo['profileId'])).'</td>' . "\n"
            . '<td align="center">'. $skypeStatus . '</td>' . "\n"
            . '<td>'.htmlize($mainUserInfo['role']).'</td>' . "\n"
            . '<td>'.$mainUserInfo['tutor'].'</td>'
            . '<td>'.$mainUserInfo['isCourseManager'].'</td>'
            ;

        if($allowedToEditDef)
        {
            $out .= '<td>'
                . '<a href="' . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                . '?editMainUserInfo=' . $userIdViewed )) . '">'
                . '<img alt="'.get_lang('Edit').'" src="' . get_icon_url('edit') . '" />'
                . '</a>'
                . '</td>' . "\n"
                ;
        }


        if($is_allowedToTrack)
        {
            $out .= '<td>'
                . '<a href="' . claro_htmlspecialchars(Url::Contextualize(  get_path('clarolineRepositoryWeb') . 'tracking/userReport.php'
                . '?userId=' . $userIdViewed )) . '">'
                . '<img alt="' . get_lang('Tracking') . '" src="' . get_icon_url('statistics') . '" />'
                . '</a>'
                . '</td>' . "\n"
                ;
        }

        $out .= '</tr>' . "\n"
            . '</tbody>' . "\n"
            . '</table>' . "\n\n"
            ;

        if ( claro_is_user_authenticated() || ! get_conf('user_email_hidden_to_anonymous') )
        {
            $out .= '<p><a href="mailto:'.claro_htmlspecialchars($mainUserInfo['email']).'">'.$mainUserInfo['email'].'</a></p>';
        }
        
        if(current_user_is_allowed_to_send_message_to_user($userIdViewed))
        {
            $out .= '<p><a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . '/messaging/sendmessage.php?cmd=rqMessageToUser&userId='.$userIdViewed)).'">'.get_lang('Send a message').'</a></p>';
        }

        $out .= '<hr noshade="noshade" size="1" />' . "\n" ;
    }


    if ($allowedToEditDef) // only course administrators see this line
    {
        $out .= "\n\n"
            . '<div align="right">' . "\n"
            . '<form method="post" action="'.claro_htmlspecialchars( $_SERVER['PHP_SELF'].'?uInfo='.$userIdViewed ).'">' . "\n"
            . claro_form_relay_context()
            . get_lang('Course administrator only').' : '
            . '<input type="submit" name="viewDefList" value="'.get_lang('Define Headings').'" />' . "\n"
            . '</form>' . "\n"
            . '<hr noshade="noshade" size="1" />' . "\n"
            . '</div>'
            ;
    }

    $catList = claro_user_info_get_course_user_info($userIdViewed);
    
    if ($catList)
    {
        foreach ($catList as $thisCat)
        {
            // Category title

            $out .= '<p>' . "\n"
                . '<b>' . $thisCat['title'] . '</b>' . "\n"
                . '</p>' . "\n"
                . '<blockquote>' . "\n"
                ;
            // Category content

            if ($thisCat['content']) $out .= htmlize($thisCat['content'])."\n";
            else                     $out .= '....';

            // Edit command

            if ($allowedToEditContent)
            {
                $out .= '<br /><br />' . "\n"
                    . '<a href="' . claro_htmlspecialchars(Url::Contextualize(  $_SERVER['PHP_SELF']
                    . '?editContent=' . $thisCat['catId']
                    . '&uInfo=' . $userIdViewed )) . '">'
                    . '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Edit') . '" />'
                    . '</a>' . "\n"
                    ;
            }

            $out .= '</blockquote>' . "\n";
        }
    }
}

// Back button for each display mode (bottom)
$out .= '<p>' . "\n"
    . '<small>' . "\n"
    . '<a href="'. claro_htmlspecialchars(Url::Contextualize( get_module_entry_url('CLUSR') )) . '">'
    . '&lt;&lt;&nbsp;'
    . get_lang('Back to user list')
    . '</a>' . "\n"
    . '</small>' . "\n"
    . '</p>' . "\n"
    ;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

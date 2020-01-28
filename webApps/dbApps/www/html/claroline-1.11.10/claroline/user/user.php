<?php // $Id: user.php 14684 2014-02-11 10:00:41Z zefredz $

/**
 * CLAROLINE
 *
 * Management tools for the users of a specific course.
 *
 * @version     1.11 $Revision: 14684 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2.0
 * @package     CLUSR
 */

/*=====================================================================
   Initialisation
  =====================================================================*/
$tlabelReq = 'CLUSR';
$gidReset = true;

require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

claro_set_display_mode_available(true);

/*----------------------------------------------------------------------
   Include Library
  ----------------------------------------------------------------------*/

require_once get_path('incRepositorySys')  . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys')  . '/lib/user.lib.php';
require_once get_path('incRepositorySys')  . '/lib/course_user.lib.php';
require_once get_path('incRepositorySys')  . '/lib/pager.lib.php';
require_once dirname(__FILE__) . '/../messaging/lib/permission.lib.php';

/*----------------------------------------------------------------------
   Load config
  ----------------------------------------------------------------------*/
include claro_get_conf_repository() . 'user_profile.conf.php';

/*----------------------------------------------------------------------
   JavaScript - Delete Confirmation
  ----------------------------------------------------------------------*/

JavascriptLanguage::getInstance()->addLangVar('Are you sure to delete %name ?');

JavascriptLoader::getInstance()->load('user');

/*----------------------------------------------------------------------
   Variables
  ----------------------------------------------------------------------*/

$userPerPage = get_conf('nbUsersPerPage',50);

$is_allowedToEdit = claro_is_allowed_to_edit();

$can_add_single_user = (bool) (claro_is_course_manager()
                     && get_conf('is_coursemanager_allowed_to_enroll_single_user') )
                     || claro_is_platform_admin();

$can_import_user_list = (bool) (claro_is_course_manager()
                     && get_conf('is_coursemanager_allowed_to_import_user_list') )
                     || claro_is_platform_admin();

$can_export_user_list = (bool) (claro_is_course_manager()
                     && get_conf('is_coursemanager_allowed_to_export_user_list', true) )
                     || claro_is_platform_admin();

$can_import_user_class = (bool) (claro_is_course_manager()
                     && get_conf('is_coursemanager_allowed_to_import_user_class') )
                     || claro_is_platform_admin();

$can_send_message_to_course = current_user_is_allowed_to_send_message_to_current_course();

$dialogBox = new DialogBox();

/*----------------------------------------------------------------------
  DB tables definition
  ----------------------------------------------------------------------*/

$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_mdb_names = claro_sql_get_main_tbl();

$tbl_rel_course_user = $tbl_mdb_names['rel_course_user'  ];
$tbl_courses         = $tbl_mdb_names['course'           ];
$tbl_users           = $tbl_mdb_names['user'             ];
$tbl_right_profile   = $tbl_mdb_names['right_profile'];
$tbl_courses_users   = $tbl_rel_course_user;

$tbl_rel_users_groups= $tbl_cdb_names['group_rel_team_user'    ];
$tbl_groups          = $tbl_cdb_names['group_team'             ];

/*----------------------------------------------------------------------
  Filter data
  ----------------------------------------------------------------------*/

$cmd = ( isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '');
$offset = (int) isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;

if (isset($_REQUEST['user_id']))
{
    if ($_REQUEST['user_id'] == 'allStudent'
                    &&  $cmd == 'unregister' ) $req['user_id'] = 'allStudent';
    elseif ( 0 < (int) $_REQUEST['user_id'] )  $req['user_id'] = (int) $_REQUEST['user_id'];
    else                                       $req['user_id'] = false;
}

if ( $cmd == 'unregister' )
{
    if ( isset( $_REQUEST['deleteClasses'] ) )
    {
        $req['keepClasses'] = false;
    }
    else
    {
        $req['keepClasses'] = true;
    }
}

/*=====================================================================
  Main section
  =====================================================================*/

$disp_tool_link = false;

if ( $is_allowedToEdit )
{
    $disp_tool_link = true;
    
    // Register a new user
    if ( $cmd == 'register' && $req['user_id'])
    {
        $done = user_add_to_course($req['user_id'], claro_get_current_course_id(), false, false, null);

        if ($done)
        {
            Console::log( "{$req['user_id']} subscribe to course ". claro_get_current_course_id(), 'COURSE_SUBSCRIBE');
            $dialogBox->success( get_lang('User registered to the course') );
        }
    }
    
    // Unregister a user
    if ( $cmd == 'unregister')
    {
        $forceUnenrolment = false;
            
        if ( claro_is_platform_admin () )
        {
            if ( isset($_REQUEST['force'] ) && $_REQUEST['force'] == '1' )
            {
                $forceUnenrolment = true;
            }
        }
            
        // Unregister user from course
        // (notice : it does not delete user from claroline main DB)
        
        if ( 'allStudent' == $req['user_id'] )
        {
            // TODO : add a function to unenroll all users from a course
            $course = new Claro_Course( claro_get_current_course_id() );
            $course->load();
            
            $claroCourseRegistration = new Claro_BatchCourseRegistration( $course );
            $claroCourseRegistration->removeAllUsers( $req['keepClasses'] );
            
            $result = $claroCourseRegistration->getResult();
            
            if ( !$result->hasError() || !$result->checkStatus( Claro_BatchRegistrationResult::STATUS_ERROR_DELETE_FAIL ) )
            {
                $unregisterdUserCount = count($result->getDeletedUserList());

                if ( $unregisterdUserCount )
                {
                    Console::log( "{$req['user_id']} ({$unregisterdUserCount}) removed by user ". claro_get_current_user_id(), 'COURSE_UNSUBSCRIBE');                 
                }

                $dialogBox->info( get_lang('%number student(s) unregistered from this course', array ( '%number' => $unregisterdUserCount) ) );
            }
            else
            {
                Console::error("Error while deleting all users from course " . claro_get_current_course_id() . " : " . var_export( $result->getErrorLog(), true ) );
                
                $dialogBox->error( get_lang('An error occured') . ' : <ul><li>' . implode('</li><li>', $result->getErrorLog() ) . '</li></ul>' );
            }
        }
        elseif ( 0 < (int)  $req['user_id'] )
        {
            if ( $forceUnenrolment )
            {
                $course = new Claro_Course( claro_get_current_course_id () );
                $course->load();

                $userCourseRegistration = new Claro_CourseUserRegistration(
                    AuthProfileManager::getUserAuthProfile($req['user_id'] ),
                    $course
                );

                if ( claro_is_platform_admin () )
                {
                    $userCourseRegistration->forceUnregistrationOfManager();
                }


                if ( !$userCourseRegistration->forceRemoveUser( false, array() ) )
                {
                    $dialogBox->error( get_lang('The user cannot be removed from the course') );
                }
                else
                {
                    Console::log( "{$req['user_id']} removed [forced] by admin ". claro_get_current_user_id(), 'COURSE_UNSUBSCRIBE');
                    $dialogBox->success( get_lang('The user has been successfully unregistered from course') );
                }
            }
            else
            {
                // delete user from course user list
                if ( user_remove_from_course(  $req['user_id'], claro_get_current_course_id(), false, false, null) )
                {
                    Console::log( "{$req['user_id']} removed by user ". claro_get_current_user_id(), 'COURSE_UNSUBSCRIBE');
                    $dialogBox->success( get_lang('The user has been successfully unregistered from course') );
                }
                else
                {
                    switch ( claro_failure::get_last_failure() )
                    {
                        case 'cannot_unsubscribe_the_last_course_manager' :
                            $dialogBox->error( get_lang('You cannot unsubscribe the last course manager of the course') );
                            break;
                        case 'course_manager_cannot_unsubscribe_himself' :
                            $dialogBox->error( get_lang('Course manager cannot unsubscribe himself') );
                            break;
                        default :
                            $dialogBox->error( get_lang('Error!! you cannot unregister a course manager') );
                    }
                }
            }
        }
    } // end if cmd == unregister
    
    // Export users list
    if( $cmd == 'export' && $can_export_user_list )
    {
        require_once( dirname(__FILE__) . '/lib/export.lib.php');
        
        // contruction of XML flow
        $csv = export_user_list(claro_get_current_course_id());
        
        if( !empty($csv) )
        {
            $courseData = claro_get_current_course_data();
            claro_send_stream( $csv, $courseData[ 'officialCode' ] .'_userlist.csv');
            exit;
        }
    }
    
    // Validate a user (if this option is enable for the course)
    if ( $cmd == 'validation' && $req['user_id'])
    {
        $courseUserPrivileges = new CourseUserPrivileges(  claro_get_current_course_id (), $req['user_id'] );
        $courseUserPrivileges->load();
        
        $courseObject = new Claro_Course(  claro_get_current_course_id ());
        $courseObject->load();
        
        $validation = new UserCourseEnrolmentValidation( 
            $courseObject, 
            $courseUserPrivileges 
        );
        
        $validationChange = isset($_REQUEST['validation']) ? $_REQUEST['validation'] : null;
        
        if ( $validation->isModifiable() )
        {
            if ( 'grant' == $validationChange && $validation->isPending() )
            {
                if ( $validation->grant() )
                {
                    $dialogBox->success( get_lang('This user account is now active in the course') );
                }
                else
                {
                    $dialogBox->warning( get_lang('No change') );
                }
            }
            elseif( 'revoke' == $validationChange && !$validation->isPending() )
            {
                if ( $validation->revoke() )
                {
                    $dialogBox->success( get_lang('This user account is not active anymore in this course') );
                }
                else
                {
                    $dialogBox->warning( get_lang('No change') );
                }
            }
            else
            {
                $dialogBox->warning( get_lang('No change') );
            }
        }
        else
        {
            $dialogBox->error( get_lang('The user activation cannot be changed') );
            
            if ( $courseUserPrivileges->isCourseManager () )
            {
                $dialogBox->error( get_lang('You have to remove the course manager status first') );
            }
        }
    }
}    // end if allowed to edit

$courseUserList = new Claro_CourseUserList(claro_get_current_course_id());
        
if ( $courseUserList->has_registrationPending () )
{
    $usersPanelUrl = claro_htmlspecialchars(Url::Contextualize( get_module_entry_url ( 'CLUSR' ) ) );
    
    $dialogBox->warning(
        get_lang('You have to validate users to give them access to this course through the <a href="%url">course user list</a>', array('%url' => $usersPanelUrl))
    );
}


/*----------------------------------------------------------------------
   Get Course informations
  ----------------------------------------------------------------------*/

$sql = "SELECT `course`.`registration`
        FROM `" . $tbl_courses . "` AS course
        WHERE `course`.`code`='" . claro_sql_escape(claro_get_current_course_id()) . "'";

$course = claro_sql_query_get_single_row($sql);


/*----------------------------------------------------------------------
   Get User List
  ----------------------------------------------------------------------*/

$sqlGetUsers = "
    SELECT 
        `user`.`user_id`      AS `user_id`,
        `user`.`nom`          AS `nom`,
        `user`.`prenom`       AS `prenom`,
        `user`.`email`        AS `email`,
        `course_user`.`profile_id`,
        `course_user`.`isCourseManager`,
        `course_user`.`isPending`,
        `course_user`.`tutor`  AS `tutor`,
        `course_user`.`role`   AS `role`,
        `course_user`.`enrollment_date`,
        `course_user`.`count_class_enrol`,
        `course_user`.`count_user_enrol`,

	GROUP_CONCAT(`grp`.name ORDER BY `grp`.name SEPARATOR ',' ) AS `groups`

    FROM 
        
            `{$tbl_users}` AS user,
            `{$tbl_rel_course_user}` AS course_user

    LEFT JOIN `{$tbl_rel_users_groups}` AS user_group
    ON user_group.user = `course_user`.`user_id`

    LEFT JOIN `{$tbl_groups}` AS `grp`
    ON `grp`.id = user_group.team

    WHERE ( `user`.`user_id`=`course_user`.`user_id`
    AND   `course_user`.`code_cours`='" . claro_sql_escape(claro_get_current_course_id()) . "' )

    GROUP BY user.user_id";

$myPager = new claro_sql_pager($sqlGetUsers, $offset, $userPerPage);

if ( isset($_GET['sort']) )
{
    $myPager->add_sort_key( $_GET['sort'], isset($_GET['dir']) ? $_GET['dir'] : SORT_ASC );
}

$defaultSortKeyList = array ('course_user.isCourseManager' => SORT_DESC,
                             'course_user.tutor'  => SORT_DESC,
                             'user.nom'          => SORT_ASC,
                             'user.prenom'       => SORT_ASC,
                             'groups'       => SORT_ASC,
                             'enrollment_date' => SORT_ASC );

foreach($defaultSortKeyList as $thisSortKey => $thisSortDir)
{
    $myPager->add_sort_key( $thisSortKey, $thisSortDir);
}

$userList    = $myPager->get_result_list();
$userTotalNb = $myPager->get_total_item_count();


/*----------------------------------------------------------------------
  Prepare display
  ----------------------------------------------------------------------*/

$nameTools = get_lang('Users');

// Command list
$cmdList = array();
$advancedCmdList = array();

if ($is_allowedToEdit)
{
    if ($can_add_single_user)
    {
    
        // Add a user link
        $cmdList[] = array(
            'img' => 'user',
            'name' => get_lang('Add a user'),
            'url' => claro_htmlspecialchars(Url::Contextualize(get_module_url('CLUSR') . '/user_add.php'))
        );
    }
    
    if ($can_import_user_list)
    {
        // Add CSV file of user link
        $advancedCmdList[] = array(
            'img' => 'import_list',
            'name' => get_lang('Add a user list'),
            'url' => claro_htmlspecialchars(Url::Contextualize(get_module_url('CLUSR')
                .'/addcsvusers.php?addType=userTool'))
        );
    }
    
    if ($can_export_user_list)
    {
        // Export CSV file of user link
        $advancedCmdList[] = array(
            'img' => 'export',
            'name' => get_lang('Export user list'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=export'))
        );
    }
    
    if ($can_import_user_class)
    {
        // Add a class link
        $advancedCmdList[] = array(
            'img' => 'class',
            'name' => get_lang('Enrol class'),
            'url' => claro_htmlspecialchars(Url::Contextualize(get_module_url('CLUSR')
                . '/class_add.php'))
        );
    }
    
    if ($can_send_message_to_course)
    {
        // Main group settings
        $cmdList[] = array(
            'img' => 'mail_send',
            'name' => get_lang("Send a message to the course"),
            'url' => claro_htmlspecialchars(Url::Contextualize(get_path('clarolineRepositoryWeb')
                . 'messaging/sendmessage.php?cmd=rqMessageToCourse'))
        );
    }
    
    $advancedCmdList[] = array(
        'img' => 'group',
        'name' => get_lang('Group management'),
        'url' => claro_htmlspecialchars(Url::Contextualize(get_module_entry_url('CLGRP')))
    );
    
    $cmdList[] = array(
        'img' => 'unenroll',
        'name' => get_lang('Unregister all students'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
            . '?cmd=unregister&user_id=allStudent')),
        'params' => array('onclick' => 'return confirmationUnregisterAll();')
    );
    
    $courseObj = new Claro_Course(claro_get_current_course_id());
    $courseObj->load();
    $courseClassList = new Claro_CourseClassList($courseObj);
    $courseClassListIt = $courseClassList->getClassListIterator();
    
    if ( count($courseClassListIt) )
    {
        $htmlHeadXtra[] = '<script>var mustConfirmClassDelete = true;</script>';
    }
    else
    {
        $htmlHeadXtra[] = '<script>var mustConfirmClassDelete = false;</script>';
    }
    
    
    $htmlHeadXtra[] =
    '<script type="text/javascript">

    function confirmationUnregisterAll ()
    {
        if (confirm(\'' . clean_str_for_javascript( get_lang( "Are you sure you want to unregister all students from your course ?")) . '\'))
        {
            if ( mustConfirmClassDelete && confirm(\'' . clean_str_for_javascript( get_lang( "Do you also want to unregister all classes from your course ?")) . '\') )
            {
                document.location.href = \''.Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=unregister&user_id=allStudent&deleteClasses=1').'\'; 
                    return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return false;
        }
    };

    </script>'."\n";
    
    $htmlHeadXtra[] =
    '<script type="text/javascript">

    function warnCannotDeleteClassStudent ()
    {
        alert(\'' . clean_str_for_javascript( get_lang( "This student is enroled from a class and cannot be removed directly from the course. You have to delete the whole class instead")) . '\');
        return false;
    };

    </script>'."\n";
}

if ( get_conf('allow_profile_picture', true) )
{
    $cmdList[] = array(
        'img' => 'picture',
        'name' => get_lang('Users\' pictures'),
        'url' => claro_htmlspecialchars(Url::Contextualize(get_path('clarolineRepositoryWeb')
            . 'user/user_pictures.php'))
    );
}

// Tool name
$titleParts = array(
    'mainTitle' => $nameTools,
    'subTitle' => '(' . get_lang('number') . ' : ' . $userTotalNb . ')'
);

// Help url
$helpUrl = $is_allowedToEdit ? get_help_page_url('blockUsersHelp', 'CLUSR') : null;

/*=====================================================================
Display section
  =====================================================================*/

$out = '';
$out .= claro_html_tool_title($titleParts, $helpUrl, $cmdList, $advancedCmdList ); //, 3);

// Display Forms or dialog box (if needed)
$out .= $dialogBox->render();


/*----------------------------------------------------------------------
   Display pager
  ----------------------------------------------------------------------*/

$out .= $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);

$sortUrlList = $myPager->get_sort_url_list($_SERVER['PHP_SELF']);


/*----------------------------------------------------------------------
   Display table header
  ----------------------------------------------------------------------*/

$out .= '<table class="claroTable emphaseLine" width="100%" cellpadding="2" cellspacing="1" '
    . ' border="0" summary="' . get_lang('Course users list') . '">' . "\n"
    ;

$out .= '<thead>' . "\n"
    . '<tr class="headerX" align="center" valign="top">'."\n"
    . '<th><a href="' . claro_htmlspecialchars(Url::Contextualize($sortUrlList['nom'])) . '">' . get_lang('Last name') . '</a></th>' . "\n"
    . '<th><a href="' . claro_htmlspecialchars(Url::Contextualize($sortUrlList['prenom'])) . '">' . get_lang('First name') . '</a></th>'."\n"
    . '<th><a href="' . claro_htmlspecialchars(Url::Contextualize($sortUrlList['profile_id'])) . '">' . get_lang('Profile') . '</a></th>'."\n"
    . '<th><a href="' . claro_htmlspecialchars(Url::Contextualize($sortUrlList['role'])) . '">' . get_lang('Role') . '</a></th>'."\n"
    . '<th><a href="' . claro_htmlspecialchars(Url::Contextualize($sortUrlList['groups'])) . '">' . get_lang('Group') . '</a></th>'."\n"
    ;

if ( $is_allowedToEdit ) // EDIT COMMANDS
{
    $out .= '<th><a href="'.claro_htmlspecialchars(Url::Contextualize($sortUrlList['enrollment_date'])).'">'.get_lang('Enrollment date').'</a></th>'
        . '<th><a href="'.claro_htmlspecialchars(Url::Contextualize($sortUrlList['tutor'])).'">'.get_lang('Group Tutor').'</a></th>'."\n"
        . '<th><a href="'.claro_htmlspecialchars(Url::Contextualize($sortUrlList['isCourseManager'])).'">'.get_lang('Course manager').'</a></th>'."\n"
        . '<th>'.get_lang('Edit').'</th>'."\n"
        . '<th>'.get_lang('Unregister').'</th>'."\n"
        . '<th>'.get_lang('Activation').'</th>'."\n" 
        ;
    
    if ( claro_is_platform_admin () )
    {
        $out .= '<th>'.get_lang('User profile').'</th>' . "\n";
    }
}

$out .= '</tr>'."\n"
    . '</thead>'."\n"
    . '<tbody>'."\n"
    ;

   
/*----------------------------------------------------------------------
   Display users
  ----------------------------------------------------------------------*/

$i = $offset;
$previousUser = -1;

reset($userList);

foreach ( $userList as $thisUser )
{
    // Username column
    $i++;
    $out .= '<tr align="center" valign="top">'."\n"
        . '<td align="left">'
        . '<img src="' . get_icon_url('user') . '" alt="" />'."\n"
        . '<small>' . $i . '</small>'."\n"
        . '&nbsp;'
        ;
    
    if ( $is_allowedToEdit || get_conf('linkToUserInfo') )
    {
        $out .= '<a href="'.claro_htmlspecialchars(Url::Contextualize( get_module_url('CLUSR') . '/userInfo.php?uInfo=' . (int) $thisUser['user_id'] )) . '">'
            . claro_htmlspecialchars( ucfirst(strtolower($thisUser['nom'])) )
            . '</a>'
            ;
    }
    else
    {
        $out .= claro_htmlspecialchars( ucfirst(strtolower($thisUser['nom']) ) );
    }

    $out .= '</td>'
        . '<td align="left">' . claro_htmlspecialchars( $thisUser['prenom'] ) . '</td>'
        // User profile column
        . '<td align="left">'
        . claro_get_profile_name($thisUser['profile_id'])
        . '</td>' . "\n"
        ;

    // User role column
    if ( empty($thisUser['role']) )    // NULL and not '0' because team can be inexistent
    {
        $out .= '<td> - </td>'."\n";
    }
    else
    {
        $out .= '<td>'.claro_htmlspecialchars( $thisUser['role'] ).'</td>'."\n";
    }
    
    if ( empty($thisUser['groups']) )
    {
        $out .= '<td> - </td>'."\n";
    }
    else
    {
        $out .= '<td>'.  claro_htmlspecialchars($thisUser['groups']).'</td>'."\n";
    }

    if ($previousUser == $thisUser['user_id'])
    {
        $out .= '<td>&nbsp;</td>'."\n";
    }
    elseif ( $is_allowedToEdit )
    {
        if ( !empty( $thisUser['enrollment_date'] ) )
        {
            $out .= '<td>' . claro_html_localised_date('%a %d %b %Y', strtotime( $thisUser['enrollment_date'] ) ) . '</td>' . "\n";
        }
        else
        {
            $out .= '<td>&nbsp;-&nbsp;</td>';
        }
        
        // Tutor column
        if($thisUser['tutor'] == '0')
        {
            $out .= '<td> - </td>' . "\n";
        }
        else
        {
            $out .= '<td>' . get_lang('Group Tutor') . '</td>' . "\n";
        }

        // course manager column
        if($thisUser['isCourseManager'] == '1')
        {
            $out .= '<td>' . get_lang('Course manager') . '</td>' . "\n";
        }
        else
        {
            $out .= '<td> - </td>' . "\n";
        }

        // Edit user column
        $out .= '<td>'
            . '<a href="' . claro_htmlspecialchars(Url::Contextualize( get_module_url('CLUSR') . '/userInfo.php?editMainUserInfo='.$thisUser['user_id']))
            . '">'
            . '<img alt="'.get_lang('Edit').'" src="' . get_icon_url('edit') . '" />'
            . '</a>'
            . '</td>' . "\n"
            // Unregister user column
            . '<td>'
            ;
        
        if ($thisUser['user_id'] != claro_get_current_user_id())
        {
            if ( (int)$thisUser['count_class_enrol'] > 0 )
            {
                $out .= '<a href="javascript:warnCannotDeleteClassStudent()">'
                    . '<img alt="' . get_lang('class enrolment') . '" 
                        title="'.get_lang('This student is enroled from a class and cannot be removed directly from the course. You have to delete the whole class instead').'" 
                        src="' . get_icon_url('unenroll_disabled') . '" />'
                    . '</a>'
                    ;
                
                
                if ( claro_is_platform_admin () )
                {
                    $out .= '&nbsp;<a href="'.claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                        . '?cmd=unregister&force=1&user_id=' . $thisUser['user_id'] )) . '&offset='.$offset . '" '
                        . 'onclick="return CLUSR.confirmation(\''.clean_str_for_javascript($thisUser['nom'].' '.$thisUser['prenom']).'\');">'
                        . '<img alt="' . get_lang('Force unenrolment') . '" 
                            title="'.get_lang('Force unenrolment').'" 
                            src="' . get_icon_url('unenroll') . '" />'
                        . '</a>'
                        ;
            }
            }
            else
            {
                $out .= '<a href="'.claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                    . '?cmd=unregister&user_id=' . $thisUser['user_id'] )) . '&offset='.$offset . '" '
                    . 'onclick="return CLUSR.confirmation(\''.clean_str_for_javascript($thisUser['nom'].' '.$thisUser['prenom']).'\');">'
                    . '<img alt="' . get_lang('Unregister') . '" src="' . get_icon_url('unenroll') . '" />'
                    . '</a>'
                    ;
            }
        }
        else
        {
            $out .= '&nbsp;';
        }
        
        $out .= '</td>' . "\n";
        
        // User's validation column
        $out .= '<td>' . "\n";
        
        if ($thisUser['user_id'] != claro_get_current_user_id())
        {
            $icon = '';
            $tips = '';
            
            if ($thisUser['isPending'])
            {
                $icon = 'untick';
                $tips = 'Click to make this user active in this course';
                $validationChangeAction = 'grant';
            }
            else
            {
                $icon = 'tick';
                $tips = 'Click to make this user inactive in this course';
                $validationChangeAction = 'revoke';
            }
            
            $out .= '<a href="'.claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                . '?cmd=validation&user_id=' . $thisUser['user_id'] )). '&validation='.$validationChangeAction . '&offset='.$offset . '" '
                . ' title="'.get_lang($tips).'">'
                . '<img alt="' . get_lang('Validation') . '" src="' . get_icon_url($icon) . '" />'
                . '</a>'
                ;
        }
        else
        {
            $out .= '&nbsp;';
        }
        
        $out .= '</td>' . "\n";
        
        if ( claro_is_platform_admin () )
        {
            $out .= '<td><a href="'.claro_htmlspecialchars(get_path('url')
                . '/claroline/admin/admin_profile.php?uidToEdit=' . $thisUser['user_id'] ). '&cfrom=culist&cid='.  claro_get_current_course_id ().'&cidReset=true&cidReq=">'
                . '<img alt="' . get_lang('User profile') . '" src="' . get_icon_url('usersetting') . '" />'
                . '</a></td>'
                ;
        }
        
    }  // END - is_allowedToEdit

    $out .= '</tr>'."\n";

    $previousUser = $thisUser['user_id'];

} // END - foreach users


/*----------------------------------------------------------------------
   Display table footer
  ----------------------------------------------------------------------*/

$out .= '</tbody>' . "\n"
. '</table>' . "\n"
;

$out .= $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);


$claroline->display->body->appendContent($out);

echo $claroline->display->render();

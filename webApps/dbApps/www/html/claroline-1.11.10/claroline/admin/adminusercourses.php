<?php // $Id: adminusercourses.php 14450 2013-05-15 12:02:23Z zefredz $

/**
 * CLAROLINE
 *
 * Management tools for users subscriptions.
 *
 * @version     $Revision: 14450 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLADMIN
 * @author      Claro Team <cvs@claroline.net>
 * @author      Guillaume Lederer <guim@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 */

$cidReset = true;
$gidReset = true;
$tidReset = true;

require '../inc/claro_init_global.inc.php';
include_once get_path('incRepositorySys') . '/lib/user.lib.php';
include_once get_path('incRepositorySys') . '/lib/course_user.lib.php';
include_once get_path('incRepositorySys') . '/lib/pager.lib.php';
include_once get_path('incRepositorySys') . '/lib/courselist.lib.php';
include claro_get_conf_repository() . 'user_profile.conf.php';

$dialogBox = new DialogBox();

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

// Filter input
$validCmdList = array('unsubscribe','rqRmAll');
$cmd = (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$validCmdList)? $_REQUEST['cmd'] : null);

$validRefererList = array('ulist',);
$cfrom = (isset($_REQUEST['cfrom']) && in_array($_REQUEST['cfrom'],$validRefererList) ? $_REQUEST['cfrom'] : null);

$uidToEdit = (int) (isset($_REQUEST['uidToEdit']) ?  $_REQUEST['uidToEdit'] : null);
$courseId = (isset($_REQUEST['courseId'])?$_REQUEST['courseId']:null);
$do = null;

// Filter input for pading/sorting : $offset, $sort, $dir
$offset = (int) (!isset($_REQUEST['offset'])) ? 0 :  $_REQUEST['offset'];
$pagerSortKey = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'name';
$pagerSortDir = isset($_REQUEST['dir' ]) ? $_REQUEST['dir' ] : SORT_ASC;


// Parse command
$userData = user_get_properties($uidToEdit);

if ((false === $userData) || $uidToEdit != $userData['user_id']) $dialogBox->error( get_lang('Not valid user id') );

if ('unsubscribe' == $cmd)
{
    if (is_null($courseId)) $dialogBox->error( get_lang('Not valid course code') );
    else                    $do = 'rem_user';
}

if ( 'rqRmAll' == $cmd)
{
    $courseList = claro_get_user_course_list($uidToEdit);
    $ok = true;
    foreach ($courseList as $course)
    {
        if ( !user_remove_from_course($uidToEdit,$course['sysCode'],true,false) )
        {
            $ok = false;
            $dialogBox->error( get_lang('The user has not been successfully unregistered for course '. $course['sysCode']) );
        }
    }
    if ($ok)
    {
        $dialogBox->success( get_lang('The user has been successfully unregistered for all courses') );
    }
}
    
// Execute command
if ('rem_user' == $do )
{
    if ( user_remove_from_course($uidToEdit,$courseId,true,false) )
    {
        $dialogBox->success( get_lang('The user has been successfully unregistered') );
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
                $dialogBox->error( get_lang('Unknow error during unsubscribing') );
        }
    }
}

$addToUrl = ('ulist' == $cfrom ? '&amp;cfrom=ulist' : '');

$sqlUserCourseList = prepare_sql_get_courses_of_a_user($uidToEdit);

$myPager = new claro_sql_pager($sqlUserCourseList, $offset, get_conf('coursePerPage', 20));
$myPager->set_sort_key($pagerSortKey, $pagerSortDir);

$userCourseList = $myPager->get_result_list();
$userCourseGrid = array();

foreach ($userCourseList as $courseKey => $course)
{
    $userCourseGrid[$courseKey]['officialCode'] = $course['officialCode'];

    $iconUrl = get_course_access_icon( $course['access'] );

    $userCourseGrid[$courseKey]['name'] = '<img class="iconDefinitionList" src="' . $iconUrl . '" alt="" />'
                                        . '<a href="'. get_path( 'clarolineRepositoryWeb' ) . 'course/index.php?cid=' . claro_htmlspecialchars( $course['sysCode'] ) . '">' . $course['name']. '</a><br />' . $course['titular'];


    $userCourseGrid[$courseKey]['profileId'] = claro_get_profile_name($course['profileId']);

    if ( $course['isCourseManager'] )
    {
        $userCourseGrid[$courseKey]['isCourseManager'] = '<img class="qtip" src="' . get_icon_url('manager') . '" alt="' . get_lang('Course manager') . '" />';
    }
    else
    {
        $userCourseGrid[$courseKey]['isCourseManager'] = '<img class="qtip" src="' . get_icon_url('user') . '" alt="' . get_lang('Student') . '" />';
    }

    $userCourseGrid[$courseKey]['edit_course_user'] = '<a href="admin_user_course_settings.php?cidToEdit='.$course['sysCode'].'&amp;uidToEdit='.$uidToEdit.'&amp;ccfrom=uclist">'
    .                                                 '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Course manager') . '" title="' . get_lang('User\'s course settings') . '" />'
    .                                                 '</a>'
    ;

    $userCourseGrid[$courseKey]['delete'] = '<a href="' . $_SERVER['PHP_SELF']
    .                                       '?uidToEdit=' . $uidToEdit
    .                                       '&amp;cmd=unsubscribe'
    .    $addToUrl
    .    '&amp;courseId=' . claro_htmlspecialchars($course['sysCode'])
    .    '&amp;sort=' . $pagerSortKey . '&amp;dir='.$pagerSortDir
    .    '&amp;offset=' . $offset . '"'
    .    ' onclick="return ADMIN.confirmationUnReg(\''.clean_str_for_javascript($userData['firstname'] . ' ' . $userData['lastname']).'\');">' . "\n"
    .    '<img src="' . get_icon_url('unenroll') . '" alt="' . get_lang('Delete') . '" />' . "\n"
    .    '</a>' . "\n"
    ;
}

$sortUrlList = $myPager->get_sort_url_list($_SERVER['PHP_SELF'].'?uidToEdit='. $uidToEdit);

$userCourseDataGrid = new claro_datagrid();
$userCourseDataGrid->set_grid($userCourseGrid);

// extended setting for this datagrid
$userCourseDataGrid->set_colTitleList(array (
'officialCode'     => '<a href="' . $sortUrlList['officialCode'] . '">' . get_lang('Course code') . '</a>'
,'name'     => '<a href="' . $sortUrlList['name'] . '">' . get_lang('Course title') . '</a>'
,'profileId'  => '<a href="' . $sortUrlList['profileId'] . '">' . get_lang('User profile') . '</a>'
,'isCourseManager' => '<a href="' . $sortUrlList['isCourseManager'] . '">' . get_lang('Role') . '</a>'
,'edit_course_user' => get_lang('Edit settings') . '</a>'
,'delete'   => get_lang('Unregister user')
));

if ( 0 == count($userCourseGrid)  )
{
    $userCourseDataGrid->set_noRowMessage( get_lang('No course to display') );
}
else
{
    $userCourseDataGrid->set_colAttributeList(array ( 'officialCode' => array ('align' => 'left')
    , 'name'                => array ('align' => 'left')
    , 'isCourseManager'     => array ('align' => 'center')
    , 'edit_course_user'    => array ('align' => 'center')
    , 'delete'              => array ('align' => 'center')
    ));
}

// Initialisation of global variables and used libraries
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('User course list');

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance()->addLangVar('Are you sure you want to unregister %name ?');
JavascriptLanguage::getInstance()->addLangVar('Are you sure you want to unregister %name for all courses?');

JavascriptLoader::getInstance()->load('admin');

// Command list
$cmdList[] = array(
    'img' => 'usersetting',
    'name' => get_lang('User settings'),
    'url' => 'admin_profile.php?uidToEdit=' . $uidToEdit
);

$cmdList[] = array(
    'img' => 'course',
    'name' => get_lang('Enrol to a new course'),
    'url' => '../auth/courses.php?cmd=rqReg&amp;uidToEdit=' . $uidToEdit . '&amp;category=&amp;fromAdmin=usercourse'
);

$cmdList[] = array(
    'img' => 'delete',
    'name' => get_lang('Unregister for all courses'),
    'url' => $_SERVER['PHP_SELF'] . '?cmd=rqRmAll&amp;uidToEdit=' . $uidToEdit, 
    'params' => array('onclick' => "return ADMIN.confirmationUnRegForAllCourses('".clean_str_for_javascript($userData['firstname'] . " " . $userData['lastname'])."');")
);


if ( 'ulist' == $cfrom )  //if we come from user list, we must display go back to list
{
    $cmdList[] = array(
        'img' => 'back',
        'name' => get_lang('Back to user list'),
        'url' => 'admin_users.php'
    );
}

// Display
$out = '';

$titleParts = array(
    'mainTitle' => $nameTools,
    'subTitle' => $userData['firstname'] . ' ' . $userData['lastname']);

$out .= claro_html_tool_title($titleParts, null, $cmdList);

// Display forms and dialogBox, alphabetic choice,...
$out .= $dialogBox->render();

$out .= $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'] . '?uidToEdit=' . $uidToEdit)
      . $userCourseDataGrid->render()
      . $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'] . '?uidToEdit=' . $uidToEdit) ;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();



/**
 * prepare sql to get a list of course of a given user
 *
 * @param integer $userId id of you to fetch courses
 * @return string : mysql statement
 */
function prepare_sql_get_courses_of_a_user($userId=null)
{
    if (is_null($userId)) $userId = claro_get_current_user_id();
    $tbl_mdb_names       = claro_sql_get_main_tbl();
    $tbl_course          = $tbl_mdb_names['course'];
    $tbl_rel_course_user = $tbl_mdb_names['rel_course_user' ];

    $sql = "SELECT `C`.`code`              AS `sysCode`,
                   `C`.`intitule`          AS `name`,
                   `C`.`administrativeNumber` AS `officialCode`,
                   `C`.`directory`            AS `path`,
                   `C`.`dbName`               AS `dbName`,
                   `C`.`titulaires`           AS `titular`,
                   `C`.`email`                AS `email`,
                   `C`.`language`             AS `language`,
                   `C`.`extLinkUrl`           AS `extLinkUrl`,
                   `C`.`extLinkName`          AS `extLinkName`,
                   `C`.`visibility`           AS `visibility`,
                   `C`.`access`               AS `access`,
                   `C`.`registration`         AS `registration`,
                   `C`.`registrationKey`      AS `registrationKey` ,
                   `CU`.`profile_id`          AS `profileId`,
                   `CU`.`isCourseManager`,
                   `CU`.`tutor`
            FROM `" . $tbl_course . "`          AS C,
                 `" . $tbl_rel_course_user . "` AS CU
            WHERE CU.`code_cours` = C.`code`
              AND CU.`user_id` = " . (int) $userId;

    return $sql;
}
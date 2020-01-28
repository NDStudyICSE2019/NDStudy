<?php // $Id: courses.php 14564 2013-10-18 09:35:35Z ldumorti $

/**
 * CLAROLINE
 *
 * Prupose list of course to enroll or leave.
 *
 * @version     $Revision: 14564 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @package     AUTH
 */

require '../inc/claro_init_global.inc.php';

$nameTools  = get_lang('User\'s course');
$noPHP_SELF = true;

/*---------------------------------------------------------------------
Security Check
---------------------------------------------------------------------*/

if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
$can_see_hidden_course = claro_is_platform_admin();

/*---------------------------------------------------------------------
Include Files and initialize variables
---------------------------------------------------------------------*/

require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/courselist.lib.php';
require_once get_path('incRepositorySys') . '/lib/coursesearchbox.class.php';

include claro_get_conf_repository() . 'user_profile.conf.php';
include claro_get_conf_repository() . 'course_main.conf.php';
include claro_get_conf_repository() . 'CLHOME.conf.php';

$parentCategoryCode = '';
$userSettingMode    = false;
$dialogBox          = new DialogBox();
$coursesList        = array();
$categoriesList     = array();

/*---------------------------------------------------------------------
Define Display
---------------------------------------------------------------------*/

define ('DISPLAY_USER_COURSES',                 __LINE__); // in order to unenroll
define ('DISPLAY_COURSE_TREE',                  __LINE__); // in order to enroll
define ('DISPLAY_MESSAGE_SCREEN',               __LINE__);
define ('DISPLAY_REGISTRATION_KEY_FORM',        __LINE__);
define ('DISPLAY_REGISTRATION_DISABLED_FORM',   __LINE__);

$displayMode = DISPLAY_USER_COURSES; // default display

/*---------------------------------------------------------------------
Get request variables
---------------------------------------------------------------------*/

$cmd        = ( isset($_REQUEST['cmd']) ) ? ( $_REQUEST['cmd'] ) : ( 'rqReg' );
$uidToEdit  = ( isset($_REQUEST['uidToEdit']) ) ? ( (int) $_REQUEST['uidToEdit'] ) : ( 0 );
$fromAdmin  = ( isset($_REQUEST['fromAdmin']) && claro_is_platform_admin() ) ? ( trim($_REQUEST['fromAdmin']) ) : ( '' );
$asTeacher  = ( isset($_REQUEST['asTeacher']) && $_REQUEST['asTeacher'] == 'true' ) ? true : false;
$courseCode = ( isset($_REQUEST['course']) ) ? ( trim($_REQUEST['course']) ) : ( '' );
$categoryId = (!empty($_REQUEST['categoryId'])) ? (int) $_REQUEST['categoryId'] : 0;


/*=====================================================================
Main Section
=====================================================================*/

/*---------------------------------------------------------------------
Define user we are working with and build enroll URL
---------------------------------------------------------------------*/

// URL parameters for the navigation
$urlParamList = array();

if ( !empty($categoryId) )
{
    $urlParamList['categoryId'] = $categoryId;
}

if ( claro_is_platform_admin() )
{
    // Security: only a platform admin can edit other users than himself
    if ( isset($fromAdmin)
        && ( $fromAdmin == 'settings' || $fromAdmin == 'usercourse' )
        && !empty($uidToEdit)
        )
    {
        $userSettingMode = true;
    }
    
    // Build the list of params for the URLs
    if ( !empty($fromAdmin) ) 
    {
        $urlParamList['fromAdmin'] = $_REQUEST['fromAdmin'];
    }
    
    if ( !empty($uidToEdit) ) 
    {
        $urlParamList['uidToEdit'] = $_REQUEST['uidToEdit'];
    }
    
    if ( $asTeacher )
    {
        $urlParamList['asTeacher'] = 'true';
    }
    else
    {
        $urlParamList['asTeacher'] = 'false';
    }
    
    /*
     * In admin mode, there are 2 possibilities: we might want to enroll 
     * themself or either be here from admin tool
     */
    if ( !empty($uidToEdit) )
    {
        $userId = $uidToEdit;
    }
    else
    {
        // Default use is enroll for itself
        $userId     = claro_get_current_user_id(); 
        $uidToEdit  = claro_get_current_user_id();
    }
}
else
{
    if (get_conf('allowToSelfEnroll', true))
    {
        $userId    = claro_get_current_user_id(); // default use is enroll for itself...
        $uidToEdit = claro_get_current_user_id();
    }
    else
    {
        claro_redirect('..');
    }
}

/*---------------------------------------------------------------------
Define breadcrumbs
---------------------------------------------------------------------*/

if ( isset($_REQUEST['addNewCourse']) )
{
    ClaroBreadCrumbs::getInstance()->prepend(
        get_lang('My personal course list'), 
        $_SERVER['PHP_SELF']);
}

/*---------------------------------------------------------------------
Breadcrumbs is different if we come from admin tool
---------------------------------------------------------------------*/

if ( !empty($fromAdmin) )
{
    if ( $fromAdmin == 'settings' || $fromAdmin == 'usercourse' || $fromAdmin == 'class' )
    {
        ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
    }
    
    if ( $fromAdmin == 'class' )
    {
        if ( isset($_REQUEST['class_id']) )
        {
            $classId = trim($_REQUEST['class_id']);
            $_SESSION['admin_user_class_id'] = $classId;
        }
        elseif (isset($_SESSION['admin_user_class_id']))
        {
            $classId = $_SESSION['admin_user_class_id'];
        }
        else
        {
            $classId = '';
        }
        
        if ( !empty($classId) )
        {
            $urlParamList['class_id'] = $classId;
        }
        
        // Breadcrumbs different if we come from admin tool for a CLASS
        $nameTools = get_lang('Enrol class');
        
        $classinfo = class_get_properties ($_SESSION['admin_user_class_id']);
    }
}

/*---------------------------------------------------------------------
DB tables initialisation
Find info about user we are working with
---------------------------------------------------------------------*/

$userInfo = user_get_properties($userId);

if(!$userInfo)
{
    $cmd = '';
    
    switch (claro_failure::get_last_failure())
    {
        case 'user_not_found' :
        {
            $msg = get_lang('User not found');
        }
        break;
        
        default :
        {
            $msg = get_lang('User is not valid');
        }
        break;
    }
}

/*----------------------------------------------------------------------------
Unsubscribe from a course
----------------------------------------------------------------------------*/

if ( $cmd == 'exUnreg' )
{
    if ( user_remove_from_course($userId, $courseCode, false, false, null) )
    {
        $claroline->log('COURSE_UNSUBSCRIBE', array('user'=>$userId,'course'=>$courseCode));
        $dialogBox->success( get_lang('Your enrolment on the course has been removed') );
    }
    else
    {
        switch ( claro_failure::get_last_failure() )
        {
            case 'cannot_unsubscribe_the_last_course_manager' :
            {
                $dialogBox->error( get_lang('You cannot unsubscribe the last course manager of the course') );
            }
            break;
            
            case 'course_manager_cannot_unsubscribe_himself' :
            {
                $dialogBox->error( get_lang('Course manager cannot unsubscribe himself') );
            }
            break;
            
            default :
            {
                $dialogBox->error( get_lang('Unable to remove your registration to the course') );
            }
            break;
        }
    }
    
    $displayMode = DISPLAY_MESSAGE_SCREEN;
} // end if ($cmd == 'exUnreg')

/*----------------------------------------------------------------------------
Subscribe to a course
----------------------------------------------------------------------------*/

if ( $cmd == 'exReg' )
{
    $registrationKey = isset($_REQUEST['registrationKey']) ? $_REQUEST['registrationKey'] : null;
    $categoryId = isset($_REQUEST['categoryId']) ? $_REQUEST['categoryId'] : null;
    
    $courseObj = new Claro_Course($courseCode);
    $courseObj->load();
    
    $courseRegistration = new Claro_CourseUserRegistration(
        AuthProfileManager::getUserAuthProfile($userId),
        $courseObj,
        $registrationKey,
        $categoryId
    );
    
    if ( !empty( $classId ) )
    {
        $claroClass = new Claro_Class();
        $claroClass->load($classId);
        
        $courseRegistration->setClass( $claroClass );
    }
    
    if ( $courseRegistration->addUser() )
    {
        $claroline->log('COURSE_SUBSCRIBE',array('user'=>$userId,'course'=>$courseCode));
        
        $displayMode = DISPLAY_MESSAGE_SCREEN;
        
        if ( claro_get_current_user_id() != $uidToEdit )
        {
            // Message for admin
            $dialogBox->success( get_lang('The user has been enroled to the course') );
        }
        else
        {
            $dialogBox->success( get_lang('You\'ve been enroled on the course') );
        }
        
        $properties = array();
        
        if ( $asTeacher && claro_is_platform_admin() )
        {
            $properties['isCourseManager']  = 1;
            $properties['role']             = get_lang('Course manager');
            $properties['tutor']            = 1;
            user_set_course_properties($userId, $courseCode, $properties);
        }
    }
    else
    {
        switch ($courseRegistration->getStatus())
        {
            case Claro_CourseUserRegistration::STATUS_KEYVALIDATION_FAILED :
            {
                $displayMode = DISPLAY_REGISTRATION_KEY_FORM;
                $dialogBox->error( $courseRegistration->getErrorMessage() );
            }
            break;
            
            case Claro_CourseUserRegistration::STATUS_SYSTEM_ERROR :
            {
                $displayMode = DISPLAY_MESSAGE_SCREEN;
                $dialogBox->error( $courseRegistration->getErrorMessage() );
            }
            break;
            
            case Claro_CourseUserRegistration::STATUS_REGISTRATION_NOTAVAILABLE :
            {
                $displayMode = DISPLAY_REGISTRATION_DISABLED_FORM;
                $dialogBox->error( $courseRegistration->getErrorMessage() );
                $dialogBox->info(
                    get_lang('Please contact the course manager : %email' ,
                    array ('%email' => '<a href="mailto:'.$courseObj->email 
                         . '?body=' . $courseObj->officialCode 
                         . '&amp;subject=[' . rawurlencode( get_conf('siteName')) 
                         . ']' . '">' . claro_htmlspecialchars($courseObj->titular) . '</a>')) );
            }
            break;
            
            default :
            {
                $displayMode = DISPLAY_MESSAGE_SCREEN;
                $dialogBox->warning( $courseRegistration->getErrorMessage() );
            }
            break;
        }
    }
    
} // end if ($cmd == 'exReg')

/*----------------------------------------------------------------------------
User course list to unregister
----------------------------------------------------------------------------*/

if ( $cmd == 'rqUnreg' )
{
    $courseListView = CourseTreeNodeViewFactory::getUserCourseTreeView($userId);
    $unenrollUrl = Url::buildUrl(
        $_SERVER['PHP_SELF'] . '?cmd=exUnreg', 
        $urlParamList, 
        null);
    
    $viewOptions = new CourseTreeViewOptions(
        false,
        true,
        null,
        $unenrollUrl->toUrl() );
    $courseListView->setViewOptions($viewOptions);
    
    $displayMode = DISPLAY_USER_COURSES;
} // end if ($cmd == 'rqUnreg')

/*----------------------------------------------------------------------------
Search a course to register
----------------------------------------------------------------------------*/

if ( $cmd == 'rqReg' ) // show course of a specific category
{
    // Set user id to null if we're working on a class
    $userId = ($fromAdmin == 'class') ? null : $userId;
    
    // Build the category browser
    $categoryBrowser  = new CategoryBrowser($categoryId, $userId);
    
    if ( $fromAdmin == 'class' )
    {
        $viewOptions = new CourseTreeViewOptions(
            true,
            false,
            Url::buildUrl(get_module_url('CLUSR').'/class_add.php?cmd=exEnrol', $urlParamList, null)->toUrl(),
            null,
            true );
        
        // var_dump($viewOptions);
    }
    else
    {
        $viewOptions = new CourseTreeViewOptions(
            true,
            false,
            Url::buildUrl($_SERVER['PHP_SELF'].'?cmd=exReg', $urlParamList, null)->toUrl(),
            null);
    }
    
    $categoryBrowser->setViewOptions($viewOptions);
    
    $parentCategoryId       = $categoryBrowser->getCurrentCategorySettings()->idParent;
    
    // Build the search box
    $searchBox = new CourseSearchBox($_SERVER['REQUEST_URI']);
    
    if ( $fromAdmin == 'class' )
    {
        $viewOptions = new CourseTreeViewOptions(
            true,
            false,
            Url::buildUrl(get_module_url('CLUSR').'/class_add.php?cmd=exEnrol', $urlParamList, null)->toUrl(),
            null,
            true );
        
        // var_dump($viewOptions);
    }
    else
    {
        $viewOptions = new CourseTreeViewOptions(
            true,
            false,
            Url::buildUrl($_SERVER['PHP_SELF'].'?cmd=exReg', $urlParamList, null)->toUrl(),
            null);
    }
    
    $searchBox->setViewOptions($viewOptions);
    
    $displayMode = DISPLAY_COURSE_TREE;
} // end cmd == rqReg

/*=====================================================================
   Display Section
  =====================================================================*/

$newLink = '';
// Set the back link
if ( $cmd == 'rqReg' && ( !empty($categoryId) || !empty($parentCategoryId) ) )
{
    $backUrl   = $_SERVER['PHP_SELF'].'?cmd=rqReg&categoryId=' 
               . urlencode($parentCategoryId);
    $backLabel = get_lang('Back to parent category');
}
else
{
    // Build the back url regarding the script we're coming from
    if ( $userSettingMode == true ) 
    {
        if ( $fromAdmin == 'settings' )
        {
            $backUrl   = '../admin/admin_profile.php?uidToEdit=' . $userId;
            $backLabel = get_lang('Back to user settings');
        }
        
        if ( $fromAdmin == 'usercourse' ) // admin tool used: list of a user's courses.
        {
            $backUrl   = '../admin/adminusercourses.php?uidToEdit=' . $userId;
            $backLabel = get_lang('Back to user\'s course list');    
            
            if ($courseCode !='')
            {
                $asTeacherInfo = ($asTeacher)?'true':false;
                $newLink = '<p><a class="backLink" href="'.$_SERVER['PHP_SELF'].'?cmd=rqReg&amp;fromAdmin=usercourse&amp;uidToEdit='.$userId.'&amp;asTeacher='.$asTeacherInfo.'">'. 
                    get_lang('Enrol to a new course') .'</a></p>';
            }
        }   
    }
    elseif ( $fromAdmin == 'class' ) // admin tool used : class registration
    {
        $backUrl   = '../admin/admin_class_user.php?';
        
        if (isset($_SESSION['admin_user_class_id']))
        {
            $backUrl .= 'class_id='. $_SESSION['admin_user_class_id'];
        }
        
        $backLabel = get_lang('Back to the class');
    }
    else
    {
        if ( claro_is_in_a_course() )
        {
            // add cidReset to force relaod user privileges in course
            $backUrl = '../course/index.php?cidReset=true&cid='.claro_get_current_course_id();
            $backLabel = get_lang('Course homepage');
        }
        else
        {
            $backUrl   = '../../index.php';
            $backLabel = get_lang('Back to my personal course list');
        }
    }
} // end if ( $cmd == 'rqReg' && ( !empty($categoryId) || !empty($parentCategoryId) ) )

// Notify userid of the user we are working with in admin mode and that we come from admin
$backUrl = Url::buildUrl($backUrl, $urlParamList, null)->toUrl(); 
$backLink = '<p><a class="backLink" href="' . $backUrl 
          . '" title="' . $backLabel. '" >'
          . $backLabel . '</a></p>' . "\n\n";

$out = '';

switch ( $displayMode )
{
    /*---------------------------------------------------------------------
    Display course list
    ---------------------------------------------------------------------*/
    
    case DISPLAY_COURSE_TREE :
    {
        //  Display Title
        if ( $fromAdmin == 'class' )
        {
            $mainTitle = get_lang('Enrol class');
            $subTitle = $classinfo['name'];
        }
        else
        {
            $mainTitle = get_lang('User\'s course') ;
            $subTitle = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
        }
        
        $cmdList = array();
        
        /*
         * When you enroll another user and if you are platform admin, 
         * give the possibility to enroll him as a student or as a teacher
         */
        if ($fromAdmin == 'usercourse' && claro_is_platform_admin())
        {
            // Rewrite the "asTeacher" URL parameter only for this button action
            if ($asTeacher)
            {
                $cmdList[] = array(
                    'img' => 'user',
                    'name' => get_lang('Enrol as student'),
                    'url' => Url::buildUrl($_SERVER['PHP_SELF'].'?cmd=rqReg', 
                        array_merge($urlParamList, array('asTeacher' => 'false')), 
                        null)->toUrl(),
                );
            }
            else
            {
                $cmdList[] = array(
                    'img' => 'manager',
                    'name' => get_lang('Enrol as teacher'),
                    'url' => Url::buildUrl($_SERVER['PHP_SELF'].'?cmd=rqReg', 
                        array_merge($urlParamList, array('asTeacher' => 'true')), 
                        null)->toUrl(),
                );
            }
        }
        
        // Display the title
        $out .= claro_html_tool_title(array(
            'mainTitle' =>  $mainTitle, 
            'subTitle' => $subTitle), 
            null, 
            $cmdList);
        
        // Display dialogbox and backlink
        $out .= $dialogBox->render();
        
        $out .= $categoryBrowser->getTemplate()->render()
              . $searchBox->render();
    }
    break;
    
    /*---------------------------------------------------------------------
    Display message
    ---------------------------------------------------------------------*/
    
    case DISPLAY_MESSAGE_SCREEN :
    {
        $mainTitle = get_lang('User\'s course') ;
        $subTitle = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
        
        $out .= claro_html_tool_title(array(
                    'mainTitle' =>  $mainTitle, 
                    'subTitle' => $subTitle))
              . $dialogBox->render();
    }
    break;
    
    /*---------------------------------------------------------------------
    Display user courses in order to unenroll (default display)
    ---------------------------------------------------------------------*/
    
    case DISPLAY_USER_COURSES :
    {
        $out .= claro_html_tool_title(array(
                    'mainTitle' => get_lang('User\'s course') . ' : ' . $userInfo['firstname'] . ' ' . $userInfo['lastname'],
                    'subTitle' => get_lang('Remove course from your personal course list')))
              . $dialogBox->render()
              . $courseListView->render();
    }
    break;
    
    case DISPLAY_REGISTRATION_KEY_FORM :
    {
        $courseData = claro_get_course_data($_REQUEST['course']);
        $courseName = $courseData['name'];
        
        $out .= claro_html_tool_title(array(
                    'mainTitle' => get_lang('User\'s course') . ' : ' 
                                 . $userInfo['firstname'] . ' ' . $userInfo['lastname'],
                    'subTitle' => get_lang('Enrol to %course', array('%course' => $courseName) )));
        
        $template = new CoreTemplate('course_registration_key_form.tpl.php');
        $template->assign('formAction', Url::Contextualize($_SERVER['PHP_SELF']));
        $template->assign('courseCode', $courseCode);
        
        $dialogBox->form($template->render());
        
        $out .= $dialogBox->render();
    }
    break;
    
    case DISPLAY_REGISTRATION_DISABLED_FORM :
    {
        if ( empty($courseData['email']) ) $courseData['email'] = get_conf('administrator_email');
        if ( empty($courseData['titular']) ) $courseData['titular'] = get_conf('administrator_name');
        
        $out .= $dialogBox->render();
    }
    break;
    
} // end of switch ($displayMode)

if ($newLink != '')
{
    $out .= $newLink;
}
$out .= $backLink;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

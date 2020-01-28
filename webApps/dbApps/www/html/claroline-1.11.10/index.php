<?php // $Id: index.php 14321 2012-11-13 07:28:51Z zefredz $

/**
 * CLAROLINE
 *
 * Campus Home Page.
 *
 * @version     Claroline 1.11 $Revision: 14321 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLINDEX
 * @author      Claro Team <cvs@claroline.net>
 */

unset($includePath); // prevent hacking

// Flag forcing the 'current course' reset, as we're not anymore inside a course
$cidReset = true;
$tidReset = true;
$_SESSION['courseSessionCode'] = null;

// Include Library and configuration files
require './claroline/inc/claro_init_global.inc.php'; // main init
include claro_get_conf_repository() . 'CLHOME.conf.php'; // conf file
require_once dirname(__FILE__) . '/claroline/inc/lib/coursesearchbox.class.php';
require_once dirname(__FILE__) . '/claroline/inc/lib/course/courselist.lib.php';


if (get_conf('display_former_homepage', false) || !claro_is_user_authenticated())
{
    // Main template
    $template = new CoreTemplate('platform_index.tpl.php');
    
    // Languages
    $template->assign('languages', get_language_to_display_list());
    $template->assign('currentLanguage', language::current_language());
    
    // Last user action
    $lastUserAction = (isset($_SESSION['last_action']) && $_SESSION['last_action'] != '1970-01-01 00:00:00') ?
        $_SESSION['last_action'] :
        date('Y-m-d H:i:s');
    
    $template->assign('lastUserAction', $lastUserAction);
    
    
    // Manage the search box and search results
    $searchBox = new CourseSearchBox($_SERVER['REQUEST_URI']);
    
    $template->assign('searchBox', $searchBox);
    
    
    if (claro_is_user_authenticated())
    {
        // User course (activated and deactivated) lists and search results (if any)
        if (empty($_REQUEST['viewCategory']))
        {
            $courseTreeView = 
                CourseTreeNodeViewFactory::getUserCourseTreeView(
                    claro_get_current_user_id()
                );
        }
        else
        {
            $courseTreeView = 
                CourseTreeNodeViewFactory::getUserCategoryCourseTreeView(
                    claro_get_current_user_id(), $_REQUEST['viewCategory']
                );
        }
        
        $template->assign('templateMyCourses', $courseTreeView);
        
        // User commands
        $userCommands = array();
        
        $userCommands[] = '<a href="' . $_SERVER['PHP_SELF'] . '" class="userCommandsItem">'
                        . '<img src="' . get_icon_url('mycourses') . '" alt="" /> '
                        . get_lang('My course list')
                        . '</a>' . "\n";
        
        // 'Create Course Site' command. Only available for teacher.
        if (claro_is_allowed_to_create_course())
        {
            $userCommands[] = '<a href="claroline/course/create.php" class="userCommandsItem">'
                            . '<img src="' . get_icon_url('courseadd') . '" alt="" /> '
                            . get_lang('Create a course site')
                            . '</a>' . "\n";
        }
        elseif ( $GLOBALS['currentUser']->isCourseCreator )
        {
            $userCommands[] = '<span class="userCommandsItemDisabled">'
                            . '<img src="' . get_icon_url('courseadd') . '" alt="" /> '
                            . get_lang('Create a course site')
                            . '</span>' . "\n";
        }
        
        if (get_conf('allowToSelfEnroll',true))
        {
            $userCommands[] = '<a href="claroline/auth/courses.php?cmd=rqReg&amp;categoryId=0" class="userCommandsItem">'
                            . '<img src="' . get_icon_url('enroll') . '" alt="" /> '
                            . get_lang('Enrol on a new course')
                            . '</a>' . "\n";
            
            $userCommands[] = '<a href="claroline/auth/courses.php?cmd=rqUnreg" class="userCommandsItem">'
                            . '<img src="' . get_icon_url('unenroll') . '" alt="" /> '
                            . get_lang('Remove course enrolment')
                            . '</a>' . "\n";
        }
        
        $userCommands[] = '<a href="claroline/course/platform_courses.php" class="userCommandsItem">'
                        . '<img src="' . get_icon_url('course') . '" alt="" /> '
                        . get_lang('All platform courses')
                        . '</a>' . "\n";
        
        $userCommands[] = '<img class="iconDefinitionList" src="'.get_icon_url('hot').'" alt="'.get_lang('New items').'" />'
                        . ' '.get_lang('New items').' '
                        . '(<a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'notification_date.php')).'" class="userCommandsItem">'
                        . get_lang('to another date')
                        . '</a>)'
                        . ((substr($lastUserAction, strlen($lastUserAction) - 8) == '00:00:00' ) ?
                            (' <br />['.claro_html_localised_date(
                                get_locale('dateFormatNumeric'),
                                strtotime($lastUserAction)).']') :
                            (''))
                        . "\n";
        
        $template->assign('userCommands', $userCommands);
        
        // User profilebox
        FromKernel::uses('display/userprofilebox.lib');
        $userProfileBox = new UserProfileBox(false);
        
        $template->assign('userProfileBox', $userProfileBox);
    }
    else
    {
        // Category browser
        $categoryId = ( !empty( $_REQUEST['categoryId']) ) ? ( (int) $_REQUEST['categoryId'] ) : ( 0 );
        $categoryBrowser = new CategoryBrowser( $categoryId );
        $templateCategoryBrowser = $categoryBrowser->getTemplate();
        
        $template->assign('templateCategoryBrowser', $templateCategoryBrowser);
    }
    
    
    // Render
    $claroline->display->body->setContent($template->render());
    
    if (!(isset($_REQUEST['logout']) && isset($_SESSION['isVirtualUser'])))
    {
        echo $claroline->display->render();
    }
}
else
{
    require_once get_path('clarolineRepositorySys') . '/desktop/index.php';
}

// Logout request : delete session data
if (isset($_REQUEST['logout']))
{
    if (isset($_SESSION['isVirtualUser']))
    {
        unset($_SESSION['isVirtualUser']);
        claro_redirect(get_conf('rootWeb') . 'claroline/admin/admin_users.php');
        exit();
    }
    
    // notify that a user has just loggued out
    if (isset($logout_uid)) // Set  by local_init
    {
        $eventNotifier->notifyEvent('user_logout', array('uid' => $logout_uid));
    }
    /* needed to be able to :
         - log with claroline when 'magic login' has previously been clicked
         - notify logout event
         (logout from CAS has been commented in casProcess.inc.php)*/
    if( get_conf('claro_CasEnabled', false) && ( get_conf('claro_CasGlobalLogout') && !phpCAS::checkAuthentication() ) )
    {
        phpCAS::logout((isset( $_SERVER['HTTPS']) && ($_SERVER['HTTPS']=='on'||$_SERVER['HTTPS']==1) ? 'https://' : 'http://')
                        . $_SERVER['HTTP_HOST'].get_conf('urlAppend').'/index.php');
    }
    session_destroy();
}

// Hide breadcrumbs and view mode on platform home page
// $claroline->display->banner->hideBreadcrumbLine();

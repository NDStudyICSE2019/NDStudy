<?php // $Id: index.php 14525 2013-08-21 07:05:21Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14525 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *              old version : http://cvs.claroline.net/cgi-bin/viewcvs.cgi/claroline/claroline/course_home/course_home.php
 * @package     CLHOME
 * @author      Claro Team <cvs@claroline.net>
 */


// If user is here, that means he isn't neither in specific group space
// nor a specific course tool now. So it's careful to reset the group
// and tool settings

$gidReset = true;
if ( isset( $_REQUEST['gidReq'] ) ) unset( $_REQUEST['gidReq'] );
$tidReset = true;

if ( isset($_REQUEST['cid']) ) $cidReq = $_REQUEST['cid'];
elseif ( isset($_REQUEST['cidReq']) ) $cidReq = $_REQUEST['cidReq'];

$portletCmd     = (isset($_REQUEST['portletCmd']) ? $_REQUEST['portletCmd'] : null);
$portletId      = (isset($_REQUEST['portletId']) ? $_REQUEST['portletId'] : null);
$portletLabel   = (isset($_REQUEST['portletLabel']) ? $_REQUEST['portletLabel'] : null);
$portletClass   = (isset($portletLabel) ? ($portletLabel.'_portlet') : null);

require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/claroCourse.class.php';
require_once get_path('incRepositorySys') . '/lib/users/userlist.lib.php';

require_once dirname(__FILE__) . '/coursehomepage/lib/coursehomepageportlet.class.php';
require_once dirname(__FILE__) . '/coursehomepage/lib/coursehomepageportletiterator.class.php';

// Instanciate dialog box
$dialogBox = new DialogBox();

// Display the auth form if necessary
// Also redirect if no cid specified
if ( !claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

if (empty($cidReq))
{
    claro_die(get_lang('Cannot find course'));
}

// Fetch this course's portlets
$portletiterator = new CourseHomePagePortletIterator(ClaroCourse::getIdFromCode($cidReq));

// Include specific CSS if any
if ( file_exists( get_conf('coursesRepositorySys')
        . $_course['path'] . '/css/course.css' ) )
{
    $claroline->display->header->addHtmlHeader(
        '<link rel="stylesheet" media="screen" type="text/css" href="'
        . get_path('url') . '/' . get_path('coursesRepositoryAppend')
        . $_course['path'] . '/css/course.css" />');
}

// Instantiate course
$thisCourse = new ClaroCourse();
$thisCourse->load($cidReq);

include claro_get_conf_repository() . 'rss.conf.php';

// Include the course home page special CSS
CssLoader::getInstance()->load('coursehomepage', 'all');

$toolRepository = get_path('clarolineRepositoryWeb');
claro_set_display_mode_available(true);

// Manage portlets
if (claro_is_course_manager() && !empty($portletClass))
{
    // Require the right class
    $portletPath = get_module_path( $portletLabel )
        . '/connector/coursehomepage.cnr.php';
    if ( file_exists($portletPath) )
    {
        require_once $portletPath;
    }
    else
    {
        throw new Exception(get_lang('Cannot find this portlet'));
    }
    
    if ($portletCmd == 'exAdd')
    {
        $portlet = new $portletClass();
        $portlet->handleForm();
        if ($portlet->save())
        {
            $dialogBox->success(get_lang('Portlet created'));
        }
        else
        {
            $dialogBox->error(get_lang('Can\'t create this portlet (%portlet)', array('%portlet' => $portlet->getLabel())));
        }
    }
    elseif ($portletCmd == 'delete' && !empty($portletId) && class_exists($portletClass))
    {
        $portlet = new $portletClass();
        $portlet->load($portletId);
        if ($portlet->delete())
        {
            $dialogBox->success(get_lang('Portlet deleted'));
        }
    }
    elseif ($portletCmd == 'makeVisible' && !empty($portletId) && class_exists($portletClass))
    {
        $portlet = new $portletClass();
        if ($portlet->load($portletId))
        {
            $portlet->makeVisible();
            if ($portlet->save())
            {
                $dialogBox->success(get_lang('Portlet visibility modified'));
            }
        }
    }
    elseif ($portletCmd == 'makeInvisible' && !empty($portletId) && class_exists($portletClass))
    {
        $portlet = new $portletClass();
        if ($portlet->load($portletId))
        {
            $portlet->makeInvisible();
            if ($portlet->save())
            {
                $dialogBox->success(get_lang('Portlet visibility modified'));
            }
        }
    }
    elseif ($portletCmd == 'moveUp' && !empty($portletId) && class_exists($portletClass))
    {
        $portlet = new $portletClass();
        $portlet->load($portletId);
        
        if ($portlet->load($portletId))
        {
            if ($portlet->moveUp())
            {
                $dialogBox->success(get_lang('Portlet moved up'));
            }
            else
            {
                $dialogBox->error(get_lang('This portlet can\'t be moved up'));
            }
        }
    }
    elseif ($portletCmd == 'moveDown' && !empty($portletId) && class_exists($portletClass))
    {
        $portlet = new $portletClass();
        if ($portlet->load($portletId))
        {
            if ($portlet->moveDown())
            {
                $dialogBox->success(get_lang('Portlet moved down'));
            }
            else
            {
                $dialogBox->error(get_lang('This portlet can\'t be moved down'));
            }
        }
    }
}

// Language initialisation of the tool names
$toolNameList = claro_get_tool_name_list();

// Get tool id where new events have been recorded since last login
if (claro_is_user_authenticated())
{
    $date = $claro_notifier->get_notification_date(claro_get_current_user_id());
    $modified_tools = $claro_notifier->get_notified_tools(claro_get_current_course_id(), $date, claro_get_current_user_id());
}
else
{
    $modified_tools = array();
}

/**
 * TOOL LIST
 */

$is_allowedToEdit = claro_is_allowed_to_edit();


// Fetch the portlets
$portletiterator = new CourseHomePagePortletIterator(ClaroCourse::getIdFromCode($cidReq));


// Fetch the session courses (if any)
if (ClaroCourse::isSourceCourse($thisCourse->id))
{
    $sessionCourses = $thisCourse->getSessionCourses();
}
else
{
    $sessionCourses = array();
}

// Notices for course managers
if (claro_is_allowed_to_edit())
{
    if ($thisCourse->status == 'pending')
    {
        $dialogBox->warning(
            get_lang('This course is deactivated: you can reactive it from your course list'));
    }
    elseif  ( $thisCourse->status == 'date' )
    {
        if (!empty($thisCourse->publicationDate) && $thisCourse->publicationDate > claro_mktime())
        {
            $dialogBox->warning(
                get_lang('This course will be enabled on the %date',
                array('%date' => claro_date('d/m/Y', $thisCourse->publicationDate))));
        }
        if (!empty($thisCourse->expirationDate) && $thisCourse->expirationDate > claro_mktime())
        {
            $dialogBox->warning(
                get_lang('This course will be disable on the %date',
                array('%date' => claro_date('d/m/Y', $thisCourse->expirationDate))));
        }
    }
    
    if ($thisCourse->userLimit > 0)
    {
        $dialogBox->warning(
            get_lang('This course is limited to %userLimit users',
            array('%userLimit' => $thisCourse->userLimit)));
    }
    
    if ( $thisCourse->registration == 'validation' )
    {
        $courseUserList = new Claro_CourseUserList(claro_get_current_course_id());
        
        if ( $courseUserList->has_registrationPending () )
        {
            $usersPanelUrl = claro_htmlspecialchars(Url::Contextualize( $toolRepository . 'user/user.php' ));
            $dialogBox->warning(
                get_lang('You have to validate users to give them access to this course through the <a href="%url">course user list</a>', array('%url' => $usersPanelUrl))
            );
        }
    }
}

// Get the portlets buttons
$activablePortlets = claro_is_course_manager() ? CourseHomePagePortlet::getActivablePortlets() : array();

// Display
$template = new CoreTemplate('course_index.tpl.php');
$template->assign('dialogBox', $dialogBox);
$template->assign('activablePortlets', $activablePortlets);
$template->assign('portletIterator', $portletiterator);

$claroline->display->body->setContent($template->render());

echo $claroline->display->render();

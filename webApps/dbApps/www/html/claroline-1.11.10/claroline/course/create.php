<?php // $Id: create.php 14422 2013-04-15 07:14:27Z zefredz $

/**
 * CLAROLINE
 *
 * This script manages the creation of a course.
 * It contains 3 panels:
 * - Form
 * - Wait
 * - Done
 *
 * @version     $Revision: 14422 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/CLCRS/
 * @package     COURSE
 *              old version : http://cvs.claroline.net/cgi-bin/viewcvs.cgi/claroline/claroline/create_course/add_course.php
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.9
 */

require '../inc/claro_init_global.inc.php';

//=================================
// Security check
//=================================

if ( ! claro_is_user_authenticated() )       claro_disp_auth_form();
if ( ! claro_is_allowed_to_create_course() ) claro_die(get_lang('Not allowed'));

//=================================
// Main section
//=================================

include claro_get_conf_repository() . 'course_main.conf.php';
require_once get_path('incRepositorySys') . '/lib/add_course.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/course.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php'; // for claro_get_uid_of_platform_admin()
require_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
require_once get_path('incRepositorySys') . '/lib/form.lib.php';
require_once get_path('incRepositorySys') . '/lib/sendmail.lib.php';
require_once get_path('incRepositorySys') . '/lib/claroCourse.class.php';

define('DISP_COURSE_CREATION_FORM'     ,__LINE__);
define('DISP_COURSE_CREATION_SUCCEED'  ,__LINE__);
define('DISP_COURSE_CREATION_FAILED'   ,__LINE__);
define('DISP_COURSE_CREATION_PROGRESS' ,__LINE__);

$display = DISP_COURSE_CREATION_FORM; // default display

$dialogBox = new DialogBox();

$cmd                = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : null;
$adminContext       = isset($_REQUEST['adminContext']) ? (bool) $_REQUEST['adminContext'] : null;

// $sourceCourseId has a value only if we're about to create a session course; it's null otherwise
$sourceCourseId = isset($_REQUEST['course_sourceCourseId']) ? (int) $_REQUEST['course_sourceCourseId'] : null;

// New course object
$thisUser = claro_get_current_user_data();
$course = new ClaroCourse($thisUser['firstName'], $thisUser['lastName'], $thisUser['mail']);

if (!is_null($sourceCourseId))
{
    $course->sourceCourseId = $sourceCourseId;
}

if ( !is_null($course->sourceCourseId) && !empty($course->sourceCourseId) )
{
    $sourceCourse = new claroCourse();
    $sourceCourse->load(claroCourse::getCodeFromId($course->sourceCourseId));
    
    if( $sourceCourse->sourceCourseId )
    {
        claro_die( get_lang( 'You cannot create a course session from another course session' ) );
    }
    
    $course->categories = $sourceCourse->categories;
}

if ( $adminContext && claro_is_platform_admin() )
{
    // From admin, add param to form
    $course->addHtmlParam('adminContext','1');
}

if ( claro_is_platform_admin() || get_conf('courseCreationAllowed', true) )
{
    if ( $cmd == 'exEdit' )
    {
        $course->handleForm();
        
        if( $course->validate() )
        {
            if( $course->save() )
            {
                // include the platform language file with all language variables
                language::load_translation();
                language::load_locale_settings();
                
                $course->mailAdministratorOnCourseCreation($thisUser['firstName'], $thisUser['lastName'], $thisUser['mail']);
                
                $dialogBox->success( get_lang('You have just created the course website')
                .            ' : ' . '<strong>' . $course->officialCode . '</strong>' );
                
                $display = DISP_COURSE_CREATION_SUCCEED;
            }
            else
            {
                $dialogBox->error( $course->backlog->output() );
                $display = DISP_COURSE_CREATION_FAILED;
            }
        }
        else
        {
            $dialogBox->error( $course->backlog->output() );
            $display = DISP_COURSE_CREATION_FAILED;
        }
    }
    
    if( $cmd == 'rqProgress' )
    {
        $course->handleForm();
    
        if( $course->validate() )
        {
            // Trig a waiting screen as course creation may take a while...
            $progressUrl = $course->buildProgressUrl();
    
            $htmlHeadXtra[] = '<meta http-equiv="REFRESH" content="0; URL=' . $progressUrl . '">';
    
            // Display "progression" page
            $dialogBox->info( get_lang('Creating course (it may take a while) ...') . '<br />' . "\n"
            .      '<p align="center">'
            .      '<img src="' . get_icon_url('processing') . '" alt="" />'
            .      '</p>' . "\n"
            .      '<p>'
            .      get_lang('If after while no message appears confirming the course creation, please click <a href="%url">here</a>',array('%url' => $progressUrl))
            .      '</p>' );
            
            $display = DISP_COURSE_CREATION_PROGRESS;
        }
        else
        {
            $dialogBox->error( $course->backlog->output() );
            $display = DISP_COURSE_CREATION_FAILED;
        }
    }
}

// Set navigation url
if ( $adminContext && claro_is_platform_admin() )
{
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Create course'), get_path('clarolineRepositoryWeb') . 'course/create.php?adminContext=1' );
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
    $backUrl = get_path('rootAdminWeb') ;
}
else
{
    if ( $course->courseId )
    {
        $backUrl = get_path('url') . '/claroline/course/index.php?cid='.$course->courseId;
    }
    else
    {
        $backUrl = get_path('url') . '/index.php';
    }
}

if ( ! get_conf('courseCreationAllowed', true) )
{
    $dialogBox->warning(get_lang('Course creation is disabled on the platform'));
}


//=================================
// Display section
//=================================

$out = '';

$out .=  claro_html_tool_title(get_lang('Create a course website'));

$out .= $dialogBox->render();

if ( claro_is_platform_admin()
    || get_conf('courseCreationAllowed', true) )
{
    if( $display == DISP_COURSE_CREATION_FORM || $display == DISP_COURSE_CREATION_FAILED )
    {
        // display form
        $out .= $course->displayForm($backUrl);
    }
    elseif ( $display == DISP_COURSE_CREATION_PROGRESS )
    {
        // do nothing except displaying dialogBox content
    }
    elseif ( $display == DISP_COURSE_CREATION_SUCCEED )
    {
        // display back link
        $out .= '<p>'
        .    claro_html_cmd_link( claro_htmlspecialchars( $backUrl ), get_lang('Continue') )
        .     '</p>' . "\n"
        ;
    }
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
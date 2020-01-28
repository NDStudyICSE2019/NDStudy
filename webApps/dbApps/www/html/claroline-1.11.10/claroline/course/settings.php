<?php // $Id: settings.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * This tool manage properties of an exiting course.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      claroline Team <cvs@claroline.net>
 *              old version : http://cvs.claroline.net/cgi-bin/viewcvs.cgi/claroline/claroline/course_info/infocours.php
 * @package     CLCRS
 */

$gidReset = true;
require '../inc/claro_init_global.inc.php';

$nameTools = get_lang('Course settings');
$noPHP_SELF = true;

if ( ! claro_is_in_a_course() || ! claro_is_user_authenticated()) claro_disp_auth_form(true);

$is_allowedToEdit = claro_is_course_manager();

if ( ! $is_allowedToEdit )
{
    claro_die(get_lang('Not allowed'));
}

//=================================
// Main section
//=================================

include claro_get_conf_repository() . 'course_main.conf.php';
require_once get_path('incRepositorySys') . '/lib/course.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
require_once get_path('incRepositorySys') . '/lib/form.lib.php';
require_once get_path('incRepositorySys') . '/lib/claroCourse.class.php';

// Initialisation
define('DISP_COURSE_EDIT_FORM',__LINE__);
define('DISP_COURSE_RQ_DELETE',__LINE__);

$dialogBox = new DialogBox();

$cmd            = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : null;
$adminContext   = isset($_REQUEST['adminContext']) ? (bool) $_REQUEST['adminContext'] : null;
$courseType     = isset($_REQUEST['courseType']) ? ($_REQUEST['courseType']) : null;
$current_cid    = null;
$display        = DISP_COURSE_EDIT_FORM;

$course = new ClaroCourse();


// Initialise current course id


// TODO cidToEdit would  die. cidReq be the  the  only  container to enter in a course context
if ( $adminContext && claro_is_platform_admin() )
{
    // from admin
    if ( isset($_REQUEST['cidToEdit']) )
    {
        $current_cid = trim($_REQUEST['cidToEdit']);
    }
    elseif ( isset($_REQUEST['cidReq']) )
    {
        $current_cid = trim($_REQUEST['cidReq']);
    }

    // add param to form
    $course->addHtmlParam('adminContext','1');
    $course->addHtmlParam('cidToEdit',$current_cid);

    // Back url
    $backUrl = get_path('rootAdminWeb') . 'admin_courses.php' ;
}
elseif ( claro_is_in_a_course() )
{
    // from my course
    $current_cid = claro_get_current_course_id();
    $backUrl = get_path('clarolineRepositoryWeb') . 'course/index.php?cid=' . claro_htmlspecialchars($current_cid);
}
else
{
    $current_cid = null ;
}

if ( $course->load($current_cid) )
{
    if ( $cmd == 'exEnable' )
    {
        if ( ! claro_is_course_manager() && ! claro_is_platform_admin() )
        {
            claro_die( get_lang("Not allowed") );
            exit();
        }
        
        if ( ($course->status == 'disable' || $course->status == 'trash' ) && ! claro_is_platform_admin() )
        {
            claro_die( get_lang("Not allowed") );
            exit();
        }
        elseif ( ( $course->status == 'disable' || $course->status == 'trash' ) && claro_is_platform_admin() )
        {
            $course->status = 'enable';
            
            if ($course->save())
            {
                $dialogBox->success(get_lang('This course has been activated and is now available on this platform'));
            }
            else
            {
                $dialogBox->error(get_lang('Unable to reactivate this course'));
            }
        }
        elseif ( ($course->status == 'pending') && claro_is_in_a_course() && claro_is_course_manager() )
        {
            $course->status = 'enable';
            
            if ($course->save())
            {
                $dialogBox->success(get_lang('This course has been activated and is now available on this platform'));
            }
            else
            {
                $dialogBox->error(get_lang('Unable to reactivate this course'));
            }
        }
        else
        {
            $dialogBox->error(get_lang('This course is already activated'));
        }
    }
    
    if ( $cmd == 'exEdit' )
    {
        $course->handleForm();
        
        if ( $course->validate() )
        {
            if ( $course->save() )
            {
                $dialogBox->success( get_lang('The information have been modified') ) ;
                
                if ( ! $adminContext )
                {
                    // force reload of the "course session" of the user
                    $cidReset = true;
                    $cidReq = $current_cid;
                    include(get_path('incRepositorySys') . '/claro_init_local.inc.php');
                }
            }
            else
            {
                $dialogBox->error( get_lang('Unable to save') );
            }
        }
        else
        {
            $dialogBox->error( $course->backlog->output() );
        }
    }

    if ( $cmd == 'exDelete' )
    {
        if ( $course->delete() )
        {
            $claroline->log( 'DELETION COURSE' , array ('courseName' => $course->title, 'uid' => claro_get_current_user_id()));
            if( $adminContext )
            {
                claro_redirect( get_path('rootAdminWeb') . '/admin_courses.php');
            }
            else
            {
                claro_redirect(get_path('url') . '/index.php');
            }
        }
        else
        {
            $dialogBox->error( get_lang('Unable to delete') );
        }
    }

    if ( $cmd == 'rqDelete' )
    {
        $display = DISP_COURSE_RQ_DELETE;
    }

}
else
{
    // course data load failed
    claro_die(get_lang('Wrong parameters'));
}



// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'edit',
    'name' => get_lang('Edit Tool list'),
    'url' => claro_htmlspecialchars(Url::Contextualize(get_path('clarolineRepositoryWeb') . 'course/tools.php'))
);

// Main group settings
$cmdList[] = array(
    'img' => 'settings',
    'name' => get_lang('Main Group Settings'),
    'url' => claro_htmlspecialchars(Url::Contextualize(get_module_url('CLGRP') . '/group_properties.php'))
);

// Add tracking link
if ( get_conf('is_trackingEnabled') )
{
    $cmdList[] = array(
        'img' => 'statistics',
        'name' => get_lang('Statistics'),
        'url' => claro_htmlspecialchars(Url::Contextualize(get_path('clarolineRepositoryWeb') . 'tracking/courseReport.php'))
    );
}

// Add delete course link
if ( get_conf('showLinkToDeleteThisCourse') )
{
    $paramString = $course->getHtmlParamList('GET');

    $cmdList[] = array(
        'img' => 'delete',
        'name' => get_lang('Delete the whole course website'),
        'url' => claro_htmlspecialchars(Url::Contextualize(get_path('clarolineRepositoryWeb')
               . 'course/settings.php?cmd=rqDelete'
               . ( !empty($paramString) ? '&'.$paramString : '')))
    );
}

if ( $adminContext && claro_is_platform_admin() )
{
    // Switch to admin breadcrumb
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
    unset($_cid);

    $cmdList[] = array(
        'img' => 'back',
        'name' => get_lang('Back to course list'),
        'url' => claro_htmlspecialchars(Url::Contextualize($backUrl))
    );
}



//=================================
// Display section
//=================================

$out = '';

$out .= claro_html_tool_title($nameTools, null, $cmdList);

$out .= $dialogBox->render();

if( $display == DISP_COURSE_EDIT_FORM )
{
    // Display form
    $out .= $course->displayForm($backUrl);
}
elseif( $display == DISP_COURSE_RQ_DELETE )
{
    // display delete confirmation request
    $out .= $course->displayDeleteConfirmation();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

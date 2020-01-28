<?php // $Id: login.php 14388 2013-02-12 13:14:08Z zefredz $

/**
 * CLAROLINE
 *
 * This script allows users to log on platform and back to requested ressource.
 *
 * @version     $Revision: 14388 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLAUTH
 * @author      Claro Team <cvs@claroline.net>
 */

require '../inc/claro_init_global.inc.php';
require_once get_path('incRepositorySys').'/lib/course_user.lib.php';

// Keep the username in session
if (isset($_REQUEST['login']))
{
    $_SESSION['lastUserName'] = strip_tags($_REQUEST['login']);
}

// Capture the source of the authentication's trigger to get back to it
if ( isset($_REQUEST['fromPortal']) && $_REQUEST['fromPortal'] == 'true' && !isset($_REQUEST['sourceUrl']) )
{
    $sourceUrl = null;
}
elseif ( isset( $_REQUEST['sourceUrl'] ) )
{
    if ( strstr( base64_decode( $_REQUEST['sourceUrl'] ), 'logout=true' ) )
    {
        $sourceUrl = base64_encode( get_path( 'rootWeb' ) );
    }
    else
    {
        $sourceUrl = $_REQUEST['sourceUrl'];
    }
}
elseif ( isset($_SERVER ['HTTP_REFERER'])
         &&   basename($_SERVER ['HTTP_REFERER']) != basename($_SERVER['PHP_SELF'])
         && ! strstr($_SERVER ['HTTP_REFERER'], 'logout=true') )
{
     $sourceUrl = base64_encode($_SERVER ['HTTP_REFERER']);
}
elseif ( isset( $_SERVER ['HTTP_REFERER'] )
    && basename($_SERVER ['HTTP_REFERER']) != basename($_SERVER['PHP_SELF'])
    && strstr($_SERVER ['HTTP_REFERER'], 'logout=true') )
{
    $sourceUrl = base64_encode( get_path( 'rootWeb' ) );
}
else
{
    $sourceUrl = null;
}

// Immediatly redirect to the CAS authentication process
// If CAS is the only authentication system enabled
if (get_conf('claro_CasEnabled',false) && ! get_conf('claro_displayLocalAuthForm',true))
{
    claro_redirect($_SERVER['PHP_SELF'] . '?authModeReq=CAS&sourceUrl='.urlencode($sourceUrl));
}

if ( $sourceUrl )
{
    $sourceUrl = claro_htmlspecialchars($sourceUrl);
}
else
{
    $sourceUrl = '';
}

if (claro_is_in_a_course())
{
    $sourceCid = claro_htmlspecialchars(claro_get_current_course_id());
}
else
{
    $sourceCid = '';
}

if (claro_is_in_a_group())
{
    $sourceGid = claro_htmlspecialchars(claro_get_current_group_id());
}
else
{
    $sourceGid = '';
}

$cidRequired = (isset($_REQUEST['cidRequired']) ? $_REQUEST['cidRequired'] : false);

//TODO: possibility to continue in anonymous
$uidRequired = true;

// The script needs the user to be authentificated
if (!claro_is_user_authenticated() && $uidRequired)
{
    $defaultLoginValue  = '';
    $dialogBox          = new DialogBox;
    
    if (isset($_SESSION['lastUserName']))
    {
        $defaultLoginValue = strip_tags($_SESSION['lastUserName']);
        unset($_SESSION['lastUserName']);
    }
    
    if (get_conf('claro_displayLocalAuthForm',true) == true)
    {
        if ( $claro_loginRequested && ! $claro_loginSucceeded ) // var comming from claro_init_local.inc.php
        {
            if (AuthManager::getFailureMessage())
            {
                // need to use get_lang two times...
                $dialogBox->error( get_lang( AuthManager::getFailureMessage() ) );
            }
            else
            {
                $dialogBox->error( get_lang('Login failed.') . ' ' . get_lang('Please try again.') );
            }
            
            if (get_conf('allowSelfReg', false))
            {
                $dialogBox->warning( get_lang('If you haven\'t a user account yet, use the <a href="%url">the account creation form</a>.',array('%url'=> get_path('url') . '/claroline/auth/inscription.php')) );
            }
            else
            {
                $dialogBox->error( get_lang('Contact your administrator.') );
            }
            
            $dialogBox->warning( get_lang('Warning the system distinguishes uppercase (capital) and lowercase (small) letters') );
        }
        
        if (get_conf('claro_secureLogin', false))
        {
            $formAction = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
            
        }
        else
        {
            $formAction = $_SERVER['PHP_SELF'];
        }
    } // end if claro_dispLocalAuthForm
    
    $template = new CoreTemplate('auth_form.tpl.php');
    $template->assign('dialogBox', $dialogBox);
    $template->assign('formAction', $formAction);
    $template->assign('sourceUrl', $sourceUrl);
    $template->assign('sourceCid', $sourceCid);
    $template->assign('sourceGid', $sourceGid);
    $template->assign('cidRequired', $cidRequired);
    $template->assign('defaultLoginValue', $defaultLoginValue);
    $template->assign('sourceUrl', $sourceUrl);
    
    $claroline->display->body->appendContent($template->render());
    
    echo $claroline->display->render();
}
// The script needs a course id, but no course is set
elseif (!claro_is_in_a_course() && $cidRequired)
{
    $tbl                = claro_sql_get_main_tbl();
    $sql = "
            SELECT c.code                                             AS `value`,
                   CONCAT(c.intitule,' (',c.administrativeNumber,')') AS `name`
            FROM `" . $tbl['course'] . "`          AS c ,
                 `" . $tbl['rel_course_user'] . "` AS cu
            WHERE c.code = cu.code_cours
            AND cu.user_id = " . (int) claro_get_current_user_id();
    
    $courseList = claro_sql_query_fetch_all($sql);
    
    $template = new CoreTemplate('select_course_form.tpl.php');
    $template->assign('formAction', $_SERVER['PHP_SELF']);
    $template->assign('sourceUrl', $sourceUrl);
    $template->assign('sourceCid', $sourceCid);
    $template->assign('sourceGid', $sourceGid);
    $template->assign('cidRequired', $cidRequired);
    $template->assign('courseList', $courseList);
    
    $claroline->display->body->appendContent($template->render());
    
    echo $claroline->display->render();
}
// Login succeeded
else
{
    if(!isset($userLoggedOnCas))
        $userLoggedOnCas = false;
    
    $claroline->notifier->event( 'user_login', array('data' => array('ip' => $_SERVER['REMOTE_ADDR']) ) );
    
    if ( claro_is_in_a_course() && ! claro_is_course_allowed() )
    {
        $out = '';
        
        if ( $_course['registrationAllowed'] )
        {
            if ( claro_is_user_authenticated() )
            {
                if (claro_is_current_user_enrolment_pending())
                {
                    // enrolment pending message displayed by body.tpl
                }
                else
                {
                    // Display link to student to enrol to this course
                    $out .= '<p align="center">' . "\n"
                          . get_lang('Your user profile doesn\'t seem to be enrolled on this course').'<br />'
                          . get_lang('If you wish to enrol on this course') . ' : '
                          . ' <a href="' . get_path('clarolineRepositoryWeb') . 'auth/courses.php?cmd=exReg&amp;course=' . urlencode(  claro_get_current_course_id () ) . '">'
                          . get_lang('Enrolment').'</a>' . "\n"
                          . '</p>' . "\n";
                }
            }
            elseif ( get_conf('allowSelfReg') )
            {
                // Display a link to anonymous to register on the platform
                $out .= '<p align="center">' . "\n"
                      . get_lang('Create first a user account on this platform') . ' : '
                      . '<a href="' . get_path('clarolineRepositoryWeb') . 'auth/inscription.php">'
                      . get_lang('Go to the account creation page')
                      . '</a>'."\n"
                      . '</p>'."\n";
            }
            else
            {
                // Anonymous cannot register on the platform
                $out .= '<p align="center">'."\n"
                      . get_lang('Registration not allowed on the platform')
                      . '</p>'."\n";
            }
        }
        else
        {
        // Enrolment is not allowed for this course
            $out .= '<p align="center">'."\n"
                  . get_lang('Enrol to course not allowed');
            
            if ($_course['email'] && $_course['titular'])
            {
                $out .= '<br />' . get_lang('Please contact course titular(s)') . ' : ' . $_course['titular']
                      . '<br /><small>' . get_lang('Email') . ' : <a href="mailto:' . $_course['email'] .'">' . $_course['email']. '</a>';
            }
            $out .= '</p>' . "\n";
        }
        
        $claroline->display->body->appendContent($out);
        
        echo $claroline->display->render();
    }
    elseif($userLoggedOnCas && isset($_SESSION['casCallBackUrl']))
    {
        claro_redirect($_SESSION['casCallBackUrl']);
    }
    // Send back the user to the script authentication trigger
    elseif( isset($sourceUrl) )
    {
        $sourceUrl = base64_decode($sourceUrl);
        
        if (isset($_REQUEST['sourceCid']) )
        {
            $sourceUrl .= ( strstr( $sourceUrl, '?' ) ? '&' : '?')
                       .  'cidReq=' . $_REQUEST['sourceCid'];
        }
        
        if (isset($_REQUEST['sourceGid']))
        {
            $sourceUrl .= ( strstr( $sourceUrl, '?' ) ? '&' : '?')
                       .  'gidReq=' . $_REQUEST['sourceGid'];
        }
        
        if ( !preg_match('/^http/', $sourceUrl) && get_conf('claro_secureLogin', false) )
        {
            $sourceUrl = 'http://'.$_SERVER['HTTP_HOST'].$sourceUrl;
        }
        
        claro_redirect($sourceUrl);
    }
    elseif ( claro_is_in_a_course() )
    {
        // claro_redirect(get_path('coursesRepositoryWeb') . '/' . claro_get_course_path());
        if ( get_conf('claro_secureLogin', false) )
        {
            claro_redirect('http://'.$_SERVER['HTTP_HOST'].get_path('clarolineRepositoryWeb').'claroline/course?cid='.claro_get_current_course_id());
        }
        else
        {
            claro_redirect(get_path('clarolineRepositoryWeb').'claroline/course?cid='.claro_get_current_course_id());
        }
    }
    else
    {
        if ( get_conf('claro_secureLogin', false) )
        {
            claro_redirect('http://'.$_SERVER['HTTP_HOST'].get_path('clarolineRepositoryWeb'));
        }
        else
        {
            claro_redirect(get_path('clarolineRepositoryWeb'));
        }
    }
}

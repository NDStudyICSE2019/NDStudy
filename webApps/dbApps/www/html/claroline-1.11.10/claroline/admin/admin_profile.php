<?php // $Id: admin_profile.php 14470 2013-06-13 07:39:57Z zefredz $

/**
 * CLAROLINE
 *
 * Management tools for users' profiles.
 *
 * @version     $Revision: 14470 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     ADMIN
 * @author      Guillaume Lederer <lederer@claroline.net>
 * @author      claro team <cvs@claroline.net>
 */

$cidReset = true;
$gidReset = true;
$tidReset = true;

define( 'CSRF_PROTECTED', true );

require '../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

// Include configuration
include claro_get_conf_repository() . 'user_profile.conf.php';

// Include libraries
require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/file.lib.php';
require_once get_path('incRepositorySys') . '/lib/image.lib.php';
require_once get_path('incRepositorySys') . '/lib/fileUpload.lib.php';
require_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
require_once get_path('incRepositorySys') . '/lib/display/dialogBox.lib.php';

// Initialise variables
$dialogBox  = new DialogBox;

// Breadcrumb
ClaroBreadCrumbs::getInstance()->append( get_lang('Administration'), get_path('rootAdminWeb') );
if( isset($_REQUEST['cfrom']) && $_REQUEST['cfrom'] == 'ulist')
{
    ClaroBreadCrumbs::getInstance()->append( get_lang('User list'), get_path('rootAdminWeb') . 'admin_users.php' );
}
ClaroBreadCrumbs::getInstance()->append( get_lang('User settings'), $_SERVER['REQUEST_URI']);

/*=====================================================================
  Main Section
 =====================================================================*/

$userId     = isset($_REQUEST['uidToEdit'])?(int)$_REQUEST['uidToEdit']:null;

if (empty($userId))
{
    $dialogBox->error(get_lang('Cannot find user'));
}
else
{
    $userData = user_get_properties($userId);
    $user_extra_data = user_get_extra_data($userId);
}

if (!empty($user_extra_data) && count($user_extra_data) > 0)
{
    $dgExtra = new claro_datagrid(user_get_extra_data($userId));
}
else
{
    $dgExtra = null;
}

if ( isset($_REQUEST['applyChange']) ) // For formular modification
{
    // Get input from the form
    $userData = user_initialise();
    
    // Handle user picture
    $pictureUpdated = user_handle_profile_picture($userData);
    
    if ($pictureUpdated['success'])
    {
        $userData['picture'] = $pictureUpdated['pictureName'];
        foreach ($pictureUpdated['messages'] as $success)
        {
            $dialogBox->success($success);
        }
    }
    else
    {
        foreach ($pictureUpdated['messages'] as $error)
        {
            $dialogBox->error($error);
        }
    }
    
    // Validate form input
    $messageList = user_validate_form_admin_user_profile($userData, $userId);
    
    if (count($messageList) == 0)
    {
        if (empty($userData['password']))
        {
            unset($userData['password']);
        }
        
        // Save informations
        user_set_properties($userId, $userData);
        set_user_property($userId, 'skype', $userData['skype']);
        
        if ( $userId == claro_get_current_user_id() ) // re-init system to take new settings in account
        {
            $uidReset = true;
            include get_path('incRepositorySys') . '/claro_init_local.inc.php';
        }
        
        $dialogBox->success( get_lang('Changes have been applied to the user settings') );
    }
    else // user validate form return error messages
    {
        $dialogBox->error( get_lang('Changes have not been applied to the user settings') );
        foreach ( $messageList as $message )
        {
            $dialogBox->error($message);
        }
    }
} // if apply changes


// Command list
$cmdList = array();

if (!empty($userId))
{
    $cmdList[] = array(
        'img' => 'enroll',
        'name' => get_lang('Enrol to a new course'),
        'url' => claro_htmlspecialchars('../auth/courses.php'
               . '?cmd=rqReg'
               . '&uidToEdit=' . $userId
               . '&fromAdmin=settings'
               . '&category=')
    );
    
    $cmdList[] = array(
        'img' => 'mail_close',
        'name' => get_lang('Send account information to user by email'),
        'url' => claro_htmlspecialchars('../auth/lostPassword.php'
               . '?Femail=' . urlencode($userData['email'])
               . '&searchPassword=1')
    );
    
    $cmdList[] = array(
        'img' => 'course',
        'name' => get_lang('User course list'),
        'url' => claro_htmlspecialchars('adminusercourses.php?uidToEdit='
               . $userData['user_id'])
    );
    
    $cmdList[] = array(
        'img' => 'deluser',
        'name' => get_lang('Delete user'),
        'url' => claro_htmlspecialchars('adminuserdeleted.php'
               . '?uidToEdit='.$userId.'&cmd=rqDelete')
    );
    
    $cmdList[] = array(
        'name' => get_lang('Send a message to the user'),
        'url' => claro_htmlspecialchars('../messaging/sendmessage.php'
               . '?cmd=rqMessageToUser'
               . '&userId='.$userId)
    );
    
    if (isset($_REQUEST['cfrom']) && $_REQUEST['cfrom'] == 'ulist' ) // if we come form user list, we must display go back to list
    {
        $cmdList[] = array(
            'img' => 'back',
            'name' => get_lang('Back to user list'),
            'url' => claro_htmlspecialchars('admin_users.php')
        );
    }
    elseif (isset($_REQUEST['cfrom']) && $_REQUEST['cfrom'] == 'culist' ) // if we come form course user list, we must display go back to list
    {
        $cid = isset($_REQUEST['cid']) ? $_REQUEST['cid'] : null;
        
        $cmdList[] = array(
            'img' => 'back',
            'name' => get_lang('Back to user list'),
            'url' => claro_htmlspecialchars(get_path('url').'/claroline/user/user.php?cidReq='.$cid.'&cidReset=true')
        );
    }
}


// Display
$out = '';

// Tool title
if ( !empty( $userId ) )
{
    $titleParts = array(
        'mainTitle' => get_lang('User settings'),
        'subTitle' => $userData['firstname'].' '.$userData['lastname']
    );
}
else
{
    $titleParts = array(
        'mainTitle' => get_lang('User settings')
    );
}

$out .= claro_html_tool_title($titleParts, null, $cmdList)
      . $dialogBox->render();

if (!empty($userId))
{
    $out .= user_html_form($userId);
}

if (!empty($dgExtra))
{
    $out .= $dgExtra->render();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

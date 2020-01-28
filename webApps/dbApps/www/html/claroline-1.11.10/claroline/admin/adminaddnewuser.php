<?php // $Id: adminaddnewuser.php 13658 2011-10-05 12:46:57Z ffervaille $

/**
 * CLAROLINE
 *
 * Management tools for new users.
 *
 * @version     $Revision: 13658 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

define('DISP_REGISTRATION_SUCCEED','DISP_REGISTRATION_SUCCEED');
define('DISP_REGISTRATION_FORM','DISP_REGISTRATION_FORM');
$cidReset = true;
$gidReset = true;
$tidReset = true;
require '../inc/claro_init_global.inc.php';

// Security Check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

// Include library
require claro_get_conf_repository() . 'user_profile.conf.php';

require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/sendmail.lib.php';

// Initialise variables
$nameTools = get_lang('Create a new user');
$error = false;
$messageList = array();
$display = DISP_REGISTRATION_FORM;

$dialogBox = new DialogBox;

/*=====================================================================
  Main Section
 =====================================================================*/

if ( isset($_REQUEST['cmd']) ) $cmd = $_REQUEST['cmd'];
else                           $cmd = '';

if ( $cmd == 'registration' )
{
    // get params from the form
    $userData = user_initialise();
    
    $userData['language'] = null;
    // validate forum params
    
    $messageList = user_validate_form_registration($userData);
    
    if ( count($messageList) == 0 )
    {
        // register the new user in the claroline platform
        $userId = user_create($userData);
        
        if (false===$userId)
        {
            $dialogBox->error( claro_failure::get_last_failure() );
        }
        else
        {
            $dialogBox->success( get_lang('The new user has been sucessfully created') );
            
            $newUserMenu[]= claro_html_cmd_link( '../auth/courses.php?cmd=rqReg&amp;uidToEdit=' . $userId . '&amp;category=&amp;fromAdmin=settings'
                                               , get_lang('Register this user to a course'));
            $newUserMenu[]= claro_html_cmd_link( 'admin_profile.php?uidToEdit=' . $userId . '&amp;category='
                                               , get_lang('User settings'));
            $newUserMenu[]= claro_html_cmd_link( 'adminaddnewuser.php'
                                               , get_lang('Create another new user'));
            $newUserMenu[]= claro_html_cmd_link( 'index.php'
                                               , get_lang('Back to administration page'));
            
            $display = DISP_REGISTRATION_SUCCEED;
            
            // Send a mail to the user
            if (false !== user_send_registration_mail($userId, $userData))
            {
                $dialogBox->success( get_lang('Mail sent to user') );
            }
            else
            {
                $dialogBox->warning( get_lang('No mail sent to user') );
                // TODO  display in a popup "To Print" with  content to give to user.
            };
        }
    }
    else
    {
        // User validate form return error messages
        if( is_array($messageList) && !empty($messageList) )
        {
            foreach( $messageList as $message )
            {
                $dialogBox->error($message);
            }
        }
        $error = true;
    }
}

/*=====================================================================
  Display Section
 =====================================================================*/

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$noQUERY_STRING   = true;

if ( $display == DISP_REGISTRATION_FORM )
{
    $dialogBox->info( get_lang('New users will receive an e-mail with their username and password') );
}

$out = '';

// Display title
$out .= claro_html_tool_title( array('mainTitle'=>$nameTools ) )
      . $dialogBox->render();

if ( $display == DISP_REGISTRATION_SUCCEED )
{
    $out .= claro_html_list($newUserMenu);
}
else // $display == DISP_REGISTRATION_FORM;
{
    $out .= user_html_form();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
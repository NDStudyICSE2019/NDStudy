<?php // $Id: adminuserunregistered.php 12941 2011-03-10 15:25:18Z abourguignon $

/**
 * CLAROLINE
 *
 * @version     $Revision: 12941 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/CLUSR
 * @package     CLUSR
 * @package     CLCOURSES
 * @author      Claro Team <cvs@claroline.net>
 */

$cidReset = true;
$gidReset = true;
$tidReset = true;

require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';

include claro_get_conf_repository() . 'user_profile.conf.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

$nameTools = get_lang('User settings');
$dialogBox = new DialogBox();

// BC
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$user_id = $_REQUEST['uidToEdit'];

//------------------------------------
// Execute COMMAND section
//------------------------------------

if ( isset($_REQUEST['cmd'] ) && claro_is_platform_admin() )
{
    if ( $_REQUEST['cmd'] == 'UnReg' )
    {
        if ( user_remove_from_course($user_id, $_REQUEST['cidToEdit'],true, false) )
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
                    $dialogBox ->error( get_lang('Course manager cannot unsubscribe himself') );
                    break;
                default :
            }
        }
    }
}

/**
 * PREPARE DISPLAY
 */

$cmdList[] = '<a class="claroCmd" href="index.php">' . get_lang('Back to administration page') . '</a>';
$cmdList[] = '<a class="claroCmd" href="adminusercourses.php?uidToEdit=' . $user_id.'">' . get_lang('Back to course list') . '</a>';

/**
 * DISPLAY
 */

$out = '';

$out .= claro_html_tool_title(get_lang('User unregistered'));

// Display Forms or dialog box(if needed)

$out .= $dialogBox->render();

$out .= '<p>'
.    claro_html_menu_horizontal($cmdList)
.    '</p>'
;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
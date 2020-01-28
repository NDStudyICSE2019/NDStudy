<?php // $Id: admin_class_course_registered.php 12941 2011-03-10 15:25:18Z abourguignon $

/**
 * CLAROLINE
 *
 * Management tools for users registered to courses.
 *
 * @version     $Revision: 12941 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Guillaume Lederer <lederer@cerdecam.be>
 * @author      Christophe Gesche <moosh@claroline.net>
 */

require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

include claro_get_conf_repository() . 'user_profile.conf.php'; // find this file to modify values.

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

//bredcrump
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Class registered');

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$class_id = isset($_REQUEST['class_id'])?$_REQUEST['class_id']:0;
$course_id = isset($_REQUEST['course_id'])?$_REQUEST['course_id']:null;

$dialogBox = new DialogBox();

//------------------------------------
// Execute COMMAND section
//------------------------------------

if (isset($cmd) && claro_is_platform_admin())
{
    if ($cmd == 'exReg')
    {
        $resultLog = register_class_to_course($class_id,$course_id);
        
        if ( isset($resultLog['OK']) && is_array($resultLog['OK']) )
        {
            foreach($resultLog['OK'] as $thisUser)
            {
                $dialogBox->success( get_lang('<i>%firstname %lastname</i> has been sucessfully registered to the course',array('%firstname'=>$thisUser['firstname'], '%lastname'=>$thisUser['lastname'])) . '<br />' );
            }
        }

        if ( isset($resultLog['KO']) && is_array($resultLog['KO']) )
        {
            foreach($resultLog['KO'] as $thisUser)
            {
                $dialogBox->error( get_lang('<i>%firstname %lastname</i> has not been sucessfully registered to the course',array('%firstname'=>$thisUser['firstname'], '%lastname'=>$thisUser['lastname'])) . '<br />' );
            }
        }
    }
	elseif ($cmd == 'exUnreg')
    {
    	if (unregister_class_to_course($class_id,$course_id))
    	{
    		$dialogBox->success( get_lang('Class has been unenroled') );
    	}
    }
}

/**
 * PREPARE DISPLAY
 */

$classinfo = class_get_properties($class_id);

$cmdList[] =  '<a class="claroCmd" href="index.php">' . get_lang('Back to administration page') . '</a>';
$cmdList[] =  '<a class="claroCmd" href="' . 'admin_class_user.php?class_id=' . $classinfo['id'] . '">' . get_lang('Back to class members') . '</a>';
$cmdList[] =  '<a class="claroCmd" href="' . get_path('clarolineRepositoryWeb') . 'auth/courses.php?cmd=rqReg&amp;fromAdmin=class' . '">' . get_lang('Register class for course') . '</a>';

/**
 * DISPLAY
 */
$out = '';

$out .= claro_html_tool_title(get_lang('Class registered') . ' : ' . $classinfo['name']);

$out .= $dialogBox->render();

$out .=  '<p>'
.    claro_html_menu_horizontal($cmdList)
.    '</p>'
;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
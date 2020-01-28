<?php // $Id: admin_user_course_settings.php 14576 2013-11-07 09:27:59Z zefredz $

/**
 * CLAROLINE
 *
 * This tool edit status of user in a course.
 *
 * @version     $Revision: 14576 $
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

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';

include claro_get_conf_repository() . 'user_profile.conf.php'; // find this file to modify values.

// used tables
$tbl_mdb_names = claro_sql_get_main_tbl();

// deal with session variables (must unset variables if come back from enroll script)
unset($_SESSION['userEdit']);

$nameTools=get_lang('User course settings');
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );


// see which user we are working with ...

if ( isset($_REQUEST['uidToEdit']) && isset($_REQUEST['cidToEdit']) )
{
    $uidToEdit = (int) $_REQUEST['uidToEdit'];
    $cidToEdit = strip_tags( $_REQUEST['cidToEdit'] );
}
else
{
    claro_die('Missing parameters');
}

$courseData = claro_get_course_data($cidToEdit);

if ( ! $courseData )
{
    unset($_REQUEST['cidToEdit']);
    claro_die( 'ERROR : COURSE NOT FOUND!!!' );
}

$dialogBox = new DialogBox();

//------------------------------------
// Execute COMMAND section
//------------------------------------

//Display "form and info" about the user

$ccfrom = isset($_REQUEST['ccfrom'])?$_REQUEST['ccfrom']:'';
$cfrom  = isset($_REQUEST['cfrom'])?$_REQUEST['cfrom']:'';

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null ;

switch ($cmd)
{
    case 'exUpdateCourseUserProperties' :

        if ( isset($_REQUEST['profileId']) )
        {
            $properties['profileId'] = $_REQUEST['profileId'];
        }

        if ( isset($_REQUEST['isTutor']) )
        {
            $properties['tutor'] = (int) $_REQUEST['isTutor'];
        }
        else
        {
            $properties['tutor'] = 0 ;
        }

        if ( isset($_REQUEST['role']) )
        {
            $properties['role'] = trim($_REQUEST['role']);
        }

        $done = user_set_course_properties($uidToEdit, $cidToEdit, $properties);

        if ( ! $done )
        {
            $dialogBox->warning( get_lang('No change applied') );
        }
        elseif( !empty( $properties['profileId'] ) )
        {
            if ( claro_get_profile_label($properties['profileId']) == 'manager' )
            {
                $dialogBox->success( get_lang('User is now course manager') );
            }
            else
            {
                $dialogBox->success( get_lang('User is now student for this course') );
            }
        }

    break;
}

//------------------------------------
// FIND GLOBAL INFO SECTION
//------------------------------------

if ( isset($uidToEdit) )
{
    // get course user info
    $courseUserProperties = course_user_get_properties($uidToEdit, $cidToEdit);
}

//------------------------------------
// PREPARE DISPLAY
//------------------------------------

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance()->addLangVar('Are you sure you want to unregister %name ?');

JavascriptLoader::getInstance()->load('admin');

$displayBackToCU = false;
$displayBackToUC = false;
if ( 'culist'== $ccfrom )//coming from courseuser list
{
    $displayBackToCU = TRUE;
}
elseif ('uclist'== $ccfrom)//coming from usercourse list
{
    $displayBackToUC = TRUE;
}

$cmd_menu[] = '<a class="claroCmd" href="adminuserunregistered.php'
.             '?cidToEdit=' . $cidToEdit
.             '&amp;cmd=UnReg'
.             '&amp;uidToEdit=' . $uidToEdit . '" '
.             ' onclick="return ADMIN.confirmationUnReg(\'' . clean_str_for_javascript(claro_htmlspecialchars($courseUserProperties['firstName']) . ' ' . claro_htmlspecialchars($courseUserProperties['lastName'])) . '\');">'
.             get_lang('Unsubscribe')
.             '</a>'
;

$cmd_menu[] = '<a class="claroCmd" href="admin_profile.php'
.             '?uidToEdit=' . $uidToEdit . '">'
.             get_lang('User settings')
.             '</a>'
;

//link to go back to list : depend where we come from...

if ( $displayBackToCU )//coming from courseuser list
{
    $cmd_menu[] = '<a class="claroCmd" href="admincourseusers.php'
    .             '?cidToEdit=' . $cidToEdit
    .             '&amp;uidToEdit=' . $uidToEdit . '">'
    .             get_lang('Back to list')
    .             '</a> ' ;
}
elseif ( $displayBackToUC )//coming from usercourse list
{
    $cmd_menu[] = '<a class="claroCmd" href="adminusercourses.php'
    .             '?cidToEdit=' . $cidToEdit
    .             '&amp;uidToEdit=' . $uidToEdit . '">'
    .             get_lang('Back to list')
    .             '</a> ' ;
}

//------------------------------------
// DISPLAY
//------------------------------------
$out = '';
// Display tool title

$out .= claro_html_tool_title( array( 'mainTitle' =>$nameTools
                                 , 'subTitle' => get_lang('Course') . ' : '
                                              .  claro_htmlspecialchars($courseUserProperties['courseName'])
                                              .  '<br />'
                                              .  get_lang('User') . ' : '
                                              .  claro_htmlspecialchars($courseUserProperties['firstName'])
                                              .  ' '
                                              .  claro_htmlspecialchars($courseUserProperties['lastName'])
                                 )
                          );

// Display Forms or dialog box(if needed)
$out .= $dialogBox->render();

$hidden_param = array( 'uidToEdit' => $uidToEdit,
                       'cidToEdit' => $cidToEdit,
                       'cfrom' => $cfrom,
                       'ccfrom' => $ccfrom);

$out .= course_user_html_form ( $courseUserProperties, $cidToEdit, $uidToEdit, $hidden_param )
.    '<p>'
.    claro_html_menu_horizontal($cmd_menu)
.    '</p>'
;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
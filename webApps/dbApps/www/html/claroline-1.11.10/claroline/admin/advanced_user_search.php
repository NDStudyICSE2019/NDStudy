<?php // $Id: advanced_user_search.php 12969 2011-03-14 14:40:42Z abourguignon $

/**
 * CLAROLINE
 *
 * Management tools for users research.
 *
 * @version     $Revision: 12969 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLUSR
 * @author      Claro Team <cvs@claroline.net>
 * @author      Guillaume Lederer <lederer@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 */

$cidReset = true;
$gidReset = true;
$tidReset = true;

require '../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

include_once(get_path('incRepositorySys') . '/lib/admin.lib.inc.php');
include_once(get_path('incRepositorySys') . '/lib/form.lib.php');

//-----------------------------------------------------------------------------------------------------------
//  USED SESSION VARIABLES
//-----------------------------------------------------------------------------------------------------------
// deal with session variables clean session variables from previous search


unset($_SESSION['admin_user_letter']);
unset($_SESSION['admin_user_search']);
unset($_SESSION['admin_user_firstName']);
unset($_SESSION['admin_user_lastName']);
unset($_SESSION['admin_user_userName']);
unset($_SESSION['admin_user_officialCode']);
unset($_SESSION['admin_user_mail']);
unset($_SESSION['admin_user_action']);
unset($_SESSION['admin_order_crit']);

//declare needed tables
$tbl_mdb_names    = claro_sql_get_main_tbl();
$tbl_course_nodes = $tbl_mdb_names['category'];

// Deal with interbredcrumps  and title variable

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Advanced user search');

//retrieve needed parameters from URL to prefill search form

if (isset($_REQUEST['action']))    $action    = $_REQUEST['action'];    else $action = '';
if (isset($_REQUEST['lastName']))  $lastName  = $_REQUEST['lastName'];  else $lastName = '';
if (isset($_REQUEST['firstName'])) $firstName = $_REQUEST['firstName']; else $firstName = '';
if (isset($_REQUEST['userName']))  $userName  = $_REQUEST['userName'];  else $userName = '';
if (isset($_REQUEST['officialCode']))  $userName  = $_REQUEST['officialCode'];  else $officialCode = '';
if (isset($_REQUEST['mail']))      $mail      = $_REQUEST['mail'];      else $mail = '';

$action_list[get_lang('All')] = 'all';
$action_list[get_lang('Student')] = 'followcourse';
$action_list[get_lang('Course creator')] = 'createcourse';
$action_list[get_lang('Platform administrator')] = 'plateformadmin';

//header and bredcrump display

/////////////
// OUTPUT

$out = '';
$out .= claro_html_tool_title($nameTools . ' : ');

$tpl = new CoreTemplate('advanced_user_search.tpl.php');
$tpl->assign('lastName', $lastName);
$tpl->assign('firstName', $firstName);
$tpl->assign('userName', $userName);
$tpl->assign('officialCode', $officialCode);
$tpl->assign('mail', $mail);
$tpl->assign('action', $action);
$tpl->assign('action_list', $action_list);

$out .= $tpl->render();

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
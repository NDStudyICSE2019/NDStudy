<?php // $Id: index.php 14195 2012-07-04 13:17:51Z zefredz $

/**
 * CLAROLINE
 *
 * Admin panel.
 *
 * @version     $Revision: 14195 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     ADMIN
 * @author      claro team <cvs@claroline.net>
 */

$cidReset = true;
$gidReset = true;
require '../inc/claro_init_global.inc.php';

// Security check
if ( !claro_is_user_authenticated() ) claro_disp_auth_form();
if ( !claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';

//------------------------
//  USED SESSION VARIABLES
//------------------------

// Clean session of possible previous search information (COURSE)
unset($_SESSION['admin_course_code']);
unset($_SESSION['admin_course_search']);
unset($_SESSION['admin_course_intitule']);
unset($_SESSION['admin_course_category']);
unset($_SESSION['admin_course_language']);
unset($_SESSION['admin_course_access']);
unset($_SESSION['admin_course_subscription']);
unset($_SESSION['admin_course_order_crit']);


// Deal with session variables clean session variables from previous search (USER)

// TODO : these unset should disappear
unset($_SESSION['admin_user_search']);
unset($_SESSION['admin_user_firstName']);
unset($_SESSION['admin_user_lastName']);
unset($_SESSION['admin_user_userName']);
unset($_SESSION['admin_user_mail']);
unset($_SESSION['admin_user_action']);
unset($_SESSION['admin_order_crit']);

$dialogBox = new DialogBox();

// Set the administration menus
// ============================

// Users' administration menu
$menu['AdminUser'][] = get_lang('Search for a user').'<br />'
                     . '<form name="searchUser" action="admin_users.php" method="get">' . "\n"
                     . '<input type="text" name="search" id="search_user" class="inputSearch" />&nbsp;'
                     . '<input type="submit" value="' . get_lang('Go') . '" />'
                     . '&nbsp;'
                     . '<small>'
                     . '<a href="advanced_user_search.php">'
                     . get_lang('Advanced')
                     . '</a>'
                     . '</small>'
                     . '</form>';

$menu['AdminUser'][] = '<a href="admin_users.php">'.get_lang('User list').'</a>';
$menu['AdminUser'][] = '<a href="../messaging/sendmessage.php?cmd=rqMessageToAllUsers">'.get_lang('Send a message to all users').'</a>';
$menu['AdminUser'][] = '<a href="adminaddnewuser.php">'.get_lang('Create user').'</a>';
$menu['AdminUser'][] = '<a href="../user/addcsvusers.php?AddType=adminTool">'.get_lang('Add a user list').'</a>';
$menu['AdminUser'][] = '<a href="admin_class.php">'.get_lang('Manage classes').'</a>';
$menu['AdminUser'][] = '<a href="right/profile_list.php">'.get_lang('Right profile list').'</a>';
$menu['AdminUser'][] = '<a href="../desktop/config.php">'.get_lang('Manage user desktop').'</a>';
$menu['AdminUser'][] = '<a href="adminmergeuser.php">'.get_lang('Merge user accounts').'</a>';

// Courses' administration menu
$menu['AdminCourse'][] = get_lang('Search for a course').'<br />'
                       . '<form name="searchCourse" action="admin_courses.php" method="get">' . "\n"
                       . '<input type="text" name="search" id="search_course" class="inputSearch" />&nbsp;'
                       . '<input type="submit" value="' . get_lang('Go'). '" />'
                       . '&nbsp;<small><a href="advanced_course_search.php">' . get_lang('Advanced') . '</a></small>' . "\n"
                       . '</form>';

$menu['AdminCourse'][] = '<a href="admin_courses.php">'.get_lang('Course list').'</a>';
$menu['AdminCourse'][] = '<a href="../course/create.php?adminContext=1">'.get_lang('Create course').'</a>';
$menu['AdminCourse'][] = '<a href="admin_category.php">'.get_lang('Manage course categories').'</a>';

// Platform's administration menu
$menu['AdminPlatform'][] = '<a href="tool/config_list.php">'.get_lang('Configuration').'</a>';
$menu['AdminPlatform'][] = '<a href="managing/editFile.php">'.get_lang('Edit text zones').'</a>';
$menu['AdminPlatform'][] = '<a href="module/module_list.php">'.get_lang('Modules').'</a>';
$menu['AdminPlatform'][] = '<a href="adminmailsystem.php">'.get_lang('Manage administrator email notifications').'</a>';


if (file_exists(dirname(__FILE__) . '/maintenance/checkmails.php'))
{
    $menu['AdminPlatform'][] = '<a href="maintenance/checkmails.php">'.get_lang('Check and Repair emails of users').'</a>';
}

// Claroline's administration menu
$menu['AdminClaroline'][] = '<a href="http://www.claroline.net/index.php?plugin=formidable&controller=forms&frm_action=preview&form=o4x38v">'.get_lang('Register my campus').'</a>';
$menu['AdminClaroline'][] = '<a href="http://forum.claroline.net/">'.get_lang('Support forum').'</a>';
$menu['AdminClaroline'][] = '<a href="clarolinenews.php">'.get_lang('Claroline.net news').'</a>';

// Technical's administration menu
$menu['AdminTechnical'][] = '<a href="technical/phpInfo.php">'.get_lang('System Info').'</a>';
$menu['AdminTechnical'][] = '<a href="technical/files_stats.php">'.get_lang('Files statistics').'</a>';

$menu['AdminTechnical'][] = '<a href="../tracking/platform_report.php">'.get_lang('Platform statistics').'</a>';
$menu['AdminTechnical'][] = '<a href="campusProblem.php">'.get_lang('Scan technical fault').'</a>';
$menu['AdminTechnical'][] = '<a href="upgrade/index.php">'.get_lang('Upgrade').'</a>';

// Communication's administration menu
$menu['Communication'][] = '<a href="../messaging/admin.php">'.get_lang('Internal messaging').'</a>';

$adminModuleList = get_admin_module_list(true);

if ( count( $adminModuleList ) > 0 )
{
    foreach ( $adminModuleList as $module )
    {
        language::load_module_translation($module['label']);
        
        $menu['ExtraTools'][] = '<a href="'.get_module_entry_url($module['label']).'">'.get_lang($module['name']).'</a>';
    }
}

// Deal with interbreadcrumbs and title variable
$nameTools = get_lang('Administration');

// No sense because not allowed with claro_is_platform_admin(),
// but claro_is_platform_admin() should be later replaced by
// get_user_property ('can view admin menu')
$is_allowedToAdmin     = claro_is_platform_admin();

// Is our installation system accessible ?
if (file_exists('../install/index.php') && ! file_exists('../install/.htaccess'))
{
    // If yes, warn the administrator
    $dialogBox->warning(get_block('blockWarningRemoveInstallDirectory'));
}

$register_globals_value = ini_get('register_globals');

// Is the php 'register_globals' param enable ?
if (!empty($register_globals_value) && strtolower($register_globals_value) != 'off')
{
    // If yes, warn the administrator
    $dialogBox->warning(get_lang('<b>Security :</b> We recommend to set register_globals to off in php.ini'));
}

$template = new CoreTemplate('admin_panel.tpl.php');
$template->assign('dialogBox', $dialogBox);
$template->assign('menu', $menu);

$claroline->display->body->appendContent($template->render());

echo $claroline->display->render();

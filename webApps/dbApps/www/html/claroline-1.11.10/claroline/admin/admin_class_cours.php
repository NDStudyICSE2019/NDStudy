<?php //$Id: admin_class_cours.php 14450 2013-05-15 12:02:23Z zefredz $

/**
 * CLAROLINE
 *
 * Management tools for courses' classes.
 *
 * @version     $Revision: 14450 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Damien Garros <dgarros@univ-catholyon.fr>
 */

$userPerPage = 50; // numbers of cours to display on the same page

// initialisation of global variables and used libraries
require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/pager.lib.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

/**#@+
 * DB tables definition
 * @var $tbl_mdb_names array table name for the central database
 */

$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_cours               = $tbl_mdb_names['course'];
$tbl_course_class          = $tbl_mdb_names['rel_course_class'];
$tbl_class              = $tbl_mdb_names['class'];

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance ()->addLangVar('Are you sure you want to unregister %name ?');

JavascriptLoader::getInstance()->load('admin');

//------------------------------------
// Execute COMMAND section
//------------------------------------

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$class_id = isset($_REQUEST['class_id'])?(int)$_REQUEST['class_id']:0;
$course_id = isset($_REQUEST['course_id'])?$_REQUEST['course_id']:null;

// find info about the class

if ( ($classinfo = class_get_properties ($class_id)) === false )
{
    $class_id = 0;
}

if ( !empty($class_id) )
{
    switch ($cmd)
    {
        case 'unsubscribe' :
            unregister_class_to_course($class_id,$course_id);
            break;

        default :
            // No command
    }

    //find this class current content
    // TODO Factorise this statement
    $sql = "SELECT distinct (cc.`courseId`), c.`code`, c.`language`,
            c.`intitule`, c.`titulaires`
            FROM `".$tbl_course_class."` cc, `".$tbl_cours."` c
            WHERE c.`code` = cc.`courseId`
            AND cc.`classId` = '". $class_id ."'";

    // deal with session variables for search criteria

    if (isset($_REQUEST['dir'])) {$_SESSION['admin_user_class_dir']  = ($_REQUEST['dir']=='DESC'?'DESC':'ASC');}

    // first see is direction must be changed

    if (isset($_REQUEST['chdir']) && ($_REQUEST['chdir']=="yes"))
    {
        if     ($_SESSION['admin_course_class_dir'] == 'ASC')  {$_SESSION['admin_course_class_dir']='DESC';}
        elseif ($_SESSION['admin_course_class_dir'] == 'DESC') {$_SESSION['admin_course_class_dir']='ASC';}
    }
    elseif (!isset($_SESSION['admin_course_class_dir']))
    {
        $_SESSION['admin_course_class_dir'] = 'DESC';
    }

    // deal with REORDER

    if (isset($_REQUEST['order_crit']))
    {
        $_SESSION['admin_course_class_order_crit'] = $_REQUEST['order_crit'];
        if ($_REQUEST['order_crit']=='user_id')
        {
            $_SESSION['admin_course_class_order_crit'] = 'U`.`user_id';
        }
    }

    if (isset($_SESSION['admin_course_class_order_crit']))
    {
        $toAdd = " ORDER BY `".$_SESSION['admin_course_class_order_crit'] . "` " . $_SESSION['admin_course_class_dir'];
        $sql.=$toAdd;
    }

    //Build pager with SQL request
    if (!isset($_REQUEST['offset'])) $offset = "0";
    else                             $offset = $_REQUEST['offset'];

    $myPager = new claro_sql_pager($sql, $offset, $userPerPage);
    $resultList = $myPager->get_result_list();

}

//------------------------------------
// DISPLAY
//------------------------------------

$dialogBox = new DialogBox();

// Deal with interbredcrumps
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Classes'), get_path('rootAdminWeb'). 'admin_class.php' );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Class members');

$out = '';

if ( empty($class_id) )
{
    $dialogBox->error( get_lang('Class not found') );
    $out .= $dialogBox->render();
}
else
{
    $cmdList = array();
    
    $cmdList[] = array(
        'img' => 'enroll', 
        'name' => get_lang('Register class for course'), 
        'url' => claro_htmlspecialchars(get_path('clarolineRepositoryWeb')
               . 'auth/courses.php?'
               . 'cmd=rqReg&fromAdmin=class&class_id='.$class_id)
    );
    
    // Display tool title
    $out .= claro_html_tool_title(
        get_lang('Course list') . ' : ' . $classinfo['name'], 
        null,
        $cmdList);
    
    // Pager
    $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'].'&amp;class_id='.$class_id);
    
    // Display list of cours
    
    // start table...
    // TODO datagrid
    $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'
    .    '<thead>'
    .    '<tr align="center" valign="top">'
    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=code&amp;chdir=yes">' . get_lang('Course code') . '</a></th>'
    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=intitule&amp;chdir=yes">' . get_lang('Course title') . '</a></th>'
    .     '<th>' . get_lang('Course settings') . '</th>'
    .    '<th>' . get_lang('Unregister from class') . '</th>'
    .    '</tr>'
    .    '</thead>'
    .    '<tbody>'
    ;
    
    // Start the list of users
    foreach($resultList as $list)
    {
        $list['officialCode'] = (isset($list['officialCode']) ? $list['officialCode'] :' - ');
        
        $out .= '<tr>'
        .    '<td align="center" >' . $list['code']      . '</td>'
        .    '<td align="left" >'   . $list['intitule']          . '</td>'
        .     '<td align="center">'
        .    '<a href="../course/settings.php?adminContext=1'
        // TODO cfrom=xxx is probably a hack
        .    '&amp;cidReq=' . $list['code'] . '&amp;cfrom=xxx">'
        .    '<img src="' . get_icon_url('settings') . '" alt="' . get_lang('Course settings') . '" />'
        .    '</a>'
        .    '</td>'
        .    '<td align="center">'
        .    '<a href="'.$_SERVER['PHP_SELF']
        .    '?cmd=unsubscribe&amp;class_id='.$class_id.'&amp;offset='.$offset.'&amp;course_id='.$list['code'].'" '
        .    ' onclick="return ADMIN.confirmationUnReg(\''.clean_str_for_javascript($list['code']).'\');">'
        .    '<img src="' . get_icon_url('unenroll') . '" alt="" />'
        .    '</a>'
        .    '</td>'
        .    '</tr>';
    }
    
    // end display users table
    if ( empty($resultList) )
    {
        $out .= '<tr>'
        .    '<td colspan="5" align="center">'
        .    get_lang('No course to display')
        .    '<br />'
        .    '<a href="' . get_path('clarolineRepositoryWeb') . 'admin/admin_class.php">'
        .    get_lang('Back')
        .    '</a>'
        .    '</td>'
        .    '</tr>'
        ;
    }
    
    $out .= '</tbody>' . "\n"
    .    '</table>' . "\n"
    ;
    
    //Pager
    $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'].'&amp;class_id='.$class_id);
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

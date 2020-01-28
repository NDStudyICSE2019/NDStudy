<?php //$Id: admin_class_register.php 14583 2013-11-08 12:32:46Z zefredz $

/**
 * CLAROLINE
 *
 * Management tools for users registration to classes.
 *
 * @version     $Revision: 14583 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Guillaume Lederer <lederer@cerdecam.be>
 */

// initialisation of global variables and used libraries

require '../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/pager.lib.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

$userPerPage = 20; // numbers of user to display on the same page

/*
 * DB tables definition
 */
$tbl_mdb_names  = claro_sql_get_main_tbl();
$tbl_user       = $tbl_mdb_names['user'];
$tbl_class      = $tbl_mdb_names['user_category'];
$tbl_class_user = $tbl_mdb_names['user_rel_profile_category'];

// Main section

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$user_id = isset($_REQUEST['user_id'])?(int)$_REQUEST['user_id']:0;
$class_id = isset($_REQUEST['class_id'])?(int)$_REQUEST['class_id']:0;
$search = isset($_REQUEST['search'])?trim($_REQUEST['search']):'';

// find info about the class

if ( ($classinfo = class_get_properties ($class_id)) === false )
{
    $class_id = 0;
}

$dialogBox = new DialogBox();

if ( !empty($class_id) )
{
    switch ( $cmd )
    {
        case 'subscribe' :
            if ( user_add_to_class($user_id,$class_id) )
            {
                $dialogBox->success( get_lang('User has been sucessfully registered to the class') );
            }
            break;

        case 'unsubscribe' :
            if ( user_remove_to_class($user_id,$class_id) )
            {
                $dialogBox->success( get_lang('User has been sucessfully unregistered from the class') );
            }
            break;
    }

    //----------------------------------
    // Build query and find info in db
    //----------------------------------

    $sql = "SELECT *, U.`user_id`
            FROM  `" . $tbl_user . "` AS U
            LEFT JOIN `" . $tbl_class_user . "` AS CU
                   ON  CU.`user_id` = U.`user_id`
                  AND CU.`class_id` = " . (int) $class_id;

    if ( !empty($search) )
    {
        $escapedSearchTerm = claro_sql_escape($search);

        $sql .= " WHERE (U.nom LIKE '%". $escapedSearchTerm ."%'
                  OR U.prenom LIKE '%". $escapedSearchTerm ."%'
                  OR U.email LIKE '%".  $escapedSearchTerm ."%'
                  OR U.username LIKE '".  $escapedSearchTerm ."%'
                  OR U.officialCode = '".  $escapedSearchTerm ."')";
    }

    // deal with REORDER

    // See SESSION variables used for reorder criteria :

    if (isset($_REQUEST['dir'])) $_SESSION['admin_class_reg_user_order_crit'] = ($_REQUEST['dir']=='DESC'?'DESC':'ASC');
    else                         $_REQUEST['dir'] = 'ASC';

    $acceptedCritValues = array( 'user_id', 'nom', 'prenom' );

    if (isset($_REQUEST['order_crit']) && in_array( $_REQUEST['order_crit'], $acceptedCritValues ) )
    {
        $order_crit = $_REQUEST['order_crit'];
        $_SESSION['admin_class_reg_user_order_crit'] = $_REQUEST['order_crit'];
        if ($_REQUEST['order_crit']=="user_id")
        {
            $_SESSION['admin_class_reg_user_order_crit'] = "U`.`user_id";
        }
    }
    else
    {
       $order_crit = 'nom';
       $_SESSION['admin_class_reg_user_order_crit'] = 'nom';
       $_SESSION['admin_class_reg_user_dir'] = 'ASC';
    }

    // first if direction must be changed

    if (isset($_REQUEST['chdir']) && ($_REQUEST['chdir']=="yes"))
    {
      if ($_SESSION['admin_class_reg_user_dir'] == "ASC") {$_SESSION['admin_class_reg_user_dir']="DESC";}
      elseif ($_SESSION['admin_class_reg_user_dir'] == "DESC") {$_SESSION['admin_class_reg_user_dir']="ASC";}
    }
    elseif (!isset($_SESSION['admin_class_reg_user_dir']))
    {
        $_SESSION['admin_class_reg_user_dir'] = 'DESC';
    }

    if (isset($_SESSION['admin_class_reg_user_order_crit']))
    {
        if ($_SESSION['admin_class_reg_user_order_crit']=="user_id")
        {
            $toAdd = " ORDER BY CU.`user_id` ".$_SESSION['admin_class_reg_user_dir'];
        }
        elseif ( $_SESSION['admin_class_reg_user_order_crit'] != 'nom')
        {    
            $toAdd = " ORDER BY `".$_SESSION['admin_class_reg_user_order_crit']."` ".$_SESSION['admin_class_reg_user_dir'];
        }
        else
        {
            if ($_SESSION['admin_class_reg_user_dir'] == 'ASC')
            {
                $toAdd = " ORDER BY `".$_SESSION['admin_class_reg_user_order_crit']."` ".$_SESSION['admin_class_reg_user_dir']. ",`prenom` ASC ";
            }
            else
            {
                $toAdd = " ORDER BY `".$_SESSION['admin_class_reg_user_order_crit']."` ".$_SESSION['admin_class_reg_user_dir']. ",`prenom` DESC ";
            }            
        }
        $sql.=$toAdd;
    }

    //Build pager with SQL request

    if (!isset($_REQUEST['offset'])) $offset = '0';
    else                             $offset = $_REQUEST['offset'];

    $myPager = new claro_sql_pager($sql, $offset, $userPerPage);
    $resultList = $myPager->get_result_list();
}

//------------------------------------
// DISPLAY
//------------------------------------

// Deal with interbredcrumps
// We have to prepend in reverse order !!!
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Class members'), get_path('rootAdminWeb') . 'admin_class_user.php?class_id='.$class_id );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Classes'), get_path('rootAdminWeb') . 'admin_class.php' );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$nameTools = get_lang('Register users to class');

$out = '';

if ( empty($class_id) )
{
    $dialogBox->error( get_lang('Class not found') );
    $out .= $dialogBox->render();
}
else
{
    // Display tool title

    $out .= claro_html_tool_title($nameTools . ' : ' . $classinfo['name']);

    // Display Forms or dialog box(if needed)
    $out .= $dialogBox->render();

    // Display tool link

    $out .= '<p><a class="claroCmd" href="' . get_path('clarolineRepositoryWeb').'admin/admin_class_user.php?class_id='.$class_id.'">'.
         get_lang('Class members').'</a></p>'."\n";

    if (isset($_REQUEST['cfrom']) && ($_REQUEST['cfrom']=='clist')) $out .= claro_html_button('admin_courses.php', get_lang('Back to course list'));

    // Display search form
    $out .= '<div style="text-align: right">'."\n"
    .    '<form action="' . $_SERVER['PHP_SELF'] . '" method="GET">' . "\n"
    .    '<input type="hidden" name="class_id" value="'.$class_id. '" />' . "\n"
    .    '<input type="text" value="' . claro_htmlspecialchars($search).'" name="search" id="search" size="20" />' . "\n"
    .    '<input type="submit" value=" ' . get_lang('Search') . ' " />' . "\n"
    .    '</form>'."\n"
    .    '</div>' . "\n"
    ;

    // Display pager

    $out .= $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'].'?class_id='.$class_id . ('&order_crit=' . $order_crit ) . ( isset( $_REQUEST['dir'] ) ? '&dir=' . $_REQUEST['dir'] : '' ). ( isset( $_REQUEST['search'] ) ? '&search=' . $_REQUEST['search'] : '' ) );

    // Display list of users
    // start table...

	if ($search == '')
	{
	    $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n"
	    .    '<thead>' . "\n"
	    .    '<tr align="center" valign="top">'
	    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=user_id&amp;chdir=yes">' . get_lang('User id') . '</a></th>' . "\n"
	    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=nom&amp;chdir=yes"    >' . get_lang('Last name') . '</a></th>' . "\n"
	    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;order_crit=prenom&amp;chdir=yes" >' . get_lang('First name') . '</a></th>' . "\n"
	    .    '<th>' . get_lang('Register to the class') . '</th>'
	    .    '<th>' . get_lang('Unregister from class') . '</th>'
	    .    '</tr>' . "\n"
	    .    '</thead>' . "\n"
	    .    '<tbody>' . "\n"
	    ;
	}
	else
	{
		 $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n"
	    .    '<thead>' . "\n"
	    .    '<tr align="center" valign="top">'
	    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;search='.$search.'&amp;order_crit=user_id&amp;chdir=yes">' . get_lang('User id') . '</a></th>' . "\n"
	    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;search='.$search.'&amp;order_crit=nom&amp;chdir=yes"    >' . get_lang('Last name') . '</a></th>' . "\n"
	    .    '<th><a href="' . $_SERVER['PHP_SELF'] . '?class_id='.$class_id.'&amp;search='.$search.'&amp;order_crit=prenom&amp;chdir=yes" >' . get_lang('First name') . '</a></th>' . "\n"
	    .    '<th>' . get_lang('Register to the class') . '</th>'
	    .    '<th>' . get_lang('Unregister from class') . '</th>'
	    .    '</tr>' . "\n"
	    .    '</thead>' . "\n"
	    .    '<tbody>' . "\n"
	    ;
	}

    // Start the list of users...

    foreach ( $resultList as $list )
    {
         $out .= '<tr>'
         .    '<td align="center">'
         .    '<a name="u' . $list['user_id'] . '"></a>' // no label in the a it's a target.
         .    $list['user_id'] . '</td>' . "\n"
         .    '<td align="left">' . $list['nom']    . '</td>' . "\n"
         .    '<td align="left">' . $list['prenom'] . '</td>' . "\n"
         ;
         // Register

         if ($list['id']==null)
         {
             $out .= '<td align="center">' . "\n"
             .    '<a href="' . $_SERVER['PHP_SELF'] . '?class_id=' . $class_id . '&amp;cmd=subscribe&user_id=' . $list['user_id'].'&amp;offset=' . $offset . '#u' . $list['user_id'] . '">' . "\n"
             .    '<img src="' . get_icon_url('enroll') . '" alt="' . get_lang('Register to the class') . '" />' . "\n"
             .    '</a>' . "\n"
             .    '</td>' . "\n"
             ;
         }
         else
         {
             $out .= '<td align="center">' . "\n"
             .    '<small>' . get_lang('User already in class') . '</small>' . "\n"
             .    '</td>' . "\n"
             ;
         }

        // Unregister

         if ($list['id']!=null)
         {
             $out .= '<td align="center">' . "\n"
             .    '<a href="'.$_SERVER['PHP_SELF'].'?class_id='.$class_id.'&amp;cmd=unsubscribe&user_id='.$list['user_id'].'&amp;offset='.$offset.'#u'.$list['user_id'].'">' . "\n"
             .    '<img src="' . get_icon_url('unenroll') . '" alt="' . get_lang('Unregister from class').'" />' . "\n"
             .    '</a>' . "\n"
             .    '</td>' . "\n"
             ;
         }
         else
         {
             $out .= '<td align="center">' . "\n"
             .    '<small>' . get_lang('User not in the class') . '</small>' . "\n"
             .    '</td>' . "\n"
             ;
         }
         $out .= '</tr>' . "\n";
    }

    // end display users table

    $out .= '</tbody>' . "\n"
    .    '</table>' . "\n"
    ;

    //Pager

    $out .= $myPager->disp_pager_tool_bar($_SERVER['PHP_SELF'].'?class_id='.$class_id . ('&order_crit=' . $order_crit ) . ( isset( $_REQUEST['dir'] ) ? '&dir=' . $_REQUEST['dir'] : '' ). ( isset( $_REQUEST['search'] ) ? '&search=' . $_REQUEST['search'] : '' ) );

}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
<?php // $Id: module_dock.php 14370 2013-01-30 14:52:26Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14370 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     ADMIN
 * @author      Claro Team <cvs@claroline.net>
 */

require '../../inc/claro_init_global.inc.php';

//SECURITY CHECK

if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

//DECLARE NEEDED LIBRARIES

require_once get_path('incRepositorySys') . '/lib/pager.lib.php';
require_once get_path('incRepositorySys') . '/lib/module/manage.lib.php';

//SQL table name

$tbl_name        = claro_sql_get_main_tbl();
$tbl_module      = $tbl_name['module'];
$tbl_dock        = $tbl_name['dock'];

$dialogBox = new DialogBox();

if ( isset($_REQUEST['dock']) )
{
    $dockList = get_dock_list('applet');
    $dock = $_REQUEST['dock'];
    $dockName = isset($dockList[$dock]) ? $dockList[$dock] : $dock ;
    $nameTools = get_lang('Dock') . ' : ' . $dockName;
}
else
{
    $dock = null;
    $dialogBox->error( get_lang('No dock selected') );
    $nameTools = get_lang('Dock');
}

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Module list'), get_path('rootAdminWeb').'module/module_list.php' );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

//CONFIG and DEVMOD vars :

$modulePerPage = get_conf('moduleDockPerPage' , 10);

//----------------------------------
// EXECUTE COMMAND
//----------------------------------

$cmd = (isset($_REQUEST['cmd'])? $_REQUEST['cmd'] : null);
$module_id = (isset($_REQUEST['module_id'])? $_REQUEST['module_id'] : null);

if ( !empty($dock))
{
    switch ( $cmd )
    {
        case 'up' :
        {
            move_module_in_dock($module_id, $dock,'up');
        }
        break;

        case 'down' :
        {
            move_module_in_dock($module_id, $dock,'down');
        }
        break;

        case 'remove' :
        {
            remove_module_dock($module_id,$dock);
            $dialogBox->success( get_lang('The module has been removed from this dock') );
        }
        break;
    }

    //----------------------------------
    // FIND INFORMATION
    //----------------------------------

    $sql = "SELECT M.`id`              AS `id`,
                   M.`label`           AS `label`,
                   M.`name`            AS `name`,
                   M.`activation`      AS `activation`,
                   M.`type`            AS `type`,
                   D.`rank`            AS `rank`
            FROM `" . $tbl_module . "` AS M, `" . $tbl_dock . "` AS D
            WHERE D.`module_id`= M.`id`
              AND D.`name` = '".$dock."'
            ORDER BY `rank`
            ";

    //pager creation

    $offset       = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0 ;
    $myPager      = new claro_sql_pager($sql, $offset, $modulePerPage);
    //$pagerSortDir = isset($_REQUEST['dir' ]) ? $_REQUEST['dir' ] : SORT_ASC;
    $moduleList   = $myPager->get_result_list();

}

//----------------------------------
// DISPLAY
//----------------------------------

$out = '';

//display title

$out .= claro_html_tool_title($nameTools);

//Display Forms or dialog box(if needed)

$out .= $dialogBox->render();

if ( !empty($dock) )
{

    //Display TOP Pager list

    $out .= $myPager->disp_pager_tool_bar('module_dock.php?dock='.$dock);

    // start table...

    $out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">'
    .    '<thead>'
    .    '<tr align="center" valign="top">'
    .    '<th>' . get_lang('Icon')               . '</th>'
    .    '<th>' . get_lang('Module name')        . '</th>'
    .    '<th colspan="2">' . get_lang('Order')           .'</th>'
    .    '<th>' . get_lang('Remove from the dock')          . '</th>'
    .    '</tr>'
    .    '</thead>'
    .    '<tbody>'
    ;

    $iteration = 1;
    $enditeration = sizeof($moduleList);

    foreach($moduleList as $module)
    {
        //display settings...
        $class_css= ($module['activation']=='activated' ? 'item' : 'invisible item');

        //find icon

        if (file_exists(get_module_path($module['label']) . '/icon.png'))
        {
            $icon = '<img src="' . get_module_url($module['label']) . '/icon.png" />';
        }
        elseif (file_exists(get_module_path($module['label']) . '/icon.gif'))
        {
            $icon = '<img src="' . get_module_url($module['label']) . '/icon.gif" />';
        }
        else $icon = '<small>' . get_lang('No icon') . '</small>';


        //module_id and icon column

        $out .= '<tr>'
        .    '<td align="center">' . $icon . '</td>' . "\n";

        //name column

        if (file_exists(get_module_path($module['label']) . '/admin.php'))
        {
            $out .= '<td align="left" class="' . $class_css . '" ><a href="'. get_module_url($module['label']) . '/admin.php" >' . $module['name'] . '</a></td>' . "\n";
        }
        else
        {
            $out .= '<td align="left" class="' . $class_css . '" >' . $module['name'] . '</td>' . "\n";
        }

        //reorder column

        //up

        $out .= '<td align="center">' . "\n";
        if (!($iteration==1))
        {
            $out .= '<a href="module_dock.php?cmd=up&amp;module_id=' . $module['id'] . '&amp;dock='.urlencode($dock).'">'
            .    '<img src="' . get_icon_url('move_up') . '" alt="' . get_lang('Move up') . '" />'
            .    '</a>' . "\n"
            ;
        }
        else
        {
            $out .= '&nbsp;';
        }
        $out .= '</td>' . "\n";

        //down

        $out .= '<td align="center">' . "\n";
        if ($iteration != $enditeration)
        {
            $out .= '<a href="module_dock.php?cmd=down&amp;module_id=' . $module['id'] . '&amp;dock=' . urlencode($dock) . '">'
            .    '<img src="' . get_icon_url('move_down') . '" alt="' . get_lang('Move down') . '" />'
            .    '</a>'
            ;
        }
        else
        {
            $out .= '&nbsp;';
        }
        $out .= '</td>' . "\n";

        //remove links

        $out .= '<td align="center">' . "\n"
        .    '<a href="module_dock.php?cmd=remove&amp;module_id=' . $module['id'] . '&amp;dock=' . urlencode($dock) . '">'
        .    '<img src="' . get_icon_url('delete') . '" alt="' . get_lang('Delete') . '" />'
        .    '</a>'
        .    '</td>' . "\n";

        $iteration++;
    }

    //end table...

    $out .= '</tbody>'
    .    '</table>';


    //Display BOTTOM Pager list

    $out .= $myPager->disp_pager_tool_bar('module_dock.php?dock='.$dock);

}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
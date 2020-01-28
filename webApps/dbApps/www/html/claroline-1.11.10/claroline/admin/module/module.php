<?php // $Id: module.php 14576 2013-11-07 09:27:59Z zefredz $

/**
 * Claroline extension modules settings script.
 *
 * @version     $Revision: 14576 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GENERAL PUBLIC LICENSE
 *  version 2 or later
 * @package     ADMIN
 * @author      Claro team <cvs@claroline.net>
 * @since       1.8
 */

require '../../inc/claro_init_global.inc.php';

//SECURITY CHECK

if ( ! claro_is_user_authenticated() )
{
    claro_disp_auth_form();
}

if ( ! claro_is_platform_admin() )
{
    claro_die(get_lang('Not allowed'));
}

//CONFIG and DEVMOD vars :

//SQL table name

$tbl_name        = claro_sql_get_main_tbl();
$tbl_module      = $tbl_name['module'];
$tbl_module_info = $tbl_name['module_info'];
$tbl_dock        = $tbl_name['dock'];

//NEEDED LIBRAIRIES

require_once get_path('incRepositorySys') . '/lib/module/manage.lib.php';
require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';

$undeactivable_tool_array = get_not_deactivable_tool_list();

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmMakeVisible ()
{
    if (confirm(\" ".clean_str_for_javascript(get_lang("Are you sure you want to make this module visible in all courses ?"))."\"))
        {return true;}
    else
        {return false;}
}
function confirmMakeInVisible ()
{
    if (confirm(\" ".clean_str_for_javascript(get_lang("Are you sure you want to make this module invisible in all courses ?"))."\"))
        {return true;}
    else
        {return false;}
}
</script>";

//----------------------------------
// GET REQUEST VARIABLES
//----------------------------------

$cmd = isset($_REQUEST['cmd'])
    ? $_REQUEST['cmd']
    : null
    ;

$item = isset($_REQUEST['item'])
    ? $_REQUEST['item']
    : 'GLOBAL'
    ;

$section_selected = isset($_REQUEST['section'])
    ? $_REQUEST['section']
    : null
    ;

$moduleId = isset($_REQUEST['module_id'])
    ? (int) $_REQUEST['module_id']
    : null
    ;

$module = get_module_info($moduleId);

if ( ! $module )
{
    claro_die("ERROR: INVALID MODULE ID!!!");
}

language::load_module_translation( $module['label'] );

$dockList = get_dock_list($module['type']);

$nameTools = get_lang('Module settings');
$noPHP_SELF=true;

// FIXME : BAD use of get_lang !!!!!
ClaroBreadCrumbs::getInstance()->prepend( get_lang($module['module_name']) );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Module list'), get_path('rootAdminWeb').'module/module_list.php?typeReq=' . $module['type'] );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools );

$dialogBox = new dialogBox();

//----------------------------------
// EXECUTE COMMAND
//----------------------------------

switch ( $cmd )
{
    case 'activplatformadmin' :
    {
        if ( allow_module_activation_by_course_manager( $module['label'], false ) )
        {
            $dialogBox->success( get_lang('Only PLATFORM_ADMIN can activate this module') );
            $module['accessManager']  = 'PLATFORM_ADMIN';
        }
        else
        {
            $dialogBox->error( get_lang('Cannot change module activation on course creation') );
        }
        break;
    }
    case 'activcoursemanager' :
    {
        if ( allow_module_activation_by_course_manager( $module['label'], true ) )
        {
            $dialogBox->success( get_lang('COURSE_ADMIN can activate this module') );
            $module['accessManager']  = 'COURSE_ADMIN';
        }
        else
        {
            $dialogBox->error( get_lang('Cannot change module activation on course creation') );
        }
        break;
    }
    case 'courseactiv' :
    {
        if ( set_module_autoactivation_in_course( $module['label'], true ) )
        {
            $dialogBox->success( get_lang('Module activation at course creation set to AUTOMATIC') );
            $module['activateInCourses']  = 'AUTOMATIC';
        }
        else
        {
            $dialogBox->error( get_lang('Cannot change module activation on course creation') );
        }
        break;
    }
    case 'coursedeactiv' :
    {
        if ( set_module_autoactivation_in_course( $module['label'], false ) )
        {
            $dialogBox->success( get_lang('Module activation at course creation set to MANUAL') );
            $module['activateInCourses']  = 'MANUAL';
        }
        else
        {
            $dialogBox->error( get_lang('Cannot change module activation on course creation') );
        }
        break;
    }
    case 'activ' :
    {
        if (activate_module($moduleId))
        {
            $dialogBox->success( get_lang('Module activation succeeded') );
            $module['activation']  = 'activated';
        }
        else
        {
            $dialogBox->error( get_lang('Cannot activate module') );
        }
        break;
    }
    case 'deactiv' :
    {
        if (deactivate_module($moduleId))
        {
            $dialogBox->success( get_lang('Module deactivation succeeded') );
            $module['activation']  = 'deactivated';
        }
        else
        {
            $dialogBox->error( get_lang('Cannot deactivate module') );
            $module['activation']  = 'activated';
        }
        break;
    }
    case 'movedock' :
    {
        if( is_array($dockList) )
        {
            if ( isset($_REQUEST['displayDockList']) && is_array($_REQUEST['displayDockList']) )
            {
                foreach ($dockList as $dockId => $dockName)
                {

                    if ( in_array($dockId,$_REQUEST['displayDockList']) )
                    {
                        add_module_in_dock($moduleId, $dockId);
                    }
                    else
                    {
                        remove_module_dock($moduleId, $dockId);
                    }
                }
            }
            $dialogBox->success( get_lang('Changes in the display of the module have been applied') );
        }
        break;
    }
    case 'makeVisible':
    case 'makeInvisible':
    {
        $visibility = ( 'makeVisible' == $cmd ) ? true : false;

        list ( $log, $success ) = set_module_visibility( $moduleId, $visibility );

        if ( $success )
        {
            $dialogBox->success( get_lang('Module visibility updated') );
        }
        else
        {
            $dialogBox->error( get_lang('Failed to update module visibility') );
        }

        break;
    }
}

// create an array with only dock names

$sql = "SELECT `name` AS `dockname`
        FROM `" . $tbl_dock        . "`
        WHERE `module_id` = " . (int) $moduleId;

$module_dock = claro_sql_query_fetch_all($sql);

$dock_checked = array();

foreach($module_dock as $thedock)
{
    $dock_checked[] = $thedock['dockname'];
}

//----------------------------------
// DISPLAY
//----------------------------------

$out = '';

// find module icon, if any

if (array_key_exists('icon',$module) && !empty($module['icon'])  && file_exists(get_module_path($module['label']) . '/' .$module['icon']))
{
    $icon = '<img src="' . get_module_url($module['label']) . '/' . $module['icon'] . '" alt="'.$module['label'].'" />';
}
elseif (file_exists(get_module_path($module['label']) . '/icon.png'))
{
    $icon = '<img src="' . get_module_url($module['label']) . '/icon.png" alt="'.$module['label'].'" />';
}
elseif (file_exists(get_module_path($module['label']) . '/icon.gif'))
{
    $icon = '<img src="' . get_module_url($module['label']) . '/icon.gif" alt="'.$module['label'].'" />';
}
else
{
    $icon = '<small>' . get_lang('No icon') . '</small>';
}

//display title

$out .= claro_html_tool_title($nameTools . ' : ' . get_lang($module['module_name']));

//Display Forms or dialog box(if needed)

$out .= $dialogBox->render();

//display tabbed navbar

$out .=  '<div>'
    . '<ul id="navlist">'
    . "\n"
    ;

//display the module type tabbed naviguation bar

if ($item == 'GLOBAL')
{
    $out .= '<li><a href="module.php?module_id='.$moduleId
        . '&amp;item=GLOBAL" class="current">'
        . get_lang('Global settings').'</a></li>'
        . "\n"
        ;
}
else
{
    $out .= '<li>'
    .    '<a href="module.php?module_id='.$moduleId.'&amp;item=GLOBAL">'
    .    get_lang('Global settings').'</a>'
    .    '</li>' . "\n"
    ;
}

$config_code = $module['label'];

// new config object
require_once get_path('incRepositorySys') . '/lib/configHtml.class.php';

$config = new ConfigHtml($config_code, $_SERVER['HTTP_REFERER']);

if ( $config->load() )
{
    if ($item == 'LOCAL')
    {
        $out .= '<li><a href="module.php?module_id='.$moduleId
            . '&amp;item=LOCAL" class="current">'
            . get_lang('Local settings').'</a></li>'
            . "\n"
            ;
    }
    else
    {
        $out .= '<li><a href="module.php?module_id='.$moduleId.'&amp;item=LOCAL">'
            . get_lang('Local settings').'</a></li>'
            . "\n"
            ;
    }
}

if ($item == 'About' || is_null($item))
{
    $out .= '<li><a href="module.php?module_id='.$moduleId
        . '&amp;item=About" class="current">'
        . get_lang('About').'</a></li>'
        . "\n"
        ;
}
else
{
    $out .= '<li><a href="module.php?module_id='.$moduleId.'&amp;item=About">'
        . get_lang('About').'</a></li>'
        . "\n"
        ;
}

$out .= '</ul>'. "\n"
    . '</div>'. "\n"
    ;

switch ($item)
{
    case 'GLOBAL':
    {
        $out .= claro_html_tool_title(array('subTitle' => get_lang('Platform settings')));
        
        $out .= '<dl class="onOneLine">';

        //Activation form
        if (in_array($module['label'],$undeactivable_tool_array))
        {
            $action_link = get_lang('This module cannot be deactivated');
        }
        elseif ( 'activated' == $module['activation'] )
        {
            $activ_form  = 'deactiv';
            $action_link = '<a href="'
                . claro_htmlspecialchars( $_SERVER['PHP_SELF']
                . '?cmd='.$activ_form.'&module_id='.$module['module_id']
                . '&item=GLOBAL' )
                . '" title="'.get_lang('Activated - Click to deactivate').'">'
                . '<img src="' . get_icon_url('on')
                . '" alt="'. get_lang('Activated') . '" /> '
                . get_lang('Activated') . '</a>'
                ;
        }
        else
        {
            $activ_form  = 'activ';
            $action_link = '<a href="'
                . claro_htmlspecialchars( $_SERVER['PHP_SELF']
                . '?cmd='.$activ_form.'&module_id='
                . $module['module_id'].'&item=GLOBAL')
                . '" title="'.get_lang('Deactivated - Click to activate').'">'
                . '<img src="' . get_icon_url('off')
                . '" alt="'. get_lang('Deactivated') . '"/> '
                . get_lang('Deactivated') . '</a>'
                ;
        }

        $out .= '<dt>'
          .    get_lang('Platform activation')
          .    ' : ' . "\n"
          .    '</dt>' . "\n"
          .    '<dd>' . "\n"
          .    $action_link . "\n"
          .    '</dd>' . "\n"
          ;

        if ($module['type'] == 'tool')
        {
            // Course activation automatic or manual ?
            if (in_array($module['label'],$undeactivable_tool_array))
            {
                // do not fuck with cthulhu !
                if ( 'AUTOMATIC' == $module['activateInCourses'] )
                {
                    $action_link = '<img src="' . get_icon_url('select')
                    . '" alt="'. get_lang('Automatic') . '" /> '
                    . get_lang('Automatic');
                }
                else
                {
                    $action_link = '<img src="' . get_icon_url('forbidden')
                    . '" alt="'. get_lang('Manual') . '"/> '
                    . get_lang('Manual');
                }
                
                $action_link .= ' (' . get_lang('Cannot be changed') . ')';
            }
            elseif ( 'AUTOMATIC' == $module['activateInCourses'] )
            {
                $activ_form  = 'coursedeactiv';
                $action_link = '<a href="'
                    . claro_htmlspecialchars( $_SERVER['PHP_SELF']
                    . '?cmd='.$activ_form.'&module_id='.$module['module_id']
                    . '&item=GLOBAL')
                    . '" title="' . get_lang('Automatic').'">'
                    . '<img src="' . get_icon_url('select')
                    . '" alt="'. get_lang('Automatic') . '" /> '
                    . get_lang('Automatic') . '</a>'
                    ;
            }
            else
            {
                $activ_form  = 'courseactiv';
                $action_link = '<a href="'
                    . claro_htmlspecialchars($_SERVER['PHP_SELF']
                    . '?cmd='.$activ_form.'&module_id='
                    . $module['module_id'].'&item=GLOBAL')
                    .'" title="'.get_lang('Manual').'">'
                    . '<img src="' . get_icon_url('forbidden')
                    . '" alt="'. get_lang('Manual') . '"/> '
                    . get_lang('Manual') . '</a>'
                    ;
            }
                
            $out .= '<dt>'
            .    get_lang('Activate on course creation')
            .    ' : ' . "\n"
            .    '</dt>' . "\n"
            .    '<dd>' . "\n"
            .    $action_link . "\n"
            .    '</dd>' . "\n"
            ;

            // Access Manager

            if (in_array($module['label'],$undeactivable_tool_array))
            {
                // do not fuck with cthulhu !
                if ( 'PLATFORM_ADMIN' == $module['accessManager'] )
                {
                    $action_link = '<img src="' . get_icon_url('platformadmin')
                    . '" alt="'. get_lang('Platform administrator') . '" /> '
                    . get_lang('Platform administrator');
                }
                else
                {
                    $action_link = '<img src="' . get_icon_url('manager')
                    . '" alt="'. get_lang('Course manager') . '"/> '
                    . get_lang('Course manager');
                }

                $action_link .= ' (' . get_lang('Cannot be changed') . ')';
            }
            elseif ( 'PLATFORM_ADMIN' == $module['accessManager'] )
            {
                $activ_form  = 'activcoursemanager';
                $action_link = '<a href="'
                    . claro_htmlspecialchars( $_SERVER['PHP_SELF']
                    . '?cmd='.$activ_form.'&module_id='.$module['module_id']
                    . '&item=GLOBAL')
                    .'" title="'. get_lang('Platform administrator').'">'
                    . '<img src="' . get_icon_url('platformadmin')
                    . '" alt="'. get_lang('Platform administrator') . '" /> '
                    . get_lang('Platform administrator') . '</a>'
                    ;
            }
            else
            {
                $activ_form  = 'activplatformadmin';
                $action_link = '<a href="'
                    . claro_htmlspecialchars( $_SERVER['PHP_SELF']
                    . '?cmd='.$activ_form.'&module_id='
                    . $module['module_id'].'&item=GLOBAL')
                    .'" title="'.get_lang('Course manager').'">'
                    . '<img src="' . get_icon_url('manager')
                    . '" alt="'. get_lang('Course manager') . '"/> '
                    . get_lang('Course manager') . '</a>'
                    ;
            }

            $out .= '<dt>'
            .    get_lang('In manual mode, module activable by')
            .    ' : ' . "\n"
            .    '</dt>' . "\n"
            .    '<dd>' . "\n"
            .    $action_link . "\n"
            .    '</dd>' . "\n"
            ;

            // Visibility
            
            $out .= '<dt>'
                . get_lang( 'Change visibility in all courses' )
                . ' : '
                .    '</dt>' . "\n"
                .    '<dd>' . "\n"
                . '<small><a href="'
                . claro_htmlspecialchars($_SERVER['PHP_SELF'] . '?module_id='
                . $module['module_id'].'&cmd=makeVisible&item=GLOBAL')
                .'" title="'.get_lang( 'Make module visible in all courses' ).'"'
                . ' onclick="return confirmMakeVisible();">'
                . '<img src="' . get_icon_url('visible')
                . '" alt="'. get_lang('Visible') . '"/> '
                . get_lang( 'Visible' )
                . '</a></small>'
                . " | "
                . '<small><a href="'
                . claro_htmlspecialchars($_SERVER['PHP_SELF'] . '?module_id='
                . $module['module_id'].'&cmd=makeInvisible&item=GLOBAL')
                . '" title="'.get_lang( 'Make module invisible in all courses' ).'"'
                . ' onclick="return confirmMakeInVisible();">'
                . '<img src="' . get_icon_url('invisible')
                . '" alt="'. get_lang('Invisible') . '"/> '
                . get_lang( 'Invisible' )
                . '</a></small>'
                . '</dd>' . "\n"
                ;

            $out .= '</dl>';
        }
        elseif ($module['type'] == 'applet')
        {
            //choose the dock radio button list display
            if ( is_array($dockList) && $module['type']!='tool')
            {
                $out .= '<form action="' . $_SERVER['PHP_SELF'] . '?module_id=' . $module['module_id'] . '&amp;item='.$item.'" method="post">'
                      . '<dt>' . get_lang('Display') . ' : </dt>' ."\n"
                      . '<dd>' ."\n"
                      . '<table>' ."\n"
                      . '<tbody>' ."\n";

                $i = 1;

                //display each option
                foreach ($dockList as $dockId => $dockName)
                {
                    if (in_array($dockId,$dock_checked)) $is_checked = 'checked="checked"'; else $is_checked = "";

                    $out .= '<tr>' ."\n"
                    .    '<td>' ."\n"
                    .    '<input type="checkbox" name="displayDockList[]" value="' . $dockId . '" id="displayDock_' . $i . '" ' . $is_checked . ' />'
                    .    '<label for="displayDock_' . $i . '">' . $dockName . '</label>'
                    .    '</td>' ."\n"
                    .    '</tr>' ."\n"
                    ;

                    $i++;
                }

                // display submit button
                $out .= '<tr>' ."\n"
                .    '<td>' . get_lang('Save') . '&nbsp;:' . "\n"
                .    '<input type="hidden" name="cmd" value="movedock" />'. "\n"
                .    '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp;'. "\n"
                .    claro_html_button(claro_htmlspecialchars($_SERVER['HTTP_REFERER']), get_lang('Cancel')) . '</td>' . "\n"
                .    '</tr>' . "\n"
                .    '</tbody>' . "\n"
                .    '</table>' . "\n"
                .    '</dd>'
                .    '</form>'
                ;
            }

            
        }
        else // not a tool, not an applet
        {
            // nothing to do at the moment
        }

        
        break;
    }
    case 'LOCAL':
    {
        $form = '';

        $url_params = '&module_id='. $moduleId .'&item='. claro_htmlspecialchars($item);

        $form = $config->display_section_menu($section_selected,$url_params);

           // init config name
        $config_name = $config->config_code;

        if ( isset($_REQUEST['cmd']) && isset($_REQUEST['property']) )
        {
            if ( 'save' == $_REQUEST['cmd'] )
            {
                if ( ! empty($_REQUEST['property']) )
                {
                    list($message, $error) = generate_conf($config,$_REQUEST['property']);
                }
            }
            // display form
            $form .= $config->display_form($_REQUEST['property'],$section_selected,$url_params);
        }
        else
        {
            // display form
            $form .= $config->display_form(null,$section_selected,$url_params);
        }

        $out .= '<div style="padding-left:1em;padding-right:1em;">';

        if ( ! empty($message) )
        {
            $dialogBox = new DialogBox();
            $dialogBox->success ( $message );
            $out .= $dialogBox->render();
        }

        $out .= $form . '</div>';

        break;
    }
    default:
    {
        $moduleDescription = trim( $module['description'] );

        $moduleDescription = (empty( $moduleDescription ) )
            ? get_lang('No description')
            : $moduleDescription
            ;

        $out .= claro_html_tool_title(array('subTitle' => get_lang('Description')))
        .    '<p>'
        .    claro_htmlspecialchars( $moduleDescription )
        .    '</p>' . "\n"
        ;

        $out .= claro_html_tool_title(array('subTitle' => get_lang('General Informations'))) . "\n"
        .    '<table>' . "\n"
        .    '<tr>' . "\n"
        .    '<td colspan="2">' . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">'
        .    get_lang('Icon')
        .    ' : </td>' . "\n"
        .    '<td>' . "\n"
        .    $icon . "\n"
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Module name') . ' : </td>' . "\n"
        .    '<td >' . $module['module_name'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Type') . ' : </td>' . "\n"
        .    '<td>' . $module['type'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Version') . ' : </td>' . "\n"
        .    '<td >' . $module['version'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('License') . ' : </td>' . "\n"
        .    '<td >General Public License</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Author') . ' : </td>' . "\n"
        .    '<td >' . $module['author'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Contact') . ' : </td>' . "\n"
        .    '<td >' . $module['author_email'] . '</td>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' . "\n"
        .    '<td align="right">' . get_lang('Website') . ' : </td>' . "\n"
        .    '<td><a href="' . $module['website'] . '">' . $module['website'] . '</a></td>' . "\n"
        .    '</tr>' . "\n"
        .    '</table>' . "\n"
        ;
    }
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
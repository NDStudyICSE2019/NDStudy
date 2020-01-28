<?php // $Id: tools.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * Claroline Course Tool List management script.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GENERAL PUBLIC LICENSE
 *              version 2 or later
 * @package     CLHOME
 * @author      Claro Team <cvs@claroline.net>
 */

$gidReset = true; // If user is here. It means he isn't in any group space now.
                  // So it's careful to to reset the group setting

require '../inc/claro_init_global.inc.php';

$nameTools  = get_lang('Edit Tool list');
$noPHP_SELF = TRUE;

if ( ! claro_is_in_a_course() || ! claro_is_user_authenticated() ) claro_disp_auth_form(true);

if ( claro_is_course_manager() ) $is_allowedToEdit = TRUE;
else                   claro_die(get_lang('Not allowed'));

// Prepare menu for claro_html_tabs_bar
$sectionList = array(
    'toolRights' => get_lang('Manage tool access rights'),
    'extLinks' => get_lang('Manage external links'),
    'toolList' => get_lang('Add or remove tools')
);

$currentSection = isset( $_REQUEST['section'] )
    && in_array( $_REQUEST['section'], array_keys($sectionList) )
    ? $_REQUEST['section']
    : 'toolRights'
    ;

$htmlHeadXtra[] =
'<script type="text/javascript">
function confirmation (name)
{
    if (confirm(\''.clean_str_for_javascript(get_lang('Are you sure to delete')).'\'+ name + \' ?\'))
        {return true;}
    else
        {return false;}
}
</script>';

$toolRepository = '../';

$currentCourseRepository = claro_get_course_path();
$dialogBox = new DialogBox();

//include course configuration file
include claro_get_conf_repository() . 'course_main.conf.php';

// Library
require_once get_path('incRepositorySys') . '/lib/course_home.lib.php';
require_once get_path('incRepositorySys') . '/lib/right/courseProfileToolAction.class.php';
require_once get_path('incRepositorySys') . '/lib/right/profileToolRightHtml.class.php';
require_once get_path('incRepositorySys') . '/lib/module/manage.lib.php';

/*
 * Language initialisation of the tool names
 */

$toolNameList = claro_get_tool_name_list();

/*============================================================================
 COMMAND SECTION
============================================================================*/

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$tool_id = isset($_REQUEST['tool_id'])?(int)$_REQUEST['tool_id']:null;
$profile_id = isset($_REQUEST['profile_id'])?$_REQUEST['profile_id']:null;
$right_value = isset($_REQUEST['right_value'])?$_REQUEST['right_value']:null;
$toolLabel = isset($_REQUEST['toolLabel'])?$_REQUEST['toolLabel']:null;

$externalLinkName = isset($_REQUEST['toolName'])?$_REQUEST['toolName']:null;
$externalLinkUrl = isset($_REQUEST['toolUrl'])?$_REQUEST['toolUrl']:null;

/*----------------------------------------------------------------------------
 Manage Profile
----------------------------------------------------------------------------*/

if ( !empty($profile_id) )
{
    if ( $right_value == 'manager'
        && ( claro_get_profile_label( $profile_id ) == ANONYMOUS_PROFILE
            || claro_get_profile_label( $profile_id ) == GUEST_PROFILE ) )
    {
        $dialogBox->error( get_lang('Cannot give manager rights to guest or anonymous users.') );
        
        $profile_id = null;
    }
    else
    {
        // load profile
        $profile = new RightProfile();
    
        if ( $profile->load($profile_id) )
        {
            // load profile tool right
            $courseProfileRight = new RightCourseProfileToolRight();
            $courseProfileRight->setCourseId(claro_get_current_course_id());
            $courseProfileRight->load($profile);
    
            if ( ! $profile->isLocked() || claro_is_platform_admin() )
            {
                if ( $cmd == 'set_right' && !empty($tool_id) )
                {
                    $courseProfileRight->setToolRight($tool_id,$right_value);
                    $courseProfileRight->save();
                }
            }
        }
        else
        {
            $profile_id = null;
        }
    }
}

/*----------------------------------------------------------------------------
 SET THE TOOL ACCESSES
----------------------------------------------------------------------------*/

if ( $cmd == 'exVisible' || $cmd == 'exInvisible' )
{
    if ( $cmd == 'exVisible' )
    {
        set_course_tool_visibility($tool_id,true);
    }
    else
    {
        set_course_tool_visibility($tool_id,false);
    }

    // notify that tool list has been changed

    $eventNotifier->notifyCourseEvent('toollist_changed', claro_get_current_course_id(), '0', '0', '0', '0');
}

/*----------------------------------------------------------------------------
 ADD AN EXTERNAL TOOL
----------------------------------------------------------------------------*/

if ( $cmd == 'exAdd' )
{
    if ( ! empty($externalLinkName) && ! empty($externalLinkUrl))
    {
        if( insert_local_course_tool($externalLinkName, $externalLinkUrl) !== FALSE )
        {
            // notify that tool list has been changed
            $eventNotifier->notifyCourseEvent('toollist_changed', claro_get_current_course_id(), "0", "0", "0", '0');

            $dialogBox->success( get_lang('External Tool added') );

            $cidReset = TRUE;
            $cidReq   = claro_get_current_course_id();

            include get_path('incRepositorySys') . '/claro_init_local.inc.php';
            $noQUERY_STRING = true;
        }
        else
        {
            $dialogBox->error( get_lang('Unable to add external tool') );
        }
    }
    else
    {
        $dialogBox->error( get_lang('Missing value') );
        $cmd = 'rqAdd';
    }
}

/**
 * UPDATE EXTERNAL TOOL SETTINGS
 */

if ($cmd == 'exEdit')
{
    if ( ! empty($externalLinkName) && ! empty($externalLinkUrl))
    {
        if ( set_local_course_tool($_REQUEST['externalToolId'],$externalLinkName,$externalLinkUrl) !== false )
        {
            // notify that tool list has been changed

            $eventNotifier->notifyCourseEvent('toollist_changed', claro_get_current_course_id(), "0", "0", "0", '0');

            $dialogBox->success( get_lang('External tool updated') );
            $cidReset = TRUE;
            $cidReq   = claro_get_current_course_id();

            include get_path('incRepositorySys') . '/claro_init_local.inc.php';
            $noQUERY_STRING = true;

        }
        else
        {
            $dialogBox->error( get_lang('Unable to update external tool') );
        }
    }
    else
    {
        $dialogBox->error( get_lang('Missing value') );
        $cmd = 'rqEdit';
    }

}

/*----------------------------------------------------------------------------
 DELETE EXTERNAL TOOL
----------------------------------------------------------------------------*/

if ($cmd == 'exDelete')
{
    if ($_REQUEST['externalToolId'])
    {
        if (delete_course_tool($_REQUEST['externalToolId']) !== false)
        {
            $dialogBox->success( get_lang('External tool deleted') );
            $cidReset = TRUE;
            $cidReq   = claro_get_current_course_id();

            include get_path('incRepositorySys') . '/claro_init_local.inc.php';
            $noQUERY_STRING = true;

        }
        else
        {
            $dialogBox->error( get_lang('Unable to delete external tool') );
        }
    }
    else
    {
        $dialogBox->error( get_lang('Unable to delete external tool') );
    }

}

/*----------------------------------------------------------------------------
 REQUEST AN EXTERNAL TOOL CHANGE OR ADD
----------------------------------------------------------------------------*/

if ($cmd == 'rqAdd' || $cmd == 'rqEdit')
{
    if ( isset($_REQUEST['externalToolId']) )
    {
        $externalToolId = $_REQUEST['externalToolId'];

        if ( empty($externalLinkName) || empty($externalLinkUrl) )
        {
            $toolSettingList = get_course_tool_settings($externalToolId);
            $externalLinkName = $toolSettingList['name'];
            $externalLinkUrl  = $toolSettingList['url'];
        }
    }
    else
    {
        $externalToolId = null;
    }

    $form = "\n".'<form action="'.claro_htmlspecialchars( $_SERVER['PHP_SELF'] ).'" method="post">'."\n"
    .       claro_form_relay_context()
    .       '<input type="hidden" name="claroFormId" value="'.uniqid('').'" />'."\n"
    .       '<input type="hidden" name="section" value="'.claro_htmlspecialchars($currentSection).'" />'."\n"
    .       '<input type="hidden" name="cmd" value="'.($externalToolId ? 'exEdit' : 'exAdd').'" />'."\n";

    if ($externalToolId)
    {
        $form .= '<input type="hidden" name="externalToolId" value="' . $externalToolId . '" />' . "\n";
    }

    $form .= '<label for="toolName">' . get_lang('Name link') . '</label>'
    .       '<br />' . "\n"
    .       '<input type="text" name="toolName" id="toolName" value="'.claro_htmlspecialchars($externalLinkName).'" />'
    .       '<br />' . "\n"
    .       '<label for="toolUrl">'.get_lang('URL link').'</label><br />'."\n"
    .       '<input type="text" name="toolUrl" id="toolUrl" value="'.claro_htmlspecialchars($externalLinkUrl).'" />'
    .       '<br /><br />' . "\n"
    .       '<input class="claroButton" type="submit" value="'.get_lang('Ok').'" />'
    .       '&nbsp; ' . "\n"
    .       claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel'))."\n"
    .       '</form>' . "\n"
    ;
    
    $dialogBox->form($form);
}

/*----------------------------------------------------------------------------
 ADD OR REMOVE A TOOL
----------------------------------------------------------------------------*/

$undeactivable_tool_array = get_not_deactivable_tool_list();

if ( 'exRmTool' == $cmd )
{
    if ( is_null( $toolLabel ) )
    {
        $dialogBox->error( get_lang('Missing tool label') );
    }
    elseif ( in_array( $toolLabel, $undeactivable_tool_array ) )
    {
        $dialogBox->error( 'This tool cannot be removed' );
    }
    else
    {
        // get tool id
        $toolId = get_tool_id_from_module_label( $toolLabel );
        
        if ( $toolId )
        {
            // update course_tool.activated
            if ( update_course_tool_activation_in_course( $toolId,
                                                         claro_get_current_course_id(),
                                                         false ) )
            {
                $dialogBox->success(get_lang('Tool removed from course') );
                $cidReset = TRUE;
                $cidReq   = claro_get_current_course_id();
    
                include get_path('incRepositorySys') . '/claro_init_local.inc.php';
            }
            else
            {
                $dialogBox->error( get_lang('Cannot remove tool from course') );
            }
        }
        else
        {
            $dialogBox->error( get_lang('Not a valid tool') );
        }
    }
}

if ( 'exAddTool' == $cmd )
{
    if ( is_null( $toolLabel ) )
    {
        $dialogBox->error( get_lang('Missing tool label') );
    }
    else
    {
        $moduleData = get_module_data($toolLabel);
        
        if ( $moduleData['access_manager'] == 'COURSE_ADMIN'
            || claro_is_platform_admin() )
        {
            // get tool id
            $toolId = get_tool_id_from_module_label( $toolLabel );

            if ( $toolId )
            {
                if ( ! is_module_registered_in_course( $toolId, claro_get_current_course_id()) )
                {
                    register_module_in_single_course( $toolId, claro_get_current_course_id() );
                }

                // update course_tool.activated
                if ( update_course_tool_activation_in_course( $toolId,
                                                             claro_get_current_course_id(),
                                                             true ) )
                {
                    set_module_visibility_in_course( $toolId, $_cid, true );

                    $dialogBox->success( get_lang('Tool added to course') );
                    $cidReset = TRUE;
                    $cidReq   = claro_get_current_course_id();

                    $groupToolList = get_group_tool_label_list();

                    foreach ( $groupToolList as $group )
                    {
                        if ( $group['label'] == $toolLabel )
                        {
                            // this is a group tool, enable it in groups
                            activate_module_in_groups(
                                Claroline::getDatabase(),
                                $toolLabel,
                                claro_get_current_course_id()
                            );
                        }
                    }

                    include get_path('incRepositorySys') . '/claro_init_local.inc.php';
                }
                else
                {
                    $dialogBox->error( get_lang('Cannot add tool to course') );
                }
            }
            else
            {
                $dialogBox->error( get_lang('Not a valid tool') );
            }
        }
        else
        {
            $dialogBox->error( get_lang('This tool is activable by the platform administrator only') );
        }
    }
}

// Build course tool list

// $_profileId is set in claro_init_local
// get all tools for the course

$toolList = claro_get_course_tool_list(
    claro_get_current_course_id(), $_profileId, true, true, false );

$displayToolList = array() ;

// Split course tool

foreach ( $toolList as $thisTool )
{
    $tid = $thisTool['id'];

    if ( ! empty($thisTool['label']) )
    {
        $main_tid = $thisTool['tool_id'];
        // course_tool
        $displayToolList[$main_tid]['tid'] = $tid;
        $displayToolList[$main_tid]['icon'] = get_module_url($thisTool['label']) .'/'. $thisTool['icon'];
        $displayToolList[$main_tid]['visibility'] = (bool) $thisTool['visibility'] ;
        $displayToolList[$main_tid]['activation'] = (bool) $thisTool['activation'] ;
    }
}

// Get external link list
$courseExtLinkList = claro_get_course_external_link_list();

/*============================================================================
    DISPLAY
 ============================================================================*/

$out = '';

$out .= claro_html_tool_title(get_lang('Edit Tool list'));


$out .= claro_html_tab_bar($sectionList,$currentSection);

$out .= $dialogBox->render();

if ( $currentSection == 'toolRights' )
{
    $out .= '<p>'
        . get_lang('Select the tools you want to make visible for your user.')
        . get_lang('An invisible tool will be greyed out on your personal interface.')
        . '<br />'
        . get_lang('You can also change the access rights for the different user profiles.')
        .'</p>'."\n"
        ;
    
    // Display course tool list
    
    // Get all profile
    
    $profileNameList = claro_get_all_profile_name_list();
    $display_profile_list = array_keys($profileNameList);
    
    $profileRightHtml = new RightProfileToolRightHtml();
    $profileRightHtml->addUrlParam('section', claro_htmlspecialchars($currentSection));
    $profileRightHtml->setCourseToolInfo($displayToolList);
    
    $profileLegend = array();
    
    foreach ( $display_profile_list as $profileId )
    {
        $profile = new RightProfile();
        if ( $profile->load($profileId) )
        {
            $profileRight = new RightCourseProfileToolRight();
            $profileRight->setCourseId(claro_get_current_course_id());
            $profileRight->load($profile);
            $profileRightHtml->addRightProfileToolRight($profileRight);
            $profileLegend[] = get_lang($profileNameList[$profileId]['name'])
                . ' : <em>' . get_lang($profileNameList[$profileId]['description']) . '</em>' ;
        }
    }
    
    $out .= '<p><small><span style="text-decoration: underline">' . get_lang('Profile list')
        . '</span> : ' . implode($profileLegend,' - ') . '.</small></p>'
        ;
    
    $out .= '<blockquote>' . "\n"
        . $profileRightHtml->displayProfileToolRightList()
        . '</blockquote>' . "\n"
        ;
}
elseif ( $currentSection == 'extLinks' )
{
    // Display external link list
    
    $out .= '<p>'.get_lang('Add external links to your course').'</p>'."\n" ;
    
    $out .= '<blockquote>' . "\n"
    .    '<p>' . "\n"
    .    '<a class="claroCmd" href="'
    .    claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
    .    '?cmd=rqAdd&section='.claro_htmlspecialchars($currentSection) )).'">'
    .    '<img src="' . get_icon_url('link') . '" alt="" />'
    .    get_lang('Add external link')
    .    '</a>' . "\n"
    .    '</p>' . "\n"
    
    .    '<table class="claroTable" >'."\n\n"
    .    '<thead>'."\n"
    .    '<tr>'."\n"
    .    '<th>'.get_lang('Tools').'</th>'."\n"
    .    '<th>'.get_lang('Visibility').'</th>'."\n"
    .    '<th>'.get_lang('Edit').'</th>'."\n"
    .    '<th>'.get_lang('Delete').'</th>'."\n"
    .    '</tr>'."\n"
    .    '</thead>'."\n\n"
    .    '<tbody>'."\n"
    ;
    
    if ( !empty( $courseExtLinkList ) )
    {
        foreach ( $courseExtLinkList as $linkId => $link )
        {
            $out .= '<tr>'."\n";
        
            $out .= '<td ' . ($link['visibility']?'':'class="invisible"') . '>'
            . '<img src="' . get_icon_url( 'link' ) . '" alt="" />' .$link['name']
            . '</td>';
        
            $out .= '<td align="center">' ;
        
            if ( $link['visibility'] == true )
            {
                $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exInvisible&amp;tool_id=' . $linkId . '&amp;section='.claro_htmlspecialchars($currentSection) )).'" >'
                . '<img src="' . get_icon_url('visible') . '" alt="' . get_lang('Visible') . '" />'
                . '</a>';
            }
            else
            {
                $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exVisible&amp;tool_id=' . $linkId .'&amp;section='.claro_htmlspecialchars($currentSection) )).'" >'
                . '<img src="' . get_icon_url('invisible') . '" alt="' . get_lang('Invisible') . '" />'
                . '</a>';
        
            }
        
            $out .= '</td>'."\n";
        
            $out .= '<td align="center">'
            . '<a href="'.claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?cmd=rqEdit&amp;externalToolId='.$linkId.'&amp;section='.claro_htmlspecialchars($currentSection) )).'">'
            . '<img src="' . get_icon_url('edit') . '" alt="'.get_lang('Modify').'" />'
            . '</a></td>' . "\n" ;
        
            $out .= '<td align="center">'
            .'<a href="'.claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'].'?cmd=exDelete&amp;externalToolId='.$linkId.'&amp;section='.claro_htmlspecialchars($currentSection) )).'"'
            .' onclick="return confirmation(\''.clean_str_for_javascript($link['name']).'\');">'
            .'<img src="' . get_icon_url('delete') . '" alt="'.get_lang('Delete').'" />'
            .'</a></td>'."\n";
        
            $out .= '</tr>'."\n";
        }
    }
    else
    {
        $out .= '<tr><td colspan="4">'.get_lang('Empty').'</td></tr>' . "\n";
    }
    
    $out .= '</tbody>' . "\n"
        . '</table>'."\n\n"
        . '</blockquote>'
        . "\n"
        ;
}
elseif ( $currentSection == 'toolList' )
{
    $out .= '<p>'.get_lang('Add or remove tools from your course').'</p>'."\n" ;
    
    $activeCourseToolList = module_get_course_tool_list(
        claro_get_current_course_id(), true, true );
    
    $inactiveCourseToolList = module_get_course_tool_list(
        claro_get_current_course_id(), true, false );
    
    $platformCourseToolList = claro_get_main_course_tool_list(true);
    
    $completeInactiveToolList = array();
    
    foreach ( $inactiveCourseToolList as $inactiveCourseTool )
    {
        $completeInactiveToolList[] = array(
            'id' =>  $inactiveCourseTool['id'],
            'tool_id' => $inactiveCourseTool['tool_id'],
            'label' => $inactiveCourseTool['label'],
            'icon' => get_module_url($inactiveCourseTool['label']) . '/' . $inactiveCourseTool['icon'],
            'access_manager' => $inactiveCourseTool['access_manager'],
        );
    }
    
    foreach ( $platformCourseToolList as $toolId => $platformCourseTool )
    {
        $found = false;
        foreach ( $activeCourseToolList as $activeCourse )
        {
            if ( $activeCourse['label'] == $platformCourseTool['label'] )
            {
                $found = true;
                break;
            }
        }
        
        $alreadyThere = false;
        foreach ( $inactiveCourseToolList as $inactiveCourseTool )
        {
            if ( $inactiveCourseTool['label'] == $platformCourseTool['label'] )
            {
                $alreadyThere = true;
                break;
            }
        }
        
        if ( $platformCourseTool['activation'] == true && ! $found && ! $alreadyThere )
        {
            $completeInactiveToolList[] = array(
                'tool_id' => $toolId,
                'label' => $platformCourseTool['label'],
                'icon' => $platformCourseTool['icon'],
                'access_manager' => $platformCourseTool['access_manager'],
            );
        }
    }
    
    $out .= '<h3>' . get_lang('Tools currently in your course') . '</h3>' . "\n";
    
    $out .= '<blockquote>' . "\n"
        . '<table class="claroTable emphaseLine" style="width: 100%" >'."\n\n"
        . '<thead>'."\n"
        . '<tr>'."\n"
        . '<th>'.get_lang('Tool').'</th>'."\n"
        . '<th>'.get_lang('Remove from course').'</th>'."\n"
        . '</tr>'."\n"
        . '</thead>'."\n\n"
        . '<tbody>'."\n"
        ;
    
    if ( !empty( $activeCourseToolList ) )
    {
        foreach ( $activeCourseToolList as $activeTool )
        {
            if ( ! in_array( $activeTool['label'], $undeactivable_tool_array ) )
            {
                $action_link = '<a href="' . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                    . '?cmd=exRmTool&amp;toolLabel='
                    . claro_htmlspecialchars($activeTool['label'])
                    .'&amp;section='.claro_htmlspecialchars($currentSection) )).'" '
                    . 'title="'.get_lang('Remove').'">'
                    . '<img src="' . get_icon_url('delete') . '" border="0" alt="'. get_lang('Remove') . '"/>'
                    . '</a>'
                    ;
            }
            else
            {
                $action_link = '-';
            }
            $out .= '<tr>'
                . '<td><img src="'
                . get_module_url($activeTool['label']) . '/' . $activeTool['icon'] . '" alt="" /> '
                . get_lang(claro_get_tool_name($activeTool['tool_id'])).'</td>'
                . '<td>'.$action_link.'</td>'
                . '</tr>' . "\n"
                ;
        }
    }
    else
    {
        $out .= '<tr><td colspan="2">'.get_lang('Empty').'</td></tr>' . "\n";
    }
    
    $out .= '</tbody>' . "\n"
        . '</table>'."\n\n"
        . '</blockquote>'
        . "\n"
        ;
        
    $out .= '<h3>' . get_lang('Available tools to add to your course') . '</h3>' . "\n";
        
    $out .= '<blockquote>' . "\n"
        . '<table class="claroTable emphaseLine" style="width: 100%" >'."\n\n"
        . '<thead>'."\n"
        . '<tr>'."\n"
        . '<th>'.get_lang('Tool').'</th>'."\n"
        . '<th>'.get_lang('Add to course').'</th>'."\n"
        . '</tr>'."\n"
        . '</thead>'."\n\n"
        . '<tbody>'."\n"
        ;
    
    if ( !empty( $completeInactiveToolList ) )
    {
        foreach ( $completeInactiveToolList as $inactiveTool )
        {
            if ( $inactiveTool['access_manager'] == 'COURSE_ADMIN'
                || claro_is_platform_admin() )
            {
                $action_link = '<a href="' . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                    . '?cmd=exAddTool&amp;toolLabel='
                    . claro_htmlspecialchars($inactiveTool['label'])
                    .'&amp;section='.claro_htmlspecialchars($currentSection) )).'" '
                    . 'title="'.get_lang('Add').'">'
                    . '<img src="' . get_icon_url('select') . '" alt="'. get_lang('Add') . '"/>'
                    . '</a>'
                    ;
            }
            else
            {
                $action_link = '<em>'.get_lang('Activable only by the platform administrator !').'</em>';
            }

            $out .= '<tr>'
                . '<td><img src="'
                . $inactiveTool['icon'] . '" alt="" /> '
                . get_lang(claro_get_tool_name($inactiveTool['tool_id'])).'</td>'
                . '<td>'.$action_link.'</td>'
                . '</tr>' . "\n"
                ;
        }
    }
    else
    {
        $out .= '<tr><td colspan="2">'.get_lang('Empty').'</td></tr>' . "\n";
    }
    
    $out .= '</tbody>' . "\n"
        . '</table>'."\n\n"
        . '</blockquote>'
        . "\n"
        ;
}
else
{
    // should never happen
    $out .= get_lang('Invalid section');
}


$claroline->display->body->appendContent($out);

echo $claroline->display->render();

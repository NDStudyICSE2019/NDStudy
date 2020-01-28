<?php // $Id: profile.php 13348 2011-07-18 13:58:28Z abourguignon $

/**
 * CLAROLINE
 *
 * This tool manage profile of the course.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline Team <cvs@claroline.net>
 * @package     RIGHT
 */

require '../inc/claro_init_global.inc.php';

$nameTools = get_lang('Course profile');
$dialogBox = new DialogBox();
$tidReset = true;

if ( ! claro_is_in_a_course() || ! claro_is_user_authenticated()) claro_disp_auth_form(true);

$is_allowedToEdit = claro_is_course_manager();

if ( ! $is_allowedToEdit )
{
    claro_die(get_lang('Not allowed'));
}

require_once get_path('incRepositorySys') . '/lib/right/courseProfileToolAction.class.php' ;
require_once get_path('incRepositorySys') . '/lib/right/profileToolRightHtml.class.php' ;

//=================================
// Main section
//=================================

$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$display_profile = isset($_REQUEST['display_profile'])?$_REQUEST['display_profile']:null;
$profile_id = isset($_REQUEST['profile_id'])?$_REQUEST['profile_id']:null;
$tool_id = isset($_REQUEST['tool_id'])?$_REQUEST['tool_id']:null;
$right_value = isset($_REQUEST['right_value'])?$_REQUEST['right_value']:null;

if ( !empty($profile_id) )
{
    // load profile
    $profile = new RightProfile();

    if ( $profile->load($profile_id) )
    {
        // load profile tool right
        $courseProfileRight = new RightCourseProfileToolRight();
        $courseProfileRight->setCourseId(claro_get_current_course_id());
        $courseProfileRight->load($profile);

        if ( ! $profile->isLocked() )
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

//---------------------------------
// Build list of profile to display
//---------------------------------

$display_profile_list = array();
$display_profile_url_param = null;

if ( !empty($display_profile) )
{
    if ( is_numeric($display_profile) )
    {
        $display_profile_list[] = $display_profile;
        $display_profile_url_param = $display_profile;
    }
}

// default : display all profile

if ( empty($display_profile_list) )
{
    $profileNameList = claro_get_all_profile_name_list();
    $display_profile_list = array_keys($profileNameList);
    $display_profile_url_param = 'all';
}

//=================================
// Display section
//=================================

// define bredcrumb
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Course profile list'), 'profile_list.php' );

$out = '';

// Set display right

$profileRightHtml = new RightProfileToolRightHtml();
$profileRightHtml->addUrlParam('display_profile', $display_profile_url_param);

$profileFoundCount = 0;

foreach ( $display_profile_list as $profileId )
{
    $profile = new RightProfile();
    if ( $profile->load($profileId) )
    {
        $profileRight = new RightCourseProfileToolRight();
        $profileRight->setCourseId(claro_get_current_course_id());
        $profileRight->load($profile);
        $profileRightHtml->addRightProfileToolRight($profileRight);
        $profileFoundCount++;
    }
}

if ( $profileFoundCount == 0 )
{
    $dialogBox->error( get_lang('Profile not found') );
    $out .= $dialogBox->render();
}
else
{
    if ( $profileFoundCount == 1 )
    {
        // display tool title
        $out .= claro_html_tool_title(array('mainTitle'=>get_lang('Manage Right'),'subTitle'=>$profile->getName()));
        $out .= '<p>' . $profile->getDescription() . '</p>';
    }
    else
    {
        // display tool title
        $out .= claro_html_tool_title(array('mainTitle'=>get_lang('Manage Right'),'subTitle'=> get_lang('All profiles') ));
    }
    $out .= $profileRightHtml->displayProfileToolRightList();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
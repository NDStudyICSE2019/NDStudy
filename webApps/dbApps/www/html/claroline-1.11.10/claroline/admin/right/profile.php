<?php // $Id: profile.php 13348 2011-07-18 13:58:28Z abourguignon $

/**
 * CLAROLINE
 *
 * Edit right & action of a profile.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @package     RIGHT
 */

require '../../inc/claro_init_global.inc.php';

include_once get_path('incRepositorySys') . '/lib/right/profileToolRight.class.php';
include_once get_path('incRepositorySys') . '/lib/right/profileToolRightHtml.class.php';

//=================================
// Security check
//=================================

if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

//=================================
// Main section
//=================================

$profile_id = isset($_REQUEST['profile_id'])?$_REQUEST['profile_id']:null;
$tool_id = isset($_REQUEST['tool_id'])?$_REQUEST['tool_id']:null;
$right_value = isset($_REQUEST['right_value'])?$_REQUEST['right_value']:null;
$cmd = isset($_REQUEST['cmd'])?$_REQUEST['cmd']:null;
$display_profile = isset($_REQUEST['display_profile'])?$_REQUEST['display_profile']:null;

if ( !empty($profile_id) )
{
    // load profile
    $profile = new RightProfile();

    if ( $profile->load($profile_id) )
    {
        // load profile tool right
        $profileRight = new RightProfileToolRight();
        $profileRight->load($profile);

        // update tool right
        if ( $cmd == 'set_right' && !empty($tool_id) )
        {
            $profileRight->setToolRight($tool_id,$right_value);
            $profileRight->save();
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
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Rights'), get_path('rootAdminWeb').'right/profile.php?display_profile=' . $display_profile_url_param );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Course profile list'), get_path('rootAdminWeb').'right/profile_list.php' );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$dialogBox = new DialogBox();

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
        $profileRight = new RightProfileToolRight();
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
        $out .= '<p><a href="' . $_SERVER['PHP_SELF'] . '?display_profile=all">' . get_lang('View all right profile') . '</a></p>';
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
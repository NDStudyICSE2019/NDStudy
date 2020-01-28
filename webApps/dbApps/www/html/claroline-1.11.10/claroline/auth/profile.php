<?php // $Id: profile.php 14315 2012-11-08 14:51:17Z zefredz $

/**
 * CLAROLINE
 *
 * This script prupose to user to edit his own profile.
 *
 * @version     $Revision: 14315 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/Auth/
 * @author      Claro Team <cvs@claroline.net>
 * @package     AUTH
 */

/*=====================================================================
Init Section
=====================================================================*/

$cidReset       = true;
$gidReset       = true;
$uidRequired    = true;

require '../inc/claro_init_global.inc.php';

if( !claro_is_user_authenticated() ) claro_disp_auth_form();

$dialogBox  = new DialogBox();
$display    = '';
$error      = false;

// include configuration files
include claro_get_conf_repository() . 'user_profile.conf.php'; // find this file to modify values.

// include library files
include_once get_path('incRepositorySys') . '/lib/user.lib.php';
include_once get_path('incRepositorySys') . '/lib/sendmail.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileUpload.lib.php';
include_once get_path('incRepositorySys') . '/lib/image.lib.php';
include_once get_path('incRepositorySys') . '/lib/display/dialogBox.lib.php';

$nameTools = get_lang('My user account');

// define display
define('DISP_PROFILE_FORM',__LINE__);
define('DISP_MOREINFO_FORM',__LINE__);
define('DISP_REQUEST_COURSE_CREATOR_STATUS',__LINE__);
define('DISP_REQUEST_REVOQUATION',__LINE__);

$display = DISP_PROFILE_FORM;

/*=====================================================================
CONTROLER Section
=====================================================================*/

$extraInfoDefList = get_userInfoExtraDefinitionList();

$userId = claro_get_current_user_id();

$userData = user_get_properties($userId);

$acceptedCmdList = array( 'exCCstatus'
                        , 'exRevoquation'
                        , 'reqCCstatus'
                        , 'reqRevoquation'
                        , 'editExtraInfo'
                        , 'exMoreInfo'
                        );

if ( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'],$acceptedCmdList) )
{
    $cmd = $_REQUEST['cmd'];
}
else
{
    $cmd = '';
}

if ( isset($_REQUEST['applyChange']) )
{
    // Get params form the form
    $userData = user_initialise();
    
    if ( get_conf('allow_profile_picture', true) )
    {
        // Handle user picture
        $pictureUpdated = user_handle_profile_picture($userData);

        if ($pictureUpdated['success'])
        {
            $userData['picture'] = $pictureUpdated['pictureName'];
            foreach ($pictureUpdated['messages'] as $success)
            {
                $dialogBox->success($success);
            }
        }
        else
        {
            foreach ($pictureUpdated['messages'] as $error)
            {
                $dialogBox->error($error);
            }
        }
    }
    
    // Manage password
    if (empty($userData['password']) && empty($userData['password_conf']))
    {
        unset ($userData['password']);
        unset ($userData['password_conf']);
    }
    
    if (empty($userData['authSource']))
    {
        unset ($userData['authSource']);
    }
    
    if ( ! get_conf( 'allowSelfRegProf' ) && ! claro_is_platform_admin() )
    {
        unset( $userData['isCourseCreator'] );
    }
    
    if( ! claro_is_platform_admin() )
    {
        unset( $userData['isPlatformAdmin'] );
    }
    
    // Validate form params
    $errorMsgList = user_validate_form_profile($userData, claro_get_current_user_id());
    
    if ( count($errorMsgList) == 0 )
    {
        // if no error update use setting
        user_set_properties(claro_get_current_user_id(), $userData);
        set_user_property(claro_get_current_user_id(), 'skype', $userData['skype']);
        
        $claroline->log('PROFILE_UPDATE', array('user'=>claro_get_current_user_id()));
        
        // re-init the system to take new settings in account
        $uidReset = true;
        include dirname(__FILE__) . '/../inc/claro_init_local.inc.php';
        $dialogBox->success( get_lang('The information have been modified') );
        
        // Initialise
        $userData = user_get_properties(claro_get_current_user_id());
        
    } // end if $userSettingChangeAllowed
    else
    {
        // user validate form return error messages
        foreach( $errorMsgList as $errorMsg )
        {
            $dialogBox->error($errorMsg);
        }
        
        $error = true;
    }
}
elseif ( ! claro_is_allowed_to_create_course()
    && get_conf('can_request_course_creator_status')
    && 'exCCstatus' == $cmd )
{
    // send a request for course creator status
    profile_send_request_course_creator_status($_REQUEST['explanation']);
    $dialogBox->success( get_lang('Your request to become a course creator has been sent to platform administrator(s).') );
}
elseif ( get_conf('can_request_revoquation')
    && 'exRevoquation' == $cmd )
{
    // send a request for revoquation
    if (profile_send_request_revoquation($_REQUEST['explanation'], $_REQUEST['loginToDelete'],$_REQUEST['passwordToDelete']))
    {
        $dialogBox->success( get_lang('Your request to remove your account has been sent') );
    }
    else
    {
        switch (claro_failure::get_last_failure())
        {
            case 'EXPLANATION_EMPTY' :
                $dialogBox->error( get_lang('You left some required fields empty') );
                $noQUERY_STRING = true;
                ClaroBreadCrumbs::getInstance()->prepend( $nameTools, $_SERVER['PHP_SELF'] );
                $nameTools = get_lang('Request to remove this account');
                $display = DISP_REQUEST_REVOQUATION;
            break;
    
        }
    }
}
elseif (  !claro_is_allowed_to_create_course()
    && get_conf('can_request_course_creator_status')
    && 'reqCCstatus' == $cmd )
{
    // display course creator status form
    $noQUERY_STRING = true;
    $display = DISP_REQUEST_COURSE_CREATOR_STATUS;
    ClaroBreadCrumbs::getInstance()->prepend( $nameTools, $_SERVER['PHP_SELF'] );
    $nameTools = get_lang('Request course creation status');
}
elseif ( get_conf('can_request_revoquation')
    && 'reqRevoquation' == $cmd )
{
    // display revoquation form
    $noQUERY_STRING = true;
    ClaroBreadCrumbs::getInstance()->prepend( $nameTools, $_SERVER['PHP_SELF'] );
    $nameTools = get_lang('Request to remove this account');
    $display = DISP_REQUEST_REVOQUATION;
}
elseif ( 'editExtraInfo' == $cmd
    && 0 < count($extraInfoDefList) )
{
    // display revoquation form
    $noQUERY_STRING = true;
    $display = DISP_MOREINFO_FORM;
    ClaroBreadCrumbs::getInstance()->prepend( $nameTools, $_SERVER['PHP_SELF'] );
    $nameTools = get_lang('Complementary fields');
    $userInfo = get_user_property_list(claro_get_current_user_id());

}
elseif ( 'exMoreInfo' == $cmd
    && 0 < count($extraInfoDefList)  )
{
    if (array_key_exists('extraInfoList',$_REQUEST))
    {
        foreach( $_REQUEST['extraInfoList'] as $extraInfoName=> $extraInfoValue)
        {
            set_user_property(claro_get_current_user_id(),$extraInfoName,$extraInfoValue,'userExtraInfo');
        }
    }
}

// Initialise
$userData['userExtraInfoList'] =  get_user_property_list(claro_get_current_user_id());

// Command list
$cmdList = array();

switch ( $display )
{
    case DISP_PROFILE_FORM :
        // Display user tracking link
        $profileText = claro_text_zone::get_content('textzone_edit_profile_form');
        
        if( get_conf('is_trackingEnabled') )
        {
            // Display user tracking link
            $cmdList[] = array(
                'img' => 'statistics',
                'name' => get_lang('View my statistics'),
                'url' => claro_htmlspecialchars(Url::Contextualize(get_conf('urlAppend') . '/claroline/tracking/userReport.php?userId='.claro_get_current_user_id()))
            );
        }
        
        // Display request course creator status
        if ( ! claro_is_allowed_to_create_course() && get_conf('can_request_course_creator_status') )
        {
            $cmdList[] = array(
                'name' => get_lang('Request course creation status'),
                'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=reqCCstatus'))
            );
        }
        
        // Display user revoquation
        if ( get_conf('can_request_revoquation') )
        {
            $cmdList[] = array(
                'img' => 'delete',
                'name' => get_lang('Delete my account'),
                'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=reqRevoquation'))
            );
        }
        
        if (claro_is_platform_admin())
        {
            $dialogBox->info(get_lang('As a platform administrator, you can edit any field you want, even if this field isn\'t editable for other users.<br />You can check the list of editable fields in your platform\'s configuration.'));
        }
        
        break;
}

// Display
$out = '';

$out .= claro_html_tool_title($nameTools, null, $cmdList)
      . $dialogBox->render();

switch ( $display )
{
    case DISP_PROFILE_FORM :
        
        // Display form profile
        if ( trim ($profileText) != '')
        {
            $out .= '<div class="info profileEdit">'
                  . $profileText
                  . '</div>';
        }
        
        $out .= user_html_form($userId);
        break;

    case DISP_MOREINFO_FORM :
        
        // Display request course creator form
        $out .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
              . '<input type="hidden" name="cmd" value="exMoreInfo" />' . "\n"
              . '<table>' . "\n";
        
        foreach ($extraInfoDefList as $extraInfoDef)
        {
            $currentValue = array_key_exists($extraInfoDef['propertyId'],$userInfo)
            ? $userInfo[$extraInfoDef['propertyId']]
            : $extraInfoDef['defaultValue'];
            $requirement = (bool) (true == $extraInfoDef['required']);
            
            $labelExtraInfoDef = $extraInfoDef['label'];
            $out .= form_input_text('extraInfoList['.claro_htmlentities($extraInfoDef['propertyId']).']',$currentValue,get_lang($labelExtraInfoDef),$requirement);
        }
        
        $out .= '<tr valign="top">' . "\n"
              . '<td>' . get_lang('Submit') . ': </td>' . "\n"
              . '<td>'
              . '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp; ' . "\n"
              . claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel')) . "\n"
              . '</td>'
              . '</tr>' . "\n"
              .  form_row('&nbsp;', '<small>' . get_lang('<span class="required">*</span> denotes required field') . '</small>')
              . '</table>' . "\n"
              . '</form>' . "\n";
        break;

    case DISP_REQUEST_COURSE_CREATOR_STATUS :
        
        $out .= '<p>' . get_lang('Fill in the text area to motivate your request and then submit the form to send it to platform administrators') . '</p>';
        
        // Display request course creator form
        $out .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
              . '<input type="hidden" name="cmd" value="exCCstatus" />' . "\n"
              . '<table>' . "\n"
              . form_input_textarea('explanation','',get_lang('Comment'),true,6)
              . '<tr valign="top">' . "\n"
              . '<td>' . get_lang('Submit') . ': </td>' . "\n"
              . '<td><input type="submit" value="' . get_lang('Ok') . '" />&nbsp; ' . "\n"
              . claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel')) . "\n"
              . '</td></tr>' . "\n"
              . '</table>' . "\n"
              . '</form>' . "\n";
        break;

    case DISP_REQUEST_REVOQUATION :
        
        if ( get_conf('can_request_revoquation') )
        {
            $out .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
                  . '<input type="hidden" name="cmd" value="exRevoquation" />' . "\n"
                  . '<table>' . "\n"
                  . form_input_text('loginToDelete','',get_lang('Username'),true)
                  . form_input_password('passwordToDelete','',get_lang('Password'),true)
                  . form_input_textarea('explanation','',get_lang('Comment'),true,6)
                  . '<tr valign="top">' . "\n"
                  . '<td>' . get_lang('Delete my account') . ': </td>' . "\n"
                  . '<td>'
                  . '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp; ' . "\n"
                  . claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel')) . "\n"
                  . '</td></tr>' . "\n"
                  . '</table>' . "\n"
                  . '</form>' . "\n";
        }
        break;

} // end switch display

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
<?php // $Id: adminmailsystem.php 14109 2012-04-03 12:54:09Z ffervaille $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14109 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     ADMIN
 * @author      claro team <cvs@claroline.net>
 */

$cidReset = true;
$gidReset = true;
$tidReset = true;

require '../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

// Include libraries
require_once get_path('incRepositorySys') . '/lib/user.lib.php';


// Initialise variables
$nameTools = get_lang('System mail : recipients list');
$error = false;
$messageList = array();

/*
Main Section
*/


$platformAdminUidList = claro_get_uid_of_platform_admin();

if ( isset($_REQUEST['cmd']) )  //for formular modification
{
    $notifiedList = isset($_REQUEST['notifiedList']) && is_array($_REQUEST['notifiedList'])?$_REQUEST['notifiedList']:array();
    $requestList = isset($_REQUEST['requestList']) && is_array($_REQUEST['requestList'])?$_REQUEST['requestList']:array();
//    $contactList = isset($_REQUEST['contactList']) && is_array($_REQUEST['contactList'])?$_REQUEST['contactList']:array();

    foreach ($platformAdminUidList as $platformAdminUid )
    {
      //  claro_set_uid_of_platform_contact($platformAdminUid,in_array($platformAdminUid,$contactList));
        claro_set_uid_recipient_of_system_notification($platformAdminUid,in_array($platformAdminUid,$notifiedList));
        claro_set_uid_recipient_of_request_admin($platformAdminUid,in_array($platformAdminUid,$requestList));
    }


} // if apply changes

/**
 * PREPARE DISPLAY
 */

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$contactUidList = claro_get_uid_of_platform_contact();
$requestUidList = claro_get_uid_of_request_admin();
$notifiedUidList = claro_get_uid_of_system_notification_recipient();


foreach ($platformAdminUidList as $k => $platformAdminUid )
{
    $userData = user_get_properties($platformAdminUid);
    $userDataGrid[$k]['id'] = $userData['user_id'];
    $userDataGrid[$k]['name'] = $userData['lastname'];
    $userDataGrid[$k]['firstname'] = $userData['firstname'];
    $userDataGrid[$k]['email'] = $userData['email'];
    $userDataGrid[$k]['authSource'] = $userData['authSource'];
    //$userDataGrid[$k]['contact_switch'] = '<input name="contactList[]" type="checkbox" value="' . $platformAdminUid . '" ' . ((bool) in_array($platformAdminUid,$contactUidList)  ? 'checked="checked"  />' : '>');
    $userDataGrid[$k]['request_switch'] = '<input name="requestList[]" type="checkbox" value="' . $platformAdminUid . '" '
    .    ((bool) in_array($platformAdminUid,$requestUidList)  ? 'checked="checked"  /> ' : '> ');
    $userDataGrid[$k]['notification_switch'] = '<input name="notifiedList[]" type="checkbox" value="' . $platformAdminUid . '" '
    .    ((bool) in_array($platformAdminUid,$notifiedUidList)  ? 'checked="checked"  /> ' : '> ');

}
$adminDataGrid = new claro_datagrid($userDataGrid);
$adminDataGrid->set_idLineType('none');
$adminDataGrid->set_colHead('name');
$adminDataGrid->set_colTitleList(array ( 'user id'              => get_lang('User id')
                                        , 'name'                => get_lang('Last name')
                                        , 'firstname'           => get_lang('First name')
                                        , 'email'               => get_lang('Email')
                                        , 'authSource'          => get_lang('Authentication source')
//                                        , 'contact_switch'      => get_lang('Contact')
                                        , 'request_switch'      => get_lang('Request')
                                        , 'notification_switch' => get_lang('Notify')
                                        )
                                        );

$adminDataGrid->set_colAttributeList( array (  'request_switch' => array ('align' => 'left')
                                             , 'notification_switch' => array ('align' => 'left')
                                             //, 'contact_switch' => array ('align' => 'left')
                                             , 'authSource'  => array ('align' => 'center')
                                             ));
/**
 * DISPLAY
 */

$out = '';

// Display tool title
$out .= claro_html_tool_title($nameTools)
.    claro_html_msg_list($messageList)
.    '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n"
.    '<input type="hidden" name="cmd" value="setRecipient" />' . "\n"
.    $adminDataGrid->render()
.    '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp;' . "\n"
.    claro_html_button($_SERVER['PHP_SELF'], get_lang('Cancel')) . "\n"
.    '</form>' . "\n"
;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
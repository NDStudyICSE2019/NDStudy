<?php // $Id: CLPROFIL.def.conf.inc.php 13412 2011-08-10 13:08:25Z zefredz $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * This file describe the parameter for profil editor.
 *
 * @version     1.8 $Revision: 13412 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Config
 * @author      Claro Team <cvs@claroline.net>
 * @package     CLPROFIL
 */

// CONFIG HEADER
$conf_def['config_code'] = 'CLPROFIL';
$conf_def['config_name'] = 'User profile options';
//$conf_def['description'] = '';
$conf_def['config_file'] = 'user_profile.conf.php';
$conf_def['old_config_file'][] ='profile.conf.php';
$conf_def['config_class']='user';

// Section required fields

$conf_def['section']['agreement']['label'] = 'Registration agreement';
$conf_def['section']['agreement']['description'] = '';
$conf_def['section']['agreement']['properties'] =
array ( 'show_agreement_panel'
      );

$conf_def_property_list['show_agreement_panel'] =
array ( 'label'         => 'Display an agreement page before the "create user account" form'
      ,'description'   => 'The content of this panel is editable in administration '
      , 'default'       => false
      , 'type'          => 'boolean'
      , 'acceptedValue' => array ('TRUE'  => 'Yes'
                                 ,'FALSE' => 'No'
                                 )
      );

$conf_def['section']['required']['label'] = 'Data checking';
$conf_def['section']['required']['description'] = '';
$conf_def['section']['required']['properties'] =
array ( 'profile_editable'
      , 'allow_profile_picture'
      , 'userOfficialCodeCanBeEmpty'
      , 'ask_for_official_code'
      , 'userMailCanBeEmpty'
      , 'SECURE_PASSWORD_REQUIRED'
      
      );

$conf_def_property_list['userOfficialCodeCanBeEmpty'] =
array ( 'label'         => 'Official code is'
      , 'default'       => true
      , 'type'          => 'boolean'
      , 'acceptedValue' => array ('TRUE'  => 'Optional'
                                 ,'FALSE' => 'Required'
                                 )
      );

$conf_def_property_list['userMailCanBeEmpty'] =
array ( 'label'         => 'Email is'
      , 'description'   => 'Accept email as valid (best choice)'
      , 'default'       => true
      , 'type'          => 'boolean'
      , 'acceptedValue' => array ('FALSE' => 'Required'
                                 ,'TRUE'  => 'Optional'
                                 )
      );

$conf_def_property_list['ask_for_official_code'] =
array ( 'label'         => 'Ask the official code'
      , 'description'   => 'Display the field official code in form'
      , 'default'       => true
      , 'type'          => 'boolean'
      , 'acceptedValue' => array ('TRUE' => 'Yes'
                                 ,'FALSE'  => 'No'
                                 )
      );

$conf_def_property_list['profile_editable'] =
array ( 'label'         => 'Profile form'
      , 'description'   => 'Which parts of the profile can be changed?'
      , 'default'       => array('name','official_code','login','password','email','phone','language','picture','skype')
      , 'type'          => 'multi'
      , 'acceptedValue' => array ('name' => 'Name'
                                 ,'official_code' => 'Official code'
                                 ,'login' => 'Username'
                                 ,'password' => 'Password'
                                 ,'email' => 'Email'
                                 ,'phone' => 'Phone'
                                 ,'language' => 'Language'
                                 ,'picture' => 'User picture'
                                 ,'skype' => 'Skype account'
                                 )
      );

$conf_def_property_list['allow_profile_picture'] =
array ( 'label'         => 'Allow user to add a picture to their profile'
      , 'description'   => ''
      , 'default'       => true
      , 'type'          => 'boolean'
      , 'acceptedValue' => array ('TRUE' => 'Yes'
                                 ,'FALSE'  => 'No'
                                 )
      );

// Section read only fields

$conf_def['section']['readonly']['label'] = 'Allow to modify field';
//$conf_def['section']['readonly']['description'] = '';
$conf_def['section']['readonly']['display'] = false;
$conf_def['section']['readonly']['properties'] =
array (
      );

$conf_def_property_list['SECURE_PASSWORD_REQUIRED'] =
array ('label'         => 'Password security check'
      ,'description'   => 'Check if the password is not too easy to find'
      ,'default'       => false
      ,'type'          => 'boolean'
      ,'acceptedValue' => array ('TRUE'  => 'Yes'
                                ,'FALSE' => 'No'
                                )
      , 'container'     => 'CONST'
      );

// Section view

$conf_def['section']['view']['label'] = 'Display data';
$conf_def['section']['view']['display'] = false;
//$conf_def['section']['view']['description'] = '';
$conf_def['section']['view']['properties'] =
array (
      );

// Section

$conf_def['section']['request']['label'] = 'User request';
$conf_def['section']['request']['description'] = '';
$conf_def['section']['request']['properties'] =
array ( 'allowSelfRegProf'
      , 'can_request_course_creator_status'
      , 'can_request_revoquation'
      );

$conf_def_property_list['can_request_course_creator_status'] =
array ( 'label'         => 'Display "Request a Course Creator status"'
      , 'description'   => 'This option insert a command in the user profile form to request a status of course creator. This request is sent by e-mail to platform administrator.'
      , 'display'       => true
      , 'default'       => false
      , 'type'          => 'boolean'
      , 'acceptedValue' => array ('TRUE'  => 'Yes'
                                ,'FALSE' => 'No'
                                )
      );

$conf_def_property_list['can_request_revoquation'] =
array ( 'label'         => 'Display "Request to be deleted from the platform"'
      , 'description'   => 'This option insert a command in the user profile form to request the removal of the user from the platform.  This request is sent by e-mail to platform administrator.'."\n"
                         .'This option allow only to request it, and don\'t prework the answer'."\n"
      , 'display'       => true
      , 'default'       => false
      , 'type'          => 'boolean'
      , 'acceptedValue' => array ('TRUE'  => 'Yes'
                                 ,'FALSE' => 'No'
                                )
      );


$conf_def_property_list['allowSelfRegProf'] =
array ('label'       => 'Creation of Course Creator account'
       ,'description' => 'Are users allowed to create themselves a Course Creator account ?'
      ,'default'     => true
      ,'type'        => 'boolean'
      ,'acceptedValue' => array ('TRUE'  => 'On'
                                ,'FALSE' => 'Off'
                                )
      ,'display'     => true
      ,'readonly'    => false
      );
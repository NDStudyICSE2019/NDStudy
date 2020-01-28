<?php // $Id: CLUSR.def.conf.inc.php 13965 2012-01-30 15:14:56Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * This file describe the parameter for user tool
 *
 * @version 1.8 $Revision: 13965 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/index.php/Config
 *
 * @author Claro Team <cvs@claroline.net>
 *
 * @package CLUSR
 *
 */
// TOOL
$conf_def['config_code'] = 'CLUSR';
$conf_def['config_file'] = 'CLUSR.conf.php';
$conf_def['config_name'] = 'Users list';
$conf_def['old_config_file'][]='user.conf.inc.php';
$conf_def['config_class']='user';



//SECTION
$conf_def['section']['main']['label']='Main settings';
//$conf_def['section']['main']['description']='Settings of the tool';
$conf_def['section']['main']['properties'] =
array ( 'linkToUserInfo'
      , 'user_email_hidden_to_anonymous'
      , 'nbUsersPerPage'
      );

//PROPERTIES

$conf_def_property_list['linkToUserInfo'] =
array ('label'         => 'Show user profile page'
      ,'description'   => 'Allow users to see detailed informations about other users'
      ,'default'       => TRUE
      ,'type'          => 'boolean'
      ,'acceptedValue' => array ('TRUE'  => 'Yes'
                                ,'FALSE' => 'No'
                                )
      );

$conf_def_property_list['user_email_hidden_to_anonymous'] =
array ('label'         => 'Hide email address to anonymous user'
      ,'description'   => 'Don\'t display email of the users to anonymous (to avoid spam)'
      ,'default'       => FALSE
      ,'type'          => 'boolean'
      ,'acceptedValue' => array ('TRUE'  => 'Yes'
                                ,'FALSE' => 'No'
                                )
      );

$conf_def_property_list['nbUsersPerPage'] =
array ( 'label'   => 'Number of user per page'
      , 'default' => '25'
      , 'unit'    => 'users'
      ,  'type'    => 'integer'
      ,'acceptedValue' => array ('min'=>'5')
      );


// section

$conf_def['section']['add_user']['label'] = 'Add user';
$conf_def['section']['add_user']['description'] = '';
$conf_def['section']['add_user']['properties'] =
array ( 'is_coursemanager_allowed_to_register_single_user'
      ,    'is_coursemanager_allowed_to_enroll_single_user'
      , 'is_coursemanager_allowed_to_import_user_list'
      , 'is_coursemanager_allowed_to_import_user_class'

);

$conf_def_property_list['is_coursemanager_allowed_to_register_single_user'] =
array('label'         => 'Teachers can register new users to the campus'
     ,'default'       => TRUE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
     );

$conf_def_property_list['is_coursemanager_allowed_to_enroll_single_user'] =
array('label'         => 'Teacher can add a user in his course'
     ,'default'       => TRUE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
     );

$conf_def_property_list['is_coursemanager_allowed_to_import_user_list'] =
array('label'         => 'Teacher can import user list in his course'
     ,'default'       => TRUE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
     );

$conf_def_property_list['is_coursemanager_allowed_to_import_user_class'] =
array('label'         => 'Teachers are allowed to register whole classes to their courses'
     ,'default'       => TRUE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
     );

$conf_def['section']['export']['label'] = 'Export';
$conf_def['section']['export']['description'] = '';
$conf_def['section']['export']['properties'] =
array ( 'is_coursemanager_allowed_to_export_user_list'
      , 'export_user_username'
      , 'export_user_password'
      , 'export_user_password_encrypted'
      , 'export_user_id'
      , 'export_sensitive_data_for_admin'
);

$conf_def_property_list['is_coursemanager_allowed_to_export_user_list'] =
array('label'         => 'Teacher can export user list from his course'
     ,'default'       => TRUE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
     );

$conf_def_property_list['export_sensitive_data_for_admin'] =
array('label'         => 'Platform admin can export username, user_id and password'
     ,'default'       => FALSE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
);

$conf_def_property_list['export_user_username'] =
array('label'         => 'Teacher can export username'
     ,'default'       => FALSE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
);
     
$conf_def_property_list['export_user_id'] =
array('label'         => 'Teacher can export user id'
     ,'default'       => FALSE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
);

$conf_def_property_list['export_user_password'] =
array('label'         => 'Teacher can export password'
     ,'default'       => FALSE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
);

$conf_def_property_list['export_user_password_encrypted'] =
array('label'         => 'Encrypt exported password using md5 algorithm for teacher'
     ,'default'       => TRUE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
);

$conf_def['section']['import']['label'] = 'Import CVS';
$conf_def['section']['import']['description'] = '';
$conf_def['section']['import']['properties'] =
array ( 'update_user_properties'
);

$conf_def_property_list['update_user_properties'] =
array('label'         => 'Update properties of users on CVS import'
     ,'default'       => FALSE
     ,'type'          => 'boolean'
     ,'display'       => TRUE
     ,'readonly'      => FALSE
     ,'acceptedValue' => array ('TRUE'=>'Yes'
                              ,'FALSE'=>'No'
                              )
     );

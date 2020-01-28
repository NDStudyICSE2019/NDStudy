<?php // $Id: CLMSG.def.conf.inc.php 14265 2012-09-04 15:21:47Z ffervaille $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * This file describe the parameter for user tool.
 *
 * @version 1.8 $Revision: 14265 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see http://www.claroline.net/wiki/index.php/Config
 * @author Claro Team <cvs@claroline.net>
 * @package CLUSR
 */

// TOOL
$conf_def['config_code'] = 'CLMSG';
$conf_def['config_file'] = 'CLMSG.conf.php';
$conf_def['config_name'] = 'Internal messaging system';
$conf_def['config_class']= 'kernel';


//SECTION
$conf_def['section']['main']['label']='Main settings';
//$conf_def['section']['main']['description']='';
$conf_def['section']['main']['properties'] =
array (
    'messagePerPage',
    'mailNotification',
    'userCanSendMessage'
);

//PROPERTIES
$conf_def_property_list['messagePerPage'] =
array ( 'label'   => 'Number of message per page'
      , 'default' => '15'
      , 'unit'    => 'messages'
      , 'type'    => 'integer'
      , 'acceptedValue' => array ('min'=>'5')
      , 'display'     => true
      , 'readonly'    => false
      );


$conf_def_property_list['mailNotification'] =
array ( 'label'   => 'Enable Email notification'
      , 'default' => true
      , 'type'    => 'boolean'
      , 'acceptedValue' => array('TRUE'=>'Yes', 'FALSE' => 'No')
      , 'display'     => true
      , 'readonly'    => false
      );

$conf_def_property_list['userCanSendMessage'] =
array ( 'label'   => 'Users can send messages from outside a course context'
      , 'default' => false
      , 'type'    => 'boolean'
      , 'acceptedValue' => array('TRUE'=>'Yes', 'FALSE' => 'No')
      , 'display'     => true
      , 'readonly'    => false
      );
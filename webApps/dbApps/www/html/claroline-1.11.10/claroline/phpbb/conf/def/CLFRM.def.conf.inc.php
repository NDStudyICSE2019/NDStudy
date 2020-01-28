<?php //$Id: CLFRM.def.conf.inc.php 12923 2011-03-03 14:23:57Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * This file describe the parameter for forum tool
 *
 * @version 1.8 $Revision: 12923 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/index.php/Config
 *
 * @author Claro Team <cvs@claroline.net>
 *
 * @package CLFRM
 *
 */

// TOOL
$conf_def['config_code']='CLFRM';
$conf_def['config_file']='CLFRM.conf.php';
$conf_def['config_name'] = 'Forums';
$conf_def['config_class']='tool';

$conf_def['section']['forum']['label']='General settings';
$conf_def['section']['forum']['description']='Settings of the tool';
$conf_def['section']['forum']['properties'] =
array ( 'allow_html'
      , 'posts_per_page'
      , 'topics_per_page'
      , 'clfrm_notification_enabled'
      , 'clfrm_anonymity_enabled'
      , 'confirm_not_anonymous'
      );

//PROPERTIES
// Setup forum Options.
$conf_def_property_list['allow_html']
= array ('label'     => 'HTML in posts'
        ,'description' => 'Allow user to use html tag in messages'
        ,'display'       => false
        ,'default'   => '1'
        ,'type'      => 'enum'
        ,'container' => 'VAR'
        ,'readonly'      => FALSE
        ,'acceptedValue' => array ( '1'=>'Yes'
                                  , '0'=>'No'
                                  )
        );

$conf_def_property_list['posts_per_page']
= array ('label'     => 'Number of posts per page'
        ,'default'   => '5'
        ,'unit'      => 'posts'
        ,'type'      => 'integer'
        ,'container' => 'VAR'
        ,'acceptedValue' => array ( 'min'=>2
                                  , 'max'=>25
                                  )
        );

$conf_def_property_list['topics_per_page']
= array ('label'     => 'Number of topics per page'
        ,'default'   => '5'
        ,'unit'      => 'topics'
        ,'type'      => 'integer'
        ,'container' => 'VAR'
        );

$conf_def_property_list['clfrm_notification_enabled']
= array ('label'     => 'Enable notification of new items'
        ,'description' => ''
        ,'display'       => false
        ,'default'   => TRUE
        ,'type'        => 'boolean'
        ,'display'     => TRUE
        ,'readonly'    => FALSE
        ,'acceptedValue' => array ('TRUE'=>'On', 'FALSE' => 'Off')
        );
        
$conf_def_property_list['clfrm_anonymity_enabled']
= array ('label'     => 'Allow anonymity management'
        ,'description' => 'Choose "Yes" to give course managers the possibility to allow anonymous posting in forums.'
        ,'display'       => false
        ,'default'   => TRUE
        ,'type'        => 'boolean'
        ,'display'     => TRUE
        ,'readonly'    => FALSE
        ,'acceptedValue' => array ('TRUE'=>'Yes', 'FALSE' => 'No')
        );
        
$conf_def_property_list['confirm_not_anonymous']
= array ('label'     => 'Confirm signed posts'
        ,'description' => 'Choose "Yes" to display a confirmation message when users sign posts in anonymous forums'
        ,'display'       => false
        ,'default'   => TRUE
        ,'type'        => 'boolean'
        ,'display'     => TRUE
        ,'readonly'    => FALSE
        ,'acceptedValue' => array ('TRUE'=>'Yes', 'FALSE' => 'No')
        );

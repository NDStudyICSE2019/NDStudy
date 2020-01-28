<?php //$Id: CLDSC.def.conf.inc.php 14360 2013-01-24 14:07:22Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * This file describe the parameter for description tool
 *
 * @version 1.8 $Revision: 14360 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/index.php/Config
 *
 * @author Claro Team <cvs@claroline.net>
 *
 * @package CLDSC
 *
 */

// TOOL
$conf_def['config_code']='CLDSC';
$conf_def['config_file']='CLDSC.conf.php';
$conf_def['config_name'] = 'Course description';
$conf_def['config_class']='tool';

$conf_def['section']['main']['label']      = 'Main';
$conf_def['section']['main']['description']='Settings of the tool';
$conf_def['section']['main']['properties'] =
array ( 'cldsc_use_new_ordering_of_labels'
      );

//PROPERTIES
// Setup Course Description Options.
$conf_def_property_list['cldsc_use_new_ordering_of_labels']
= array ('label'     => 'Use new ordering of labels'
        ,'description' => 'Display description elements corresponding to others at the end'
      , 'default'     => FALSE
      , 'type'        => 'boolean'
      , 'acceptedValue' => array ('TRUE'=>'Yes'
                               ,'FALSE'=>'No'
                               )
      , 'display'     => TRUE
      , 'readonly'    => FALSE
        );

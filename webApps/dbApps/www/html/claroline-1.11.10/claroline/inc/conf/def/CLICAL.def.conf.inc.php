<?php // $Id: CLICAL.def.conf.inc.php 12923 2011-03-03 14:23:57Z abourguignon $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * This file describe the parameter for user tool.
 *
 * @version     1.8 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Config
 * @author      Claro Team <cvs@claroline.net>
 * @package     CLICAL
 */

// TOOL
$conf_def['config_code']  = 'CLICAL';
$conf_def['config_file']  = 'ical.conf.php';
$conf_def['config_name']  = 'iCal generator';
$conf_def['config_class'] = 'kernel';

//SECTION
$conf_def['section']['main']['label']='Main settings';
$conf_def['section']['main']['properties'] =
array ( 'enableICalInCourse'
      , 'defaultEventDuration'
      , 'iCalGenStandard'
      , 'iCalGenXml'
      , 'iCalGenRdf'
      );

$conf_def['section']['cache']['label']='Cache settings';
$conf_def['section']['cache']['properties'] =
array ( 'iCalUseCache'
      , 'iCalRepositoryCache'
      , 'iCalCacheLifeTime'
      );

//PROPERTIES

$conf_def_property_list['enableICalInCourse'] =
array ('label'         => 'Enable iCal in course'
      , 'description'  => ''
      ,'default'       => true
      ,'type'          => 'boolean'
      , 'readonly'      => false
      , 'acceptedValue' => array('TRUE'=>'Yes', 'FALSE' => 'No')

      );

$conf_def_property_list['iCalRepositoryCache'] =
array ('label'         => 'Repository for cache files'
      , 'description'  => 'Note :  this repository should be protected with a .htaccess or
       be placed outside the web. Because there contain data of private courses.'
      ,'default'       => 'tmp/cache/ical/'
      ,'type'          => 'relpath'
      );

$conf_def_property_list['iCalUseCache'] =
array ('label'         => 'Enable cache'
      , 'description'  => 'Enabling the cache may increase performance'
      ,'default'       => true
      ,'type'          => 'boolean'
      , 'readonly'      => false
      , 'acceptedValue' => array('TRUE'=>'Yes', 'FALSE' => 'No')
      );

$conf_def_property_list['iCalGenStandard'] =
array ('label'         => 'Generate ics file'
      , 'description'  => 'When iCal File is regenerated, make the ics version.'
      ,'default'       => true
      ,'type'          => 'boolean'
      , 'display'      => true
      , 'readonly'      => false
      , 'acceptedValue' => array('TRUE'=>'Yes, create ics version', 'FALSE' => 'No')
      );

      $conf_def_property_list['iCalGenXml'] =
array ('label'         => 'Generate Xml file'
      , 'description'  => 'When iCal File is regenerated, make the xml version.'
      ,'default'       => true
      ,'type'          => 'boolean'
      , 'display'      => true
      , 'readonly'      => false
      , 'acceptedValue' => array('TRUE'=>'Yes, create XML version', 'FALSE' => 'No')
      );

$conf_def_property_list['iCalGenRdf'] =
array ('label'         => 'Generate RDF file'
      , 'description'  => 'When iCal File is regenerated, make the RDF version.'
      , 'default'       => false
      , 'type'          => 'boolean'
      , 'display'      => true
      , 'readonly'      => false
      , 'acceptedValue' => array('TRUE'=>'Yes, create RDF version', 'FALSE' => 'No')
      );


$conf_def_property_list['defaultEventDuration'] =
array (
        'label'         => 'Event duration'
      , 'description'   => 'In iCal an event has a duration but not in claroline. 3600 seconds = 1 Hour.'
      , 'default'       => '3600'
      , 'type'           => 'integer'
      , 'unit'           => 'seconds'
      , 'display'      => true
      , 'readonly'      => false
      , 'acceptedValue' => array('min'=> '1', 'max' => '86400')
      );


$conf_def_property_list['iCalCacheLifeTime'] =
array (
        'label'         => 'Life time of cache'
      , 'description'   => 'Time before really compute data. 86400 seconds = 1 day.'
      , 'default'       => '86400'
      , 'type'           => 'integer'
      , 'unit'           => 'seconds'
      , 'display'      => true
      , 'readonly'      => false
      , 'acceptedValue' => array('min'=> '360', 'max' => '8640000')
      );
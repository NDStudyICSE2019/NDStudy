<?php // $Id: CLRSS.def.conf.inc.php 12923 2011-03-03 14:23:57Z abourguignon $

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
 * @package     CLRSS
 */

// TOOL
$conf_def['config_code']  = 'CLRSS';
$conf_def['config_file']  = 'rss.conf.php';
$conf_def['config_name']  = 'Rss (read and write) tool';
$conf_def['config_class'] = 'kernel';

//SECTION
$conf_def['section']['main']['label']='Main settings';
$conf_def['section']['main']['properties'] =
array ( 'enableRssInCourse'
      , 'rssRepositoryCache'
      , 'rssUseCache'
      , 'rssCacheLifeTime'
      );

//PROPERTIES

$conf_def_property_list['enableRssInCourse'] =
array ('label'          => 'Enable RSS in course'
      , 'description'   => ''
      ,'default'        => true
      ,'type'           => 'boolean'
      , 'readonly'      => false
      , 'acceptedValue' => array('TRUE'=>'Yes', 'FALSE' => 'No')

      );

$conf_def_property_list['rssRepositoryCache'] =
array ('label'         => 'Repository for cache files'
      , 'description'  => 'Note :  this repository should be protected with a .htaccess or
       be placed outside the web. Because there contain data of private courses.'
      ,'default'       => 'tmp/cache/rss/'
      ,'type'          => 'relpath'
      );

$conf_def_property_list['rssUseCache'] =
array (
        'label'         => 'Enable cache'
      , 'description'   => 'Enabling the cache may increase performance'
      , 'default'       => true
      ,'type'           => 'boolean'
      , 'readonly'      => false
      , 'acceptedValue' => array('TRUE'=>'Yes', 'FALSE' => 'No')
      , 'oldName' => 'use_rss_cache'
      );

$conf_def_property_list['rssCacheLifeTime'] =
array (
        'label'         => 'Life time of cache'
      , 'description'   => 'Time before really compute data. 86400 = 1 day.'
      , 'default'       => '86400'
      , 'type'          => 'integer'
      , 'unit'          => 'seconds'
      , 'display'       => true
      , 'readonly'      => false
      , 'acceptedValue' => array('min'=> '360', 'max' => '8640000')
      , 'oldName'       => 'use_rss_cache'
      );
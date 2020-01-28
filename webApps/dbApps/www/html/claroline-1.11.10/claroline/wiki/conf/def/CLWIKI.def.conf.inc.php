<?php // $Id: CLWIKI.def.conf.inc.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * This file describe the parameter for user tool
 *
 * @version 1.8 $Revision: 13708 $
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
$conf_def['config_code'] = 'CLWIKI';
$conf_def['config_file'] = 'CLWIKI.conf.php';
$conf_def['config_name'] = 'Wiki';
$conf_def['config_class']='tool';


//SECTION
$conf_def['section']['main']['label']='Main settings';
//$conf_def['section']['main']['description']='Settings of the tool';
$conf_def['section']['main']['properties'] =
array ( 'showWikiEditorToolbar'
    , 'forcePreviewBeforeSaving'
      );

//PROPERTIES

$conf_def_property_list['showWikiEditorToolbar'] =
array ('label'         => 'Show wiki syntax toolbar'
 //     ,'description'   => '...'
      ,'default'       => TRUE
      ,'type'          => 'boolean'
      ,'acceptedValue' => array ('TRUE'  => 'Yes'
                                ,'FALSE' => 'No'
                                )
      );

$conf_def_property_list['forcePreviewBeforeSaving'] =
array ('label'         => 'Force text preview before saving it'
    //  ,'description'   => '...'
      ,'default'       => FALSE
      ,'type'          => 'boolean'
      ,'acceptedValue' => array ('TRUE'  => 'Yes'
                                ,'FALSE' => 'No'
                                )
      );

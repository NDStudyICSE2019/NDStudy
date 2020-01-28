<?php

if ( count ( get_included_files () ) == 1 )
    die ( '---' );
/**
 * CLAROLINE
 *
 * This file describe the parameter for CLDOC config file
 *
 * @version 1.8 $Revision: 14405 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/index.php/Config
 *
 * @package CLLNP
 *
 */
// CONFIG HEADER

$conf_def[ 'config_code' ] = 'CLLNP';
$conf_def[ 'config_file' ] = 'CLLNP.conf.php';
$conf_def[ 'config_name' ] = 'Learning path';
$conf_def[ 'config_class' ] = 'tool';

// CONFIG SECTIONS
$conf_def[ 'section' ][ 'quota' ][ 'label' ] = 'Quota';
$conf_def[ 'section' ][ 'quota' ][ 'description' ] = 'Disk space allowed for import learning path';
$conf_def[ 'section' ][ 'quota' ][ 'properties' ] =
    array ( 'maxFilledSpace_for_import',
        'cllnp_resetByUserAllowed',
        'cllnp_documentDefaultTime',
        'cllnp_documentDefaultTimeOnce',
        'cllnp_countTimeSpentOnDocument',
        'cllnp_countTimeIntervalCheck',
        'cllnp_countTimeOverDefault'
);

// CONFIG PROPERTIES
$conf_def_property_list[ 'maxFilledSpace_for_import' ]
    = array ( 'label' => 'Quota for courses'
    , 'description' => 'Disk space allowed to import scorm package'
    , 'default' => '100000000'
    , 'unit' => 'bytes'
    , 'type' => 'integer'
    , 'container' => 'VAR'
    , 'acceptedValue' => array ( 'min' => '1024' )
);

$conf_def_property_list[ 'cllnp_resetByUserAllowed' ] =
    array ( 'description' => 'Set to Yes to allow students to reset their progression in learning pathes'
        , 'label' => 'Allow students to reset path'
        , 'default' => FALSE
        , 'type' => 'boolean'
        , 'acceptedValue' => array ( 'TRUE' => 'Yes'
            , 'FALSE' => 'No'
        )
        , 'display' => TRUE
        , 'readonly' => FALSE
);

$conf_def_property_list[ 'cllnp_documentDefaultTime' ] =
    array ( 'description' => 'Associate a default time in minute to a document that will be used for learnPath tracking'
        , 'label' => 'Document default time (minute)'
        , 'type' => 'integer'
        , 'default' => '0'
        , 'display' => TRUE
        , 'readonly' => FALSE
        , 'acceptedValue' => array( 'min' => '0' )
);

$conf_def_property_list[ 'cllnp_documentDefaultTimeOnce' ] =
    array( 'description' => 'Only use the document default time once. Once set no additional time will be added (except with the script)'
        , 'label' => 'Use document default time only once'
        , 'type' => 'boolean'
        , 'default' => TRUE
        , 'display' => TRUE
        , 'readonly' => FALSE
        , 'acceptedValue' => array( 'TRUE' => 'Yes', 'FALSE' => 'No' )
);

$conf_def_property_list[ 'cllnp_countTimeSpentOnDocument' ] =
    array( 'description' => 'Allow a script to count time spent on a document-type module'
        , 'label' => 'Activate script to count time'
        , 'type' => 'boolean'
        , 'default' => TRUE
        , 'display' => TRUE
        , 'readonly' => FALSE
        , 'acceptedValue' => array( 'TRUE' => 'Yes', 'FALSE' => 'No' )
);

$conf_def_property_list[ 'cllnp_countTimeIntervalCheck' ] =
    array ( 'description' => 'A time interval (in minute) to define when the script has to check if the module is still active'
        , 'label' => 'Interval (minute)'
        , 'type' => 'integer'
        , 'default' => '1'
        , 'display' => TRUE
        , 'readonly' => FALSE
        , 'acceptedValue' => array( 'min' => '1', 'max' => '60' )
);

$conf_def_property_list[ 'cllnp_countTimeOverDefault' ] =
    array( 'description' => 'Only use the counted time if its value is longer than the default time associated to the document'
        , 'label' => 'Only use counted time if longer than default time'
        , 'type' => 'boolean'
        , 'default' => TRUE
        , 'display' => TRUE
        , 'readonly' => FALSE
        , 'acceptedValue' => array( 'TRUE' => 'Yes', 'FALSE' => 'No' )
);

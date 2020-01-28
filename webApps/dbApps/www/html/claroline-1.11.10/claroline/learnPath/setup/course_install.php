<?php // $Id: course_install.php 13708 2011-10-19 10:46:34Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

############################### LEARNING PATH  ####################################

$moduleWorkingDirectory = get_path('coursesRepositorySys') . $courseDirectory . '/modules';

if ( ! claro_mkdir($moduleWorkingDirectory, CLARO_FILE_PERMISSIONS,true) )
{
    return claro_failure::set_failure(
            get_lang( 'Unable to create folder %folder'
                ,array( '%folder' => $moduleWorkingDirectory ) ) );
}

$moduleWorkingDirectory = get_path('coursesRepositorySys') . $courseDirectory . '/scormPackages';

if ( ! claro_mkdir($moduleWorkingDirectory, CLARO_FILE_PERMISSIONS,true) )
{
    return claro_failure::set_failure(
            get_lang( 'Unable to create folder %folder'
                ,array( '%folder' => $moduleWorkingDirectory ) ) );
}

$moduleWorkingDirectory = get_path('coursesRepositorySys') . $courseDirectory . '/modules/module_1';

if ( ! claro_mkdir($moduleWorkingDirectory, CLARO_FILE_PERMISSIONS,true) )
{
    return claro_failure::set_failure(
            get_lang( 'Unable to create folder %folder'
                ,array( '%folder' => $moduleWorkingDirectory ) ) );
}

if ( get_conf('fill_course_example',true) )
{
    $questionId = 1;
    $exerciseId = 1;
    
    $TABLELEARNPATH         = $moduleCourseTblList['lp_learnPath'];//  "lp_learnPath";
    $TABLEMODULE            = $moduleCourseTblList['lp_module'];//  "lp_module";
    $TABLELEARNPATHMODULE   = $moduleCourseTblList['lp_rel_learnPath_module'];//  "lp_rel_learnPath_module";
    $TABLEASSET             = $moduleCourseTblList['lp_asset'];//  "lp_asset";

    // HANDMADE module type are not used for first version of claroline 1.5 beta so we don't show any exemple!

    claro_sql_query("INSERT INTO `".$TABLELEARNPATH."` VALUES ('1', '".claro_sql_escape(get_lang('sampleLearnPathTitle'))."', '".claro_sql_escape(get_lang('sampleLearnPathDescription'))."', 'OPEN', 'SHOW', '1')");

    claro_sql_query("INSERT INTO `".$TABLELEARNPATHMODULE."` VALUES ('1', '1', '1', 'OPEN', 'SHOW', '', '1', '0', '50')");
    claro_sql_query("INSERT INTO `".$TABLELEARNPATHMODULE."` VALUES ('2', '1', '2', 'OPEN', 'SHOW', '', '2', '0', '50')");

    claro_sql_query("INSERT INTO `".$TABLEMODULE."` VALUES ('1', '".claro_sql_escape(get_lang('sampleLearnPathDocumentTitle'))."', '".claro_sql_escape(get_lang('sampleLearnPathDocumentDescription'))."', 'PRIVATE', '1', 'DOCUMENT', '')");
    claro_sql_query("INSERT INTO `".$TABLEMODULE."` VALUES ('2', '".claro_sql_escape(get_lang('sampleQuizTitle'))."', '".claro_sql_escape(get_lang('sampleLearnPathQuizDescription'))."', 'PRIVATE', '2', 'EXERCISE', '')");

    claro_sql_query("INSERT INTO `".$TABLEASSET."` VALUES ('1', '1', '/Example_document.pdf', '')");
    claro_sql_query("INSERT INTO `".$TABLEASSET."` VALUES ('2', '2', '".$exerciseId."', '')");
}

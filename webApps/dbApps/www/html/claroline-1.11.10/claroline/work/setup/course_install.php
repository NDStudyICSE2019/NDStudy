<?php // $Id: course_install.php 13708 2011-10-19 10:46:34Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

$moduleWorkingDirectory = get_path('coursesRepositorySys') . $courseDirectory . '/work';

if ( ! claro_mkdir($moduleWorkingDirectory, CLARO_FILE_PERMISSIONS,true) )
{
    return claro_failure::set_failure(
            get_lang( 'Unable to create folder %folder'
                ,array( '%folder' => $moduleWorkingDirectory ) ) );
}

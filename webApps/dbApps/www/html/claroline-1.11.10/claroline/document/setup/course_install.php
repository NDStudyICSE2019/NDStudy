<?php // $Id: course_install.php 13708 2011-10-19 10:46:34Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 ) die( '---' );

############################### DOCUMENT  #################################

// WARNING. Do not forget to adapt queries in fill_Db_course()
// if something changed here

if ( get_conf('fill_course_example',true) )
{
    $exampleSrcPath = get_module_path('CLDOC') . '/Example_document.pdf';
    
    $exampleDestPath = get_path('coursesRepositorySys')
        . $courseDirectory . '/document/Example_document.pdf';
                
    if ( ! file_exists( $exampleDestPath ) )
    {
        return copy( $exampleSrcPath, $exampleDestPath );
    }
}

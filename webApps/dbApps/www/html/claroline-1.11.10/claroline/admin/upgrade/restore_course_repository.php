<?php // $Id: restore_course_repository.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * Try to create main database of claroline without remove existing content
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 * @package     UPGRADE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Mathieu Laurent <laurent@cerdecam.be>
 * @since       1.6
 */

/*=====================================================================
  Init Section
 =====================================================================*/

// Initialise Upgrade
require 'upgrade_init_global.inc.php';

// Security Check
if ( !claro_is_platform_admin() ) upgrade_disp_auth_form();

/*=====================================================================
  Main Section
 =====================================================================*/

$nameTools = get_lang('Restore course repository');

// Execute command

if ( isset($_REQUEST['cmd'])
     && ( $_REQUEST['cmd'] == 'exRestore'
          || ( $_REQUEST['cmd'] == 'exMove' && get_path('coursesRepositoryAppend') != 'courses/'  ) ) )
{
    if ( $_REQUEST['cmd'] == 'exMove' )
    {
        $newCourseFolder = get_path('rootSys').'courses/';

        if ( ! is_dir($newCourseFolder) )
        {
            if ( mkdir($newCourseFolder) === false )
            {
                echo sprintf('Creation of "%s" folder failed',$newCourseFolder);
            }
        }
    }

    // query returns course code and course folder
    $tbl_mdb_names = claro_sql_get_main_tbl();
    
    $tbl_course = $tbl_mdb_names['course'];
    
    $sqlListCourses = " SELECT code sysCode, directory coursePath ".
                      " FROM `". $tbl_course . "` " .
                      " ORDER BY sysCode";
    
    $res_listCourses = claro_sql_query($sqlListCourses);
    
    if (mysql_num_rows($res_listCourses))
    {
        $restored_courses =  '<ol>' . "\n";
        $moved_courses =  '<ol>' . "\n";
        
        while ( ( $course = mysql_fetch_array($res_listCourses)) )
        {
            $currentcoursePathSys = get_path('coursesRepositorySys') . $course['coursePath'] . '/';
            $currentCourseIDsys = $course['sysCode'];
            
            if ( $_REQUEST['cmd'] == 'exRestore' )
            {
                if ( restore_course_repository($currentCourseIDsys,$currentcoursePathSys) )
                {
                    $restored_courses .= '<li>' . sprintf('Course repository "%s" updated', $currentcoursePathSys) . '</li>' . "\n";
                }
            }
            elseif ( $_REQUEST['cmd'] == 'exMove' )
            {
                $currentFolder = get_path('coursesRepositorySys') . $course['coursePath'] . '/';
                $newFolder = get_path('rootSys') . 'courses/' . $course['coursePath'] . '/';

                if ( move_course_folder($currentFolder,$newFolder) === false )
                {
                    $moved_courses .= '<li>' . sprintf('Error: Cannot rename "%s" to "%s"', $currentFolder ,$newFolder) . '</li>' . "\n";
                }
                else
                {
                    $moved_courses.= '<li>' . sprintf('Course repository "%s" moved to "%s"', $currentFolder,$newFolder) . '</li>' . "\n";
                }
            }
        }
        $restored_courses .= '</ol>' . "\n";
        $moved_courses .= '</ol>' . "\n";
    }

    // TODO if course move succeed, update the value in configuration
    if ( $_REQUEST['cmd'] == 'exMove' && $error = false )
    {
        $_GLOBALS['coursesRepositoryAppend'] = 'courses/';

        $config = new Config('CLMAIN');
        $config->load();
        $config->validate(array('coursesRepositoryAppend'=>'courses/'));
        $config->save();
    }
}

// Display Header
echo upgrade_disp_header();

echo claro_html_tool_title($nameTools);

// display result

if (isset($restored_courses)) echo $restored_courses;
if (isset($moved_courses)) echo $moved_courses;

// display link to launch the restore
if ( get_path('coursesRepositoryAppend') != 'courses/' )
{
    echo '<p><a href="' . $_SERVER['PHP_SELF'] . '?cmd=exMove">' . sprintf('Move "course repository" to folder "%s"', get_path('rootSys') . 'courses/') . '</a></p>';
}

echo '<p><a href="' . $_SERVER['PHP_SELF'] . '?cmd=exRestore">' . sprintf('Launch restore of the course repository') . '</a></p>';

// Display footer
echo upgrade_disp_footer();

// move folder to new folder
// TODO use claro_failure

function move_course_folder ( $currentFolder, $newFolder )
{
    if ( ! is_dir($currentFolder) )
    {
        // current folder doesn't exist
        return false ;
    }

    if ( is_dir($newFolder) )
    {
        // folder already exists
        return false ;
    }

    if ( $currentFolder == $newFolder )
    {
        // the currentFolder is the newFolder
        return false ;
    }
                
    if ( @rename($currentFolder,$newFolder) === false )
    {
        return false;
    }
    else
    {
        return true;
    }
}

function restore_course_repository($courseId, $courseRepository)
{

    global $urlAppend;

    if ( is_writable($courseRepository) )
    {
        umask(0);

        /**
            create directory for new tools of claroline 1.5
        */
    
        if ( !is_dir($courseRepository) ) mkdir($courseRepository, CLARO_FILE_PERMISSIONS);
        if ( !is_dir($courseRepository . '/chat'          ) ) mkdir($courseRepository . '/chat'          , CLARO_FILE_PERMISSIONS);
        if ( !is_dir($courseRepository . '/modules'       ) ) mkdir($courseRepository . '/modules'       , CLARO_FILE_PERMISSIONS);
        if ( !is_dir($courseRepository . '/scormPackages' ) ) mkdir($courseRepository . '/scormPackages' , CLARO_FILE_PERMISSIONS);

        // build index.php of course
        $fd = fopen($courseRepository . '/index.php', 'w');
        if ( ! $fd) return claro_failure::set_failure('CANT_CREATE_COURSE_INDEX');

        $string = '<?php ' . "\n"
                . 'header (\'Location: '. $urlAppend . '/claroline/course/index.php?cid=' . claro_htmlspecialchars($courseId) . '\') ;' . "\n"
              . '?' . '>' . "\n" ;

        if ( ! fwrite($fd, $string) ) return false;
        if ( ! fclose($fd) )          return false;

        $fd = fopen($courseRepository . '/group/index.php', 'w');
        if ( ! $fd ) return false;

        $string = '<?php session_start(); ?'.'>';

        if ( ! fwrite($fd, $string) ) return false;

        return true;
    
    } else {
        printf ('repository %s not writable', $courseRepository);
        return 0;
    }

}

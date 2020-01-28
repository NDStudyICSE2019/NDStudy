<?php // $Id: upgrade_courses.php 14421 2013-04-12 12:46:34Z zefredz $

/**
 * CLAROLINE
 *
 * This script Upgrade course database and course space.
 *
 * @version     $Revision: 14421 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 * @package     UPGRADE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Mathieu Laurent <laurent@cerdecam.be>
 */

$new_version_branch = '';
// Initialise Upgrade
require 'upgrade_init_global.inc.php';

// Include Libraries
include ('upgrade_course_16.lib.php');
include ('upgrade_course_17.lib.php');
include ('upgrade_course_18.lib.php');
include ('upgrade_course_19.lib.php');
include ('upgrade_course_110.lib.php');

require_once $includePath . '/lib/module/manage.lib.php';

// Security Check
if (!claro_is_platform_admin()) upgrade_disp_auth_form();

// DB tables definition
$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_course = $tbl_mdb_names['course'];
$tbl_rel_course_user   = $tbl_mdb_names['rel_course_user'];
$tbl_course_tool       = $tbl_mdb_names['tool'];

/**
 * Displays flags
 * Using __LINE__ to have an arbitrary value
 */

DEFINE ('DISPLAY_WELCOME_PANEL', __LINE__ );
DEFINE ('DISPLAY_RESULT_PANEL', __LINE__);

/*=====================================================================
  Statements Section
 =====================================================================*/

if ( isset($_REQUEST['verbose']) ) $verbose = true;

if ( isset($_REQUEST['cmd']) ) $cmd = $_REQUEST['cmd'];
else                           $cmd = false;

$upgradeCoursesError = isset($_REQUEST['upgradeCoursesError'])
                     ? $_REQUEST['upgradeCoursesError']
                     : false;

if ( $cmd == 'run')
{
    $display = DISPLAY_RESULT_PANEL;
}
else
{
    $display = DISPLAY_WELCOME_PANEL;
}

// Get start time
$mtime = microtime();
$mtime = explode(' ',$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;
$steptime =$starttime;

// count course to upgrade
$count_course_upgraded = count_course_upgraded($new_version_branch);

$count_course = $count_course_upgraded['total'];
$count_course_error = $count_course_upgraded['error'];
$count_course_upgraded = $count_course_upgraded['upgraded'];

$count_course_error_at_start = $count_course_error;
$count_course_upgraded_at_start =  $count_course_upgraded;

/*=====================================================================
  Main Section
 =====================================================================*/

/*---------------------------------------------------------------------
  Steps of Display
 ---------------------------------------------------------------------*/

// auto refresh
if ( $display == DISPLAY_RESULT_PANEL && ($count_course_upgraded + $count_course_error ) < $count_course )
{
    $refresh_time = 20;
    $htmlHeadXtra[] = '<meta http-equiv="refresh" content="'. $refresh_time  .'" />'."\n";
}

// Display Header
echo upgrade_disp_header();

/*---------------------------------------------------------------------
  Main
 ---------------------------------------------------------------------*/

switch ($display)
{
    case DISPLAY_WELCOME_PANEL :

        echo '<h2>Step 3 of 4: courses upgrade</h2>
             <p>Now the <em>Claroline Upgrade Tool</em> is going to prepare <b>course</b> data
            (directories and database tables) one by one and set it to be compatible with the new
            Claroline version.<p class="help">Note. Depending of the speed of your server or the amount
            of data stored on your platform, this operation may take some time.</p>
            <p style="text-align: center"><strong>' . $count_course_upgraded . ' courses
            on ' . $count_course . ' already upgraded</strong><br /></p>
            <center>
            <p><button onclick="document.location=\'' . $_SERVER['PHP_SELF'] . '?cmd=run\';">Launch course data upgrade</button></p>
            </center>';
        break;

    case DISPLAY_RESULT_PANEL :

        echo '<h2>Step 3 of 4: courses upgrade</h2>
              <p>The <em>Claroline Upgrade Tool</em> proceeds to the courses data upgrade</p>';

        // display course upgraded

        echo '<p style="text-align: center"><strong>' . $count_course_upgraded . ' courses
              on ' . $count_course . ' already upgraded</strong><br /></p>';

        flush();

        /*
         * display refresh bloc
         */

        echo  '<div class="help" id="refreshIfBlock">
            <p>In case of interruption <sup>*</sup>, the <em>Claroline Upgrade tool</em> should restart automatically</p>
            <p style="text-align: center">
            <button onclick="document.location=\'' . $_SERVER['PHP_SELF'].'?cmd=run\';">Continue courses data upgrade</button>
            </p>
            <p><small>(*) see in the status bar of your browser.</small></p>
            </div>';

        flush();

        /*
         * Build query to select course to upgrade
         */

        if ( isset($_REQUEST['upgradeCoursesError']) )
        {
            // retry to upgrade course where upgrade failed
            claro_sql_query(" UPDATE `" . $tbl_course . "` SET `versionClaro` = '1.5' WHERE `versionClaro` = 'error-1.5'");
            claro_sql_query(" UPDATE `" . $tbl_course . "` SET `versionClaro` = '1.6' WHERE `versionClaro` = 'error-1.6'");
            claro_sql_query(" UPDATE `" . $tbl_course . "` SET `versionClaro` = '1.7' WHERE `versionClaro` = 'error-1.7'");
            claro_sql_query(" UPDATE `" . $tbl_course . "` SET `versionClaro` = '1.8' WHERE `versionClaro` = 'error-1.8'");
        }

        $sql_course_to_upgrade = " SELECT c.dbName dbName,
                                          c.code ,
                                          c.administrativeNumber ,
                                          c.directory coursePath,
                                          c.creationDate,
                                          c.versionClaro "
                               . " FROM `" . $tbl_course . "` `c` ";

        if ( isset($_REQUEST['upgradeCoursesError']) )
        {
            // retry to upgrade course where upgrade failed
            $sql_course_to_upgrade .= " WHERE c.versionClaro not like '". $new_version_branch ."%'
                                        ORDER BY c.dbName";
        }
        else
        {
            // not upgrade course where upgrade failed ( versionClaro == error* )
            $sql_course_to_upgrade .= " WHERE ( c.versionClaro not like '". $new_version_branch . "%' )
                                              and c.versionClaro not like 'error%'
                                        ORDER BY c.dbName ";
        }

        $res_course_to_upgrade = mysql_query($sql_course_to_upgrade);

        /*
         * Upgrade course
         */

        while ( ($course = mysql_fetch_array($res_course_to_upgrade) ) )
        {
            // initialise variables

            $currentCourseDbName       = $course['dbName'];
            $currentcoursePathSys      = get_path('coursesRepositorySys') . $course['coursePath'].'/';
            $currentcoursePathWeb      = get_path('coursesRepositoryWeb') . $course['coursePath'].'/';
            $currentCourseCode         = $course['code'];
            $currentCourseFakeCode     = $course['administrativeNumber'];
            $currentCourseCreationDate = $course['creationDate'];
            $currentCourseVersion      = $course['versionClaro'];
            $currentCourseDbNameGlu    = get_conf('courseTablePrefix') . $currentCourseDbName . get_conf('dbGlu'); // use in all queries

            // initialise
            $error = false;
            $upgraded = false;
            $message = '';

            echo '<p><strong>' . ( $count_course_upgraded + 1 ) . ' . </strong>
                  Upgrading course <strong>' . $currentCourseFakeCode . '</strong><br />
                  <small>DB Name : ' . $currentCourseDbName . ' - Course ID : ' . $currentCourseCode . '</small></p>';

            /**
             * Make some check.
             * For next versions these test would be set in separate process and available out of upgrade
             */

            // repair tables
            sql_repair_course_database($currentCourseDbNameGlu);

            // course repository doesn't exists

            if ( !file_exists($currentcoursePathSys) )
            {
                $error = true;
                $message .= '<p class="help"><strong>Course has no repository.</strong><br />
                             <small>' .  $currentcoursePathSys . '</small> Not found</p>' . "\n";
                $message .= '<p class="comment">The upgrade tool is not able to upgrade this course.<br />
                             Fix, first, the technical problem and relaunch the upgrade tool.</p>' . "\n";
            }

            if ( ! $error )
            {
                /*---------------------------------------------------------------------
                  Upgrade 1.5 to 1.6
                 ---------------------------------------------------------------------*/

                if ( preg_match('/^1.5/',$currentCourseVersion) )
                {
                    // Function to upgrade tool to 1.6
                    $function_list = array('assignment_upgrade_to_16',
                                           'forum_upgrade_to_16',
                                           'quizz_upgrade_to_16',
                                           'tracking_upgrade_to_16' );

                    foreach ( $function_list as $function )
                    {
                        $step = $function($currentCourseCode);
                        if ( $step > 0 )
                        {
                            echo 'Error : ' . $function . ' at step . ' . $step . '<br />';
                            $error = true;
                        }
                    }

                    if ( ! $error )
                    {
                        // Upgrade succeeded
                        clean_upgrade_status($currentCourseCode);
                        $currentCourseVersion = '1.6';
                    }
                    else
                    {
                        // Upgrade failed
                        $currentCourseVersion = 'error-1.5';
                    }
                    // Save version
                    save_course_current_version($currentCourseCode,$currentCourseVersion);
                }

                /*---------------------------------------------------------------------
                  Upgrade 1.6 to 1.7
                 ---------------------------------------------------------------------*/

                if ( preg_match('/^1.6/',$currentCourseVersion) )
                {
                    // Function to upgrade tool to 1.7
                    $function_list = array( 'agenda_upgrade_to_17',
                                            'announcement_upgrade_to_17',
                                            'course_description_upgrade_to_17',
                                            'forum_upgrade_to_17',
                                            'introtext_upgrade_to_17',
                                            'linker_upgrade_to_17',
                                            'tracking_upgrade_to_17',
                                            'wiki_upgrade_to_17');

                    foreach ( $function_list as $function )
                    {
                        $step = $function($currentCourseCode);
                        if ( $step > 0 )
                        {
                            echo 'Error : ' . $function . ' at step ' . $step . '<br />';
                            $error = true;
                        }
                    }

                    if ( ! $error )
                    {
                        // Upgrade succeeded
                        clean_upgrade_status($currentCourseCode);
                        $currentCourseVersion = '1.7';
                    }
                    else
                    {
                        // Upgrade failed
                        $currentCourseVersion = 'error-1.6';
                    }
                    // Save version
                    save_course_current_version($currentCourseCode,$currentCourseVersion);

                }

                /*---------------------------------------------------------------------
                  Upgrade 1.7 to 1.8
                 ---------------------------------------------------------------------*/

                if ( preg_match('/^1.7/',$currentCourseVersion) )
                {
                    // Function to upgrade tool to 1.8
                    $function_list = array( 'course_repository_upgrade_to_18',
                                            'group_upgrade_to_18',
                                            'tool_list_upgrade_to_18',
                                            'quiz_upgrade_to_18',
                                            'tool_intro_upgrade_to_18',
                                            'tracking_upgrade_to_18',
                                            'forum_upgrade_to_18' );

                    foreach ( $function_list as $function )
                    {
                        $step = $function($currentCourseCode);
                        if ( $step > 0 )
                        {
                            echo 'Error : ' . $function . ' at step ' . $step . '<br />';
                            $error = true;
                        }
                    }

                    if ( ! $error )
                    {
                        // Upgrade succeeded
                        clean_upgrade_status($currentCourseCode);
                        $currentCourseVersion = '1.8';
                    }
                    else
                    {
                        // Upgrade failed
                        $currentCourseVersion = 'error-1.7';
                    }
                    // Save version
                    save_course_current_version($currentCourseCode,$currentCourseVersion);

                }
                
                /*---------------------------------------------------------------------
                  Upgrade 1.8 to 1.9
                 ---------------------------------------------------------------------*/

                if ( preg_match('/^1.8/',$currentCourseVersion) )
                {
                    // Function to upgrade tool to 1.8
                    $function_list = array( 'tool_list_upgrade_to_19',
                                            'tracking_upgrade_to_19',
                                            'calendar_upgrade_to_19',
                                            'chat_upgrade_to_19',
                                            'course_description_upgrade_to_19',
                                            'linker_upgrade_to_19',
                                            'quiz_upgrade_to_19',
                                            'forum_upgrade_to_19'
                                    );
                    
                    if( isset($_SESSION['upgrade_tracking_data']) && $_SESSION['upgrade_tracking_data'])
                    {
                        $function_list[] = 'tracking_data_upgrade_to_19';
                    }
            
                    foreach ( $function_list as $function )
                    {
                        $step = $function($currentCourseCode);
                        if ( $step > 0 )
                        {
                            echo 'Error : ' . $function . ' at step ' . $step . '<br />';
                            $error = true;
                        }
                    }

                    if ( ! $error )
                    {
                        // Upgrade succeeded
                        clean_upgrade_status($currentCourseCode);
                        $currentCourseVersion = '1.9';
                    }
                    else
                    {
                        // Upgrade failed
                        $currentCourseVersion = 'error-1.8';
                    }
                    // Save version
                    save_course_current_version($currentCourseCode,$currentCourseVersion);

                }
                
                /*---------------------------------------------------------------------
                  Upgrade 1.9 to 1.10
                 ---------------------------------------------------------------------*/

                if ( preg_match('/^1.9/',$currentCourseVersion) )
                {
                    // Function to upgrade tool to 1.10
                    
                    $function_list = array();
                    
                    if ( is_module_installed_in_course( 'CLANN',$currentCourseCode ) )
                    {
                        $function_list[] = 'announcements_upgrade_to_110';
                    }
                    
                    if ( is_module_installed_in_course( 'CLCAL',$currentCourseCode ) )
                    {
                        $function_list[] = 'calendar_upgrade_to_110';
                    }
                    
                    $function_list[] = 'tool_intro_upgrade_to_110';
                    
                    if ( is_module_installed_in_course( 'CLQWZ',$currentCourseCode ) )
                    {
                        $function_list[] = 'exercise_upgrade_to_110';
                    }
                    
                    foreach ( $function_list as $function )
                    {
                        $step = $function($currentCourseCode);
                        if ( $step > 0 )
                        {
                            echo 'Error : ' . $function . ' at step ' . $step . '<br />';
                            $error = true;
                        }
                    }

                    if ( ! $error )
                    {
                        // Upgrade succeeded
                        clean_upgrade_status($currentCourseCode);
                        $currentCourseVersion = '1.10';
                    }
                    else
                    {
                        // Upgrade failed
                        $currentCourseVersion = 'error-1.9';
                    }
                    // Save version
                    save_course_current_version($currentCourseCode,$currentCourseVersion);

                }

            }


            if ( ! $error )
            {
                if ( preg_match('/^1.10/',$currentCourseVersion) )
                {
                    $message .= '<p class="success">Upgrade succeeded</p>';
                    // course upgraded
                    $count_course_upgraded++;
                }
                else
                {
                    // course version unknown
                    $count_course_error++;
                    $message .= '<p class="error">Course version unknown : ' . $currentCourseVersion . '</p>';
                    log_message('Course version unknown : ' . $currentCourseVersion . '(in ' . $currentCourseCode . ')');
                }
            }
            else
            {
                $count_course_error++;
                $message .= '<p class="error">Upgrade failed</p>';
            }

            // display message
            echo $message;

            // Calculate time
            $mtime = microtime(); $mtime = explode(' ',$mtime);    $mtime = $mtime[1] + $mtime[0]; $endtime = $mtime;
            $totaltime = ($endtime - $starttime);
            $stepDuration = ($endtime - $steptime);
            $steptime = $endtime;
            $stepDurationAvg = $totaltime / ( ($count_course_upgraded-$count_course_upgraded_at_start)
                                             + ($count_course_error-$count_course_error_at_start) );

            $leftCourses = (int) ($count_course-$count_course_upgraded);
            $leftTime = strftime('%H:%M:%S',$leftCourses * $stepDurationAvg);

            $str_execution_time = sprintf(" <!-- Execution time for this course [%01.2f s] - average [%01.2f s] - total [%s] - left courses [%d]. -->
                                           <strong>Expected remaining time %s</strong>."
                                          ,$stepDuration
                                          ,$stepDurationAvg
                                          ,strftime('%H:%M:%S',$totaltime)
                                          ,$leftCourses
                                          ,$leftTime
                                         );

            echo '<p>' . $str_execution_time . '</p>';

            echo '<hr noshade="noshade" />';
            flush();

        } // end of course upgrade

        $mtime = microtime(); $mtime = explode(" ",$mtime);    $mtime = $mtime[1] + $mtime[0];    $endtime = $mtime; $totaltime = ($endtime - $starttime);

        if ( $count_course_error > 0 )
        {
            /*
             * display block with list of course where upgrade failed
             * add a link to retry upgrade of this course
             */

            $sql = "SELECT code
                    FROM `" . $tbl_course . "`
                    WHERE versionClaro like 'error-%' ";

            $result = claro_sql_query($sql);

            if ( mysql_num_rows($result) )
            {
                echo '<p  class="error">Upgrade tool is not able to upgrade the following courses : ';
                while ( ( $course = mysql_fetch_array($result)) )
                {
                    echo $course['code'] . ' ; ';
                }
                echo  '</p>';

            }

            echo '<p class="comment">'
                    . sprintf('Fix first the technical problem and <a href="%s">relaunch the upgrade tool</a>.',
                              $_SERVER['PHP_SELF'] . '?cmd=run&upgradeCoursesError=1')
                    . '</p>';
        }
        else
        {
            // display next step
            echo '<p class="success">The Claroline upgrade process completed</p>' . "\n";
            echo '<div align="right"><p><button onclick="document.location=\'upgrade_modules.php\';">Next ></button></p></div>';
        }

        /*
         * Hide Refresh Block
         */

        echo '<script type="text/javascript">' . "\n";
        echo 'document.getElementById(\'refreshIfBlock\').style.visibility = "hidden"';
        echo '</script>';

        break;

} // end of switch display

/*---------------------------------------------------------------------
  Display Footer
 ---------------------------------------------------------------------*/

// Display footer
echo upgrade_disp_footer();


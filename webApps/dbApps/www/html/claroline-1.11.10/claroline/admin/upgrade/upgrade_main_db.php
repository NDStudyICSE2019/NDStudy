<?php // $Id: upgrade_main_db.php 14188 2012-06-15 11:53:03Z zefredz $

/**
 * CLAROLINE
 *
 * Try to create main database of claroline without remove existing content.
 *
 * @version $Revision: 14188 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 * @package UPGRADE
 * @author Claro Team <cvs@claroline.net>
 * @author Christophe Gesche <moosh@claroline.net>
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

/*=====================================================================
  Init Section
 =====================================================================*/

// Initialise Upgrade
require_once 'upgrade_init_global.inc.php';

// Security Check
if ( ! claro_is_platform_admin()) upgrade_disp_auth_form();

require_once $includePath . '/lib/module/manage.lib.php';

// Define display
DEFINE('DISPLAY_WELCOME_PANEL', 1);
DEFINE('DISPLAY_RESULT_PANEL',  2);

/*=====================================================================
  Main Section
 =====================================================================*/

/**
 * Create Upgrade Status table
 */

$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_upgrade_status = $tbl_mdb_names['upgrade_status'];

$sql = "CREATE TABLE IF NOT EXISTS `" . $tbl_upgrade_status . "` (
`id` INT NOT NULL auto_increment ,
`cid` VARCHAR( 40 ) NOT NULL ,
`claro_label` VARCHAR( 8 ) ,
`status` TINYINT NOT NULL ,
PRIMARY KEY ( `id` )
)";

claro_sql_query($sql);

$sql = "ALTER IGNORE TABLE `" . $tbl_upgrade_status . "` CHANGE `claro_label` `claro_label` VARCHAR(50) ";

claro_sql_query($sql);

/**
 * Initialise variables
 */

if ( isset($_REQUEST['verbose']) ) $verbose = true;

if ( isset($_REQUEST['cmd']) ) $cmd = $_REQUEST['cmd'];
else                           $cmd = false;

$display = DISPLAY_WELCOME_PANEL;

/**
 * Define display
 */

if ($cmd == 'run')
{
    // include sql to upgrade the main Database

    require_once('./upgrade_main_db_16.lib.php');
    require_once('./upgrade_main_db_17.lib.php');
    require_once('./upgrade_main_db_18.lib.php');
    require_once('./upgrade_main_db_19.lib.php');
    require_once('./upgrade_main_db_110.lib.php');

    $display = DISPLAY_RESULT_PANEL;

} // if ($cmd=="run")

/*=====================================================================
  Display Section
 =====================================================================*/

// Display Header
echo upgrade_disp_header();

switch ( $display )
{
    case DISPLAY_WELCOME_PANEL:

       // Display welcome message

        echo  '<h2>Step 2 of 4: main platform tables upgrade</h2>
              <p>Now, the <em>Claroline Upgrade Tool</em> is going to prepare the data stored
              into the <b>main Claroline tables</b> (users, course categories, tools list, ...)
              and set them to be compatible with the new Claroline version.</p>
              <p class="help">Note. Depending of the speed of your server or the amount of data
              stored on your platform, this operation may take some time.</p>
              <center>
              <p><button onclick="document.location=\'' . $_SERVER['PHP_SELF'] . '?cmd=run\';">Launch main platform tables upgrade</button></p>
              </center>';

        break;

    case DISPLAY_RESULT_PANEL :

        // Initialise
        $nbError = 0;

        // Display upgrade result

        echo '<h2>Step 2 of 4: main platform tables upgrade</h2>
              <h3>Upgrading main Claroline database (<em>' . $mainDbName . '</em>)</h3>' . "\n" ;

        if ( ! preg_match('/^1.8/',$currentDbVersion) )
        {
            // repair tables
            sql_repair_main_database();
        }

        /*---------------------------------------------------------------------
          Upgrade 1.5 to 1.6
         ---------------------------------------------------------------------*/

        if ( preg_match('/^1.5/',$currentDbVersion) )
        {
            $function_list = array('upgrade_main_database_to_16');

            foreach ( $function_list as $function )
            {
                $step = $function();
                if ( $step > 0 )
                {
                    echo 'Error : ' . $function . ' at step . ' . $step . '<br />';
                    $nbError++;
                }
            }

            if ( $nbError == 0 )
            {
                // Upgrade 1.5 to 1.6 Succeed
                echo '<p class="success">The claroline main tables have been successfully upgraded to 1.6</p>' . "\n";
                clean_upgrade_status();

                // Database version is 1.6
                $currentDbVersion = '1.6';

                // Update current version file
                save_current_version_file($currentClarolineVersion, $currentDbVersion) ;
            }
        } // end upgrade 1.5 to 1.6

        /*---------------------------------------------------------------------
        Upgrade 1.6 to 1.7
        ---------------------------------------------------------------------*/

        if ( preg_match('/^1.6/',$currentDbVersion) )
        {
            $function_list = array('upgrade_main_database_to_17');

            foreach ( $function_list as $function )
            {
                $step = $function();
                if ( $step > 0 )
                {
                    echo 'Error : ' . $function . ' at step . ' . $step . '<br />';
                    $nbError++;
                }
            }

            if ( $nbError == 0 )
            {
                // Upgrade 1.6 to 1.7 Succeed
                echo '<p class="success">The claroline main tables have been successfully upgraded to 1.7</p>' . "\n";
                clean_upgrade_status();

                // Database version is 1.7
                $currentDbVersion = '1.7';

                // Update current version file
                save_current_version_file($currentClarolineVersion, $currentDbVersion);
            }
        } // End of upgrade 1.6 to 1.7

        /*---------------------------------------------------------------------
        Upgrade 1.7 to 1.8
        ---------------------------------------------------------------------*/

        if ( preg_match('/^1.7/',$currentDbVersion) )
        {
            $function_list = array('upgrade_main_database_course_to_18',
                                   'upgrade_main_database_rel_course_user_to_18',
                                   'upgrade_main_database_course_category_to_18',
                                   'upgrade_main_database_user_to_18',
                                   'upgrade_main_database_course_class_to_18',
                                   'upgrade_main_database_right_to_18',
                                   'upgrade_main_database_module_to_18',
                                   'upgrade_main_database_user_property_to_18',
                                   'upgrade_main_database_tracking_to_18'
                                    );

            foreach ( $function_list as $function )
            {
                $step = $function();
                if ( $step > 0 )
                {
                    echo 'Error : ' . $function . ' at step . ' . $step . '<br />';
                    $nbError++;
                }
            }

            if ( $nbError == 0 )
            {
                // Upgrade 1.7 to 1.8 Succeed
                echo '<p class="success">The claroline main tables have been successfully upgraded to 1.8</p>' . "\n";
                clean_upgrade_status();

                // Database version is 1.8
                $currentDbVersion = '1.8';

                // Update current version file
                save_current_version_file($currentClarolineVersion, $currentDbVersion);
            }
        } // End of upgrade 1.7 to 1.8

        /*---------------------------------------------------------------------
        Upgrade 1.8 to 1.9
        ---------------------------------------------------------------------*/

        if ( preg_match('/^1.8/',$currentDbVersion) )
        {
            $function_list = array('upgrade_main_database_course_to_19',
                                   'upgrade_main_database_user_property_to_19',
                                   'upgrade_main_database_desktop_to_19',
                                   'upgrade_main_database_module_to_19',
                                   'upgrade_main_database_messaging_to_19',
                                   'upgrade_main_database_tracking_to_19',
                                   'upgrade_chat_to_19'
                                    );
                                    
            if( isset($_SESSION['upgrade_tracking_data']) && $_SESSION['upgrade_tracking_data'])
            {
                $function_list[] = 'upgrade_main_database_tracking_data_to_19';
            }
            
            foreach ( $function_list as $function )
            {
                $step = $function();
                if ( $step > 0 )
                {
                    echo 'Error : ' . $function . ' at step . ' . $step . '<br />';
                    $nbError++;
                }
            }

            if ( $nbError == 0 )
            {
                // Upgrade 1.8 to 1.9 Succeed
                echo '<p class="success">The claroline main tables have been successfully upgraded to version 1.9</p>' . "\n";
                clean_upgrade_status();

                // Database version is 1.9
                $currentDbVersion = '1.9';

                // Update current version file
                save_current_version_file($currentClarolineVersion, $currentDbVersion);
            }
        } // End of upgrade 1.8 to 1.9
        
        /*---------------------------------------------------------------------
        Upgrade 1.9 to 1.10
        ---------------------------------------------------------------------*/

        if ( preg_match('/^1.9/',$currentDbVersion) )
        {
            $function_list = array('upgrade_category_to_110',
                                   'upgrade_session_course_to_110',
                                   'upgrade_course_to_110',
                                   'upgrade_cours_user_to_110',
                                   'upgrade_coursehomepage_to_110',
                                   'upgrade_event_resource_to_110'
                                    );
            
            
            foreach ( $function_list as $function )
            {
                $step = $function();
                if ( $step > 0 )
                {
                    echo 'Error : ' . $function . ' at step . ' . $step . '<br />';
                    $nbError++;
                }
            }

            if ( $nbError == 0 )
            {
                // Upgrade 1.9 to 1.10 Succeed
                echo '<p class="success">The claroline main tables have been successfully upgraded to version 1.10</p>' . "\n";
                clean_upgrade_status();

                // Database version is 1.10
                $currentDbVersion = '1.10';

                // Update current version file
                save_current_version_file($currentClarolineVersion, $currentDbVersion);
            }
        } // End of upgrade 1.9 to 1.10
        
        /*if ( preg_match('/^1.10/',$currentDbVersion) )
        {
            // Database version is 1.11
            $currentDbVersion = $new_version;

            // Update current version file
            save_current_version_file( $currentClarolineVersion, $currentDbVersion );
        }*/
        
        

        if ( $nbError == 0 )
        {
            if ( preg_match('/^1.10/',$currentDbVersion) )
            {
                echo '<div align="right"><p><button onclick="document.location=\'upgrade_courses.php\';">Next ></button></p></div>';
            }
            else
            {
                echo '<p class="error">Db version unknown : ' . $currentDbVersion . '</p>';
            }

        }
        else
        {
            echo '<p class="error">' . sprintf(" %d errors found",$nbError) . '</p>' . "\n";
            echo '<p><button onclick="document.location=\'' . $_SERVER['PHP_SELF'].'?cmd=run&amp;verbose=true\';" >Retry with more details</button></p>';
        }

        break;

    default :
        die('Display unknow');
}

// Display footer
echo upgrade_disp_footer();
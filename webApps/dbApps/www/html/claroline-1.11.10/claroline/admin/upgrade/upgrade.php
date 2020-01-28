<?php // $Id: upgrade.php 14557 2013-10-09 11:20:25Z zefredz $

/**
 * CLAROLINE
 *
 * This script
 * - read current version
 * - check if update of main conf is needed
 *         whether do it (upgrade_conf.php)
 * - check if update of main db   is needed
 *         whether do it (upgrade_main_db.php)
 * - scan course to check if update of db is needed
 *   whether do loop (upgrade_courses.php)
 * - update course db
 * - update course repository content
 *
 * @version     $Revision: 14557 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 *
 * @package     UPGRADE
 *
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @author      Mathieu Laurent <laurent@cerdecam.be>
 */

/*=====================================================================
  Init Section
 =====================================================================*/

$cidReset                   = true;
$gidReset                   = true;
$currentClarolineVersion    = null;
$currentDbVersion           = null;

if ( ! file_exists('../../inc/currentVersion.inc.php') )
{
    // if this file doesn't exist, the current version is < claroline 1.6
    $platform_id = md5(realpath('../../inc/conf/def/CLMAIN.def.conf.inc.php'));
}

// Initialise
require 'upgrade_init_global.inc.php';

// Security Check
if (!claro_is_platform_admin()) upgrade_disp_auth_form();

// Pattern for this new stable version

$patternVarVersion = '/^1.10/';
$patternSqlVersion = '1.10%';

// Display definition

define('DISPVAL_upgrade_backup_needed'  ,__LINE__);
define('DISPVAL_upgrade_main_db_needed' ,__LINE__);
define('DISPVAL_upgrade_courses_needed' ,__LINE__);
define('DISPVAL_upgrade_done'           ,__LINE__);
define('DISPVAL_upgrade_not_needed'     ,__LINE__);

/*=====================================================================
  Main Section
 =====================================================================*/

$reset_confirm_backup = isset($_REQUEST['reset_confirm_backup'])
                          ? (bool) $_REQUEST['reset_confirm_backup']
                      : false;

$req_confirm_backup = isset($_REQUEST['confirm_backup'])
                      ? (bool) $_REQUEST['confirm_backup']
                      : false;

$is_backup_confirmed = isset($_SESSION['confirm_backup'])
                      ? (bool) $_SESSION['confirm_backup']
                      : false;

$req_upgrade_tracking_data = isset($_REQUEST['upgrade_tracking_data'])
                      ? (bool) $_REQUEST['upgrade_tracking_data']
                      : false;
                      
if( $req_upgrade_tracking_data )
{
    $_SESSION['upgrade_tracking_data'] = $req_upgrade_tracking_data;
}

if ( $reset_confirm_backup || !$is_backup_confirmed )
{
    // reset confirm backup
    unset($_SESSION['confirm_backup']);
    $confirm_backup = 0;
}

if ( !isset($_SESSION['confirm_backup']) )
{
    if ( $req_confirm_backup )
    {
        // confirm backup TRUE
        $_SESSION['confirm_backup'] = 1;
        $confirm_backup = 1;
    }
    else
    {
        $confirm_backup = 0;
    }
}
else
{
    // get value from session
    $confirm_backup  = $_SESSION['confirm_backup'];
}

/*---------------------------------------------------------------------
  Define Display
 ---------------------------------------------------------------------*/

// specific to 1.10 to 1.11 upgrade:
if ( preg_match ( '/^1.10/', $currentClarolineVersion ) && preg_match('/^1.11/', $new_version ) )
{   
    $display = DISPVAL_upgrade_not_needed;
}
// detect minor version upgrade attempt:
elseif ( compare_major_version ( $currentClarolineVersion, $new_version ) === 0 )
{
    $display = DISPVAL_upgrade_not_needed;
}
// upgrade needed:
else
{
    if ( !$confirm_backup )
    {
        // ask to confirm backup
        $display = DISPVAL_upgrade_backup_needed;
    }
    elseif ( !preg_match($patternVarVersion, $currentClarolineVersion) )
    {
        // config file not upgraded go to first step
        header("Location: upgrade_conf.php");
    }
    elseif ( !preg_match($patternVarVersion, $currentDbVersion) )
    {
        // upgrade of main conf needed.
        $display = DISPVAL_upgrade_main_db_needed;
    }
    else
    {
        // count course to upgrade
        $count_course_upgraded = count_course_upgraded($new_version_branch);
        $count_course_to_upgrade =  $count_course_upgraded['total'] - $count_course_upgraded['upgraded'];

        if ( $count_course_to_upgrade > 0 )
        {
            // upgrade of main conf needed.
            $display = DISPVAL_upgrade_courses_needed;
        }
        else
        {
            $display = DISPVAL_upgrade_done;
        }
    }
}

/*=====================================================================
  Display Section
 =====================================================================*/

// Display Header
echo upgrade_disp_header();

// Display Content

switch ($display)
{
    case DISPVAL_upgrade_not_needed:
        echo '<h2>Claroline Upgrade Tool<br />from ' . $currentClarolineVersion . ' to ' . $new_version . '</h2>
              <p class="success">There is no upgrade needed between those versions</p>
              <ul>
              <li><a href="../../../index.php?logout=true">Access to campus</a></li>
              </ul>';
        break;
    case DISPVAL_upgrade_backup_needed :

        echo  '<h2>Claroline Upgrade Tool<br />from ' . $currentClarolineVersion . ' to ' . $new_version . '</h2>
              <form action="' . $_SERVER['PHP_SELF'] . '" method="get">
              <p>The <em>Claroline Upgrade Tool</em> will retrieve the data of your previous Claroline
              installation and set them to be compatible with the new Claroline version. This upgrade
              proceeds in three steps:
              </p>
              <ol>
              <li>It will get your previous platform main settings and put them in a new configuration files</li>
              <li>It will set the main Claroline tables (user, course categories, course list, ...) to be compatible
              with the new data structure.</li>
              <li>It will update one by one each course data (directories, database tables, ...)</li>
              </ol>
              <p>Before starting the <em>Claroline Upgrade Tool</em>, we recommend you to make yourself a complete
              backup of the platform data (files and databases).</p>
              <table>
              <tbody>
              <tr valign="top">
              <td>The data backup has been done</td>
              <td>
              <input type="radio" id="confirm_backup_yes" name="confirm_backup" value="1" />
              <label for="confirm_backup_yes">Yes</label><br />
              <input type="radio" id="confirm_backup_no" name="confirm_backup" value="" checked="checked" />
              <label for="confirm_backup_no">No</label><br />
              <p>The <em>Claroline Upgrade Tool</em> is not able to start if you do not confirm that the data has been done.</p>
              </td>
              </tr>
              <tr valign="top">
              <td>Upgrade tracking data</td>
              <td>
              <input type="radio" id="upgrade_tracking_data_yes" name="upgrade_tracking_data" value="1" '.((isset($_SESSION['upgrade_tracking_data']) && $_SESSION['upgrade_tracking_data'])? ' checked="checked"' : '' ).'/>
              <label for="upgrade_tracking_data_yes">Yes, keep previous tracking information</label><br />
              <input type="radio" id="upgrade_tracking_data_no" name="upgrade_tracking_data" value="" '.((!isset($_SESSION['upgrade_tracking_data']) || !$_SESSION['upgrade_tracking_data'])? ' checked="checked"' : '' ).' />
              <label for="upgrade_tracking_data_no">No, forget all previously stored tracking data</label><br />
              <p>This may require a lot of time dependiing on amount of tracking data collected on your campus</p>
              </td>
              </tr>
              </tbody>
              </table>
              
              <div align="right"><input type="submit" value="Next > " /></div>
              </form>' . "\n" ;

        break;

    case DISPVAL_upgrade_main_db_needed :


        echo  '<h2>Claroline Upgrade Tool<br />from ' . $currentClarolineVersion . ' to ' . $new_version . '</h2>
           <h3>Done: </h3>
           <ul>
           <li>Backup confirm (<a href="' . $_SERVER['PHP_SELF'] . '?reset_confirm_backup=1">Cancel</a>)</li>
           <li>Step 1 of 4: platform main settings (<a href="upgrade_conf.php">Start again</a>)</li>
           </ul>
           <h3>To do:</h3>
           <ul>
           <li><a href="upgrade_main_db.php">Step 2 of 4: main platform tables upgrade</a></li>
           <li>Step 3 of 4: courses upgrade</li>
           <li>Step 4 of 4: disable incompatible modules</li>
           </ul>';

        break;

    case DISPVAL_upgrade_courses_needed :

        echo  '<h2>Claroline Upgrade Tool<br />from ' . $currentClarolineVersion . ' to ' . $new_version . '</h2>
            <h3>Done :</h3>
            <ul>
            <li>Backup confirm (<a href="' . $_SERVER['PHP_SELF'] . '?reset_confirm_backup=1">Cancel</a>)</li>
            <li>Step 1 of 4: platform main settings (<a href="upgrade_conf.php">Start again</a>)</li>
            <li>Step 2 of 4: main platform tables upgrade (<a href="upgrade_main_db.php">%s</a>)</li>
            </ul>
            <h3>To do:</h3>
            <ul>
            <li><a href="upgrade_courses.php">Step 3 of 4: courses upgrade</a> - ' . $count_course_to_upgrade . ' course(s) to upgrade.</li>
            <li>Step 4 of 4: disable incompatible modules</li>
            </ul>';

        break;

    case DISPVAL_upgrade_done :

        echo  '<h2>Claroline Upgrade Tool<br />from ' . $currentClarolineVersion . ' to ' . $new_version . '</h2>

            <p class="success">The <em>Claroline Upgrade Tool</em> has completly upgraded your platform.</p>
            <ul>
            <li><a href="../../../index.php?logout=true">Access to campus</a></li>
            </ul>' ;
}

// Display footer
echo upgrade_disp_footer();
<?php // $Id: upgrade_conf.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * Initialize conf settings.
 * Try to read  current values in current conf files.
 * Build new conf file content with these settings.
 * write it.
 *
 * @version     $Revision: 14314 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.7
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

if ( ! file_exists('../../inc/currentVersion.inc.php') )
{
    // if this file doesn't exist, the current version is < claroline 1.6
    // in 1.6 we need a $platform_id for session handling
    $platform_id =  md5(realpath('../../inc/conf/def/CLMAIN.def.conf.inc.php'));
}

// Initialise Upgrade
require 'upgrade_init_global.inc.php';

// Security Check
if (!claro_is_platform_admin()) upgrade_disp_auth_form();

// Define display
DEFINE ('DISPLAY_WELCOME_PANEL', __LINE__);
DEFINE ('DISPLAY_RESULT_ERROR_PANEL', __LINE__);
DEFINE ('DISPLAY_RESULT_SUCCESS_PANEL', __LINE__);
DEFINE ('ERROR_WRITE_FAILED', __LINE__);
$display = DISPLAY_WELCOME_PANEL;

/*=====================================================================
  Main Section
 =====================================================================*/

$error = FALSE;

if ( isset($_REQUEST['verbose']) ) $verbose = true;

$cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '';

if ( $cmd == 'run' )
{
    // Create module, platform, tmp folders
    if ( !file_exists(get_path('rootSys') . 'module/') )        claro_mkdir(get_path('rootSys') . 'module/', CLARO_FILE_PERMISSIONS, true);
    if ( !file_exists(get_path('rootSys') . 'platform/') )      claro_mkdir(get_path('rootSys') . 'platform/', CLARO_FILE_PERMISSIONS, true);
    if ( !file_exists(get_path('rootSys') . 'platform/conf/') ) claro_mkdir(get_path('rootSys') . 'platform/conf/', CLARO_FILE_PERMISSIONS, true);
    if ( !file_exists(get_path('rootSys') . 'tmp/') )           claro_mkdir(get_path('rootSys') . 'tmp/', CLARO_FILE_PERMISSIONS, true);

    // Create folder to backup configuration files
    $backupRepositorySys = get_path('rootSys') .'platform/bak.'.date('Y-z-B').'/';
    claro_mkdir($backupRepositorySys, CLARO_FILE_PERMISSIONS, true);

    $output = '<h3>Configuration file</h3>' . "\n" ;

    $output.= '<ol>' . "\n" ;

    /*
     * Generate configuration file from definition file
     */

    $config_code_list = get_config_code_list();
    $config_code_list = array_merge($config_code_list,array('CLANN','CLCAL','CLFRM','CLCHT','CLDOC','CLDSC','CLUSR','CLLNP','CLQWZ','CLWRK','CLWIKI'));

    if ( is_array($config_code_list) )
    {
        // Build table with current values in configuration files
        $current_property_list = array();

        foreach ( $config_code_list as $config_code )
        {
            // new config object
            $config = new ConfigUpgrade($config_code);
            $config->load();
            $this_property_list = $config->get_property_list();
            $current_property_list = array_merge($current_property_list, $this_property_list);
            unset($config);
        }

        // Set platform_id if not set in current claroline version (new in 1.6)
        if ( ! isset($current_property_list['platform_id']) )
        {
            $current_property_list['platform_id'] = $platform_id;
        }

        // Old variables from 1.5
        if ( isset($current_property_list['administrator']) )
        {
            $current_property_list['administrator_name'] = $administrator['name'];
            $current_property_list['administrator_phone'] = $administrator['phone'];
            $current_property_list['administrator_email'] = $administrator['email'];
        }

        // Old variables from 1.5
        if ( isset($current_property_list['institution']) )
        {
            $current_property_list['institution_name'] = $current_property_list['institution']['name'];
            $current_property_list['institution_url'] = $current_property_list['institution']['url'];
        }


        // UPDATE for  1.9
        // split defaultVisibilityForANewCourse in 2 new var
        // 'acceptedValue' => array ('0'=>'Private&nbsp;+ New registration denied'
        //                          ,'1'=>'Private&nbsp+ New Registration allowed'
        //                          ,'2'=>'Public&nbsp;&nbsp;+ New Registration allowed'
        //                          ,'3'=>'Public&nbsp;&nbsp;+ New Registration denied'
        if ( isset($current_property_list['defaultVisibilityForANewCourse']) )
        {
            $current_property_list['defaultAccessOnCourseCreation']    = (bool) ( $current_property_list['defaultVisibilityForANewCourse'] == 2 or $current_property_list['defaultVisibilityForANewCourse'] == 3 );
            $current_property_list['defaultRegistrationOnCourseCreation'] = (bool) ( $current_property_list['defaultVisibilityForANewCourse'] == 1 or $current_property_list['defaultVisibilityForANewCourse'] == 2 );
        }
        
        // UPDATE for 1.9
        // css should point to a theme not to a stylesheet
        $current_property_list['claro_stylesheet'] = 'classic';
        
        // Browse definition file and build them

        reset( $config_code_list );

        foreach ( $config_code_list as $config_code )
        {
            $config = new ConfigUpgrade($config_code);

            // load and initialise the config
            if ( $config->load() )
            {
                $config_filename = $config->get_config_filename();

                $output .= '<li>'. claro_htmlspecialchars(basename($config_filename))
                        .  '<ul >' . "\n";

                // Backup current file
                $output .= '<li>Validate property : ' ;

                if ( $config->validate($current_property_list) )
                {
                    $output .= '<span class="success">Succeeded</span></li>';

                    if ( !file_exists($config_filename) )
                    {
                        // Create a file empty if not exists
                        touch($config_filename);
                    }
                    else
                    {
                        // Backup current file
                        $output .= '<li>Backup old file : ' ;

                        $fileBackup = $backupRepositorySys . basename($config_filename);

                        if ( !@copy($config_filename, $fileBackup) )
                        {
                            $output .= '<span class="warning">Failed</span>';
                        }
                        else
                        {
                            $output .= '<span class="success">Succeeded</span>';
                        }
                        $output .= '</li>' . "\n" ;

                        // Change permission of the backup file
                        @chmod( $fileBackup, CLARO_FILE_PERMISSIONS );
                        @chmod( $fileBackup, CLARO_FILE_PERMISSIONS );
                    }

                    $output .= '<li>Upgrade file : ';

                    if ( $config->save() )
                    {
                        $output .= '<span class="success">Succeeded</span>';
                    }
                    else
                    {
                        $output .= '<span class="warning">Failed : ' . $config->backlog->output() . '</span>';
                        $error = true ;
                    }

                    $output .= '</li>'."\n";
                }
                else
                {
                    $output .= '<span class="warning">Failed : ' . $config->backlog->output() . '</span></li>' . "\n";
                    $error = true ;
                }

                $output .= '</ul>' . "\n"
                     . '</li>' . "\n";

            } // end if config->load()

        } // end browse definition file and build them

    } // end if is_array def file list

    /**
     * Config file to undist
     */

    $arr_file_to_undist = array ( get_path('incRepositorySys').'/conf/drivers.auth.conf.php' => get_path('rootSys').'platform/conf' );

    foreach ( $arr_file_to_undist as $undistFile => $undistPath )
    {
        $output .= '<li>'. basename ($undistFile) . "\n"
                . '<ul><li>Undist : ' . "\n" ;

        if ( claro_undist_file($undistFile, $undistPath) )
        {
            $output .= '<span class="success">Succeeded</span>';
        }
        else
        {
            $output .= '<span class="warning">Failed</span>';
            $error = TRUE;
        }
        $output .= '</li>' . "\n" . '</ul>' . "\n"
                 . '</li>' . "\n";
    }

    $output .= '</ol>' . "\n";

    if ( !$error )
    {
        $display = DISPLAY_RESULT_SUCCESS_PANEL;

        // Update current version file
        save_current_version_file($new_version,$currentDbVersion);
    }
    else
    {
        $display = DISPLAY_RESULT_ERROR_PANEL;
    }

} // end if run

/*=====================================================================
  Display Section
 =====================================================================*/

// Display Header
echo upgrade_disp_header();

// Display Content

switch ($display)
{
    case DISPLAY_WELCOME_PANEL :
        echo '<h2>Step 1 of 3: platform main settings</h2>
              <p>The <em>Claroline Upgrade Tool</em> is going to proceed to the main setting upgrade.
              These settings were stored into claroline/inc/conf/claro_main.conf.php in your previous platform version.
              </p>
              <center><p><button onclick="document.location=\'' . $_SERVER['PHP_SELF'] . '?cmd=run\';">Launch platform
              main settings upgrade</button></p></center>';
        break;

    case DISPLAY_RESULT_ERROR_PANEL :
        echo '<h2>Step 1 of 3: platform main settings - <span class="error">Failed</span></h2>';
        echo $output;
        echo '<center><p><button onclick="document.location=\'' . $_SERVER['PHP_SELF'] . '?cmd=run\';">Relaunch platform
              main settings upgrade</button></p></center>';
        break;

    case DISPLAY_RESULT_SUCCESS_PANEL :
        echo '<h2>Step 1 of 3: platform main settings - <span class="success">Succeeded</span><h2>';
        echo $output;
        echo '<div align="right"><p><button onclick="document.location=\'upgrade_main_db.php\';">Next ></button></p></div>';
        break;
}

// Display footer
echo upgrade_disp_footer();
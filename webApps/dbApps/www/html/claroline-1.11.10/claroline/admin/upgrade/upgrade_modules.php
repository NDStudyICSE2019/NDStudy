<?php // $Id: upgrade_modules.php 13348 2011-07-18 13:58:28Z abourguignon $

/**
 * CLAROLINE
 *
 * Upgrade modules database.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 * @package     UPGRADE
 * @author      Claro Team <cvs@claroline.net>
 */

$new_version_branch = '';
$patternVarVersion = '/^1.10/';
// Initialise Upgrade
require 'upgrade_init_global.inc.php';

// Define display
DEFINE ('DISPLAY_WELCOME_PANEL', __LINE__);
DEFINE ('DISPLAY_RESULT_ERROR_PANEL', __LINE__);
DEFINE ('DISPLAY_RESULT_SUCCESS_PANEL', __LINE__);
$display = DISPLAY_WELCOME_PANEL;

require_once $includePath . '/lib/module/manage.lib.php';

// Security Check
if (!claro_is_platform_admin()) upgrade_disp_auth_form();

if ( isset($_REQUEST['cmd'] ) && $_REQUEST['cmd'] == 'run' )
{
    // DB tables definition
    $tbl_mdb_names       = claro_sql_get_main_tbl();
    $tbl_module          = $tbl_mdb_names['module'];
    $tbl_module_info     = $tbl_mdb_names['module_info'];
    $tbl_module_contexts = $tbl_mdb_names['module_contexts'];
    
    $modules = claro_sql_query_fetch_all( "SELECT label, id, name FROM `{$tbl_module}`" );
    
    $deactivatedModules = array();
    $readOnlyModules = array( 'CLDOC', 'CLGRP', 'CLUSR' );
    $version = '';
    
    foreach ( $modules as $module )
    {
        $manifest = readModuleManifest( get_module_path($module['label']) );
        
        if ( $manifest )
        {
            $version = array_key_exists( 'CLAROLINE_MAX_VERSION' , $manifest )
                     ? $manifest['CLAROLINE_MAX_VERSION']
                     : $manifest['CLAROLINE_MIN_VERSION'];
            
            if ( ! in_array( $module['label'], $readOnlyModules ) && ! preg_match( $patternVarVersion, $version ) )
            {
                deactivate_module($module['id']);
                $deactivatedModules[] = $module;
            }
        }
    }
    
    $display = DISPLAY_RESULT_SUCCESS_PANEL;

}

// Display Header
echo upgrade_disp_header();

// Display Content

switch ($display)
{
    case DISPLAY_WELCOME_PANEL :
        echo '<h2>Step 4 of 4: disable incompatible modules</h2>
              <p>The <em>Claroline Upgrade Tool</em> is going to deactivate modules not compatible with the new Claroline version.
              You can reactivate those modules in the platform administration.
              </p>
              <center><p><button onclick="document.location=\'' . $_SERVER['PHP_SELF'] . '?cmd=run\';">Launch</button></p></center>';
        break;

    case DISPLAY_RESULT_ERROR_PANEL :
        echo '<h2>Step 4 of 4: disable incompatible modules - <span class="error">Failed</span></h2>';
        // echo $output;
        echo '<center><p><button onclick="document.location=\'' . $_SERVER['PHP_SELF'] . '?cmd=run\';">Relaunch</button></p></center>';
        break;

    case DISPLAY_RESULT_SUCCESS_PANEL :
        
        if ( !empty( $deactivatedModules ) )
        {
            $output = '<h3>Desactivated modules</h3>';
            $output .= '<ul>';
            
            foreach ( $deactivatedModules as $module )
            {
                $output .= '<li>' . $module['name'] . '</li>';
            }
            
            $output .= '</ul>';
        }
        else
        {
            $output = 'None';
        }
        
        echo '<h2>Step 4 of 4: disable incompatible modules - <span class="success">Succeeded</span><h2>';
        echo $output;
        echo '<p class="success">The Claroline upgrade process completed</p>' . "\n";
        echo '<div align="right"><p><button onclick="document.location=\'upgrade.php\';">Next ></button></p></div>';
        break;
}

// Display footer
echo upgrade_disp_footer();
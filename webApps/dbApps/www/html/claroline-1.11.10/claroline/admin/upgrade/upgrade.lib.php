<?php // $Id: upgrade.lib.php 14557 2013-10-09 11:20:25Z zefredz $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 14557 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     UPGRADE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Mathieu Laurent <mathieu@claroline.net>
 */

/**
 * Display header of the upgrade tool
 *
 * @param string claroline version
 * @param string database version
 *
 * @since  1.7
 */

function compare_major_version( $versionNumber1, $versionNumber2 )
{
    $vn1Array = explode('.',$versionNumber1);
    $vn2Array = explode('.',$versionNumber2);
    
    if ( count($vn1Array) >= 2 && count($vn2Array) >=2 )
    {
        if ( $vn1Array[0] != $vn2Array[0] )
        {
            return (int) $vn1Array[0] - (int) $vn2Array[0];
        }
        else
        {
            return (int) $vn1Array[1] - (int) $vn2Array[1];
        }
    }
    else
    {
        throw new Exception("Version string length mismatch {$versionNumber1} {$versionNumber2}");
    }
}

function upgrade_disp_header()
{
    global $htmlHeadXtra;
    global $new_version;

    $output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/HTML; charset=iso-8859-1"  />
  <title>-- Claroline upgrade -- version ' . $new_version . '</title>
  <link rel="stylesheet" type="text/css" href="upgrade.css" media="screen" />
  <style media="print" >
    .notethis {    border: thin double Black;    margin-left: 15px;    margin-right: 15px;}
  </style>';

if ( !empty($htmlHeadXtra) && is_array($htmlHeadXtra) )
{
    foreach($htmlHeadXtra as $thisHtmlHead)
    {
        $output .= $thisHtmlHead ;
    }
}

$output .='</head>
<body bgcolor="white" dir="' .get_locale('text_dir') . '">

<center>

<table cellpadding="10" cellspacing="0" border="0" width="650" bgcolor="#E6E6E6">
<tbody>
<tr bgcolor="navy">
<td valign="top" align="left">

<div id="header"><h1>Claroline (' . $new_version . ') - Upgrade</h1>
</div>
</td>
</tr>
<tr valign="top" align="left">
<td>
<div id="content">
';

    return $output;

}

/**
 * Display footer of the upgrade tool
 *
 * @param string claroline version
 * @param string database version
 *
 * @since  1.7
 */

function upgrade_disp_footer()
{

    $output = '</div>

</td>
</tr>
</tbody>
</table>

</body>
</html>';

    return $output;
}

/**
 * Save the file currentVersion.inc.php
 *
 * @param string claroline version
 * @param string database version
 *
 * @since  1.7
 */

function save_current_version_file ( $clarolineVersion, $databaseVersion )
{
    // open file in write mode
    $fp_currentVersion = fopen( get_path('rootSys') . 'platform/currentVersion.inc.php','w');

    // build content
    $currentVersionStr = '<?php
$clarolineVersion = "' . $clarolineVersion . '";
$versionDb = "' . $databaseVersion . '";
?>';

    // write content in file
    fwrite($fp_currentVersion, $currentVersionStr);
    // close file
    fclose($fp_currentVersion);

}

/**
 * Get current version of claroline and database
 *
 * @return array with current version of claroline and database
 * @since  1.7
 */

function get_current_version ()
{
    global $clarolineVersion, $versionDb;
    global $rootSys;

    if ( file_exists(get_path('rootSys').'platform/currentVersion.inc.php') )
    {
        // get claroline version in get_path('rootSys') folder
        include(get_path('rootSys').'platform/currentVersion.inc.php');
    }
    elseif ( file_exists(get_path('incRepositorySys').'/currentVersion.inc.php') )
    {
        // get claroline version in currentVersion file (new in 1.6)
        // before the clarolineVersion was in claro_main.conf.php
        include (get_path('incRepositorySys').'/currentVersion.inc.php');
    }

    $current_version['claroline'] = $clarolineVersion;
    $current_version['db'] = $versionDb;

    return $current_version;
}

/**
 * Get new version of claroline and database
 *
 * @return array with new version of claroline and database
 * @since  1.7
 */

function get_new_version ()
{

    $new_version = null;
    $new_version_branch  = null;

    include ( get_path('incRepositorySys') . '/installedVersion.inc.php' ) ;

    $version = array( 'complete' => $new_version,
                      'branch' => $new_version_branch );

    return $version;
}

/**
 * Apply sql queries to upgrade
 *
 * @param array sql queries
 * @param boolean verbose mode
 *
 * @return integer number of errors
 *
 * @since  1.7
 */

function upgrade_apply_sql ( $array_query )
{
    global $verbose;

    $nb_error = 0;
    
    if (!empty($array_query))
    {
        foreach ( $array_query as $sql )
        {
            if ( !upgrade_sql_query($sql, $verbose) ) $nb_error++;
        }
    }

    if ( $nb_error == 0 ) return true;
    else                  return false;

}

function upgrade_sql_query($sql,$verbose=null)
{
    global $accepted_error_list;
    global $verbose;

    // Sql query
    $handler = mysql_query($sql);

    // Sql error
    if ( mysql_errno() > 0 )
    {
        if ( in_array(mysql_errno(),$accepted_error_list) )
        {
            // error accepted
            if ( $verbose )
            {
                $message = sprintf('Warning (error sql): %s -message- %s', mysql_errno(), mysql_error()) . "\n" ;
                $message .= 'statment : ' . $sql . '' . "\n";
                $message .= mysql_info() . "\n";
                log_message($message);
            }
            return true;
        }
        else
        {
            // error not accepted
            $message = sprintf('Error sql: %s -message- %s', mysql_errno(), mysql_error()) . "\n";
            $message .= 'statment : ' . $sql . '' . "\n";
            $message .= mysql_info() . "\n";
            log_message($message);
            return false;
        }
    }
    else
    {
            // No error
            if ( $verbose )
            {
                // succeeded
                $message  = 'Successfull sql: ' . $sql . "\n";
                log_message($message);
            }
            return true;
    }
}

/**
 * Count courses, courses upgraded and upgrade failed
 *
 * @param string new database version
 * @param string new file version
 *
 * @return array
 */

function count_course_upgraded($version)
{
    $tbl_mdb_names = claro_sql_get_main_tbl();

    $tbl_course = $tbl_mdb_names['course'];

    /**
     * In cours table, versionClaro contain :
     * - 'error' if upgrade already tried but failed
     * - version of last upgrade succeed (so previous or current)
     */

    $count_course = array( 'upgraded'=>0 ,
                           'error'=>0 ,
                           'total'=>0 );

    $sql = "SELECT versionClaro, count(*) as count_course
            FROM `" . $tbl_course . "`
            GROUP BY versionClaro";

    $result = claro_sql_query($sql);

    while ( ( $row = mysql_fetch_array($result) ) )
    {
        // Count courses upgraded and upgrade failed
        if ( preg_match('/^' . $version . '/',$row['versionClaro']) )
        {
            // upgrade succeed
            $count_course['upgraded'] += $row['count_course'];
        }
        elseif ( preg_match('/^error/',$row['versionClaro']) )
        {
            // upgrade failed
            $count_course['error'] += $row['count_course'];
        }

        // Count courses
        $count_course['total'] += $row['count_course'];
    }

    return $count_course;
}

/**
 * Add a new tool in course_tool table
 *
 * @param string claro_label
 * @param string script_url
 * @param string icon
 * @param string default_access
 * @param string add_in_course
 * @param string access_manager
 *
 * @return boolean
 */

function register_tool_in_main_database ( $claro_label, $script_url, $icon, $default_access = 'ALL',
                                          $add_in_course = 'AUTOMATIC', $access_manager = 'COURSE_ADMIN' )
{
    $tbl_mdb_names = claro_sql_get_main_tbl();

    $tbl_tool = $tbl_mdb_names['tool'];

    $sql = "SELECT `id`
            FROM `" . $tbl_tool . "`
            WHERE `claro_label` = '" . claro_sql_escape($claro_label) . "'";

    $result = upgrade_sql_query($sql);

    if ( mysql_num_rows($result) == 0 )
    {
        // tool not registered

        // find max default rank
        $sql = "SELECT MAX(def_rank)
                FROM `" . $tbl_tool . "`";

        $default_rank = claro_sql_query_get_single_value($sql);

        $default_rank++ ;

        // add tool in course_tool table
        $sql = "INSERT INTO `" . $tbl_tool . "`
               (`claro_label`,`script_url`,`icon`,`def_access`,`def_rank`,`add_in_course`,`access_manager`)
               VALUES
               ('" . claro_sql_escape($claro_label) . "','" . claro_sql_escape($script_url) . "','" . claro_sql_escape($icon) . "',
                '" . claro_sql_escape($default_access) .  "','" . claro_sql_escape($default_rank) . "',
                '" . claro_sql_escape($add_in_course) . "','" . claro_sql_escape($access_manager) . "')";

        return claro_sql_query_insert_id($sql);

    }
    else
    {
        return FALSE;
    }

}

/**
 * Add a new tool in tool_list table of a course
 *
 * @param string claro_label
 * @param string access level to tools if null get the default value from main table
 * @param string course db name glued
 *
 * @return boolean
 */

function add_tool_in_course_tool_list ( $claro_label, $access = null , $courseDbNameGlu = null )
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_cdb_names = claro_sql_get_course_tbl($courseDbNameGlu);

    $tbl_course_tool = $tbl_mdb_names['tool'];
    $tbl_tool_list = $tbl_cdb_names['tool'];

    // get rank of tool in course table
    $sql = "SELECT MAX(`rank`)  as `max_rank`
            FROM `" . $tbl_tool_list . "`";

    $rank =  claro_sql_query_get_single_value($sql);
    $rank++;

    // get id of tool on the platform and default access
    $sql = "SELECT `id`, `def_access`
            FROM `" . $tbl_course_tool . "`
            WHERE `claro_label` = '" . claro_sql_escape($claro_label) . "'";

    $result = upgrade_sql_query($sql);

    if ( mysql_num_rows($result) )
    {
        $row = mysql_fetch_array($result);

        // if $access emtpy get default access
        if ( empty($access) ) $access = $row['access'];

        // add tool in course_tool table
        $sql = "INSERT INTO `" . $tbl_tool_list . "`
               (`tool_id`,`rank`,`access`)
               VALUES
               ('" . $row['id'] . "','" . $rank . "','" . $access . "')";

        $result = upgrade_sql_query($sql);
        return mysql_insert_id();
    }
    else
    {
        return FALSE;
    }

}

/**
 * Save the file currentVersion.inc.php
 *
 * @param string course code
 * @param string claroline version
 * @param string database version
 *
 * @since  1.7
 */

function save_course_current_version ( $course_code, $fileVersion )
{
    $tbl_mdb_names = claro_sql_get_main_tbl();

    // query to update version of course

    $sql = " UPDATE `" . $tbl_mdb_names['course'] . "`
             SET versionClaro = '" . claro_sql_escape($fileVersion) . "'
             WHERE code = '". $course_code ."'";

    return claro_sql_query($sql);

}

/**
 * Execute repair query on main table
 *
 * @since  1.7
 */

function sql_repair_main_database()
{
    $tbl_names = claro_sql_get_main_tbl();

    foreach ( $tbl_names as $tbl )
    {
        $sql = "REPAIR TABLE `" . $tbl . "`";
        mysql_query($sql);
    }
}

/**
 * Execute repair query on course table
 *
 * @since  1.7
 */

function sql_repair_course_database($courseDbNameGlu)
{
    $tbl_names = claro_sql_get_course_tbl($courseDbNameGlu);

    foreach ( $tbl_names as $tbl )
    {
        $sql = "REPAIR TABLE `" . $tbl . "`";
        mysql_query($sql);
    }
}

/**
 * Get upgrade status of a tool
 *
 * @param string claro_label
 * @param string course_code optionnal
 *
 * @return integer status value
 *
 * @since  1.7
 */

function get_upgrade_status($claro_label,$course_code=null)
{
    // get table name
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_upgrade_status = $tbl_mdb_names['upgrade_status'];

    // course_code empty
    if ( is_null($course_code) ) $course_code = '';

    // query to find status
    $sql = "SELECT `status`
            FROM `" . $tbl_upgrade_status . "`
            WHERE cid = '" . $course_code . "'
              AND claro_label = '" . $claro_label . "' ";

    $result = claro_sql_query($sql);

    if ( mysql_num_rows($result) > 0 )
    {
        // get status
        $row = mysql_fetch_array($result);
        $status = $row['status'];
    }
    else
    {
        // initialise status to 1
        $status = 1;
        // insert status
        $sql = "INSERT INTO `" . $tbl_upgrade_status . "`
                (`cid`,`claro_label`,`status`)
                VALUES
                ('" . $course_code .  "','" . $claro_label . "','" . $status ."')";
        claro_sql_query($sql);
    }

    return $status;
}

/**
 * Set status of a tool
 *
 * @param string claro_label
 * @param int status value
 * @param string course_code optionnal
 *
 * @return integer status value
 *
 * @since  1.7
 */

function set_upgrade_status($claro_label,$status,$course_code=null)
{
    // get table name
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_upgrade_status = $tbl_mdb_names['upgrade_status'];

    // course_code empty
    if ( is_null($course_code) ) $course_code = '';

    // update status
    $sql = " UPDATE `" . $tbl_upgrade_status . "`
             SET `status` = '" . $status . "'
             WHERE cid = '" . $course_code . "'
               AND claro_label = '" . $claro_label . "' ";

    claro_sql_query($sql);

    return $status;

}

/**
 * Clean status of a tool
 *
 * @param string course_code
 *
 * @return integer status value
 *
 * @since  1.7
 */

function clean_upgrade_status($course_code=null)
{
    // get table name
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_upgrade_status = $tbl_mdb_names['upgrade_status'];

    // course_code empty
    if ( is_null($course_code) ) $course_code = '';

    // delete all status for this course
    $sql = " DELETE FROM `" . $tbl_upgrade_status . "`
             WHERE cid = '" . $course_code . "' ";

    return claro_sql_query($sql);
}

/**
 * Write in log file
 *
 * @param string claroline version
 * @param string database version
 *
 * @since  1.7
 */

function log_message($message)
{
    global $fp_upgrade_log;

    if ( is_null($fp_upgrade_log) )
    {
        if ( ! open_upgrade_log() ) return false;
    }

    // write content in file
    $log_message = $message . "\n"
                 . '--------------------------------------------------------' . "\n";

    if ( fwrite($fp_upgrade_log, $log_message) )
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Open log file
 *
 * @param string claroline version
 * @param string database version
 *
 * @since  1.7
 */

function open_upgrade_log()
{
    global $new_version, $currentClarolineVersion, $currentDbVersion;
    global $fp_upgrade_log;
    
    if ( ! file_exists( '../../../platform' ) )
    {
        claro_mkdir( '../../../platform', CLARO_FILE_PERMISSIONS, true );
    }
    
    $upgradeLogPath = '../../../platform/upgrade_log.txt';

    // open file in write mode
    if ( $fp_upgrade_log = fopen($upgradeLogPath,'a+') )
    {
        $upgradeHeader = '========================================================' . "\n"
                       . ' * Upgrade to ' . $new_version . "\n"
                       . ' * Current File Version : ' . $currentClarolineVersion . "\n"
                       . ' * Current Database Version : ' . $currentDbVersion . "\n"
                       . ' * Date :'. claro_html_localised_date(get_locale('dateTimeFormatLong')) . "\n"
                       . '========================================================'. "\n" ;

        // write content in file
        fwrite($fp_upgrade_log, $upgradeHeader);
        return true;
    }
    else
    {
        return false;
    }
}

function upgrade_disp_auth_form()
{
    // Display Header
    echo upgrade_disp_header();

    // Display login form
    echo '<table align="center">'."\n"
        .'<tr>'
        .'<td>'
        .'<form action="'.$_SERVER['PHP_SELF'].'" method="post">'."\n"

        .'<fieldset>'."\n"

        .'<legend>Login</legend>'."\n"

        .'<label for="username">Username : </label><br />'."\n"
        .'<input type="text" name="login" id="username" /><br />'."\n"

        .'<label for="password">Password : </label><br />'."\n"
        .'<input type="password" name="password" id="password" /><br />'."\n"
        .'<input type="submit"  />'."\n"

        .'</fieldset>'."\n"

        .'</form>'."\n"
        .'</td>'
        .'</tr>'
        .'</table>';

    // Display footer
    echo upgrade_disp_footer();
    die();
}

function fill_table_config_with_md5()
{
    // For each configuration file add a hash code in the new table config_list (new in 1.6)

    $config_code_list = get_config_code_list();

    foreach ( $config_code_list as $config_code )
    {
        $conf_file = get_conf_file($config_code);

        // The Hash compute and store is differed after creation table use for this storage
        // calculate hash of the config file
        $conf_hash = md5_file($conf_file);
        save_config_hash_in_db($config_code,$conf_hash);
    }
    return true;
}


class UpgradeTrackingOffset
{
    private static $path = '/../../../platform/upgrade_tracking_status.txt';
    
    public static function store($offset)
    {
        if ( ! file_exists( dirname(__FILE__) . '/../../../platform' ) )
        {
            claro_mkdir( dirname(__FILE__) . '/../../../platform', CLARO_FILE_PERMISSIONS, true );
        }
        
        file_put_contents(dirname(__FILE__) . self::$path, (int) $offset);
    }
    
    public static function retrieve()
    {
        if( file_exists(dirname(__FILE__) . self::$path) )
        {
            $recoveredOffset = (int) trim(file_get_contents(dirname(__FILE__) . self::$path));
        }
        else
        {
            $recoveredOffset = 0;
        }
        
        return $recoveredOffset;
    }
    
    public static function reset()
    {
        if( file_exists(dirname(__FILE__) . self::$path) )
        {
            unlink(dirname(__FILE__) . self::$path);
        }
    }
}

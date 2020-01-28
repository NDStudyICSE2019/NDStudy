<?php // $Id: defaultsetting.inc.php 14712 2014-02-17 08:30:15Z zefredz $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * This script set default content at init of install
 * Most of def value are from def file.
 * Special case are set are. def file would evoluate to deprecate this script.
 *
 * @version     $Revision: 14712 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Install
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 * @package     INSTALL
 */

include_once '../inc/conf/def/CLMAIN.def.conf.inc.php';

/*
 *
 */
$installLanguage = 'english';

$dbHostForm     = $conf_def_property_list['dbHost']['default'];
$dbUsernameForm = $conf_def_property_list['dbLogin']['default'];

$dbPrefixForm   = $conf_def_property_list['dbNamePrefix']['default'];// $dbPrefixForm."c_";

$mainTblPrefixForm  = 'cl_';
$dbNameForm         = $conf_def_property_list['mainDbName']['default'];// $dbPrefixForm."claroline";
$statsTblPrefixForm = 'cl_';
$dbStatsForm        = $conf_def_property_list['statsDbName']['default'];

$singleDbForm   = $conf_def_property_list['singleDbEnabled']['default'];
$enableTrackingForm =  $conf_def_property_list['is_trackingEnabled']['default'];
/**
 * extract the path to append to the url if Claroline is not installed on the web root directory
 */

 // remove possible double slashes
$urlAppendPath = str_replace( array('///', '//'), '/', $_SERVER['PHP_SELF']);
// detect if url case sensitivity does matter
$caseSensitive = (PHP_OS == 'WIN32' || PHP_OS == 'WINNT') ? 'i' : '';
// build the regular expression pattern
$ereg = "#/claroline/install/".basename($_SERVER['SCRIPT_NAME'])."$#$caseSensitive";
$urlAppendPath  = preg_replace ($ereg, '', $urlAppendPath);
$urlForm        = 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT']!='80'?':' . $_SERVER['SERVER_PORT']:'') . $urlAppendPath . '/';
$pathForm       = dirname(dirname(dirname(__FILE__))) . '/';

$imgRepositoryAppendForm        =  $conf_def_property_list['imgRepositoryAppend']['default'];
$userImageRepositoryAppendForm =  $conf_def_property_list['userImageRepositoryAppend']['default'];

$courseRepositoryForm = $conf_def_property_list['coursesRepositoryAppend']['default'];

$campusForm          = $conf_def_property_list['siteName']['default'];
$institutionForm     = $conf_def_property_list['institution_name'] ['default'];
$institutionUrlForm  = $conf_def_property_list['institution_url'] ['default'];

$languageForm = $conf_def_property_list['platformLanguage']['default'];
$clmain_serverTimezone = $conf_def_property_list['clmain_serverTimezone']['default'];

$userPasswordCrypted = $conf_def_property_list['userPasswordCrypted']['default'];

$allowSelfReg = $conf_def_property_list['allowSelfReg']['default'] ;

/**
 * admin & contact
 */
$loginForm          = '';
$passForm           = '';
$adminNameForm      = '';
$adminSurnameForm   = '';
$adminPhoneForm    = $conf_def_property_list['administrator_phone']['default'];
$adminEmailForm    = $conf_def_property_list['administrator_email']['default'];


$contactNameForm     = '*not set*'; // This magic value is use to detect if the content is edit or not.
$contactPhoneForm    = ''; // if <not set> is found, the data form admin are copied
$contactEmailForm    = '*not set*'; // This tips  permit to  empty these fields
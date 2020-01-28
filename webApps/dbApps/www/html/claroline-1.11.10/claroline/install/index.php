<?php // $Id: index.php 14722 2014-02-18 07:01:37Z zefredz $

/**
 * CLAROLINE
 *
 * Claroline installer.
 *
 * @version     $Revision: 14722 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/install/
 * @author      Claro Team <cvs@claroline.net>
 * @package     INSTALL
 */
$tz = ini_get('date.timezone');

if ( empty( $tz ) )
{
    ini_set('date.timezone','UTC');
    date_default_timezone_set('UTC');
}
else
{
    ini_set('date.timezone',date_default_timezone_get());
    date_default_timezone_set(date_default_timezone_get());
}

/* LET DEFINE ON SEPARATE LINES !!!*/
// __LINE__ use to have arbitrary number but order of panels

define ('DISP_WELCOME',__LINE__);
define ('DISP_LICENSE',__LINE__);
define ('DISP_DB_CONNECT_SETTING',__LINE__);
define ('DISP_DB_NAMES_SETTING',__LINE__);
define ('DISP_ADMINISTRATOR_SETTING',__LINE__);
define ('DISP_PLATFORM_SETTING',__LINE__);
define ('DISP_ADMINISTRATIVE_SETTING',__LINE__);
define ('DISP_LAST_CHECK_BEFORE_INSTALL',__LINE__);
define ('DISP_RUN_INSTALL_NOT_COMPLETE',__LINE__);
define ('DISP_RUN_INSTALL_COMPLETE',__LINE__);

$imgStatus['X'] = 'delete.png';
$imgStatus['V'] = 'checkbox_on.png';
$imgStatus['?'] = 'checkbox_off.png';
$imgStatus['!'] = 'caution.png';

$cssStepStatus['X'] = 'error';
$cssStepStatus['V'] = 'done';
$cssStepStatus['?'] = 'todo';
$cssStepStatus['!'] = 'caution';

/* LET DEFINE ON SEPARATE LINES !!!*/

// TODO remove this code
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Place of Config file
$configFileName = 'claro_main.conf.php';

session_start();
$_SESSION = array();
session_destroy();

$newIncludePath ='../inc/';
include $newIncludePath . 'installedVersion.inc.php';


include '../lang/english/install.lang.php';
include '../lang/english/locale_settings.php';


require_once $newIncludePath . 'lib/user.lib.php'; // needed fo generate_passwd()
require_once dirname(__FILE__) . '/install.lib.inc.php';
require_once $newIncludePath . 'lib/config.lib.inc.php';
require_once $newIncludePath . 'lib/form.lib.php';
require_once $newIncludePath . 'lib/course.lib.inc.php';
require_once $newIncludePath . 'lib/claro_main.lib.php';
require_once $newIncludePath . 'lib/language.lib.php';
require_once $newIncludePath . 'lib/module/manage.lib.php';
require_once $newIncludePath . 'lib/right/right_profile.lib.php';

$installLanguageList =  get_available_install_language();

if( isset($_REQUEST['installLanguage']) && in_array($_REQUEST['installLanguage'], $installLanguageList) )
{
    include '../lang/'.$_REQUEST['installLanguage'].'/install.lang.php';
    include '../lang/'.$_REQUEST['installLanguage'].'/locale_settings.php';
}
/**
 * Unquote GET, POST AND COOKIES if magic quote gpc is enabled in php.ini
 */

claro_unquote_gpc();


// TODO remove this code
if (count($_GET) > 0)      {extract($_GET, EXTR_OVERWRITE);}
if (count($_POST) > 0)     {extract($_POST, EXTR_OVERWRITE);}
if (count($_SERVER) > 0)   {extract($_SERVER, EXTR_OVERWRITE);}


// LIST OF  VIEW IN ORDER TO SHOW
$panelSequence  = array(
DISP_LANG,
DISP_LICENSE,
DISP_WELCOME,
//DISP_FILE_SYSTEM_SETTING,
DISP_DB_CONNECT_SETTING,
DISP_DB_NAMES_SETTING,
DISP_ADMINISTRATOR_SETTING,
DISP_PLATFORM_SETTING,
DISP_ADMINISTRATIVE_SETTING,
DISP_LAST_CHECK_BEFORE_INSTALL);
//DISP_RUN_INSTALL_NOT_COMPLETE is not a panel of sequence


// VIEW TITLE
$panelTitle[DISP_LANG]                      = get_lang('Installation language');
$panelTitle[DISP_LICENSE]                   = get_lang('License');
$panelTitle[DISP_WELCOME]                   = get_lang('Requirements');
//$panelTitle[DISP_FILE_SYSTEM_SETTING]      = get_lang('FileSystemSetting');
$panelTitle[DISP_DB_CONNECT_SETTING]        = get_lang('MySQL Database Settings');
$panelTitle[DISP_DB_NAMES_SETTING]          = get_lang('MySQL Database and Table Names');
$panelTitle[DISP_ADMINISTRATOR_SETTING]     = get_lang('Administrator Account');
$panelTitle[DISP_PLATFORM_SETTING]          = get_lang('Platform settings');
$panelTitle[DISP_ADMINISTRATIVE_SETTING]    = get_lang('Additional Information');
$panelTitle[DISP_LAST_CHECK_BEFORE_INSTALL] = get_lang('Last check before install');
$panelTitle[DISP_RUN_INSTALL_COMPLETE]      = get_lang('Claroline setup successful');

//$rootSys="'.realpath($pathForm).'";

$cmdName[DISP_LANG]                      = 'cmdLang';
$cmdName[DISP_WELCOME]                   = 'cmdWelcomePanel';
$cmdName[DISP_LICENSE]                   = 'cmdLicence';
//$cmdName[DISP_FILE_SYSTEM_SETTING]     = 'cmdFILE_SYSTEM_SETTING';
$cmdName[DISP_DB_CONNECT_SETTING]        = 'cmdDB_CONNECT_SETTING';
$cmdName[DISP_DB_NAMES_SETTING]          = 'cmdDbNameSetting';
$cmdName[DISP_ADMINISTRATOR_SETTING]     = 'cmdAdministratorSetting';
$cmdName[DISP_PLATFORM_SETTING]          = 'cmdPlatformSetting';
$cmdName[DISP_ADMINISTRATIVE_SETTING]    = 'cmdAdministrativeSetting';
$cmdName[DISP_LAST_CHECK_BEFORE_INSTALL] = 'install6';
$cmdName[DISP_RUN_INSTALL_COMPLETE]      = 'cmdDoInstall';


// CONTROLER
// GET cmd,
if($_REQUEST['cmdLang'])
{
    $cmd=DISP_LANG;
}
if($_REQUEST['cmdLicence'])
{
    $cmd=DISP_LICENSE;
}
elseif($_REQUEST['cmdWelcomePanel'])
{
    $cmd=DISP_WELCOME;
}
//elseif($_REQUEST['cmdFILE_SYSTEM_SETTING'])
//{
//    $cmd=DISP_FILE_SYSTEM_SETTING;
//}
elseif($_REQUEST['cmdDB_CONNECT_SETTING'])
{
    $cmd=DISP_DB_CONNECT_SETTING;
}
elseif($_REQUEST['cmdDbNameSetting'])
{
    $cmd=DISP_DB_NAMES_SETTING;
}
elseif($_REQUEST['cmdAdministratorSetting'])
{
    $cmd=DISP_ADMINISTRATOR_SETTING;
}
elseif($_REQUEST['cmdPlatformSetting'])
{
    $cmd=DISP_PLATFORM_SETTING;
}
elseif($_REQUEST['install6'])
{
    $cmd=DISP_LAST_CHECK_BEFORE_INSTALL;
}
elseif($_REQUEST['cmdAdministrativeSetting'])
{
    $cmd=DISP_ADMINISTRATIVE_SETTING;
}
elseif($_REQUEST['cmdDoInstall'])
{
    $cmd = DISP_RUN_INSTALL_COMPLETE;
}




##### INITIALISE FORM VARIABLES ##################

###  IF FIRST VISIT ###
if(!$_REQUEST['alreadyVisited'] || $_REQUEST['resetConfig']) // on first step purpose values
{
     include './defaultsetting.inc.php';
     foreach (array_keys($panelTitle) as $step ) $stepStatus[$step] = '?';
}
else ###  IF NOT ###
{
    extract($_REQUEST);
    $campusForm  = $_REQUEST['campusForm'];
}






// This script is a big form.
// all value are in HIDDEN FIELD,
// and different display show step by step some fields in editable input
// The last panel have another job. It's  run install and show result.
// Run install dom many task
//  * Create and fill main Database
//  * Create and fill STAT Database
//  * Create  some  directories
//  * Write the config file
//  * Protect some  directory with an .htaccess (work only  for apache)

/**
 *
 * Check New Data  (following $_REQUEST['fromPanel'] value)
 * or if $_REQUEST['cmdDoInstall']
 *
 * Each check set the view to display following check Result
 * when check failed, some flag are set to trigger some explict messages
 */



$canRunCmd = TRUE;
if ($_REQUEST['fromPanel'] == DISP_LANG || $_REQUEST['cmdDoInstall'])
{
    $stepStatus[DISP_LANG] = 'V';
    if( isset($_REQUEST['installLanguage']) && in_array($_REQUEST['installLanguage'], $installLanguageList) )
    {
        $installLanguage = $_REQUEST['installLanguage'];
    }
}

if ($_REQUEST['fromPanel'] == DISP_WELCOME || $_REQUEST['cmdDoInstall'])
{
    $stepStatus[DISP_WELCOME] = 'V';
}

if ($_REQUEST['fromPanel'] == DISP_LICENSE || $_REQUEST['cmdDoInstall'])
{
    $stepStatus[DISP_LICENSE] = 'V';
}

if ($_REQUEST['fromPanel'] == DISP_LAST_CHECK_BEFORE_INSTALL || $_REQUEST['cmdDoInstall'])
{
    $stepStatus[DISP_LAST_CHECK_BEFORE_INSTALL] = 'V';
}

if($_REQUEST['fromPanel'] == DISP_ADMINISTRATOR_SETTING || $_REQUEST['cmdDoInstall'])
{
    $stepStatus[DISP_ADMINISTRATOR_SETTING] = 'V';
    if (empty($adminSurnameForm)||empty($passForm)||empty($loginForm)||empty($adminNameForm)||empty($adminEmailForm)||!is_well_formed_email_address($adminEmailForm))
    {
        $stepStatus[DISP_ADMINISTRATOR_SETTING] = 'X';
        $adminDataMissing = TRUE;
        if (empty($loginForm)) $missing_admin_data[] = 'login';
        if (empty($passForm))  $missing_admin_data[] = 'password';
        if (empty($adminSurnameForm)) $missing_admin_data[] = 'firstname';
        if (empty($adminNameForm)) $missing_admin_data[] = 'lastname';
        if (empty($adminEmailForm)) $missing_admin_data[] = 'email';
        if (!empty($adminEmailForm) && !is_well_formed_email_address($adminEmailForm)) $error_in_admin_data[] = 'email';

        if ($cmd>DISP_ADMINISTRATOR_SETTING)
        {
            $display=DISP_ADMINISTRATOR_SETTING;
        }
        else
        {
            $display=$cmd;
        }
        $canRunCmd = FALSE;
    }
    else
    {
        // here add some check  on email, password crackability, ... of admin.
    }
}

if( DISP_ADMINISTRATIVE_SETTING == $_REQUEST['fromPanel'] )
{
    $check_administrative_data = array();
    $institutionUrlForm = trim($institutionUrlForm);
    $contactNameForm    = trim($contactNameForm);
    $adminNameForm      = trim($adminNameForm);
    $contactEmailForm   = trim($contactEmailForm);
    $regexp = "!^(http|https|ftp)\://[a-zA-Z0-9\.-]+\.[a-zA-Z0-9]{1,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\._\?\,\'/\\\+&%\$#\=~-])*$!i";
    
    if ( (!empty($institutionUrlForm)) && !preg_match( $regexp, $institutionUrlForm) )
    {
        // problem with url. try to repair
        // if  it  only the protocol missing add http
        if (preg_match('!^[a-zA-Z0-9\-\.]+\.[a-zA-Z0-9]{2,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\-\._\?\,\'/\\\+&%\$#\=~])*$!i', $institutionUrlForm )
        && (preg_match($regexp, 'http://' . $institutionUrlForm )))
        {
            $institutionUrlForm = 'http://' . $institutionUrlForm;
        }
        else
        {
            $administrativeDataMissing = TRUE;
            $check_administrative_data[] = get_lang('Institution URL');
        }
    }

    if (empty($contactEmailForm) || empty($contactNameForm)
        || !is_well_formed_email_address($contactEmailForm)
    )
    {
        $administrativeDataMissing = TRUE;
        if (empty($contactNameForm))
        {
            $check_administrative_data[] = get_lang('Contact name');
            $contactNameForm = $adminNameForm;
        }


        if (empty($contactEmailForm)||!is_well_formed_email_address($contactEmailForm))
        {
            $check_administrative_data[] = get_lang('Contact email');
            if (empty($contactEmailForm))
            {
                $contactEmailForm = $adminEmailForm;
            }
            else     // if not empty but wrong, I can suppose the good value, so I let it blank
            {
                $contactEmailForm ='';
            }
        }

    }

    if($administrativeDataMissing)
    {
        $msg_missing_administrative_data = '<div class="claroDialogBox boxError">'
        .    '<p>' . "\n"
        .    '<strong>'.get_lang('Error').'</strong> : '
        .    get_lang('Please enter missing information')
        .    '</p>' . "\n"
        .    '<ul>';
        
        foreach ( $check_administrative_data as $missing_administrative_data )
        {
            $msg_missing_administrative_data .= '<li>'.$missing_administrative_data.'</li>';
        }
        
        $msg_missing_administrative_data .= '</ul>'
        .    '</div>';
        
        $display =  ( $cmd > DISP_ADMINISTRATIVE_SETTING ) ? DISP_ADMINISTRATIVE_SETTING : $cmd;
        $canRunCmd = FALSE;
        $stepStatus[DISP_ADMINISTRATIVE_SETTING] = 'X';
    }
    else
    {
        $stepStatus[DISP_ADMINISTRATIVE_SETTING] = 'V';
        // here add some check  on email, password crackability, ... of admin.
    }
}

if ($_REQUEST['fromPanel'] == DISP_DB_CONNECT_SETTING || $_REQUEST['cmdDoInstall'])
{
    // Check Connection //
    $databaseParam_ok = TRUE;
    $db = @mysql_connect("$dbHostForm", "$dbUsernameForm", "$dbPassForm");
    $stepStatus[DISP_DB_CONNECT_SETTING] = 'V';
    if ( mysql_errno() > 0 ) // problem with server
    {
        $no  = mysql_errno();
        $msg = mysql_error();
        $msg_no_connection =
                '<div class="claroDialogBox boxError">'
                .    '<p>'
                .     '<strong>'.get_lang('Error').'</strong> : '
                .    '</p>';
                
        if ( '2005' == $no )
        {
            $msg_no_connection .= get_lang('Wrong database host');
        }
        elseif ( '1045' == $no )
        {
            $msg_no_connection .= get_lang('Wrong database login or password');
        }
        else
        {
            $msg_no_connection .= get_lang('Server unavailable');
        }

        $msg_no_connection .= '<p>' . "\n"
        .    '<small>('.get_lang('Mysql error %no : %msg', array( '%no' => $no, '%msg' => $msg)) . ')</small>'
        .    '</p>'
        .     '</div>';
        
        $databaseParam_ok = FALSE;
        $canRunCmd = FALSE;
        
        if ($cmd>DISP_DB_CONNECT_SETTING)
        {
            $display=DISP_DB_CONNECT_SETTING;
        }
        else
        {
            $display=$cmd;
        }
        $stepStatus[DISP_DB_CONNECT_SETTING] = 'X';

    }
}


// CHECK DATA OF DB NAMES Form
if ($_REQUEST['fromPanel'] == DISP_DB_NAMES_SETTING || $_REQUEST['cmdDoInstall'])
{
    $stepStatus[DISP_DB_NAMES_SETTING] = 'V';
    $regexpPatternForDbName = '/^[a-z0-9][a-z0-9_-]*$/i';
    // Now mysql connect param are ok, try  to use given DBNames
    // 1. check given string
    // 2. check if db exists

    $databaseParam_ok = TRUE;
    if ($singleDbForm) $dbStatsForm = $dbNameForm;
    if ($singleDbForm) $statsTblPrefixForm = $mainTblPrefixForm;
    $dbNameForm = trim($dbNameForm);
    $dbStatsForm = trim($dbStatsForm);
    $databaseNameValid = TRUE;
    $databaseAlreadyExist = FALSE;

    if (!preg_match($regexpPatternForDbName,$dbNameForm)|| strlen($dbNameForm)>64
        ||
        !preg_match($regexpPatternForDbName,$dbStatsForm)|| strlen($dbStatsForm)>64 )

    //  64 is  the  max  for the name of a mysql database
    {
        $databaseNameValid = FALSE;
        $msgErrorDbMain_dbNameToolLong = (strlen($dbNameForm)>64);
        $msgErrorDbMain_dbNameInvalid = !preg_match($regexpPatternForDbName,$dbNameForm);
        $msgErrorDbMain_dbNameBadStart = !preg_match('/^[a-z0-9]/i',$dbNameForm);

        if (!$singleDbForm)
        {
            $msgErrorDbMain_dbName = $msgErrorDbMain_dbNameToolLong ||
                                     $msgErrorDbMain_dbNameInvalid ||
                                     $msgErrorDbMain_dbNameBadStart ;

            $msgErrorDbStat_dbNameInvalid = !preg_match($regexpPatternForDbName,$dbStatsForm);
            $msgErrorDbStat_dbNameToolLong = (strlen($dbStatsForm)>64);
            $msgErrorDbStat_dbNameBadStart = !preg_match('/^[a-z0-9]/i',$dbStatsForm);
        }

    }
    else
    {
        $db = mysql_connect("$dbHostForm", "$dbUsernameForm", "$dbPassForm");

        $valMain = check_if_db_exist($dbNameForm  ,$db);
        if ($dbStatsForm == $dbNameForm) $confirmUseExistingStatsDb = $confirmUseExistingMainDb ;
        if (!$singleDbForm) $valStat = check_if_db_exist($dbStatsForm ,$db);
        if ($valMain || $valStat )
        if ($confirmUseExistingStatsDb ) $stepStatus[DISP_DB_NAMES_SETTING] = 'V';
        else
        {
            $databaseAlreadyExist              = TRUE;
            if ($valMain)    $mainDbNameExist  = TRUE;
            if ($valStat)    $statsDbNameExist = TRUE;
        }

    }

    if (   $databaseAlreadyExist
       || !$databaseNameValid    )
    {
        $canRunCmd = FALSE;
        if ($cmd > DISP_DB_NAMES_SETTING)
        {
            $databaseAlreadyExist              = TRUE;
            if ($valMain)    $mainDbNameExist  = TRUE;
            if ($valStat)    $statsDbNameExist = TRUE;
            $canRunCmd                         = FALSE;
        }
        else
        {
            $databaseAlreadyExist = false;
        }

        if (!$canRunCmd)
        {
            if ($cmd > DISP_DB_NAMES_SETTING)
            {
                $display = DISP_DB_NAMES_SETTING;
            }
            else
            {
                $display= $cmd;
            }
            $stepStatus[DISP_DB_NAMES_SETTING] = 'X';

        }
    }
    else
    {
        $databaseAlreadyExist = false;
    }
    // Check to add
    // If database already exist but confirm , ok but not if one of table exist in the db.

}

if($_REQUEST['fromPanel'] == DISP_PLATFORM_SETTING || $_REQUEST['cmdDoInstall'])
{
    $stepStatus[DISP_PLATFORM_SETTING] = 'V';

    

    if(empty($urlForm) || empty($campusForm) )
    {
        $msg_missing_platform_data = '<div class="claroDialogBox boxError">'
        .    '<p>' . "\n"
        .     '<strong>'.get_lang('Error').'</strong> : '
        .     get_lang('Please enter missing information')
        .    '</p>' . "\n"
        .     '<ul>';
        
        if (empty($campusForm))
        {
            $msg_missing_platform_data .= '<li>'.get_lang('Name').'</li>';
        }

        if (empty($urlForm))
        {
            $msg_missing_platform_data .= '<li>'.get_lang('Absolute URL').' ('. get_lang('Something like : %exampleUrl', array('%exampleUrl' =>'http://'.$_SERVER['SERVER_NAME'].$urlAppendPath.'/')).'</li>';
        }
    
        
        $msg_missing_platform_data .= '</ul>' . "\n"
        .     '</div>';
        
        $canRunCmd = FALSE;
        
        if ($cmd > DISP_PLATFORM_SETTING)
        {
            $display = DISP_PLATFORM_SETTING;
        }
        else
        {
            $display= $cmd;
        }
        $stepStatus[DISP_PLATFORM_SETTING] = 'X';
    }
}

// ALL Check are done.
// $canRunCmd has set during checks

if ($canRunCmd)
{
    // OK TEST WAS GOOD, What's the next step ?

    // SET default display
    $display = $panelSequence[0];
    if($_REQUEST['cmdLang'])
    {
        $display = DISP_LANG;
    }
    if($_REQUEST['cmdWelcomePanel'])
    {
        $display = DISP_WELCOME;
    }
    if($_REQUEST['cmdLicence'])
    {
        $display = DISP_LICENSE;
    }
//    elseif($_REQUEST['cmdFILE_SYSTEM_SETTING'])
//    {
//        $display = DISP_FILE_SYSTEM_SETTING;
//    }
    elseif($_REQUEST['cmdDB_CONNECT_SETTING'])
    {
        $display = DISP_DB_CONNECT_SETTING;
    }
    elseif($_REQUEST['install6'] || $_REQUEST['back6'] )
    {
        $display = DISP_LAST_CHECK_BEFORE_INSTALL;
    }
    elseif($_REQUEST['cmdDbNameSetting'])
    {
        $display = DISP_DB_NAMES_SETTING;
    }
    elseif($_REQUEST['cmdAdministratorSetting'])
    {
        $display = DISP_ADMINISTRATOR_SETTING;
    }
    elseif($_REQUEST['cmdPlatformSetting'])
    {
        $display = DISP_PLATFORM_SETTING;
    }
    elseif($_REQUEST['cmdAdministrativeSetting'])
    {
        $display = DISP_ADMINISTRATIVE_SETTING;
    }
    elseif($_REQUEST['cmdDoInstall'])
    {
        $includePath = $newIncludePath;
        $rootSys = realpath($newIncludePath . '/../../');
        $display = DISP_RUN_INSTALL_COMPLETE;
        include('./do_install.inc.php');
    }
 }

//PREPARE DISPLAY


if( DISP_DB_NAMES_SETTING == $display )
{
    // GET DB Names  //
    // this is  to prevent duplicate before submit
    $db = @mysql_connect("$dbHostForm", "$dbUsernameForm", "$dbPassForm");
    $sql = "show databases";
    $res = claro_sql_query($sql,$db);
    while ($__dbName = mysql_fetch_array($res, MYSQL_NUM))
    {
        $existingDbs[] = $__dbName[0];
    }
    unset($__dbName);
}
elseif( DISP_ADMINISTRATIVE_SETTING == $display )
{
    if ($contactNameForm == '*not set*')
    {
        $contactNameForm = $adminSurnameForm . ' ' . $adminNameForm;
    }

    if ($contactEmailForm == '*not set*')
    {
        $contactEmailForm = $adminEmailForm;
    }

}
elseif( DISP_PLATFORM_SETTING == $display )
{
    $includePath = $newIncludePath;
    $language_list = claro_get_lang_flat_list();
}

//$display = DISP_RUN_INSTALL_NOT_COMPLETE;
// BEGIN OUTPUT

// COMMON OUTPUT Including top of form  and list of hidden values

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
.    "\t". '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' ."\n"
.    '<html>' . "\n"
.    '<head>' . "\n"
.    '<meta http-equiv="Content-Style-Type" content="text/css" />' . "\n"
.    '<meta http-equiv="Content-Type" content="text/HTML; charset='.get_locale('charset').'"  />' . "\n"
.    '<title>' . "\n"
.    get_lang('Claroline %version Installation Wizard', array('%version' => $new_version))
.    ' - ' . get_lang('Step %step', array('%step' => array_search($display, $panelSequence) + 1) ) . "\n"
.    '</title>' . "\n\n"

.    '<link rel="stylesheet" href="../../web/css/install.css" type="text/css" />' . "\n"
.    '<style media="print" type="text/css" >' . "\n"
.    '    .progressPanel{ visibility: hidden;width:0px; }' . "\n"
.    '</style>' . "\n"
.    '</head>' . "\n"
.    '<body dir="' . $text_dir . '">' . "\n\n"
.    '<div id="claroPage">' . "\n"
.    '<div id="installHeader">' . "\n"
.    '<h1>'.get_lang('Claroline %version Installation Wizard', array('%version' => $new_version)).'</h1>' . "\n"
.    '</div>' . "\n"
.    '<div id="installContainer">' . "\n\n"
.    '<div id="installBody">' . "\n\n"
.    '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n\n"
;


// don't display stepping on last panel
if (DISP_RUN_INSTALL_COMPLETE != $display )
{
    echo '<br/>' . "\n"
    .    '<div class="progressPanel">' . "\n"
    ;
    
    foreach ($panelSequence as $stepCount => $thisStep  )
    {
        $stepStyle = ($thisStep == $display) ? 'active' : $cssStepStatus[$stepStatus[$thisStep]];
    
        echo '<div class="progress ' . $stepStyle . '"  >'
        .    '<strong>' . ($stepCount +1) . '</strong> '
        .    strip_tags($panelTitle[$thisStep])
        .    '</div>' . "\n"
        ;
    }
    $stepPos = array_search($display, $panelSequence);
    echo '</div>' . "\n";
    echo '<div id="panel">' . "\n\n";
    
}
else
{
    echo '<div id="endPanel">' . "\n\n";
}

$nextStepDisable = false;

foreach (array_keys($panelTitle) as $step )
{
    echo '<input type="hidden" name="stepStatus['.$step.']" value="' . $stepStatus[$step] . '" />'                ."\n";
}

echo '<input type="hidden" name="alreadyVisited" value="1" />'                                                 ."\n"
.    '<input type="hidden" name="installLanguage"              value="'.$installLanguage.'" />'                     ."\n"
.    '<input type="hidden" name="urlAppendPath"                value="'.$urlAppendPath.'" />'                  ."\n"
.    '<input type="hidden" name="urlEndForm"                   value="'.$urlEndForm.'" />'                     ."\n"
.    '<input type="hidden" name="courseRepositoryForm"         value="'.$courseRepositoryForm.'" />'           ."\n"
.    '<input type="hidden" name="pathForm" value="'.str_replace("\\","/",realpath($pathForm)."/").'"  />'      ."\n"
.    '<input type="hidden" name="imgRepositoryAppendForm" value="'.str_replace("\\","/",$imgRepositoryAppendForm).'"  />'      ."\n"
.    '<input type="hidden" name="userImageRepositoryAppendForm" value="'.str_replace("\\","/",$userImageRepositoryAppendForm).'"  />'      ."\n"
.    '<input type="hidden" name="dbHostForm"                   value="'.$dbHostForm.'" />'                     ."\n"
.    '<input type="hidden" name="dbUsernameForm"               value="'.$dbUsernameForm.'" />'                 ."\n\n"
.    '<input type="hidden" name="singleDbForm"                 value="'.$singleDbForm.'" />'                   ."\n\n"
.    '<input type="hidden" name="dbPrefixForm"                 value="'.$dbPrefixForm.'" />'                   ."\n"
.    '<input type="hidden" name="dbNameForm"                   value="'.$dbNameForm.'" />'                     ."\n"
.    '<input type="hidden" name="dbStatsForm"                  value="'.$dbStatsForm.'" />'                    ."\n"
.    '<input type="hidden" name="mainTblPrefixForm"            value="'.$mainTblPrefixForm.'" />'              ."\n"
.    '<input type="hidden" name="statsTblPrefixForm"           value="'.$statsTblPrefixForm.'" />'              ."\n"
.    '<input type="hidden" name="dbMyAdmin"                    value="'.$dbMyAdmin.'" />'                      ."\n"
.    '<input type="hidden" name="dbPassForm"                   value="'.$dbPassForm.'" />'                     ."\n\n"
.    '<input type="hidden" name="urlForm"                      value="'.$urlForm.'" />'                        ."\n"
.    '<input type="hidden" name="adminEmailForm"               value="'.claro_htmlspecialchars($adminEmailForm).'" />'   ."\n"
.    '<input type="hidden" name="adminNameForm"                value="'.claro_htmlspecialchars($adminNameForm).'" />'    ."\n"
.    '<input type="hidden" name="adminSurnameForm"             value="'.claro_htmlspecialchars($adminSurnameForm).'" />' ."\n\n"
.    '<input type="hidden" name="loginForm"                    value="'.claro_htmlspecialchars($loginForm).'" />'        ."\n"
.    '<input type="hidden" name="passForm"                     value="'.claro_htmlspecialchars($passForm).'" />'         ."\n\n"
.    '<input type="hidden" name="languageForm"                 value="'.$languageForm.'" />'                   ."\n\n"
.    '<input type="hidden" name="campusForm"                   value="'.claro_htmlspecialchars($campusForm).'" />'       ."\n"
.    '<input type="hidden" name="contactNameForm"              value="'.claro_htmlspecialchars($contactNameForm).'" />'  ."\n"
.    '<input type="hidden" name="contactEmailForm"             value="'.claro_htmlspecialchars($contactEmailForm).'" />' ."\n"
.    '<input type="hidden" name="contactPhoneForm"             value="'.claro_htmlspecialchars($contactPhoneForm).'" />' ."\n"
.    '<input type="hidden" name="institutionForm"              value="'.claro_htmlspecialchars($institutionForm).'" />'  ."\n"
.    '<input type="hidden" name="institutionUrlForm"           value="'.$institutionUrlForm.'" />'             ."\n\n"
.    '<!-- BOOLEAN -->'                                                                                      ."\n"
.    '<input type="hidden" name="enableTrackingForm"           value="'.$enableTrackingForm.'" />'             ."\n"
.    '<input type="hidden" name="allowSelfReg"                 value="'.$allowSelfReg.'" />'                   ."\n"
.    '<input type="hidden" name="userPasswordCrypted"          value="'.$userPasswordCrypted.'" />'            ."\n"
.    '<input type="hidden" name="encryptPassForm"              value="'.$encryptPassForm.'" />'                ."\n"
.    '<input type="hidden" name="confirmUseExistingMainDb"     value="'.$confirmUseExistingMainDb.'" />'       ."\n"
.    '<input type="hidden" name="confirmUseExistingStatsDb"    value="'.$confirmUseExistingStatsDb.'" />'      . "\n"
.    '<input type="hidden" name="clmain_serverTimezone"        value="'.$clmain_serverTimezone.'" />';


##### PANNELS  ######
#
# INSTALL IS a big form
# Too big to show  in one time.
# PANEL show some  field to edit, all other are in HIDDEN FIELDS
###################################################################
############### STEP 0 LANG #######################################
###################################################################
if(DISP_LANG == $display)
{
    $language_list = claro_get_lang_flat_list();
    $displayedInstallLanguageList = array_intersect($language_list,$installLanguageList);
    
    echo '<input type="hidden" name="fromPanel" value="'.$display.'" />'  . "\n"
    .    '<h2>'  . "\n"
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_LANG, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_LANG] ) )
    .    '</h2>'  . "\n"
    
    .    '<fieldset>' . "\n"
    .     '<legend>'.get_lang('Please select a language').'</legend>' . "\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="dbHostForm"><span class="required">*</span> '.get_lang('Installation language').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    claro_html_form_select( 'installLanguage'
                               , $displayedInstallLanguageList
                               , $installLanguage
                               , array('id'=>'installLanguage')) . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    .    '</fieldset>' . "\n"
    .    '<small>'.get_lang('%requiredMark required field', array('%requiredMark' => '<span class="required">*</span>') ).'</small>' . "\n"
    ;

}
###################################################################
############### STEP 1 LICENSE  ###################################
###################################################################
elseif(DISP_LICENSE == $display)
{
    echo '<input type="hidden" name="fromPanel" value="'.$display.'" />'  . "\n"
    .    '<h2>'  . "\n"
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_LICENSE, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_LICENSE] ) )
    .    '</h2>'  . "\n"
    .    '<p>'  . "\n"
    .    get_lang('Claroline is free software, distributed under the GNU General Public license (GPL).') . '<br />'  . "\n"
    .    get_lang('Please read the license and click &quot;Next&quot; to accept it.')  . "\n"
    .    '<a href="../../LICENSE.txt">'.get_lang('Printer-friendly version'). '</a>'  . "\n"
    .    '</p>'  . "\n"
    .    '<textarea id="license" cols="65" rows="15">'
    ;

    readfile ('../license/gpl.txt');
    echo '</textarea>'
    ;

}
###################################################################
###### STEP 2 REQUIREMENTS ########################################
###################################################################
elseif ($display == DISP_WELCOME)
{
    echo '<input type="hidden" name="fromPanel" value="' . $display . '" />'
    .    '<h2>'
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_WELCOME, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_WELCOME] ) )
    .    '</h2>'
    ;
    // check if an claroline configuration file doesn't already exists.
    if ( file_exists('../../platform/conf/claro_main.conf.php')
    ||   file_exists('../inc/conf/claro_main.conf.inc.php')
    ||   file_exists('../inc/conf/claro_main.conf.php')
    ||   file_exists('../inc/conf/config.inc.php')
    ||   file_exists('../include/config.inc.php')
    ||   file_exists('../include/config.php'))
    {
        echo '<div class="claroDialogBox boxWarning">'
        .    '<p>' . "\n"
        .    '<strong>'.get_lang('Warning').'</strong>'
        .    ' : ' . get_lang('The installer has detected an existing Claroline platform on your system.') . "\n"
        .    '</p>' . "\n"
        .    '<ul>' . "\n"
        ;
        if ($is_upgrade_available)
        {
            echo '<li>'
            .    get_lang('To upgrade Claroline, click <a href="%url">here</a>', array('%url' => '../admin/upgrade/upgrade.php'))
            .    '</li>'
            ;
        }
        else
        {
            echo '<li>'
            .    get_lang('To upgrade Claroline, please wait for the next stable release.')
            .    '</li>'
            ;
        }
        echo '<li>'
        .    get_lang('To perform a full re-installation click on the "Next" button below.') . '<br />'
        .    get_lang('Be aware that performing a full re-installation will delete all the data stored in your previously installed Claroline platform.')
        .    '</li>'
        .    '</ul>'
        .    '</div>'
        ;
    }

    if(!$stable)
    {
        echo '<div class="claroDialogBox boxWarning">'
        .    '<p>' . "\n"
        .    '<strong>'.get_lang('Warning').'</strong>'
        .    ' : ' . get_lang('This version is not considered stable and is not intended for production.')
        .    '</p>' . "\n"
        .     '<p>'
        .    get_lang('If something goes wrong, please report on our support forum at %linkTag.',
                            array('%linkTag' => '<a href="http://forum.claroline.net/">http://forum.claroline.net</a>'))
        .    '</p>'."\n"
        .    '</div>'."\n\n"
        ;
    }
    
    // remove mysqlnd from client info string, if found
    $mysql_ver = preg_replace('/^mysqlnd /', '', mysql_get_client_info());
    echo '<p>'
    .    get_lang('Please, read thoroughly the <a href="%installFileUrl">%installFileName</a> document before proceeding to installation.', array('%installFileUrl' => '../../INSTALL.txt','%installFileName'=>'INSTALL.txt'))
    .    '</p>'
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Server requirements').'</legend>' . "\n"
    
    .    '<table class="requirements">'
    .    '<tbody>' . "\n"
    .    '<tr>'
    .    '<td>Php version >= 5.2</td>'
    .    '<td>' . ( version_compare(phpversion(), $requiredPhpVersion, ">=" ) ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . ' (' . phpversion() . ')</td>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>MySQL version >= 4.3</td>'
    .    '<td>' . ( version_compare($mysql_ver, $requiredMySqlVersion, ">=" ) ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . ' (' . mysql_get_client_info(). ')</td>'
    .    '</tr>'
 
    .    '<tr>'
    .    '<th colspan="2">'.get_lang('Required php extensions').'</th>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>MySql</td>'
    .    '<td>' . ( extension_loaded('mysql') ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . '</td>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>Zlib compression</td>'
    .    '<td>' . ( extension_loaded('zlib') ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . '</td>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>Regular expressions</td>'
    .    '<td>' . ( extension_loaded('pcre') ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . '</td>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>XML</td>'
    .    '<td>' . ( extension_loaded('xml') ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . '</td>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>mbstring or iconv</td>'
    .    '<td>'
    ;
    if( extension_loaded('mbstring') || extension_loaded('iconv') )
    {
        echo '<span class="ok">'.get_lang('Ok').'</span> (';
        if( extension_loaded('mbstring') ) echo ' mbstring ';
        if( extension_loaded('iconv') ) echo ' iconv ';
        echo ')';
    }
    else
    {
        echo '<span class="ko">'.get_lang('Ko').'</span>';
    }
    
    echo '</td>'
    .    '</tr>'
     
    .    '<tr>'
    .    '<th colspan="2">'.get_lang('Optional php extensions').'</th>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>GD</td>'
    .    '<td>' . ( extension_loaded('gd') ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . '</td>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>LDAP</td>'
    .    '<td>' . ( extension_loaded('ldap') ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . '</td>'
    .    '</tr>'
    .    '<tr>'
    .    '<td>OpenSSL</td>'
    .    '<td>' . ( extension_loaded('openssl') ? '<span class="ok">'.get_lang('Ok').'</span>':'<span class="ko">'.get_lang('Ko').'</span>') . '</td>'
    .    '</tr>'
    .    '</tbody>' . "\n"
    .    '</table>'
    .     '</fieldset>' . "\n\n"
    
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Recommanded settings').'</legend>' . "\n"
    .    '<table  class="requirements">' . "\n"
    .    '<tr>' . "\n"
    .    '<th>'.get_lang('Setting').'</th>' . "\n"
    .    '<th>'.get_lang('Recommended value').'</th>' . "\n"
    .    '<th>'.get_lang('Current value').'</th>' . "\n"
    .    '</tr>' . "\n"
    .    '<tbody>' . "\n"
    .    '<tr>' . "\n"
    .    '<td>Safe mode</td>' . "\n"
    .    '<td>Off</td>' . "\n"
    .    '<td>' . check_php_setting('safe_mode', 'OFF') . '</td>' . "\n"
    .    '</tr>' . "\n"
    .    '<tr>' . "\n"
    .    '<td>Display errors</td>' . "\n"
    .    '<td>Off</td>' . "\n"
    .    '<td>' . check_php_setting('display_errors', 'OFF') . '</td>' . "\n"
    .    '</tr>' . "\n"
    .    '<tr>' . "\n"
    .    '<td>Register globals</td>' . "\n"
    .    '<td>Off</td>' . "\n"
    .    '<td>' . check_php_setting('register_globals', 'OFF') . '</td>' . "\n"
    .    '</tr>' . "\n"
    .    '<tr>' . "\n"
    .    '<td>Magic quotes GPC</td>' . "\n"
    .    '<td>Off</td>' . "\n"
    .    '<td>' . check_php_setting('magic_quotes_gpc', 'OFF') . '</td>' . "\n"
    .    '</tr>' . "\n"
    .    '<tr>' . "\n"
    .    '<td>File uploads</td>' . "\n"
    .    '<td>On</td>' . "\n"
    .    '<td>' . check_php_setting('file_uploads', 'ON') . '</td>' . "\n"
    .    '</tr>' . "\n"
    .    '<tr>' . "\n"
    .    '<td>Upload max filesize</td>' . "\n"
    .    '<td>8-100M</td>' . "\n"
    .    '<td>' . ini_get('upload_max_filesize') . '</td>' . "\n"
    .    '</tr>' . "\n"
    .    '<tr>' . "\n"
    .    '<td>Post max size</td>' . "\n"
    .    '<td>8-100M</td>' . "\n"
    .    '<td>' . ini_get('post_max_size') . '</td>' . "\n"
    .    '</tr>' . "\n"
    .    '</tbody>' . "\n"
    .    '</table>' . "\n\n"
    ;

    echo '</fieldset>' . "\n\n"
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Directories and files permissions').'</legend>' . "\n"
    .    '<table class="requirements">' . "\n"
    .    '<tbody>' . "\n";
    
    $pathRoot = ('../..');
    $rootReadable = true;
    $rootWritable = true;
    
    if(!is_readable($pathRoot))
    {
        $rootReadable = false;
    }
    if(!is_writable($pathRoot))
    {
        $rootWritable = false;
    }
    
    $pathPlatform = '../../platform';
    $platformReadable = true;
    $platformWritable = true;
    if( is_dir($pathPlatform))
    {
        if(is_readable($pathPlatform))
        {
            if($h = opendir($pathPlatform))
            {
                while(($file = readdir($h)) !== false)
                {
                    if(is_dir($pathPlatform . $file))
                    {
                        if(!(is_readable($pathPlatform . $file)))
                        {
                            $platformReadable = false;
                        }
                        if(!(is_writable($pathPlatform . $file)))
                        {
                            $platformWritable = false;
                        }
                    }
                }
            }
        }
        else
        {
            $platformReadable = false;
        }
        if(!is_writable($pathPlatform))
        {
            $platformWritable = false;
        }
    }
    
    $pathModule = '../../module';
    $moduleReadable = true;
    $moduleWritable = true;
    if(is_dir($pathModule))
    {
       if(is_readable($pathModule))
        {
            if($h = opendir($pathModule))
            {
                while(($file = readdir($h)) !== false)
                {
                    if(is_dir($pathModule . $file))
                    {
                        if(!(is_readable($pathModule . $file)))
                        {
                            $moduleReadable = false;
                        }
                        if(!(is_writable($pathModule . $file)))
                        {
                            $moduleWritable = false;
                        }
                    }
                }
            }
        }
        else
        {
            $moduleReadable = false;
        }
        if(!is_writable($pathModule))
        {
            $moduleWritable = false;
        }
    }
    
    $pathTmp = '../../tmp';
    $tmpReadable = true;
    $tmpWritable = true;
    if(is_dir($pathTmp))
    {
        if(is_readable($pathTmp))
        {
            if($h = opendir($pathTmp))
            {
                while(($file = readdir($h)) !== false)
                {
                    if(is_dir($pathTmp . $file))
                    {
                        if(!(is_readable($pathTmp . $file)))
                        {
                            $tmpReadable = false;
                        }
                        if(!(is_writable($pathTmp . $file)))
                        {
                            $tmpWritable = false;
                        }
                    }
                }
            }
        }
        else
        {
            $tmpReadable = false;
        }
        if(!is_writable($pathTmp))
        {
            $tmpWritable = false;
        }
    }
    
    //Directories readable
    echo '<tr>' . "\n"
    .    '<td>'.get_lang('Are directories readable ?') . '</td>'  . "\n"
    .    '<td>';
    if( $rootReadable && $platformReadable && $moduleReadable && $tmpReadable )
    {
        echo '<span class="ok">'.get_lang('Yes').'</span>' . "\n";
    }
    else
    {
        //echo '<span class="ko">'.get_lang('No').'</span>' . "\n";
        $nextStepDisable = true;
    }
    echo '</tr>' . "\n";
    if( !$rootReadable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathRoot) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    if( !$platformReadable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathPlatform) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    if( !$moduleReadable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathModule) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    if( !$tmpReadable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathTmp) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    
    //Directories writable
    echo '<tr>' . "\n"
    .    '<td>'.get_lang('Are directories writable ?') . '</td>'  . "\n"
    .    '<td>';
    if( $rootWritable && $platformWritable && $moduleWritable && $tmpWritable )
    {
        echo '<span class="ok">'.get_lang('Yes').'</span>' . "\n";
    }
    else
    {
        //echo '<span class="ko">'.get_lang('No').'</span>' . "\n";
        $nextStepDisable = true;
    }
    echo '</tr>' . "\n";
    if( !$rootWritable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathRoot) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    if( !$platformWritable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathPlatform) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    if( !$moduleWritable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathModule) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    if( !$tmpWritable )
    {
        echo '<tr>' . "\n"
        .    '<td><em>'. realpath($pathTmp) . '</em></td>'  . "\n"
        .    '<td>'
        .    '<span class="ko">'. get_lang('No') .'</span>'
        .    '</td>' . "\n"
        .    '</tr>';
    }
    
    echo '</tbody>' . "\n"
    .    '</table>' . "\n"
    .    '</fieldset>' . "\n\n"
    ;

}



##########################################################################
###### STEP 3 MYSQL DATABASE SETTINGS ####################################
##########################################################################

elseif(DISP_DB_CONNECT_SETTING == $display)
{


    echo '<input type="hidden" name="fromPanel" value="'.$display.'" />'
    .    '<h2>'
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_DB_CONNECT_SETTING, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_DB_CONNECT_SETTING] ) )
    .    '</h2>'
    .    $msg_no_connection
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Mysql connection parameters').'</legend>' . "\n"
    .    '<p>'
    .    get_lang('Enter the parameters provided by your database server administrator.')
    .    '</p>'
    
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="dbHostForm"><span class="required">*</span> '.get_lang('Database host').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="dbHostForm" name="dbHostForm" value="'.claro_htmlspecialchars($dbHostForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' localhost' . '</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
        
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="dbUsernameForm"><span class="required">*</span> '.get_lang('Database username').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text"  size="30" id="dbUsernameForm" name="dbUsernameForm" value="'.claro_htmlspecialchars($dbUsernameForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' root' . '</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="dbPassForm"><span class="required">*</span> '.get_lang('Database password').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text"  size="30" id="dbPassForm" name="dbPassForm" value="'.claro_htmlspecialchars($dbPassForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' ' . generate_passwd(8) . '</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '</fieldset>' . "\n\n"
    
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Database usage').'</legend>' . "\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<span class="required">*</span> ' . get_lang('Database mode') . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="radio" id="singleDbForm_single" name="singleDbForm" value="1" '.($singleDbForm?'checked':'').' />'
    .    '<label for="singleDbForm_single">' . get_lang('Single') . '</label>' . "\n"
    .    '<br />'
    .    '<input type="radio" id="singleDbForm_multi" name="singleDbForm" value="0" '.($singleDbForm?'':'checked').' />'
    .    '<label for="singleDbForm_multi">' . get_lang('Multi')
    .    '<small>'
    .    '&nbsp;('.get_lang('a database is created at each course creation').')'
    .    '</small>'
    .    '</label>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    .    '</fieldset>' . "\n\n"
    .    '<small>'.get_lang('%requiredMark required field', array('%requiredMark' => '<span class="required">*</span>') ).'</small>' . "\n"
    ;
}     // cmdDB_CONNECT_SETTING


##########################################################################
###### STEP 4 MYSQL DATABASE SETTINGS ####################################
##########################################################################
elseif(DISP_DB_NAMES_SETTING == $display )
{
    echo '<input type="hidden" name="fromPanel" value="' . $display . '" />'  . "\n"
    .    '<h2>'  . "\n"
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_DB_NAMES_SETTING, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_DB_NAMES_SETTING] ) )
    .    '</h2>'  . "\n"
    .    $msg_no_connection . ''  . "\n"
    ;
    
    if( isset($databaseNameValid) && !$databaseNameValid )
    {

        echo '<div class="claroDialogBox boxError">'  . "\n"
        .    '<p>' . "\n"
        .    '<strong>'.get_lang('Error').'</strong> '  . "\n"
        .    ' : ' . get_lang('Database <em>%dbName</em> is not valid.', array('%dbName' => $dbNameForm) ) . "\n"
        .    '</p>' . "\n"
        .    '<ul>'
        .    ($msgErrorDbMain_dbName?'<li>'.get_lang('Main database').'<ul>':'')
        .    ($msgErrorDbMain_dbNameToolLong?'<li>'.get_lang('Database name is too Long'):'')
        .    ($msgErrorDbMain_dbNameInvalid?'<li>'.get_lang('Invalid name. Only letters, digits and _ are allowed'):'')
        .    ($msgErrorDbMain_dbNameBadStart?'<li>'.get_lang('Database name must begin with a letter'):'')
        .    ($msgErrorDbStat_dbName?'</ul><li>'.get_lang('Tracking database').'<ul>':'')
        .    ($msgErrorDbStat_dbNameToolLong?'<li>'.get_lang('Database name is too Long'):'')
        .    ($msgErrorDbStat_dbNameInvalid?'<li>'.get_lang('Invalid name. Only letters, digits and _ are allowed'):'')
        .    ($msgErrorDbStat_dbNameBadStart?'<li>'.get_lang('Database name must begin with a letter'):'')
        .    '</ul>'  . "\n"
        .    '</ul>'  . "\n"
        .    '</div>'  . "\n"
        ;

    }
    
    if ($mainDbNameExist)
    {
        echo '<div class="claroDialogBox boxWarning">'  . "\n"
        .    '<p>' . "\n"
        .    '<strong>'.get_lang('Warning').'</strong>'  . "\n"
        .    ' : ' . get_lang('Database <em>%dbName</em> already exists.', array('%dbName' => $dbNameForm) ) . "\n"
        .    '</p>' . "\n"
        .    '<p>'  . "\n"
        .    get_lang('Claroline will overwrite data previously stored in tables of this database.')  . "\n"
        .    '</p>'  . "\n"
        .    '<p>'  . "\n"
        .    '<input type="checkbox" name="confirmUseExistingMainDb"  id="confirmUseExistingMainDb" value="true" '.($confirmUseExistingMainDb?'checked':'').' />'  . "\n"
        .    '<label for="confirmUseExistingMainDb" >'  . "\n"
        .    '<strong>'.get_lang('I know, I want to use this database as "%fieldname"', array( '%fieldname' => ($singleDbForm ? get_lang('Database name'):get_lang('Main database')) )).'</strong>'  . "\n"
        .    '</label>'  . "\n"
        .    '</p>'  . "\n"
        .    '</div>'  . "\n"
        ;
    }
    
    if (!$singleDbForm && ($statsDbNameExist && $dbStatsForm != $dbNameForm) )
    {
        echo '<div class="claroDialogBox boxWarning">'  . "\n"
        .    '<p>' . "\n"
        .    '<strong>'.get_lang('Warning').'</strong>'  . "\n"
        .    ' : ' . get_lang('Database <em>%dbName</em> already exists.', array('%dbName' => $dbStatsForm) )   . "\n"
        .    '</p>' . "\n"
        .    '<p>'  . "\n"
        .    get_lang('Claroline will overwrite data previously stored in tables of this database.')  . "\n"
        .    '</p>'  . "\n"
        .    '<p>'  . "\n"
        .    '<input type="checkbox" name="confirmUseExistingStatsDb"  id="confirmUseExistingStatsDb" value="true" ' . ($confirmUseExistingStatsDb?'checked':'') . ' />'  . "\n"
        .    '<label for="confirmUseExistingStatsDb" >'  . "\n"
        .    '<strong>'.get_lang('I know, I want to use this database as "%fieldname"', array( '%fieldname' => get_lang('Tracking database'))).'</strong>'  . "\n"
        .    '</label>'  . "\n"
        .    '</p>'  . "\n"
        .    '</div>'  . "\n"
        ;
    }
    
    echo '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Database names').'</legend>' . "\n"
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="dbNameForm"><span class="required">*</span> '.($singleDbForm ? get_lang('Database name'):get_lang('Main database')).'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text"  size="30" id="dbNameForm" name="dbNameForm" value="'.claro_htmlspecialchars($dbNameForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' ' . $dbNameForm . '</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    /*
    Moosh would like to put this in a popup.
    .    (is_array($existingDbs) ? (5 > count($existingDbs) ? '<br/><abbr title="&quot;' . implode('&quot;, &quot;', $existingDbs) . '&quot;" >INFO : Existing databases</abbr>' . "\n"
                                                            : '<br/>INFO : ' . count($existingDbs) . ' databases found<br/><select size="8" ><option>' . implode('</option><option>', $existingDbs) . '</option></select>')
                                 : '')
     */
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="mainTblPrefixForm">'.get_lang('Prefix for main tables').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text"  size="5" id="mainTblPrefixForm" name="mainTblPrefixForm" value="'.claro_htmlspecialchars($mainTblPrefixForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' ' . $mainTblPrefixForm . '</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    ;
    
    if (!$singleDbForm)
    {
        echo '<div class="row">' . "\n"
        .    '<div class="rowTitle">' . "\n"
        .    '<label for="dbStatsForm"><span class="required">*</span> '.get_lang('Tracking database').'</label>' . "\n"
        .    '</div>' . "\n"
        .    '<div class="rowField">' . "\n"
        .    '<input type="text"  size="30" id="dbStatsForm" name="dbStatsForm" value="'.claro_htmlspecialchars($dbStatsForm).'" />' . "\n"
        .    '<span class="example">' . get_lang('e.g.') . ' ' . $dbStatsForm . '</span>' . "\n"
        .    '</div>' . "\n"
        .    '</div>' . "\n\n"
        
        .    '<div class="row">' . "\n"
        .    '<div class="rowTitle">' . "\n"
        .    '<label for="statsTblPrefixForm">'.get_lang('Prefix for tracking tables').'</label>' . "\n"
        .    '</div>' . "\n"
        .    '<div class="rowField">' . "\n"
        .    '<input type="text"  size="5" id="statsTblPrefixForm" name="statsTblPrefixForm" value="'.claro_htmlspecialchars($statsTblPrefixForm).'" />' . "\n"
        .    '<span class="example">' . get_lang('e.g.') . ' ' . $statsTblPrefixForm . '</span>' . "\n"
        .    '</div>' . "\n"
        .    '</div>' . "\n\n"

        .    '<blockquote><small>'  . "\n"
        .    get_lang('Tracking tables are stored by default into the main Claroline database.').'<br />'
        .    get_lang('However, you can record tracking data into a separate database or set a specific prefix for tracking tables.'). "\n"
        .    '</small></blockquote>'  . "\n"
        ;
    }
    
    echo '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="dbPrefixForm">'.($singleDbForm?get_lang('Prefix for course tables'):get_lang('Prefix for course databases')).'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text"  size="30" id="dbPrefixForm" name="dbPrefixForm" value="'.claro_htmlspecialchars($dbPrefixForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' ' . $dbPrefixForm . '</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    ;
    
    if (!$singleDbForm)
    {
        echo '<blockquote>'  . "\n"
        .    '<small>'  . "\n"
        .    '<strong>'  . "\n"
        .    get_lang('A database will be created for each course.') . "\n"
        .    '</strong>'  . "\n"
        .    '<br />'  . "\n"
        .    get_lang('You can choose the prefix that will be used for these databases')  . "\n"
        .    '</small>'  . "\n"
        .    '</blockquote>'  . "\n"
        ;

    }
    
    echo '</fieldset>' . "\n\n"
    .    '<small>'.get_lang('%requiredMark required field', array('%requiredMark' => '<span class="required">*</span>') ).'</small>' . "\n";
    
}     // cmdDB_CONNECT_SETTING

##########################################################################
###### STEP ADMIN SETTINGS ##############################################
##########################################################################
elseif(DISP_ADMINISTRATOR_SETTING == $display )

{
    echo '<input type="hidden" name="fromPanel" value="'.$display.'" />'  . "\n"
    .    '<h2>'  . "\n"
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_ADMINISTRATOR_SETTING, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_ADMINISTRATOR_SETTING] ) )
    .    '</h2>'  . "\n"
    ;
    
    if( is_array($missing_admin_data) || is_array($error_in_admin_data) )
    {
        echo '<div class="claroDialogBox boxError">'  . "\n"
        .    '<p>' . "\n"
        .    '<strong>'.get_lang('Error').'</strong> : '
        .    get_lang('Please enter missing information')
        .    '</p>' . "\n"
        .    '<p>'  . "\n"
        .    ( is_array($missing_admin_data) ? 'Fill in '.implode(', ',$missing_admin_data) .'<br />' : '' )
        .    ( is_array($error_in_admin_data) ? 'Check '.implode(', ',$error_in_admin_data) : '' )
        .    '</p>'  . "\n"
        .    '</div>'  . "\n"
        ;
    }
    
    echo '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Administrator details').'</legend>' . "\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="loginForm"><span class="required">*</span> '.get_lang('Login').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="loginForm" name="loginForm" value="'.claro_htmlspecialchars($loginForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' jdoe</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="passForm"><span class="required">*</span> '.get_lang('Password').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="passForm" name="passForm" value="'.claro_htmlspecialchars($passForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . generate_passwd(8) . '</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="adminEmailForm"><span class="required">*</span> '.get_lang('Email').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="adminEmailForm" name="adminEmailForm" value="'.claro_htmlspecialchars($adminEmailForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' jdoe@mydomain.net</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
        
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="adminNameForm"><span class="required">*</span> '.get_lang('Last name').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="adminNameForm" name="adminNameForm" value="'.claro_htmlspecialchars($adminNameForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' Doe</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="adminSurnameForm"><span class="required">*</span> '.get_lang('First name').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="adminSurnameForm" name="adminSurnameForm" value="'.claro_htmlspecialchars($adminSurnameForm).'" />' . "\n"
    .    '<span class="example">' . get_lang('e.g.') . ' John</span>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '</fieldset>'  . "\n"
    .    '<small>'.get_lang('%requiredMark required field', array('%requiredMark' => '<span class="required">*</span>') ).'</small>' . "\n"
    ;
}

###################################################################
###### STEP CONFIG SETTINGS #######################################
###################################################################

elseif(DISP_PLATFORM_SETTING == $display)
{
    echo '<input type="hidden" name="fromPanel" value="'.$display.'" />' . "\n"
    .    '<h2>' . "\n"
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_PLATFORM_SETTING, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_PLATFORM_SETTING] ) )
    .    '</h2>'  . "\n"
    .    $msg_missing_platform_data . "\n"
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Campus').'</legend>' . "\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="campusForm"><span class="required">*</span> '.get_lang('Name').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="campusForm" name="campusForm" value="'.claro_htmlspecialchars($campusForm).'" />' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="urlForm"><span class="required">*</span> '.get_lang('Absolute URL').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="urlForm" name="urlForm" value="'.claro_htmlspecialchars($urlForm).'" />' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="courseRepositoryForm">'.get_lang('Path to courses repository (relative to the URL above)').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="courseRepositoryForm" name="courseRepositoryForm" value="'.claro_htmlspecialchars($courseRepositoryForm).'" />' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="languageForm"><span class="required">*</span> '.get_lang('Main language').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    claro_html_form_select( 'languageForm'
                               , $language_list
                               , $languageForm
                               , array('id'=>'languageForm')) . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="clmain_serverTimezone"><span class="required">*</span> '.get_lang('Server timezone').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    claro_html_form_select( 'clmain_serverTimezone'
                               , get_timezone_list ()
                               , $clmain_serverTimezone
                               , array('id'=>'clmain_serverTimezone')) . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '</fieldset>' . "\n\n"
    
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('User').'</legend>' . "\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<span class="required">*</span> ' . "\n"
    .    get_lang('Self-registration') . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="radio" id="allowSelfReg_1" name="allowSelfReg" value="1" ' . ($allowSelfReg?'checked':'') . ' />' . "\n"
    .    '<label for="allowSelfReg_1">'.get_lang('Enabled').'</label>' . "\n"
    .    '<br />' . "\n"
    .    '<input type="radio" id="allowSelfReg_0" name="allowSelfReg" value="0" '.($allowSelfReg?'':'checked').' />' . "\n"
    .    '<label for="allowSelfReg_0">'.get_lang('Disabled').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<span class="required">*</span> ' . "\n"
    .    get_lang('Password storage') . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="radio" name="encryptPassForm" id="encryptPassForm_0" value="0"  '.($encryptPassForm?'':'checked') . ' />' . "\n"
    .    '<label for="encryptPassForm_0">'.get_lang('Clear text').'</label>' . "\n"
    .    '<br />' . "\n"
    .    '<input type="radio" name="encryptPassForm" id="encryptPassForm_1" value="1" ' . ($encryptPassForm?'checked':'') . ' />' . "\n"
    .    '<label for="encryptPassForm_1">'.get_lang('Encrypted').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
   
    .    '</fieldset>' . "\n"
    .    '<small>'.get_lang('%requiredMark required field', array('%requiredMark' => '<span class="required">*</span>') ).'</small>' . "\n"
    ;
}
###################################################################
###### STEP CONFIG SETTINGS #######################################
###################################################################
elseif(DISP_ADMINISTRATIVE_SETTING == $display)
{
    echo '<input type="hidden" name="fromPanel" value="' . $display . '" /><h2>'
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_ADMINISTRATIVE_SETTING, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_ADMINISTRATIVE_SETTING] ) )
    .    '</h2>'  . "\n"
    .    $msg_missing_administrative_data
    
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Related organization').'</legend>' . "\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="institutionForm">'.get_lang('Institution name').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="institutionForm" name="institutionForm" value="'.claro_htmlspecialchars($institutionForm) . '" />' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="institutionUrlForm">'.get_lang('Institution URL').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="institutionUrlForm" name="institutionUrlForm" value="'.claro_htmlspecialchars($institutionUrlForm) . '" />' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"

    .    '</fieldset>' . "\n\n"
    
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Campus contact').'</legend>' . "\n"

    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="contactNameForm"><span class="required">*</span> '.get_lang('Contact name').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="contactNameForm" name="contactNameForm" value="'.claro_htmlspecialchars($contactNameForm) . '"/>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="contactEmailForm"><span class="required">*</span> '.get_lang('Contact email').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="contactEmailForm" name="contactEmailForm" value="'.claro_htmlspecialchars($contactEmailForm) . '"/>' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '<div class="row">' . "\n"
    .    '<div class="rowTitle">' . "\n"
    .    '<label for="contactPhoneForm">'.get_lang('Contact phone').'</label>' . "\n"
    .    '</div>' . "\n"
    .    '<div class="rowField">' . "\n"
    .    '<input type="text" size="30" id="contactPhoneForm" name="contactPhoneForm" value="'.claro_htmlspecialchars($contactPhoneForm) . '" />' . "\n"
    .    '</div>' . "\n"
    .    '</div>' . "\n\n"
    
    .    '</fieldset>' . "\n"
    .    '<small>'.get_lang('%requiredMark required field', array('%requiredMark' => '<span class="required">*</span>') ).'</small>' . "\n"
    ;
}

###################################################################
###### STEP LAST CHECK BEFORE INSTALL #############################
###################################################################
elseif(DISP_LAST_CHECK_BEFORE_INSTALL == $display )
{
    $pathForm = str_replace("\\\\", "/", $pathForm);
    //echo "pathForm $pathForm";
    echo '<input type="hidden" name="fromPanel" value="'.$display . '" />' . "\n"
    .    '<h2>'
    .    get_lang('Step %step of %nb_step : %step_name', array( '%step' => array_search(DISP_LAST_CHECK_BEFORE_INSTALL, $panelSequence)+1 ,
                                                                '%nb_step' => count($panelSequence) ,
                                                                '%step_name' => $panelTitle[DISP_LAST_CHECK_BEFORE_INSTALL] ) )
    .    '</h2>' . "\n"
    .    '<p>' . "\n"
    .    get_lang('Please check the values you entered.') . '<br />' . "\n"
    .    get_lang('Print this page to keep your administrator password and other settings.') . "\n"
    .    '</p>' . "\n"
    .    '<fieldset>' . "\n"
    .    '<legend>'.$panelTitle[DISP_DB_CONNECT_SETTING] .'</legend>' . "\n"

    .    '<table class="checkList">' . "\n\n"
    .    '<tr class="checkSubTitle">'
    .    '<th colspan="2">' . get_lang('Mysql connection parameters') . '</th>'
    .    '</tr>' . "\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Database host') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($dbHostForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
        
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Database username') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($dbUsernameForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
        
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Database password') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars((empty($dbPassForm) ? '--empty--' : $dbPassForm))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
    
    .    '</table>' . "\n\n"
    
    .    '<table class="checkList">' . "\n\n"
    .    '<tr class="checkSubTitle">'
    .    '<th colspan="2">' . get_lang('Database usage') . '</th>'
    .    '<tr>' . "\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Database mode') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    ($singleDbForm ? get_lang('Single') : get_lang('Multi'))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
   
    .    '</table>' . "\n\n"
    .    '</fieldset>' . "\n"
    
    .    '<fieldset>' . "\n"
    .    '<legend>'.$panelTitle[DISP_DB_NAMES_SETTING] .'</legend>' . "\n"
    
    .    '<table class="checkList">' . "\n\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Main database') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($dbNameForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Tracking database') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($dbStatsForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
    
    .    '</table>' . "\n\n"
    
    .    '<table class="checkList">' . "\n\n"
    .    '<tr class="checkSubTitle">'
    .    '<th colspan="2">' . get_lang('Table prefixes') . '</th>'
    .    '<tr>' . "\n"
      ;
      
    if ( '' != $mainTblPrefixForm )
    {
        echo '<tr class="check">' . "\n"
        .    '<td class="checkTitle">' . "\n"
        .    get_lang('Main tables') . ' : ' . "\n"
        .    '</td>' . "\n"
        .    '<td class="checkValue">' . "\n"
        .    claro_htmlspecialchars($mainTblPrefixForm)
        .    '</td>' . "\n"
        .    '</tr>' . "\n\n"
        ;
    }

    if ( '' != $statsTblPrefixForm )
    {
        echo '<tr class="check">' . "\n"
        .    '<td class="checkTitle">' . "\n"
        .    get_lang('Tracking tables') . ' : ' . "\n"
        .    '</td>' . "\n"
        .    '<td class="checkValue">' . "\n"
        .    claro_htmlspecialchars($statsTblPrefixForm)
        .    '</td>' . "\n"
        .    '</tr>' . "\n\n"
        ;
    }
    
    if ( '' != $dbPrefixForm )
    {
        echo '<tr class="check">' . "\n"
        .    '<td class="checkTitle">' . "\n"
        .    get_lang('Course databases') . ' : ' . "\n"
        .    '</td>' . "\n"
        .    '<td class="checkValue">' . "\n"
        .    claro_htmlspecialchars($dbPrefixForm)
        .    '</td>' . "\n"
        .    '</tr>' . "\n\n"
        ;
    }
        
    echo '</table>' . "\n\n"
    .    '</fieldset>' . "\n"

    .    '<fieldset>' . "\n"
    .    '<legend>'.$panelTitle[DISP_ADMINISTRATOR_SETTING].'</legend>' . "\n"
    
    .    '<table class="checkList">' . "\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle notehis">' . "\n"
    .    get_lang('Login') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($loginForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle notethis">' . "\n"
    .    get_lang('Password') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars((empty($passForm)?'--'.get_lang('empty').'-- <strong>&lt;-- '.get_lang('Error').'</strong>':$passForm))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Email') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($adminEmailForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Last name') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($adminNameForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('First name') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($adminSurnameForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
   
    .    '</table>' . "\n\n"
    
    .    '</fieldset>' . "\n"

    .    '<fieldset>' . "\n"
    .    '<legend>'.$panelTitle[DISP_PLATFORM_SETTING].'</legend>' . "\n"
    
    .    '<table class="checkList">' . "\n\n"
    .    '<tr class="checkSubTitle">'
    .    '<th colspan="2">' . get_lang('Campus') . '</th>'
    .    '<tr>' . "\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Campus name') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($campusForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Campus URL') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    (empty($urlForm)?'--'.get_lang('empty').'--':$urlForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Main language') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    ucwords($languageForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
        
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Server timezone') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    ucwords($clmain_serverTimezone)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
    
    .    '</table>' . "\n\n"
    
    .    '<table class="checkList">' . "\n\n"
    .    '<tr class="checkSubTitle">'
    .    '<th colspan="2">' . get_lang('Users') . '</th>'
    .    '<tr>' . "\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Self-registration') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    ($allowSelfReg? get_lang('Enabled') : get_lang('Disabled'))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Password storage') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    ($encryptPassForm ? get_lang('Encrypted') : get_lang('Clear text'))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
    
    .    '</table>' . "\n\n"

    .    '</fieldset>' . "\n"
    
    
    .    '<fieldset>' . "\n"
    .    '<legend>'. get_lang('Additional Information') . '</legend>' . "\n"
    
    .    '<table class="checkList">' . "\n\n"
    .    '<tr class="checkSubTitle">'
    .    '<th colspan="2">' . get_lang('Related organization') . '</th>'
    .    '<tr>' . "\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Institution name') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars((empty($institutionForm)?'--'.get_lang('empty').'--':$institutionForm))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Institution URL') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    (empty($institutionUrlForm)?'--'.get_lang('empty').'--':$institutionUrlForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
    
    .    '</table>' . "\n\n"
    
    .    '<table class="checkList">' . "\n\n"
    .    '<tr class="checkSubTitle">'
    .    '<th colspan="2">' . get_lang('Campus contact') . '</th>'
    .    '<tr>' . "\n"
    
    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Contact name') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars((empty($contactNameForm)?'--'.get_lang('empty').'--':$contactNameForm))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Contact email') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars((empty($contactEmailForm)?$adminEmailForm:$contactEmailForm))
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"

    .    '<tr class="check">' . "\n"
    .    '<td class="checkTitle">' . "\n"
    .    get_lang('Contact phone') . ' : ' . "\n"
    .    '</td>' . "\n"
    .    '<td class="checkValue">' . "\n"
    .    claro_htmlspecialchars($contactPhoneForm)
    .    '</td>' . "\n"
    .    '</tr>' . "\n\n"
    
    .    '</table>' . "\n\n"
    .    '</fieldset>' . "\n"
    ;

}

###################################################################
###### DB NAME ERROR !#########################################
###################################################################

elseif($display==DISP_DB_NAMES_SETTING_ERROR)
{
    echo '<input type="hidden" name="fromPanel" value="' . $display . '" />' . "\n"
    .    '<h2>'.get_lang('Installation failed').'</h2>';
    
    if ( $mainDbNameExist || $statsDbNameExist )
    {
        echo "<hr />";
        if ($mainDbNameExist)
            echo '<div class="claroDialogBox boxWarning">' . "\n"
            .    '<strong>'.get_lang('Warning') . '</strong>' . "\n"
            .    ' : '.get_lang('Database <em>%dbName</em> already exists.', array('%dbName' => $dbNameForm)) . '<br />' . "\n"
            .    '<input type="checkbox" name="confirmUseExistingMainDb"  id="confirmUseExistingMainDb" value="true" '.($confirmUseExistingMainDb?'checked':'').' />' . "\n"
            .    '<label for="confirmUseExistingMainDb" >'.get_lang('I know, I want to use this database as "%fieldname"', array( '%fieldname' => get_lang('Main database'))).'</label>' . "\n"
            .    '</div>'
            ;
        if ($statsDbNameExist && $dbStatsForm!=$dbNameForm)
            echo '<div class="claroDialogBox boxWarning">' . "\n"
            .    '<strong>'.get_lang('Warning').'</strong>' . "\n"
            .    ' : '.get_lang('Database <em>%dbName</em> already exists.', array('%dbName' => $dbStatsForm)) . '<br />' . "\n"
            .    '<br />' . "\n"
            .    '<input type="checkbox" name="confirmUseExistingStatsDb"  id="confirmUseExistingStatsDb" value="true" '.($confirmUseExistingStatsDb?'checked':'') . ' />' . "\n"
            .    '<label for="confirmUseExistingStatsDb" >'.get_lang('I know, I want to use this database as "%fieldname"', array( '%fieldname' => get_lang('Tracking database'))).'</label><br />' . "\n"
            .    '</div>'
            ;
        echo '<p>' . "\n"
        .    get_lang('or') . ' <input type="submit" name="cmdDbNameSetting" value="'.get_lang('Change database names').'" />' . "\n"
        .    '</p>' . "\n"
        .    '<hr />'
        ;
    }

    if( $mainDbNameCreationError )
    {
        echo '<br />' . $mainDbNameCreationError;
    }

    echo '<p align="right">' . "\n"
    .    '<input type="submit" name="alreadyVisited" value="|&lt; '.get_lang('Start again from the beginning').'" />' . "\n"
    .    '<input type="submit" name="cmdDoInstall" value="'.get_lang('Retry').'" />' . "\n"
    .    '</p>'
    ;
}

###################################################################
###### INSTALL INCOMPLETE!#########################################
###################################################################

elseif(DISP_RUN_INSTALL_NOT_COMPLETE == $display)
{
    echo '<input type="hidden" name="fromPanel" value="'.$display.'" />'
    .    '<h2>'.get_lang('Installation failed').'</h2>';
    
    if($mainDbNameCreationError)
    {
        echo '<br />'.$mainDbNameCreationError;
    }
    
    if($statsDbNameCreationError)
    {
        echo '<br />'.$statsDbNameCreationError;
    }
    
    if($fileAccessInLangRepositoryCreationError)
    {
        echo '<br />'
        .     get_lang('Error when creating file <em>%htAccessName</em> in %htAccessLangPath',
                    array('%htAccessName'=>$htAccessName, '%htAccessLangPath' => realpath($htAccessLangPath)))
        .     '<br />';
    }
    
    if($fileAccessInSqlRepositoryCreationError)
    {
        echo '<br />'
        .     get_lang('Error when creating file <em>%htAccessName</em> in %htAccessLangPath',
                    array('%htAccessName'=> $htAccessName, '%htAccessLangPath' => realpath($htAccessSqlPath)))
        .     '<br />';
    }
    
    if ($configError)
    {
        if(is_array($messageConfigErrorList) && !empty($messageConfigErrorList) )
        {
            echo '<br />'.get_lang('Error when creating configuration files').'<ul>';

            foreach($messageConfigErrorList as $messageConfigError)
            {
                echo '<li><strong>'. $messageConfigError . '</strong></li>';
            }
            echo '</ul>';
        }
        else
        {
            echo '<br />' . "\n"
            .    get_lang('Unhandled error during the creation of configuration files')
            ;
        }

    }

    if ($coursesRepositorySysMissing)
    {
        echo '<br />'
        .    get_lang('%path is missing', array('%path' => $coursesRepositorySys ) ) . "\n"
        ;
    }

    if ($coursesRepositorySysWriteProtected)
    {
        echo '<br />'
        .    get_lang('Claroline cannot write to %path', array('%path' => $coursesRepositorySys) ) . "\n"
        ;
    }

    if ($garbageRepositorySysMissing)
    {
        echo '<br />'
        .    get_lang('%path is missing', array('%path' => $garbageRepositorySys ) ) . "\n"
        ;
    }

    if ($garbageRepositorySysWriteProtected)
    {
        echo '<br />'
        .    get_lang('Claroline cannot write to %path', array('%path' => $garbageRepositorySys ) ) . "\n"
        ;
    }

    if ($platformConfigRepositorySysMissing)
    {
        echo '<br />'
        .    get_lang('%path is missing', array('%path' => claro_get_conf_repository() ) ) . "\n"
        ;
    }

    if ($platformConfigRepositorySysWriteProtected)
    {
        echo '<br />'
        .    get_lang('Claroline cannot write to %path', array('%path' => claro_get_conf_repository() ) ) . "\n"
        ;
    }


    echo get_lang('Write problems can come from two possible causes') .' :<br />' . "\n"
    .    '<ul>' . "\n"
    .    '<li>' . "\n"
    .    get_lang('Permission problems.') . '<br />' . "\n"
    .    get_lang('Try initially with <em>chmod 777 -R</em> and increase restrictions gradually.') . "\n"
    .    '</li>' . "\n"
    .    '<li>' . "\n"
    .    get_lang('PHP is running in <a href="http://www.php.net/manual/en/features.safe-mode.php" target="_phpman">SAFE MODE</a>.') . '<br />' . "\n"
    .    get_lang('If possible, try to switch it off.') . "\n"
    .    '</li>' . "\n"
    .    '</ul>' . "\n"
    ;

    echo '<p align="right">'
    .    '<input type="submit" name="alreadyVisited" value="'.get_lang('Start again from the beginning').'" />' . "\n"
    .    '<input type="submit" name="cmdDoInstall" value="'.get_lang('Retry').'" />' . "\n"
    .    '</p>'
    ;

}

###################################################################
###### STEP RUN_INSTALL_COMPLETE !#################################
###################################################################
elseif(DISP_RUN_INSTALL_COMPLETE == $display)
{

    echo '<h2>'
    .    $panelTitle[DISP_RUN_INSTALL_COMPLETE]
    .    '</h2>' . "\n"
    .    '<div class="claroDialogBox boxWarning">'
    .    '<p>'
    .     '<strong>'.get_lang('Warning').'</strong>' . "\n"
    .    ' : ' . get_lang('We highly recommend that you <strong>protect or remove the <em>/claroline/install/</em> directory</strong>.') . "\n"
    .    '</p>'
    .    '</div>' . "\n"
    .    '<fieldset>' . "\n"
    .    '<legend>'.get_lang('Do not forget to').'</legend>'
    .    '<ul>'
    .    '<li>'
    .    get_lang('Tune your platform configuration in %administration | %configuration', array('%administration' => get_lang('Administration'),'%configuration' => get_lang('Configuration'))) . "\n"
    .    '</li>'
    .    '<li>'
    .    get_lang('Build your course category tree in %administration | %manage course categories', array('%administration' => get_lang('Administration'),'%manage course categories' => get_lang('Manage course categories'))) . "\n"
    .    '</li>'
    .    '<li>'
    .    get_lang('Edit or clear text zones in %administration | %edit text zones', array('%administration' => get_lang('Administration'),'%edit text zones' => get_lang('Edit text zones'))) . "\n"
    .    '</li>'
    .    '</ul>' . "\n"
    .    '</fieldset>' . "\n"
    .    '<div id="goToCampusLink">' . "\n"
    .    '<a href="../../index.php">'.get_lang('Go to your brand new campus').'</a>' . "\n"
    .    '</div>' . "\n"
    ;
}    // STEP RUN_INSTALL_COMPLETE

else
{
    echo get_lang('Unhandled error') . '<br />' . "\n"
    .    '<br />' . "\n"
    .    get_lang('Please report and explain this issue on <a href="%forumUrl">Claroline\'s support forums</a>',
            array('%forumUrl' => 'http://forum.claroline.net') )
    ;
}

// navigation buttons
$htmlNextPrevButton = '<div id="navigation">'  . "\n"
.    '<div id="navToNext">'  . "\n"
;

if( !is_null($stepPos) && $stepPos !== false && ($stepPos+1 < count($panelSequence)) )
{
    $htmlNextPrevButton .= '<input type="submit" name="' . $cmdName[$panelSequence[$stepPos+1]] . '" value="'.get_lang('Next') .' &gt; " '. ( $nextStepDisable ? 'disabled="disabled"' : '' ) .' />'. "\n";
}
elseif( DISP_LAST_CHECK_BEFORE_INSTALL == $display )
{
    $htmlNextPrevButton .= '<input type="submit" name="cmdDoInstall" value="'.get_lang('Install Claroline') .'" />'. "\n";
}

$htmlNextPrevButton .= '</div>' . "\n"
.    '<div id="navToPrev">'  . "\n"
.    (!is_null($stepPos) && $stepPos !== false && ( $stepPos > 0 ) ? '<input type="submit" name="' . $cmdName[$panelSequence[$stepPos-1]] . '" value="&lt; '.get_lang('Back') .'" />' :'')
.    '</div>' . "\n"
.    '</div>' . "\n"
;

echo $htmlNextPrevButton;
?>
</div><!-- end panel -->
</form>
</div><!--  end installBody -->
</div><!--  end installContainer -->
<div id="footer">
    <hr />
    <div id="footerLeft">
        <a href="http://www.claroline.net">http://www.claroline.net</a>
    </div>
    
    
    <div id="footerRight">
    <?php get_lang('For help ask on %clarolineForumLink', array('%clarolineForumLink' => '<a href="http://forum.claroline.net" target="_blank">http://forum.claroline.net</a>')); ?>
    </div>
    
    
    <div id="footerCenter">
    <?php echo get_lang('Powered by %clarolineLink', array('%clarolineLink' => '<a href="http://www.claroline.net">Claroline</a>')); ?> &copy; 2001 - 2011
    </div>
    
    </div>
</div>

</div><!-- end claroPage -->
</body>
</html>
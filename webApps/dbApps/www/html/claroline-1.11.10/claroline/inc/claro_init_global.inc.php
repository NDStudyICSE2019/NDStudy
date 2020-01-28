<?php // $Id: claro_init_global.inc.php 14721 2014-02-18 07:01:21Z zefredz $

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

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 14721 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLKERNEL
 * @author      Claro Team <cvs@claroline.net>
 */

// The CLARO_INCLUDE_ALLOWED constant allows to include PHP file further in the
// code. Files which are meant to be included check if this constant is defined.
// If it isn't the case, these files immediately die.
// This process prevents hacking by direct calls of included file and setting
// of global variable (when PHP register_globals is set to 'ON')

define('CLARO_INCLUDE_ALLOWED', true);

// include the main Claroline platform configuration file

// Determine the directory path where this current file lies
// This path will be useful to include the other intialisation files

$mainConfigurationFile = __DIR__ . '/../../platform/conf/claro_main.conf.php';

if ( file_exists($mainConfigurationFile) )
{
    include $mainConfigurationFile;
}
else
{
    die ('<center>'
       .'WARNING ! SYSTEM UNABLE TO FIND CONFIGURATION SETTINGS.'
       .'<p>'
       .'If it is your first connection to your Claroline platform, '
       .'read thoroughly INSTALL.txt file provided in the Claroline package.'
       .'</p>'
       .'</center>');
}

if ( !empty($GLOBALS['clmain_serverTimezone']) )
{
    ini_set('date.timezone', $GLOBALS['clmain_serverTimezone']);
    date_default_timezone_set($GLOBALS['clmain_serverTimezone']);
}

require_once  __DIR__ . '/lib/claro_main.lib.php';

$_SERVER['PHP_SELF'] = php_self();

// Most PHP package has increase the error reporting.
// The line below set the error reporting to the most fitting one for Claroline
if( claro_debug_mode() )
{
    // Make sure all errors are reported
    error_reporting( E_ALL );
    
    // Activate assertions
    assert_options(ASSERT_ACTIVE, 1);
    assert_options(ASSERT_WARNING, 0);
    assert_options(ASSERT_QUIET_EVAL, 1);
    assert_options(ASSERT_CALLBACK, 'claro_debug_assertion_handler');
}

/*----------------------------------------------------------------------
  Various Path Init
  ----------------------------------------------------------------------*/

$GLOBALS['includePath']            = get_path('incRepositorySys');
$GLOBALS['clarolineRepositorySys'] = get_path('clarolineRepositorySys');
$GLOBALS['clarolineRepositoryWeb'] = get_path('clarolineRepositoryWeb');
$GLOBALS['coursesRepositorySys']   = get_path('coursesRepositorySys');
$GLOBALS['coursesRepositoryWeb']   = get_path('coursesRepositoryWeb');
$GLOBALS['rootAdminWeb']           = get_path('rootAdminWeb');
$GLOBALS['imgRepositoryAppend']    = get_path('imgRepositoryAppend');
$GLOBALS['imgRepositorySys']       = get_path('imgRepositorySys');
$GLOBALS['imgRepositoryWeb']       = get_path('imgRepositoryWeb');

/*
 * Path to the PEAR library. PEAR stands for "PHP Extension and Application
 * Repository". It is a framework and distribution system for reusable PHP
 * components. More on http://pear.php.net.
 * Claroline is provided with the basic PEAR components needed by the
 * application in the "claroline/inc/lib/pear" directory. But, server
 * administator can redirect to their own PEAR library directory by setting
 * its path to the PEAR_LIB_PATH constant.x
 */

define('PEAR_LIB_PATH', get_path('incRepositorySys') . '/lib/thirdparty/pear');

// Add the Claroline PEAR path to the php.ini include path
// This action is mandatory because PEAR inner include() statements
// rely on the php.ini include_path settings

set_include_path( '.' . PATH_SEPARATOR . PEAR_LIB_PATH . PATH_SEPARATOR . get_include_path() );

// Unix file permission access ...

define('CLARO_FILE_PERMISSIONS', 0777);

// Web server

$GLOBALS['is_IIS'] = strstr($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') ? 1 : 0;
$GLOBALS['is_Apache'] = strstr($_SERVER['SERVER_SOFTWARE'], 'Apache') ? 1 : 0;
$GLOBALS['is_Apache2'] = strstr($_SERVER['SERVER_SOFTWARE'], 'Apache/2') ? 1 : 0;

// Compatibility with IIS web server - REQUEST_URI

if ( !isset($_SERVER['REQUEST_URI']) )
{
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
    if ( !empty($_SERVER['QUERY_STRING']) )
    {
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
}

/*----------------------------------------------------------------------
  Start session
  ----------------------------------------------------------------------*/

session_name(get_conf('platform_id','claroline'));

session_start();

if ( get_conf('triggerDebugMode', false) &&  isset($_REQUEST['debug']) )
{
    if ( !claro_debug_Mode() &&  $_REQUEST['debug'] == 'on' )
    {
        $_SESSION['claro_debug_mode'] = true;
        
        error_reporting( E_ALL );
        
        // Activate assertions
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_QUIET_EVAL, 1);
        assert_options(ASSERT_CALLBACK, 'claro_debug_assertion_handler');
    }
    elseif ( $_REQUEST['debug'] == 'off' )
    {
        $_SESSION['claro_debug_mode'] = false;
    }
}


/*----------------------------------------------------------------------
  Include main library
  ----------------------------------------------------------------------*/

require_once get_path('incRepositorySys') . '/lib/language.lib.php';
require_once get_path('incRepositorySys') . '/lib/right/right_profile.lib.php';

if( claro_debug_mode() )
{
    require_once get_path('incRepositorySys') . '/lib/debug.lib.inc.php';
}

/*----------------------------------------------------------------------
  Unquote GET, POST AND COOKIES if magic quote gpc is enabled in php.ini
  ----------------------------------------------------------------------*/

claro_unquote_gpc();

/*----------------------------------------------------------------------
  Connect to the server database and select the main claroline DB
  ----------------------------------------------------------------------*/

FromKernel::uses('core/claroline.lib');

try
{
    // Initialize the main database connection
    Claroline::initMainDatabase();
}
catch ( Exception $e )
{
    if ( claro_debug_mode() ) 
    {
        $details = '<pre>' . var_export( $e->__toString(), true ) . '</pre>';
    }
    else
    {
        $details = '';
    }
    
    die ('<center>'
        .$e->getMessage()
        . $details
        .'</center>');
}

/*----------------------------------------------------------------------
  Include the local (contextual) parameters of this course or section
  ----------------------------------------------------------------------*/

require get_path('incRepositorySys') . '/claro_init_local.inc.php';

/*----------------------------------------------------------------------
  Load language translation and locale settings
  ----------------------------------------------------------------------*/

language::load_translation();
language::load_locale_settings();
language::load_module_translation();

// set the mysql connexion for the course !!! does not work ignored by mysql :(
// I'm afraid this will not be possible if we don't reset the connection
// We could solve this when the charset is in the session (since this will allow
// us to change the charset while initialising the database), but as a 
// consequence this will not work onn the first access to the course.
/*
Claroline::getDatabase()->setCharset( strtolower(get_locale('charset')) );
pushClaroMessage( get_locale('charset') . ':' . Claroline::getDatabase ()->getCharset (),'debug' );
pushClaroMessage( Claroline::getDatabase ()->getCharset (),'debug' );
 */

// Initialize the claroline display
Claroline::initDisplay();
// Assign the Claroline singleton to a variable for more convenience
// for legacy code, it's far better to use Claroline class static methods.
$claroline = Claroline::getInstance();

/*===========================================================================
                     Load configuration files
 ===========================================================================*/

// Course tools
if (isset($_cid) && $_courseTool['label'])
{
    $config_code = rtrim($_courseTool['label'],'_');

    if (file_exists(claro_get_conf_repository() . $config_code . '.conf.php'))
    {
        include claro_get_conf_repository() . $config_code . '.conf.php';
        pushClaroMessage("Loading configuration file "
            . claro_get_conf_repository() . $config_code
            . '.conf.php','debug');
    }

    if ( claro_is_in_a_course()
        && file_exists( get_conf('coursesRepositorySys')
            . $_course['path'] . '/conf/' . $config_code . '.conf.php' ) )
    {
        require get_conf('coursesRepositorySys') . $_course['path']
            . '/conf/' . $config_code . '.conf.php';

        pushClaroMessage("Loading configuration file "
            . get_conf('coursesRepositorySys') . $_course['path']
            . '/conf/' . $config_code . '.conf.php', 'debug');
    }
}
// Other modules
elseif ( $tlabelReq )
{
    $config_code = rtrim($tlabelReq,'_');

    if (file_exists(claro_get_conf_repository() . $config_code . '.conf.php'))
    {
        include claro_get_conf_repository() . $config_code . '.conf.php';

        pushClaroMessage("Loading configuration file "
            . claro_get_conf_repository() . $config_code
            . '.conf.php','debug');
    }
}

if ( isset( $tlabelReq ) && !empty( $tlabelReq ) )
{
    /*----------------------------------------------------------------------
        Check tool access right an block unautorised users
    ----------------------------------------------------------------------*/
    
    if ( claro_is_course_required() && !claro_is_in_a_course() )
    {
        claro_disp_auth_form(true);
    }
    
    if ( get_module_data( $tlabelReq, 'type' ) == 'admin' && ! claro_is_platform_admin() )
    {
        if ( !claro_is_user_authenticated() )
        {
            claro_disp_auth_form();
        }
        else
        {
            claro_die(get_lang('Not allowed'));
        }
    }
    
    if ( get_module_data( $tlabelReq, 'type' ) == 'crsmanage' 
        && ! ( claro_is_course_manager() || claro_is_platform_admin() ) )
    {
        if ( !claro_is_user_authenticated() )
        {
            claro_disp_auth_form(true);
        }
        else
        {
            claro_die(get_lang('Not allowed'));
        }
    }
    
    if ( $tlabelReq !== 'CLWRK' && $tlabelReq !== 'CLGRP' && ! claro_is_module_allowed()
        && ! ( isset($_SESSION['inPathMode']) && $_SESSION['inPathMode'] 
        && ( $tlabelReq == 'CLQWZ' || $tlabelReq == 'CLDOC') ) ) // WORKAROUND FOR OLD LP
    {
        if ( ! claro_is_user_authenticated() )
        {
            claro_disp_auth_form(true);
        }
        else
        {
            claro_die( get_lang( 'Not allowed' ) );
        }
    }
    
    if ( $tlabelReq !== 'CLGRP'
        && $tlabelReq !== 'CLWRK'
        && claro_is_in_a_group()
        && ( !claro_is_group_allowed()
        || ( !claro_is_allowed_to_edit()
            && !is_tool_activated_in_groups($_cid, $tlabelReq) ) ) )
    {
        claro_die( get_lang( 'Not allowed' ) );
    }

    /*----------------------------------------------------------------------
        Install module
    ----------------------------------------------------------------------*/
    if ( claro_is_in_a_course()
        && ! is_module_installed_in_course( $tlabelReq, claro_get_current_course_id() ) )
    {
        install_module_database_in_course( $tlabelReq, claro_get_current_course_id() ) ;
    }
}

/*----------------------------------------------------------------------
  Context from URL
  ----------------------------------------------------------------------*/
// if page is called from another tool ... (from LP for an example)
if ( isset($_REQUEST['calledFrom']) )
{
    $calledFrom = $_REQUEST['calledFrom'];
}
else
{
    $calledFrom = false;
}

// if page is embedded hide banner and footer
if ( isset($_REQUEST['embedded']) && $_REQUEST['embedded'] == 'true' )
{
    // old school method
    $GLOBALS['hide_banner'] = true;
    $GLOBALS['hide_footer'] = true;
    
    // fashion victim method
    $claroline->setDisplayType(Claroline::FRAME);
}
/*----------------------------------------------------------------------
  Initialize the event manager declarations for the notification system
  ----------------------------------------------------------------------*/

// for backward compatibility
$GLOBALS['eventNotifier'] = $claroline->notifier;
$GLOBALS['claro_notifier'] = $claroline->notification;


// Register listener in the event manager for the NOTIFICATION system :
// EXAMPLE :
//
//  $claroline->notification->addListener( 'document_visible', 'update' );
//
// 'document_visible' is the name of the event that you want to track
// 'update' is the name of the function called in the listener class when the event happens

// register listener for access to platform
$claroline->notification->addListener( 'platform_access', 'trackPlatformAccess');
// todo move this to a better place ? like end of script ?
$claroline->notifier->event( 'platform_access' );

// we must register this listener here else it will not be registered when 'inscription login' will occur
$claroline->notification->addListener( 'user_login', 'trackInPlatform' );

if ( claro_is_user_authenticated() )
{
   //global events (can happen outside of courses too)

   $claroline->notification->addListener( 'course_deleted', 'modificationDelete' );
}

if ( claro_is_user_authenticated() && claro_is_in_a_course() )
{
    //global events IN COURSE only

    $claroline->notification->addListener( 'toollist_changed', 'modificationDefault' );
    $claroline->notification->addListener( 'introsection_modified', 'modificationDefault' );

    $claroline->notification->addListener( 'course_access', 'trackCourseAccess' );
    // todo : should move this event to initialisation of course context
    $claroline->notifier->event( 'course_access' );
}

if ( claro_is_in_a_group() )
{
    $claroline->notification->addListener( 'group_deleted', 'modificationDelete' );
}

if ( claro_is_in_a_tool() )
{
    // generic tool event
    $claroline->notification->addListener( 'tool_access', 'trackToolAccess' );
    // todo : should move this event to initialisation of tool context
    $claroline->notifier->event( 'tool_access' );

    // others
    load_current_module_listeners();

}

/*----------------------------------------------------------------------
  Prevent duplicate form submission
  ----------------------------------------------------------------------*/

// The code below is a routine to prevent duplicate form submission, for
// example if the user clicks on the 'Refresh' or 'Back' button of his
// browser. It will nullify all the variables posted to the server by the
// form, provided this form complies to 2 points :
//
// 1. The form is submitted by POST method (<form method="post">). GET
// method is not taken into account.
//
// 2. A unique ID value is provided at form submission that way
//
//    <input type="hidden" name="claroFormId" value="< ?php echo uniqid(''); ? >">
//
// The routine records in PHP session all the the ID of the submitted
// forms. Once a form is submitted, its ID is compared to recorded ID, to
// check if the form hasn't be posted before.
//
// One can set a limit to the stored ID in session by adapting the
// CLARO_MAX_REGISTERED_FORM_ID constant.

define('CLARO_MAX_REGISTERED_FORM_ID', 50);

if ( claro_is_user_authenticated() )
{
    if ( empty( $_SESSION['csrf_token'] ) || !isset( $_SESSION['csrf_token'] ) )
    {
        $_SESSION['csrf_token'] = strrev(md5(time()));
    }
    
    if ( defined('CSRF_PROTECTED') && CSRF_PROTECTED )
    {
        if ( $_POST )
        {
            if ( !isset( $_POST['csrf_token'] ) || ( $_POST['csrf_token'] != $_SESSION['csrf_token'] ) )
            {
                claro_die( get_lang("Not Allowed !") );
                exit();
            }
        }
    }
}

if ( isset($_POST['claroFormId']) )
{
    if ( ! isset($_SESSION['claroFormIdList']) )
    {
        $_SESSION['claroFormIdList'] = array( $_POST['claroFormId'] );
    }
    elseif ( in_array($_POST['claroFormId'], $_SESSION['claroFormIdList']) )
    {
        foreach( $_POST as $thisPostKey => $thisPostValue )
        {
            $_REQUEST[$thisPostKey] = null;
        }

        $_POST = array();
    }
    else
    {
         $claroFormIdListCount = array_unshift($_SESSION['claroFormIdList'],
                                               $_POST['claroFormId']         );

         if ( $claroFormIdListCount > CLARO_MAX_REGISTERED_FORM_ID )
         {
            array_pop( $_SESSION['claroFormIdList'] );
         }
    }
}

/*----------------------------------------------------------------------
  Load default javascript libraries
 ----------------------------------------------------------------------*/

JavascriptLoader::getInstance()->load('jquery');

if ( claro_debug_mode() )
{
    JavascriptLoader::getInstance()->load('jquery-migrate');
}

JavascriptLoader::getInstance()->load('jquery.qtip');
JavascriptLoader::getInstance()->load('claroline');
JavascriptLoader::getInstance()->load('claroline.ui');

// add other default platform javascript here

// Load course home page javascript
if ( claro_is_in_a_course() )
{
    // add other default course javascript here
    
    if ( claro_is_in_a_group() )
    {
        // add other default group javascript here
    }
}

/*----------------------------------------------------------------------
  Find MODULES's includes to add and include them using a cache system
 ----------------------------------------------------------------------*/

// TODO : move module_cache to cache directory
// TODO : includePath is probably not needed

$module_cache_filename = get_conf('module_cache_filename','moduleCache.inc.php');
$cacheRepositorySys = get_path('rootSys') . get_conf('cacheRepository', 'tmp/cache/');

if (!file_exists($cacheRepositorySys . $module_cache_filename))
{
    require_once get_path('incRepositorySys') . '/lib/module/manage.lib.php';
    generate_module_cache();
}

require_once get_path('incRepositorySys') . '/lib/lock.lib.php';

if (file_exists($cacheRepositorySys . $module_cache_filename))
{
    include $cacheRepositorySys . $module_cache_filename;
}
else
{
    pushClaroMessage('module_cache not generated : check access right in '.$cacheRepositorySys,'warning');
}

// reset current module label after calling the cache
if ( isset($tlabelReq) && get_current_module_label() != $tlabelReq )
{
    // reset all previous occurence of module label in stack
    while (clear_current_module_label());
    // set the current module label
    set_current_module_label($tlabelReq);
}

// Add feed RSS in header
if ( claro_is_in_a_course() && get_conf('enableRssInCourse', true) )
{
    require claro_get_conf_repository() . 'rss.conf.php';

    $claroline->display->header->addHtmlHeader('<link rel="alternate" type="application/rss+xml" title="' . claro_htmlspecialchars($_course['name'] . ' - ' . get_conf('siteName')) . '"'
    .' href="' . get_path('url') . '/claroline/backends/rss.php?cidReq=' . claro_get_current_course_id() . '" />' );
}

// timezone debug code
if ( claro_debug_mode() && get_conf('clmain_serverTimezone','') )
{
    pushClaroMessage('timezone set to '.date_default_timezone_get(),'debug');
}

if ( claro_is_in_a_course() && isset( $tlabelReq ) && $tlabelReq == 'CLQWZ' )
{
    require_once get_path('incRepositorySys').'/../exercise/lib/add_missing_table.lib.php';
    init_qwz_questions_categories ();
}

if ( !claro_is_platform_admin () )
{
    $courseStatus = claro_get_current_course_data ( 'status' );

    if ( $courseStatus == 'trash' || $courseStatus == 'disable' )
    {
        Claroline::getDisplay()->body->hideCourseTitleAndTools();
        claro_die( get_lang('This course is not available anymore, please contact the platform administrator.') );
    }
}

// post kernel access check

if ( claro_is_in_a_course() )
{
    if ( !( 
        basename ( php_self () ) == 'courses.php' 
        && isset($_REQUEST['cmd']) 
        && $_REQUEST['cmd'] == 'exReg' 
    ) )
    {
        if ( !claro_is_course_allowed() ) 
        {
            if ( !claro_is_user_authenticated() ) 
            {
                claro_disp_auth_form();
            }
            else
            {
                if ( claro_get_current_course_data('access') == 'private' && !claro_is_course_member () )
                {
                    claro_die(get_lang("You have to be enroled to this course to access its contents") 
                        . '<br /><a href="'
                        . claro_htmlspecialchars( get_path('clarolineRepositoryWeb')
                            . 'auth/courses.php?cmd=exReg&course='
                            . claro_get_current_course_id() )
                        . '">'
                        . claro_html_icon( 'enroll' ) . ' '
                        . '<b>' . get_lang('Enrolment') . '</b>'
                        . '</a>'
                    );
                }
                else
                {
                    claro_die(get_lang("Not allowed!"));
                }
            }
        }
    }
}

// group_space.php?registration=1&selfReg=1

if ( claro_is_in_a_group() )
{
    if ( !( 
        basename ( php_self () ) == 'group_space.php' 
        && isset($_REQUEST['registration']) 
        && $_REQUEST['registration'] == '1' 
    ) )
    {
        if (! claro_is_group_allowed() )
        { 
            if ( !claro_is_user_authenticated() ) 
            {
                claro_disp_auth_form();
            }
            else
            {
                claro_die(get_lang("Not allowed!"));
            }
        }
    }
}

// FORCE reloading current module translation here
language::load_module_translation();

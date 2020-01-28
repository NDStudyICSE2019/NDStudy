<?php // $Id: module_list.php 14450 2013-05-15 12:02:23Z zefredz $

/**
 * CLAROLINE
 *
 * Claroline extension modules management script.
 *
 * @version     $Revision: 14450 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GENERAL PUBLIC LICENSE
 *              version 2 or later
 * @package     ADMIN
 * @author      Claro Team <cvs@claroline.net>
 */

$cidReset = true ;
$gidReset = true ;
require '../../inc/claro_init_global.inc.php' ;

//SECURITY CHECK
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

FromKernel::uses (
    'display/dialogBox.lib',
    'pager.lib',
    'sqlxtra.lib',
    'fileManage.lib',
    'fileUpload.lib',
    'file.lib',
    'html.lib',
    'module/manage.lib',
    'backlog.class'
);

//OLD TOOLS ;


$old_tool_array = array('CLANN',
                        'CLCAL',
                        'CLFRM',
                        'CLCHT',
                        'CLDOC',
                        'CLDSC',
                        'CLUSR',
                        'CLLNP',
                        'CLQWZ',
                        'CLWRK',
                        'CLWIKI',
                        'CLLNK',
                        'CLGRP'
                        );

//UNDEACTIVABLE    TOOLS array

$undeactivable_tool_array = get_not_deactivable_tool_list();

//NONUNINSTALABLE TOOLS array

$nonuninstalable_tool_array = get_not_uninstallable_tool_list();

//SQL table name

$tbl_name        = claro_sql_get_main_tbl();
$tbl_module      = $tbl_name['module'];
$tbl_dock        = $tbl_name['dock'];
$tbl_course_tool = $tbl_name['tool'];

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$dialogBox = new DialogBox;

$nameTools = get_lang('Modules');

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance()->addLangVar('Are you sure you want to uninstall the module %name ?');

JavascriptLoader::getInstance()->load('admin');

//CONFIG and DEVMOD vars :

//TODO remove pagination
$modulePerPage = 1000;

$typeLabel['']        = get_lang('No name');
$typeLabel['tool']    = get_lang('Tools');
$typeLabel['applet']  = get_lang('Applets');
$typeLabel['crsmanage'] = get_lang('Course management tools');
$typeLabel['admin']  = get_lang('Administration tools');
//$typeLabel['lang']    = get_lang('Language packs');
//$typeLabel['theme']   = get_lang('Themes');
//$typeLabel['extauth'] = get_lang('External authentication drivers');

$moduleTypeList = get_available_module_types();


$cmd          = (isset($_REQUEST['cmd'])          ? $_REQUEST['cmd']          : null);
$module_id    = (isset($_REQUEST['module_id'])    ? $_REQUEST['module_id']    : null );
$courseToolId = (isset($_REQUEST['courseToolId']) ? $_REQUEST['courseToolId'] : null );
$typeReq      = (isset($_REQUEST['typeReq'])      ? $_REQUEST['typeReq']      : 'tool');
$offset       = (isset($_REQUEST['offset'])       ? $_REQUEST['offset']       : 0 );
$_cleanInput['selectInput'] = (isset($_REQUEST['selectInput'])     ? $_REQUEST['selectInput'] : null );

$notAutoActivateInCourses = ( array_key_exists( 'notAutoActivateInCourses', $_REQUEST )
    && $_REQUEST['notAutoActivateInCourses'] == 'on' )
    ? true
    : false
    ;

$activableOnlyByPlatformAdmin = ( array_key_exists( 'activableOnlyByPlatformAdmin', $_REQUEST )
    && $_REQUEST['activableOnlyByPlatformAdmin'] == 'on' )
    ? true
    : false
    ;

$activateOnInstall = ( array_key_exists( 'activateOnInstall', $_REQUEST )
    && $_REQUEST['activateOnInstall'] == 'on' )
    ? true
    : false
    ;

$visibleOnInstall = ( array_key_exists( 'visibleOnInstall', $_REQUEST )
    && $_REQUEST['visibleOnInstall'] == 'on' )
    ? true
    : false
    ;

$deleteModuleDatabase = ( array_key_exists( 'deleteModuleDatabase', $_REQUEST )
    && $_REQUEST['deleteModuleDatabase'] == 'on' )
    ? true
    : false
    ;

//----------------------------------
// EXECUTE COMMAND
//----------------------------------
// TODO improve status message and backlog display
switch ( $cmd )
{
    case 'activ' :
        list( $backlog, $success ) = activate_module($module_id);
        $details = $backlog->output();
        if ( $success )
        {
            $summary  = get_lang('Module activation succeeded');
            $dialogBox->success( Backlog_Reporter::report( $summary, $details ) );
        }
        else
        {
            $summary  = get_lang('Module activation failed');
            $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
        }
        break;
    
    case 'desactiv' :
        list( $backlog, $success ) = deactivate_module($module_id);
        $details = $backlog->output();
        if ( $success )
        {
            $summary  = get_lang('Module desactivation succeeded');
            $dialogBox->success( Backlog_Reporter::report( $summary, $details ) );
        }
        else
        {
            $summary  = get_lang('Module desactivation failed');
            $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
        }
        break;
        
    case 'mvUp' :
            if(!is_null($courseToolId))
            {
                move_module_tool($courseToolId, 'up');
            }
            break;
        
    case 'mvDown' :
            if(!is_null($courseToolId))
            {
                move_module_tool($courseToolId, 'down');
            }
            break;
            
    case 'byDefaultVisible':
    case 'byDefaultInvisible':
    {
        $visibility = ( 'byDefaultVisible' == $cmd ) ? true : false;

        $success = set_tool_visibility_at_course_creation( $module_id, $visibility );

        if ( $success )
        {
            $dialogBox->success( get_lang('Default module visibility updated') );
        }
        else
        {
            $dialogBox->error( get_lang('Failed to update default module visibility') );
        }

        break;
    }
        
    case 'exUninstall' :
        $moduleInfo = get_module_info ( $module_id ) ;
        
        if (in_array ( $moduleInfo [ 'label' ], $old_tool_array ))
        {
            $dialogBox->error( get_lang ( 'This tool can not be uninstalled.' ) );
        }
        else
        {
            list ( $backlog, $success ) = uninstall_module ( $module_id, $deleteModuleDatabase ) ;
            
            $details = $backlog->output () ;
            
            if ($success)
            {
                $summary = get_lang ( 'Module uninstallation succeeded' ) ;
                $dialogBox->success( Backlog_Reporter::report( $summary, $details ) );
            }
            else
            {
                $summary = get_lang ( 'Module uninstallation failed' ) ;
                $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
            }
        }
        break ;

    case 'rqUninstall' :
        $moduleInfo = get_module_info ( $module_id ) ;

        $dialogBox->form( '<p>' . get_lang ( 'Are you sure you want to delete module %module% ?', array ( '%module%' => $moduleInfo [ 'module_name' ] ) ) . '</p>'
            . '<form enctype="multipart/form-data" action="' . $_SERVER [ 'PHP_SELF' ] . '" method="post">' . "\n"
            . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />'
            . '<input type="hidden" name="module_id" value="' . $module_id . '" />'
            . '<input name="cmd" type="hidden" value="exUninstall" />' . "\n"
            . '<input name="deleteModuleDatabase" id="deleteModuleDatabase" type="checkbox" checked="checked" />'
            . '<label for="deleteModuleDatabase">' . get_lang ( 'Also delete module main database' ) . '</label>'
            . '<br />' . "\n"
            . '<br />' . "\n"
            . '<input value="' . get_lang ( 'Continue' ) . '" type="submit" onclick="return confirmation(\'' . $moduleInfo [ 'module_name' ] . '\');" />'
            . '&nbsp;' . "\n"
            . claro_html_button ( $_SERVER [ 'PHP_SELF' ], get_lang ( 'Cancel' ) ) . '</form>' . "\n"
            );
    break ;
    case 'exInstall' :

        // call by rqInstall
        //1 GET THE FILE
        //2 UNZIP IF ZIPPED
        //3 INSTALL


        $moduleInstallable = false ;

        //include needed librabries for treatment
        //1 GET THE FILE
        // File can be an uploaded package file
        // or a local package file
        // or a local unpackaged file
        // later: an url to a package file)
        // later: a local repository of many packages
        // Actually interface display two input, and only one must be filed. If the user give both , the uploaded package win.


        // If it's a zip file, it would be place into package repositorys.


        pushClaroMessage(__LINE__ . '<pre>$_FILES ='.var_export($_FILES,1).'</pre>','dbg');
        
        if (array_key_exists ( 'uploadedModule', $_FILES )
            || array_key_exists ( 'packageCandidatePath', $_REQUEST ))
        {

            pushClaroMessage ( __LINE__ . '<pre>$_REQUEST =' . var_export ( $_REQUEST, 1 ) . '</pre>', 'dbg' ) ;
            // Thread uploaded file
            if (array_key_exists ( 'uploadedModule', $_FILES ))
            {

                pushClaroMessage ( __LINE__ . 'files founds', 'dbg' ) ;

                if (file_upload_failed ( $_FILES [ 'uploadedModule' ] ))
                {
                    $summary = get_lang ( 'Module upload failed' ) ;
                    $details = get_file_upload_error_message ( $_FILES [ 'uploadedModule' ] ) ;
                    
                    $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
                }
                else
                {

                    // move uploadefile to package repository, and unzip them
                    // actually it's done in function wich must be splited.
                    if (false !== ($modulePath = get_and_unzip_uploaded_package ()))
                    {
                        $moduleInstallable = true ;
                    }
                    else
                    {
                        $summary = get_lang ( 'Module unpackaging failed' ) ;
                        $details = implode ( "<br />\n", claro_failure::get_last_failure () ) ;
                        
                        $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
                    }
                }
            }
            elseif (array_key_exists ( 'packageCandidatePath', $_REQUEST ))
            {
                // If the target is a zip file, it must be unpack
                // If it's a unziped package, We copye the content
                if (is_package_file ( $_REQUEST [ 'packageCandidatePath' ] ))
                {
                    pushClaroMessage ( __LINE__ . 'packageCandidatePath is a package', 'dbg' ) ;
                    $modulePath = unzip_package ( $_REQUEST [ 'packageCandidatePath' ] ) ;
                    pushClaroMessage ( __LINE__ . '<pre>$modulePath =' . var_export ( $modulePath, 1 ) . '</pre>', 'dbg' ) ;
                }
                elseif (file_exists ( $_REQUEST [ 'packageCandidatePath' ] ))
                {
                    // COPY THE FILE TO WORK REPOSITORY
                    pushClaroMessage ( __LINE__ . 'packageCandidatePath is a path', 'dbg' ) ;
                    claro_mkdir ( get_package_path () ) ;

                    $modulePath = create_unexisting_directory ( get_package_path () . basename ( $_REQUEST [ 'packageCandidatePath' ] ) ) ;
                    claro_mkdir ( $modulePath ) ;
                    pushClaroMessage ( __LINE__ . 'create target' . $modulePath, 'dbg' ) ;

                    if (claro_copy_file ( $_REQUEST [ 'packageCandidatePath' ], $modulePath . '/' ))
                    {
                        $modulePath .= '/' . basename ( $_REQUEST [ 'packageCandidatePath' ] ) ;

                        $moduleInstallable = true ;
                    }
                    else
                    {
                        $dialogBox->error( get_lang ( 'Module catching failed. Check your path' ) );
                        $moduleInstallable = false ;
                    }
                }
            }

            pushClaroMessage ( __LINE__ . '<pre>$modulePath =' . var_export ( $modulePath, 1 ) . '</pre>', 'dbg' ) ;
            
            // OK TO TRY TO INSTALL ?
            if ($moduleInstallable)
            {

                list ( $backlog, $module_id ) = install_module ( $modulePath ) ;
                
                $details = $backlog->output () ;
                
                if (false !== $module_id)
                {

                    $summary = get_lang ( 'Module installation succeeded' ) ;
                    $moduleInfo = get_module_info ( $module_id ) ;
                    $typeReq = $moduleInfo [ 'type' ] ;

                    $dialogBox->success( Backlog_Reporter::report( $summary, $details ) );

                    if ($activateOnInstall)
                    {

                        list ( $backlogActivation, $successActivation ) = activate_module ( $module_id, false ) ;
                        $detailsActivation = $backlogActivation->output () ;

                        if ($successActivation)
                        {
                            $dialogBox->success(  get_lang ( 'Module activation succeeded' ) );

                            if ($visibleOnInstall && $typeReq == 'tool')
                            {

                                list ( $backlogVisibility, $successVisibility ) = set_module_visibility ( $module_id, true ) ;
                                $detailsVisibility = $backlogVisibility->output () ;

                                if ($successVisibility)
                                {
                                    $dialogBox->success( get_lang ( 'Module visibility updated' ) );
                                } else
                                {
                                    $summaryVisibility = get_lang ( 'Failed to update module visibility' ) ;
                                    $dialogBox->error( Backlog_Reporter::report ( $summaryVisibility, $detailsVisibility ) );
                                }
                            }
                        }
                        else
                        {

                            $summaryActivation = get_lang ( 'Module activation failed' ) ;
                            $dialogBox->error( Backlog_Reporter::report ( $summaryActivation, $detailsActivation ) );
                        }

                    }

                    if ( $typeReq == 'tool' && $notAutoActivateInCourses )
                    {
                        if ( set_module_autoactivation_in_course( $moduleInfo['label'], false ) )
                        {
                            $dialogBox->success(
                                get_lang('Module activation at course creation set to MANUAL') );
                        }
                        else
                        {
                            $dialogBox->error(
                                get_lang('Cannot change module activation on course creation') );
                        }

                        if ( $activableOnlyByPlatformAdmin )
                        {
                            if ( allow_module_activation_by_course_manager( $moduleInfo['label'], false ) )
                            {
                                $dialogBox->success(
                                    get_lang('Only PLATFORM_ADMIN can activate this module') );
                            }
                            else
                            {
                                $dialogBox->error(
                                    get_lang('Cannot change module activation on course creation') );
                            }
                        }
                    }
                }
                else
                {
                    $dialogBox->error( get_lang ( 'Module installation failed' ) );
                }
            }

        }
        else
        {
            $summary = get_lang ( 'Module upload failed' ) ;
            $details = 'No file uploaded' ;
            claro_die ( Backlog_Reporter::report ( $summary, $details ) ) ;
        }

    break ;
    case 'rqInstall' :
        /**
         * Check input possibilities
         *
         *
         */
        $inputPackage = array ( ) ;

        if (get_conf ( 'can_install_local_module', false ))
        {
            $inputPackage [] = 'local' ;
        }
        
        if (get_conf ( 'can_install_upload_module', true ))
        {
            $inputPackage [] = 'upload' ;
        }
        
        if (get_conf ( 'can_install_curl_module', false ))
        {
            $inputPackage [] = 'curl' ;
        }

        if ( in_array($_cleanInput['selectInput'],$inputPackage))
        {
            $selectInput = $_cleanInput['selectInput'];
        }
        else
        {
            switch ( count ( $inputPackage ))
            {
                case 0 :
                    // You can't add packages
                    $dialogBox->warning(  get_lang("You cannot add module. Change this in configuration.") . '<br />'
                          //TODO CHeck if it's the good place : config_code=CLMAIN&section=ADVANCED
                          . claro_html_button('../tool/config_edit.php?config_code=CLMAIN&section=ADVANCED', get_lang('Go to config'))
                    );
                break ;
            
                case 1 : //Direct display
                    $_cleanInput['selectInput'] = $selectInput = $inputPackage [ 0 ];
                break ;
            
                default : // SELECT ONE
                    $dialogBox->form(
                          '<form action="' . $_SERVER [ 'PHP_SELF' ] . '" method="GET">' . "\n"
                        . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />'
                        . '<input name="cmd" type="hidden" value="rqInstall" />' . "\n"
                        . get_lang('Where is your package ?')  . '<br />' . "\n"

                        . (get_conf ( 'can_install_upload_module', true ) ?
                        
                          '<input name="selectInput" value="upload"  id="zipOnYouComputerServer" type="radio" checked="checked" />'
                        . '<label for="zipOnYouComputerServer" >' . get_lang ( 'Package on your computer (zip only)' ) . '</label>' . '<br />'
                        :'')

                        . (get_conf ( 'can_install_local_module', false ) ?
                        
                          '<input name="selectInput"  value="local" id="packageOnServer" type="radio" />'
                        . '<label for="packageOnServer" >' . get_lang ( 'Package on server (zipped or not)' ) . '</label>' . '<br />'
                        :'')
                        
                        . (get_conf ( 'can_install_curl_module', false ) ?
                        
                          '<input name="selectInput" value="curl" id="zipOnThirdServer" type="radio" />'
                        . '<label for="zipOnThirdServer" >' . get_lang ( 'Package on the net (zip only)' ) . '</label>' . '<br />'
                        :'')
                        . '<br />' . "\n"
                        . '<br />' . "\n"
                        . '<input value="' . get_lang ( 'Next' ) . '" type="submit" />&nbsp;' . "\n"
                        . claro_html_button ( $_SERVER [ 'PHP_SELF' ], get_lang ( 'Cancel' ) )
                        . '</form>' . "\n"
                    );

            }
        }

        switch ( $_cleanInput['selectInput'])
        {
            case 'upload' :
                $dialogBox->warning(
                      '<p>' . "\n"
                    . get_lang ( 'Imported modules must consist of a zip file and be compatible with your Claroline version.' ) . '<br />' . "\n"
                    . get_lang ( 'Find more available modules on <a href="http://www.claroline.net/">Claroline.net</a>.' ) . '</p>' . "\n\n"
                );

                $dialogBox->form(
                      '<form enctype="multipart/form-data" action="' . $_SERVER [ 'PHP_SELF' ] . '" method="post">' . "\n"
                    . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />'  . "\n"
                    . '<input name="cmd" type="hidden" value="exInstall" />' . "\n"
                    . '<input name="uploadedModule" type="file" /><br />' . "\n"
                    . '<input name="activateOnInstall" id="activateOnInstall" type="checkbox" />'  . "\n"
                    . '<label for="activateOnInstall" >' . get_lang ( 'Activate module on install' ) . '</label>' . '<br />' . "\n"
                    . '<fieldset>'
                    . '<legend>'.get_lang('The following options will only work for course tool modules :').'</legend>'
                    . '<input name="notAutoActivateInCourses" id="autoActivateInCourses" type="checkbox" />'  . "\n"
                    . '<label for="notAutoActivateInCourses" >' . get_lang ( 'This tool must be activated manualy in each course' ) . '</label>' . '<br />' . "\n"
                    . '<input name="activableOnlyByPlatformAdmin" id="activableOnlyByPlatformAdmin" type="checkbox" />'  . "\n"
                    . '<label for="activableOnlyByPlatformAdmin" >' . get_lang ( 'Make this tool activable only by the platform administrator <small>(available if the previous option is checked)</small>' ) . '</label>' . '<br />' . "\n"
                    . '<input name="visibleOnInstall" id="visibleOnInstall" type="checkbox" />'  . "\n"
                    . '<label for="visibleOnInstall" >' . get_lang ( 'Visible in all courses on install <small>(this can take some time depending on the number of courses in your campus)</small>' ) . '</label>'
                    . '</fieldset>'
                    . '<br />' . "\n"
                    . '<br />' . "\n"
                    . '<input value="' . get_lang ( 'Upload and Install module' ) . '" type="submit" />&nbsp;' . "\n"
                    .  claro_html_button ( $_SERVER [ 'PHP_SELF' ], get_lang ( 'Cancel' ) ) . '</form>' . "\n"
                );
            break ;
        
            case 'local' :
                $dialogBox->warning(
                     '<p>' . "\n"
                    . get_lang ( 'Imported modules must be compatible with your Claroline version.' ) . '<br />' . "\n"
                    . get_lang ( 'Find more available modules on <a href="http://www.claroline.net/">Claroline.net</a>.' ) . '</p>' . "\n\n"
                );
                
                $dialogBox->form(
                      '<form enctype="multipart/form-data" action="' . $_SERVER [ 'PHP_SELF' ] . '" method="GET">' . "\n"
                    . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />'
                    . '<input name="cmd" type="hidden" value="exInstall" />' . "\n"
                    . get_lang ( 'Path to zip file or package directory on server' )
                    . ': <br />' . "\n"
                    // TODO ADD A SERVER FILE BROWSER
                    . '<input size="80" name="packageCandidatePath" type="text" /><br />' . "\n"
                    . '<input name="activateOnInstall"  id="activateOnInstall" type="checkbox" />'
                    . '<label for="activateOnInstall" >' . get_lang ( 'Activate module on install' ) . '</label>' . '<br />'
                    // . '<input name="autoActivateInCourses" id="autoActivateInCourses" type="checkbox" />'  . "\n"
                    // . '<label for="autoActivateInCourses" >' . get_lang ( 'Activate automaticaly in courses <small>(course tool only)</small>' ) . '</label>' . '<br />' . "\n"
                    . '<input name="visibleOnInstall"  id="visibleOnInstall" type="checkbox" />'
                    . '<label for="visibleOnInstall" >' . get_lang ( 'Visible on  each course on install <small>(tool only)</small>' ) . '</label>'
                    . '<br />' . "\n"
                    . '<br />' . "\n"
                    . '<br /><input value="' . get_lang ( 'Install module' ) . '" type="submit" />&nbsp;' . "\n"
                    . claro_html_button ( $_SERVER [ 'PHP_SELF' ], get_lang ( 'Cancel' ) ) . '</form>' . "\n"
                );
            break ;
        
            case 'curl' :
                $dialogBox->error(
                     '<p>' . "\n"
                    . get_lang ( 'This feature is not ready.' ) . '</p>' . "\n\n"
                );
                
                $dialogBox->warning(
                    '<p>' . "\n"
                    . get_lang ( 'Imported modules must consist of a zip file and be compatible with your Claroline version.' ) . '<br />' . "\n"
                    . get_lang ( 'Find more available modules on <a href="http://www.claroline.net/">Claroline.net</a>.' ) . '</p>' . "\n\n"
                );

                $dialogBox->form(
                      '<form action="' . $_SERVER [ 'PHP_SELF' ] . '" method="GET">' . "\n"
                    . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />'
                    . '<input name="cmd" type="hidden" value="exInstall" />' . "\n"
                    . get_lang ( 'Url of package' ) . '<br />' . "\n"
                    . '<input name="packageCandidateUrl" size="80" type="text" /><br />' . "\n"
                    . '<input name="activateOnInstall"  id="activateOnInstall" type="checkbox" />'
                    . '<label for="activateOnInstall" >' . get_lang ( 'Activate module on install' ) . '</label>' . '<br />'
                    // . '<input name="autoActivateInCourses" id="autoActivateInCourses" type="checkbox" />'  . "\n"
                    // . '<label for="autoActivateInCourses" >' . get_lang ( 'Activate automaticaly in courses <small>(course tool only)</small>' ) . '</label>' . '<br />' . "\n"
                    . '<input name="visibleOnInstall"  id="visibleOnInstall" type="checkbox" />'
                    . '<label for="visibleOnInstall" >' . get_lang ( 'Visible on  each course on install <small>(tool only)</small>' ) . '</label>'
                    . '<br />' . "\n"
                    . '<br />' . "\n"
                    . '<input value="' . get_lang ( 'Fetch and install module' ) . '" type="submit" />&nbsp;' . "\n"
                    . claro_html_button ( $_SERVER [ 'PHP_SELF' ], get_lang ( 'Cancel' ) )
                    . '</form>' . "\n"
                );
            break ;
        }

    break ; // rqInstall

    case 'exLocalRemove' :
    {
        if ( isset( $_REQUEST['moduleDir'] ) )
        {
            $moduleDir = str_replace ( '../', '', $_REQUEST [ 'moduleDir' ] ) ;
            $moduleRepositorySys = get_path ( 'rootSys' ) . 'module/' ;
            $modulePath = $moduleRepositorySys . $moduleDir . '/' ;

            if ( file_exists($modulePath) )
            {
                if(claro_delete_file($modulePath))
                {
                    $dialogBox->success(
                          '<p>' . "\n"
                        . get_lang('Module files deleted')
                        . '</p>' . "\n"
                    );
                }
                else
                {
                    $dialogBox->error(
                          '<p>' . "\n"
                        . get_lang('Error while deleting module files')
                        . '</p>' . "\n"
                    );
                    
                    $success = false;
                }
            }

        }
        else
        {
            $summary  = get_lang('Module installation failed');
            $details = get_lang('Missing module directory');
            
            $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
        }
    }
    break ;

    case 'exLocalInstall' :
        {
            if ( isset( $_REQUEST['moduleDir'] ) )
            {
                $moduleDir = str_replace ( '../', '', $_REQUEST [ 'moduleDir' ] ) ;
                $moduleRepositorySys = get_path ( 'rootSys' ) . 'module/' ;
                $modulePath = $moduleRepositorySys . $moduleDir . '/' ;

                if (file_exists ( $modulePath ))
                {
                    list ( $backlog, $module_id ) = install_module ( $modulePath, true ) ;
                    $details = $backlog->output () ;
                    
                    if (false !== $module_id)
                    {
                        $moduleInfo = get_module_info ( $module_id ) ;
                        $typeReq = $moduleInfo [ 'type' ] ;
                        
                        $dialogBox->success( get_lang ( 'Module installation succeeded' ) );
                    } else
                    {
                        $summary  = get_lang('Module installation failed');
                        $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
                    }
                }
                else
                {
                    $summary = get_lang ( 'Module installation failed' ) ;
                    $details = get_lang ( 'Module directory not found' ) ;
                    $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
                }

            }
            else
            {
                $summary  = get_lang('Module installation failed');
                $details = get_lang('Missing module directory');
                $dialogBox->error( Backlog_Reporter::report( $summary, $details ) );
            }
        }
}

if ( empty( $typeReq ) && $module_id )
{
    $moduleInfo = get_module_info($module_id);
    $typeReq = $moduleInfo['type'];
}

//----------------------------------
// FIND INFORMATION
//----------------------------------

// $moduleTypeList = claro_get_module_types();
// $moduleTypeList = array_merge($moduleTypeList, array_keys($typeLabel));

switch($typeReq)
{
    case 'applet' :

        $sqlSelectType = "       D.`id`    AS dock_id, " . "\n"
        .                "       D.`name`  AS dockname," . "\n"
        ;

        $sqlJoinType = " LEFT JOIN `" . $tbl_dock . "` AS D " . "\n"
        .              "        ON D.`module_id`= M.id " . "\n"
        ;
        $orderType = "";
        break;

    case 'tool'   :
        $sqlSelectType = "       CT.`id`    AS courseToolId, " . "\n"
        .                "       CT.`icon`  AS icon," . "\n"
        .                "       CT.`script_url` AS script_url," . "\n"
        .                "       CT.`def_rank` AS rank," . "\n"
        .                "       CT.`def_access` AS visibility," . "\n"
        ;
        $sqlJoinType = " LEFT JOIN `" . $tbl_course_tool . "` AS CT " . "\n"
        .              "        ON CT.`claro_label`= M.label " . "\n"
        ;
        $orderType = "ORDER BY `def_rank` \n";
        break;
    
     default :
        $sqlSelectType = "" ;
        $sqlJoinType = "" ;
        $orderType = "" ;

}

$sql = "
    SELECT M.`id`     AS `id`,
           M.`label`  AS `label`,
           M.`name`            AS `name`,
           M.`activation`      AS `activation`,
           " . $sqlSelectType . "
           M.`type`            AS `type`
      FROM `" . $tbl_module . "` AS M
            " . $sqlJoinType . "
     WHERE M.`type` = '" . claro_sql_escape ( $typeReq ) . "'
  GROUP BY `id`
  " . $orderType . "
  " ;

//pager creation

$myPager    = new claro_sql_pager($sql, $offset, $modulePerPage);
$moduleList = $myPager->get_result_list();

//find docks in which the modules do appear.

$module_dock = array();
$dockList = get_dock_list($typeReq); // will be usefull to display correctly the dock names

foreach ($moduleList as $module)
{
    $module_dock[$module['id']] = array();

    $module_dock[$module['id']] = get_module_dock_list($module['id']);

    if (!file_exists(get_module_path($module['label'])))
    {
        $dialogBox->warning(
              get_lang('<b>Warning : </b>')
            . get_lang('There is a module installed in DB : <b><i>%module_name</i></b> for which there is no folder on the server.',
                array('%module_name'=>$module['label'])) );
    }

}

//do a check of modules to see if there is anyhting to install

$modules_found = check_module_repositories();

foreach ($modules_found['folder'] as $module_folder)
{
    $urlTryInstall = $_SERVER['PHP_SELF'] . '?cmd=exLocalInstall&amp;moduleDir=' . rawurlencode($module_folder);
    $urlDelete = $_SERVER['PHP_SELF'] . '?cmd=exLocalRemove&amp;moduleDir=' . rawurlencode($module_folder);
    $dialogBox->warning( get_lang('There is a folder called <b><i>%module_name</i></b> for which there is no module installed.', array('%module_name'=>$module_folder))
    . '<ul>' . "\n"
    . '<li>' . "\n"
    . '<a href="'.$urlTryInstall.'">'.get_lang( 'Install this module').'</a>'
    . '</li>' . "\n"
    . '<li>' . "\n"
    . '<a href="'.$urlDelete.'">'.get_lang( 'Remove this module').'</a>'
    . '</li>' . "\n"
    . '</ul>' . "\n"
    );
}

//needed info for reorder buttons to known if we must display action (or not)

$course_tool_min_rank = get_course_tool_min_rank();
$course_tool_max_rank = get_course_tool_max_rank();

// Command list
$cmdList = array();

$cmdList[] = array(
    'name' => get_lang('Install module'),
    'url' => 'module_list.php?cmd=rqInstall'
);


//----------------------------------
// DISPLAY
//----------------------------------


$noQUERY_STRING = true ;

$out = '';

// Title
$out .= claro_html_tool_title ( $nameTools, null, $cmdList )

// Display Forms or dialog box(if needed)
.    $dialogBox->render()

// Display tabbed navbar
.    '<div>' . "\n"
.    '<ul id="navlist">' . "\n"
;

// Display the module type tabbed naviguation bar
foreach ($moduleTypeList as $type)
{
    if ($typeReq == $type)
    {
        $out .= '<li><a href="module_list.php?typeReq=' . $type . '" class="current">' . $typeLabel[$type] . '</a></li>' . "\n";
    }
    else
    {
        $out .= '<li><a href="module_list.php?typeReq=' . $type . '">' . $typeLabel[$type] . '</a></li>' . "\n";
    }
}

$out .= '</ul>' . "\n"
.    '</div>' . "\n"
;

// Display Pager list
if ( $myPager->get_next_offset() ) $out .= $myPager->disp_pager_tool_bar('module_list.php?typeReq=' . $typeReq);

// Start table...
$out .= '<table class="claroTable emphaseLine" width="100%" border="0" cellspacing="2">' . "\n\n"
.    '<thead>' . "\n"
.    '<tr>' . "\n"
.    '<th>' . get_lang('Icon')                . '</th>' . "\n"
.    '<th>' . get_lang('Module name')         . '</th>' . "\n"
.    '<th>' . get_lang('Module administration')         . '</th>' . "\n";

if ($typeReq == 'applet')
{
    $out .= '<th>' . get_lang('Display')             . '</th>' . "\n";
}
else
{
    $out .= '<th colspan="2">' . get_lang('Order')       . '</th>' . "\n";
}

$out .= '<th>' . get_lang('Properties')          . '</th>' . "\n"
.    '<th>' . get_lang('Uninstall')           . '</th>' . "\n"
.    '<th>' . get_lang('Activated')          . '</th>' . "\n";

if ($typeReq == 'tool')
{
    $out .=  '<th>' . get_lang('Default visibility')          . '</th>' . "\n";
}

$out .=   '</tr>' . "\n"
     .    '</thead>' . "\n\n"
     .    '<tbody>'
     ;


// Start the list of modules...
foreach($moduleList as $module)
{
    // Display settings...
    $class_css = ($module['activation'] == 'activated') ? '' : ' class="invisible" ';

    // Find icon
    $modulePath = get_module_path($module['label']);

    switch ( $typeReq )
    {
        case 'crsmanage':
        case 'admin':
            $moduleDefaultIcon = 'settings';
            break;
        default:
            $moduleDefaultIcon = 'exe';
            break;
    }
    
    $iconUrl = get_module_icon_url( 
        $module['label'] , 
        array_key_exists('icon',$module) ? $module['icon'] : null, 
        $moduleDefaultIcon );
    
    $icon = '<img src="'.$iconUrl.'" alt="" />';

    // Module_id and icon column
    $out .=  "\n"  . '<tr ' . $class_css . '>' . "\n"
    .    '<td align="center">' . $icon . '</td>' . "\n";

    // Name column

    $moduleName = $module['name'];
    
    $out .= '<td align="left">' . get_lang($moduleName) . '</td>' . "\n";

    if (file_exists(get_module_path($module['label']) . '/admin.php') && ($module['type']!='tool'))
    {
        $out .= '<td align="left"><a href="' . get_module_url($module['label']) . '/admin.php" >' . get_lang('Go to administration') . '</a></td>' . "\n";
    }
    else
    {
        $out .= '<td align="left">-</td>' . "\n";
    }

    // Displaying location column
    if ( $module['type'] == 'applet' )
    {
        $out .= '<td align="left"><small>';
        if (empty($module_dock[$module['id']]))
        {
            $out .= '<span align="center">' . get_lang('No dock chosen') . '</span>';
        }
        else
        {
            foreach ( $module_dock [ $module [ 'id' ] ] as $dock )
            {
                $out .= '<a href="module_dock.php?dock=' . $dock [ 'dockname' ] . '">' . $dockList [ $dock [ 'dockname' ] ] . '</a> <br/>' ;
            }
        }

        $out .= '</small></td>' . "\n";
    }
    else
    {
        // Up command
        if (isset( $module[ 'rank' ] ) && $course_tool_min_rank != $module [ 'rank' ])
        {
            $out .= '<td align="center">'
            .    '<a href="module_list.php?courseToolId='.$module['courseToolId'].'&amp;cmd=mvUp">'
            .    '<img src="' . get_icon_url('move_up') . '" alt="'.get_lang('Move up').'" />'
            .    '</a>'
            .    '</td>' . "\n";
        }
        else
        {
            $out .= '<td>&nbsp;</td>' . "\n" ;
        }

        // Down command
        if (isset( $module[ 'rank' ] ) && $course_tool_max_rank != $module [ 'rank' ])
        {
            $out .= '<td align="center">'
            .    '<a href="module_list.php?courseToolId='.$module['courseToolId'].'&amp;cmd=mvDown">'
            .    '<img src="' . get_icon_url('move_down') . '" alt="'.get_lang('Move down').'" />'
            .    '</a>'
            .    '</td>' . "\n";
        }
        else
        {
            $out .= '<td>&nbsp;</td>' . "\n" ;
        }
    }

    // Properties link
    $out .= '<td align="center">'
    .    '<a href="module.php?module_id='.$module['id'].'">'
    .    '<img src="' . get_icon_url('settings') . '" alt="' . get_lang('Properties') . '" />'
    .    '</a>'
    .    '</td>' . "\n";

    // Uninstall link
    if (!in_array($module['label'],$nonuninstalable_tool_array))
    {
        $out .= '<td align="center">'
        .    '<a onclick="return ADMIN.confirmationUninstall(\''.clean_str_for_javascript($module['name']).'\');" '
        .    'href="'.claro_htmlspecialchars('module_list.php?module_id=' . $module['id'] . '&typeReq='.$typeReq.'&cmd=exUninstall').'" >'
        .    '<img src="' . get_icon_url('delete') . '" alt="' . get_lang('Delete') . '" />'
        .    '</a>'
        .    '</td>' . "\n";
        ;
    }
    else
    {
        $out .= '<td align="center">-</td>' . "\n" ;
    }

    // Activation link
    $out .= '<td align="center" >' ;

    if (in_array ( $module [ 'label' ], $undeactivable_tool_array ))
    {
        $out .= '-';
    }
    else
    {
        if ( 'activated' == $module['activation'] )
        {
            $out .= '<a href="module_list.php?cmd=desactiv&amp;module_id='
            . $module['id'] . '&amp;typeReq=' . $typeReq .'" '
            . 'title="'.get_lang('Activated - Click to deactivate').'">'
            . '<img src="' . get_icon_url('on')
            . '" alt="'. get_lang('Activated') . '" /></a>'
            ;
        }
        else
        {
            $out .= '<a href="module_list.php?cmd=activ&amp;module_id='
            . $module['id'] . '&amp;typeReq='.$typeReq.'" '
            . 'title="'.get_lang('Deactivated - Click to activate').'">'
            . '<img src="' . get_icon_url('off')
            . '" alt="'. get_lang('Deactivated') . '"/></a>';
        }
    }
    $out .= '</td>' . "\n";
            
    // Visibility by default at course creation
    if  ($typeReq == 'tool')
    {
        $out .= '<td align="center" >' ;

            if ( 'ALL' == $module['visibility'] )
            {
                $out .= '<a href="module_list.php?cmd=byDefaultInvisible&amp;module_id='
                . $module['id'] . '&amp;typeReq=' . $typeReq .'" '
                . 'title="'.get_lang('Visible - Click to make invisible').'">'
                . '<img src="' . get_icon_url('visible')
                . '" alt="'. get_lang('Visible') . '" /></a>'
                ;
            }
            else
            {
                $out .= '<a href="module_list.php?cmd=byDefaultVisible&amp;module_id='
                . $module['id'] . '&amp;typeReq='.$typeReq.'" '
                . 'title="'.get_lang('Invisible - Click to make visible').'">'
                . '<img src="' . get_icon_url('invisible')
                . '" alt="'. get_lang('Invisible') . '"/></a>';
            }

        $out .= '</td>' . "\n";
    }
    
    //end table line

    $out .=  '</tr>' . "\n\n";
}

// End table
$out .= '</tbody>' . "\n"
.    '</table>' . "\n\n"
;

//Display BOTTOM Pager list
if ( $myPager->get_previous_offset() ) $out .= $myPager->disp_pager_tool_bar ( 'module_list.php?typeReq=' . $typeReq ) ;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
<?php // $Id: addcsvusers.php 14390 2013-02-13 17:02:53Z ffervaille $

/**
 * CLAROLINE
 *
 * Tool for bulk subscribe.
 *
 * @version     1.11 $Revision: 14390 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLUSR
 * @author      Claro Team <cvs@claroline.net>
 */

//$tlabelReq = 'CLUSR';

require '../inc/claro_init_global.inc.php';

//used libraries
require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';
require_once get_path('incRepositorySys') . '/lib/class.lib.php';
require_once get_path('incRepositorySys') . '/lib/course_user.lib.php';
require_once get_path('incRepositorySys') . '/lib/group.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/password.lib.php';
require_once get_path('incRepositorySys') . '/lib/utils/validator.lib.php';
require_once get_path('incRepositorySys') . '/lib/utils/input.lib.php';
require_once get_path('incRepositorySys') . '/lib/thirdparty/parsecsv/parsecsv.lib.php';

require_once './csvimport.class.php';

include claro_get_conf_repository() . 'user_profile.conf.php';

load_module_config( 'CLUSR' );

if ( !$is_courseAllowed ) claro_disp_auth_form(true);

$is_courseManager = claro_is_course_manager();
$is_platformAdmin = claro_is_platform_admin();

$is_allowedToEnroll = ( $is_courseManager && get_conf('is_coursemanager_allowed_to_enroll_single_user') ) || $is_platformAdmin;
$is_allowedToImport = ( $is_courseManager && get_conf('is_coursemanager_allowed_to_import_user_list') ) || $is_platformAdmin;
$is_allowedToCreate = ( $is_courseManager && get_conf('is_coursemanager_allowed_to_register_single_user') ) || $is_platformAdmin;

if( !$is_allowedToImport )
{
  claro_die(get_lang('Not allowed'));
}

$courseId = claro_get_current_course_id();

$userInput = Claro_UserInput::getInstance();
$userInput->setValidator( 'cmd' , new Claro_Validator_AllowedList( array( 'rqCSV',
                                                                          'rqChangeFormat',
                                                                          'exChangeFormat',
                                                                          'rqLoadDefaultFormat',
                                                                          'exLoadDefaultFormat' )
                                                                  )
                         );

$userInput->setValidator( 'fieldSeparator' , new Claro_Validator_allowedList( array( ',' , ';' , ' ' )
                                                                             )
                         );

$userInput->setValidator( 'enclosedBy' , new Claro_Validator_allowedList( array( 'dbquote' , '.' , 'none' )
                                                                             )
                         );

$userInput->setValidator( 'firstLineFormat' , new Claro_Validator_allowedList( array( 'YES' , 'NO' )
                                                                             )
                         );

$cmd = $userInput->get( 'cmd' );
$step = (int)$userInput->get( 'step' , 0 );
$class_id = (int)$userInput->get( 'class_id' , 0 );
$updateUserProperties = (int)$userInput->get( 'updateUserProperties' , 0 );
$sendEmailToUserCreated = (int)$userInput->get( 'sendEmailToUserCreated' , 0 );
$firstLineFormat = $userInput->get( 'firstLineFormat' ) == 'YES';

$nameTools = get_lang('Add a user list in course');

if (claro_is_in_a_course())
{
    ClaroBreadCrumbs::getInstance()->prepend( 
        get_lang('Users'), 
        get_module_url('CLUSR').'/user.php'.(!is_null($courseId) ? '?cid='.$courseId : '') 
    );
}
else
{
    ClaroBreadCrumbs::getInstance()->prepend( 
        get_lang('Platform administration'), 
        get_path('rootAdminWeb')
    );
}

$dialogBox = new DialogBox();

$defaultFormat = 'lastname,firstname,username,email,officialCode,groupId,groupName';

if ( empty($_SESSION['claro_usedFormat']) )
{
    $_SESSION['claro_usedFormat'] = $defaultFormat;
}

$usedFormat = $_SESSION['claro_usedFormat'];

switch( $cmd )
{
    case 'rqChangeFormat' :
    {
        $compulsory_list = array('firstname','lastname','username');

        $chFormatForm = get_lang('Modify the format') .' : ' . '<br /><br />' . "\n"
            . get_lang( 'Simply write the fields\' names in right order and separated by commas' ) . '<br />' . "\n"
            . get_lang('The fields <em>%field_list</em> are compulsory', array ('%field_list' => implode(', ',$compulsory_list)) ) . '<br /><br />' . "\n"
            . '<form name="chFormat" method="post" action="' . claro_htmlspecialchars($_SERVER['PHP_SELF']) . '?&cmd=exChangeFormat" >' . "\n"
            . '<input type="text" name="usedFormat" value="' . claro_htmlspecialchars($usedFormat) . '" size="55" />' . "\n"
            . claro_form_relay_context() . "\n"
            . '<br /><br />' . "\n"
            . '<input type="submit" value="' . get_lang('Ok') . '" />' . "\n"
            . '</form>'
            ;
        
        $dialogBox->form( $chFormatForm );
    }
    break;
    
    case 'exChangeFormat' :
    {
        $usedFormat = $userInput->get( 'usedFormat' );
        $userFormat = str_replace( ';' , ',' , $usedFormat ); //replace ; by ,
        
        if( ! $usedFormat )
        {
            $dialogBox->error( get_lang( 'Unable to load the selected format' ) );
            break;
        }
        
        if( ! CsvImport::format_ok( $usedFormat ) )
        {
            $dialogBox->error( get_lang('ERROR: The format you gave is not compatible with Claroline') );
            break;
        }
        
        $dialogBox->success( get_lang('Format changed') );
        
        $_SESSION['claro_usedFormat']   = $usedFormat;
    }
    break;
    
    case 'rqLoadDefaultFormat':
    {
        $_SESSION['claro_usedFormat'] = $defaultFormat;
    }
    break;
}

$usedFormat = $_SESSION['claro_usedFormat'];
// Content
$content = '';
$out = '';

$addType = $userInput->get( 'addType' );

if( $addType )
{
    switch( $addType )
    {
        case 'userTool' :
            $_SESSION['CSV_CancelButton'] = 'user.php';
            break;
        case 'adminTool' :
            $_SESSION['CSV_CancelButton'] = '../admin/';
            break;
        case 'adminClassTool' :
            $_SESSION['CSV_CancelButton'] = '../admin/admin_class_user.php?class_id=' . $class_id;
            break;
        default :
            $_SESSION['CSV_CancelButton'] = '../index.php';
    }
}
else
{
    if( empty($_SESSION['CSV_CancelButton']) )
    {
        $_SESSION['CSV_CancelButton'] = '../index.php';
    }
}

$backButtonUrl = Url::Contextualize($_SESSION['CSV_CancelButton']);

$content_default = get_lang('You must specify the CSV format used in your file') . ':' . "\n"
    . '<br /><br />' . "\n"
    . '<form method="post" action="' . claro_htmlspecialchars($_SERVER['PHP_SELF']) . '" enctype="multipart/form-data"  >' . "\n"
    . '<input type="hidden" name="step" value="1" />' . "\n"
    . '<input type="hidden" name="class_id" value="' . $class_id . '" />' . "\n"
    . claro_form_relay_context()
    . '<input type="radio" name="firstLineFormat" value="YES" id="firstLineFormat_YES" /> '
    . '<label for="firstLineFormat_YES">' . get_lang('Use format defined in first line of file') . '</label>' . "\n"
    . '<br /><br />' . "\n"
    . '<input type="radio" name="firstLineFormat" value="NO" checked="checked" id="firstLineFormat_NO" />' . "\n"
    . '<label for="firstLineFormat_NO">' . get_lang('Use the following format') . ' : ' . '</label>' . "\n"
    . '<br /><br />' . "\n"
    . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
    . '<span style="font-weight: bold;">' . $usedFormat . '</span><br /><br />' . "\n"
    . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . "\n"
    . claro_html_cmd_link( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
                            . '?display=default'
                            . '&cmd=rqLoadDefaultFormat'
                            . '&addType=' . $addType ))
                            , get_lang('Load default format')
                            ) . "\n"
    . ' | '
    . claro_html_cmd_link( claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
                            . '?display=default'
                            . '&cmd=rqChangeFormat'
                            . '&addType=' . $addType ))
                            , get_lang('Edit format to use')
                            ) . "\n"
    . '<br /><br />' . "\n"
    . get_lang('CSV file with the user list :') . "\n"
    . '<input type="file" name="CSVfile" />' . "\n"
    . '<br /><br />' . "\n" . "\n"
    ;

$content_default .= '<h3>' . get_lang('Options') . '</h3>';

$content_default .= '<input type="checkbox" name="sendEmailToUserCreated" value="1" id="sendEmailToUserCreated" />' . "\n"
    .'<label for="sendEmailToUserCreated">' 
    . get_lang('Send email to new users') . ' ' . '</label>' . "\n"
    .'<br /><br />' . "\n"
    ;

if (get_conf('update_user_properties'))
{
    $content_default .= '<input type="checkbox" name="updateUserProperties" value="1" id="updateUserProperties" />' . "\n"
        .'<label for="updateUserProperties">' 
        . get_lang('Update user\'properties ') . ' ' . '</label>' . "\n"
        .'<br /><br />' . "\n"
        ;
}

$content_default .=   '<input type="submit" name="submitCSV" value="' . get_lang('Add user list') . '" />' . "\n"
    . claro_html_button(claro_htmlspecialchars( $backButtonUrl ),get_lang('Cancel'))  . "\n"
    . '</form>' . "\n"
    ;

$csvImport = new CsvImport();
$csvImport->heading = $firstLineFormat;

if ( ! $firstLineFormat )
{
    // $csvImport->titles = explode( ',' , $usedFormat);
    $csvImport->fields = explode( ',' , $usedFormat);
}

switch( $step )
{
    case 1 : // check csv data & display the selection
    {
        if( !isset( $_FILES['CSVfile'] ) || empty($_FILES['CSVfile']['name']) || $_FILES['CSVfile']['size'] == 0 )
        {
            $dialogBox->error(get_lang('You must select a file'));
            
            $content .= $content_default;
        }
        else
        {
            $tmpName = $_FILES['CSVfile']['tmp_name'];
            $csvContent = file_get_contents( $tmpName );
            
            if( ! $csvImport->auto( $tmpName ) )
            {
                $dialogBox->error(get_lang('Unable to read the content of the CSV'));
            }
            elseif( ! $csvImport->validateFields() )
            {
                $dialogBox->error(get_lang('Missing field(s)'));
            }
            else
            {
                $csvContent = $csvImport->data;
                
                if( $firstLineFormat )
                {
                    $keys = $csvImport->titles;
                    $firstLine = $csvImport->getFirstLine();
                }
                else
                {
                    $keys = explode( ',' , $usedFormat);
                    $firstLine = $usedFormat;
                }
                
                $csvUseableArray = $csvImport->createArrayForCsvUsage($firstLineFormat, $keys) ;
                $errors = CsvImport::checkFieldsErrors( $csvUseableArray );
                
                if( ! CsvImport::format_ok( $firstLine ) )
                {
                    // $dialogBox->error( get_lang('ERROR: The format you gave is not compatible with Claroline') );
                    $dialogBox->error( get_lang('ERROR: The format of lines you gave is not compatible with Claroline') );
                    break;
                }
                
                if( !count($csvContent) )
                {
                    $dialogBox->error(get_lang('No data to import'));
                }
                else
                {
                    if( count($errors) )
                    {
                        $errorsDisplayed = '';
                        foreach( $errors as $error )
                        {
                            if( !empty($error) )
                            {
                                foreach($error as $e)
                                {
                                    $errorsDisplayed .= '<div>' . $e . '</div>';
                                }
                            }
                        }
                        if(!empty($errorsDisplayed))
                        {
                            $dialogBox->error($errorsDisplayed);
                        }
                    }
                    
                    $content .= '<br />' . get_lang('Select users you want to import in the course') 
                        . '<br />'
                        . ( count($errors) ? get_lang('Errors can be ignored to force the import') : '') 
                        . "\n" . '<br />' . "\n"
                        ;
                    
                    $content .= '<form method="post" action="' . claro_htmlspecialchars($_SERVER['PHP_SELF']) . '" >' . "\n"
                        //. '<input type="hidden" name="csvContent" value="' . str_replace( '"' , '\'' , serialize( $csvContent ) ) . '" />' . "\n"
                        . '<input type="hidden" name="step" value="2" />' . "\n"
                        . '<input type="hidden" name="class_id" value="' . $class_id .'" />' . "\n"
                        . '<input type="hidden" name="updateUserProperties" value="' . $updateUserProperties . '" />' . "\n"
                        . '<input type="hidden" name="sendEmailToUserCreated" value="' . $sendEmailToUserCreated  . '" />' . "\n"
                        . claro_form_relay_context() . "\n"
                        // options
                        // TODO: check if user can create users
                        //. get_lang('Create new users') . '<input type="checkbox" value="1" name="newUsers" />'
                        // Data
                        . '<table class="claroTable emphaseLine" width="100%" cellpadding="2" cellspacing="1"  border="0">' . "\n"
                        . '<thead>' . "\n"
                        . '<tr class="headerX">' . "\n"
                        . '<th><input type="checkbox" name="checkAll" id="checkAll" onchange="changeAllCheckbox();" checked="checked" /></th>' . "\n"
                        ;
                    
                    foreach($keys as $key => $value)
                    {
                        $content .= '<th>' . $value . '</th>' . "\n";
                    }
                    
                    //$content .= '<th>Errors</th>' . "\n";
                    $content .= '</tr>' . "\n"
                        . '</thead>' . "\n"
                        ;
                    
                    foreach( $csvContent as $key => $data)
                    {
                        $content .= '<tr>' . "\n"
                            . '<td style="text-align: center;">' . "\n"
                            . '    <input type="checkbox" name="users[' . $key . ']" class="checkAll" checked="checked" />' . "\n"
                            . '</td>' . "\n";
                            ;
                        
                        foreach( $data as $name => $value )
                        {
                            $content .= '<td>' . "\n"
                                . '    ' . (!empty($d) ? $d : '&nbsp;') . "\n"
                                . '    <input type="hidden" name="csvContent[' . $key . '][' . $name .']" value="' . $value . '"/>' . "\n"
                                . '    ' . $value . "\n"
                                . '</td>' . "\n"
                                ;
                        }
                        //$content .= '<td></td>' . "\n";
                        $content .= '</tr>' . "\n";
                    }
                    
                    $content .=   '</table>' . "\n"
                        . '<input type="submit" name="submitCSV" value="' . get_lang('Add selected users') . '" />' . "\n"
                        . claro_html_button(claro_htmlspecialchars( $backButtonUrl ),get_lang('Cancel'))  . "\n"
                        . '</form>' . "\n"
                        ;
                }
                
            }
        }
    }
    break;

    case 2 : // Import users in course
    {
        //$csvContent = unserialize( str_replace( '\'' , '"' , $userInput->get( 'csvContent' ) ) )
        $csvContent = $userInput->get( 'csvContent' );
        $userList = array_keys( $userInput->get( 'users' ) );
        $csvImport->put( $csvContent );
        
        if(is_null($courseId))
        {
            if(!claro_is_platform_admin() )
            {
                claro_die(get_lang('Not allowed'));
            }
            
            $logs = $csvImport->importUsers( $userList
                                           , $class_id
                                           , $updateUserProperties
                                           , $sendEmailToUserCreated );
        }
        else
        {
            $logs = $csvImport->importUsersInCourse( $userList
                                                   , $courseId
                                                   , $is_allowedToCreate
                                                   , $is_allowedToEnroll
                                                   , $class_id
                                                   , $sendEmailToUserCreated );
        }
        
        if( !empty($logs) )
        {
            if( isset( $logs['errors'] ) )
            {
                $_errors = "";
                foreach( $logs['errors'] as $error )
                {
                    $_errors .= '<div>' . $error . '</div>' . "\n";
                }
                if( !empty($_errors) )
                {
                    $dialogBox->error( $_errors );
                }
            }
            
            if( isset( $logs['success'] ) )
            {
                $_success = "";
                foreach( $logs['success'] as $s )
                {
                    $_success .= '<div>' . $s . '</div>' . "\n";
                }
                if( !empty( $_success ) )
                {
                    $dialogBox->success( $_success );
                }
            }
        }
        else
        {
            $dialogBox->success( 'Users imported successfully');
        }
    }
    break;
    
    default :
    {
        $content .= $content_default;
    }
}

$out .= claro_html_tool_title($nameTools);
$out .= $dialogBox->render();
$out .= $content;

$out .= '<script type="text/javascript">'
. 'function changeAllCheckbox()
    {
        if( $("#checkAll").attr("checked") )
        {
            $(".checkAll").attr("checked", true);
        }
        else
        {
            $(".checkAll").attr("checked", false);
        }
    }
    '
. '</script>';

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

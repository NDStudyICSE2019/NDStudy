<?php // $Id: work.php 14483 2013-07-02 13:13:28Z zefredz $

/**
 * CLAROLINE
 *
 * Main script for work tool.
 *
 * @version     $Revision: 14483 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/CLWRK/
 * @author      Claro Team <cvs@claroline.net>
 * @package     CLWRK
 * @since       1.8
 */

$tlabelReq = 'CLWRK';
require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

require_once './lib/assignment.class.php';

require_once get_path('incRepositorySys') . '/lib/pager.lib.php';
require_once get_path('incRepositorySys') . '/lib/fileUpload.lib.php';
require_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php'; // need format_url function
require_once get_path('incRepositorySys') . '/lib/fileManage.lib.php'; // need claro_delete_file


$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_wrk_assignment = $tbl_cdb_names['wrk_assignment'];
$tbl_wrk_submission = $tbl_cdb_names['wrk_submission'];

$currentCoursePath =  claro_get_current_course_data('path');

// 'step' of pager
$assignmentsPerPage = get_conf('assignmentsPerPage', 20);

// use viewMode
claro_set_display_mode_available(TRUE);

/*============================================================================
                     BASIC VARIABLES DEFINITION
  =============================================================================*/
$currentCourseRepositorySys = get_path('coursesRepositorySys') . $currentCoursePath . '/';
$currentCourseRepositoryWeb = get_path('coursesRepositoryWeb') . $currentCoursePath . '/';

$fileAllowedSize = get_conf('max_file_size_per_works') ;    //file size in bytes

// use with strip_tags function when strip_tags is used to check if a text is empty
// but a 'text' with only an image don't have to be considered as empty
$allowedTags = '<img>';

// initialise dialog box to an empty string, all dialog will be concat to it
$dialogBox = new DialogBox();

// permission
$is_allowedToEdit = claro_is_allowed_to_edit();

/*============================================================================
                     CLEAN INFORMATIONS SENT BY USER
  =============================================================================*/

$acceptedCmdList = array( 'rqDownload', 'exDownload', 'exChVis', 'exRmAssig', 'exEditAssig', 'rqEditAssig', 'exMkAssig', 'rqMkAssig' );

if( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )   $cmd = $_REQUEST['cmd'];
else                                                                            $cmd = null;

if( isset($_REQUEST['assigId']) ) $assigId = (int) $_REQUEST['assigId'];
else                              $assigId = null;

if( isset($_REQUEST['downloadMode']) )  $downloadMode = $_REQUEST['downloadMode'];
else                                    $downloadMode = 'all';

/*============================================================================
                HANDLING FORM DATA : CREATE/EDIT ASSIGNMENT
  =============================================================================*/
if( !is_null($cmd) )
{
    // instanciate assignment object
    $assignment = new Assignment();

    if( !is_null($assigId) )
    {
        // we handle a particular assignment, no form has been posted (delete, change visibility , ask for edition)
        // read assignment
        if( ! $assignment->load($assigId) )
        {
            // could not read assignment
            $cmd = null;
            $assigId = null;
        }
    }


    if( isset($_REQUEST['submitAssignment']) && !is_null($cmd) )
    {
        // form submitted
        if ( isset($_REQUEST['title']) )
        {
            $assignment->setTitle(strip_tags(trim($_REQUEST['title'])));
        }

        if( !isset($_REQUEST['description']) || trim( strip_tags($_REQUEST['description'], $allowedTags ) ) == '' )
        {
            $assignment->setDescription(''); // avoid multiple br tags to be added when editing an empty form
        }
        else
        {
            $assignment->setDescription(trim( $_REQUEST['description'] ));

        }
        
        if ( isset($_REQUEST['submission_visibility_applies_to_all']) && $_REQUEST['submission_visibility_applies_to_all'] == 'yes' )
        {
            $assignment->visibilityModificationAppliesToOldSubmissions ( true );
            $assignment->forceVisibilityChange();
        }

        if ( isset($_REQUEST['def_submission_visibility']) )     $assignment->setDefaultSubmissionVisibility($_REQUEST['def_submission_visibility']);
        if ( isset($_REQUEST['assignment_type']) )                $assignment->setAssignmentType($_REQUEST['assignment_type']);
        if ( isset($_REQUEST['authorized_content']) )             $assignment->setSubmissionType($_REQUEST['authorized_content']);
        if ( isset($_REQUEST['allow_late_upload']) )             $assignment->setAllowLateUpload($_REQUEST['allow_late_upload']);


        $unixStartDate = mktime( $_REQUEST['startHour'],
                                $_REQUEST['startMinute'],
                                '00',
                                $_REQUEST['startMonth'],
                                $_REQUEST['startDay'],
                                $_REQUEST['startYear']);
        $assignment->setStartDate($unixStartDate);

        $unixEndDate = mktime( $_REQUEST['endHour'],
                                $_REQUEST['endMinute'],
                                '00',
                                $_REQUEST['endMonth'],
                                $_REQUEST['endDay'],
                                $_REQUEST['endYear']);
        $assignment->setEndDate($unixEndDate);

        $assignment_data['start_date'] = $unixStartDate;

        $assignment_data['end_date']     = $unixEndDate;
    }
    else
    {
        // create new assignment
        // add date format used to pre fill the form
        $assignment_data['start_date'] = $assignment->getStartDate();
        $assignment_data['end_date']     = $assignment->getEndDate();
    }
}

// Submission download requested
if( $is_allowedToEdit && $cmd == 'rqDownload' && (claro_is_platform_admin() || get_conf('allow_download_all_submissions')) )
{
    require_once($includePath . '/lib/form.lib.php');
    
    $dialogBox->title( get_lang('Download') );
    $dialogBox->form( '<form action="'.  get_module_url('CLWRK').'/export.php" method="POST">' . "\n"
    .    claro_form_relay_context()
    .    '<input type="hidden" name="cmd" value="exDownload" />' . "\n"
    .     '<input type="radio" name="downloadMode" id="downloadMode_from" value="from" checked /><label for="downloadMode_from">' . get_lang('Submissions posted or modified after date :') . '</label><br />' . "\n"
    .     claro_html_date_form('day', 'month', 'year', time(), 'long') . ' '
    .     claro_html_time_form('hour', 'minute', time() - fmod(time(), 86400) - 3600) . '<small>' . get_lang('(d/m/y hh:mm)') . '</small>' . '<br /><br />' . "\n"
    .     '<input type="radio" name="downloadMode" id="downloadMode_all" value="all" /><label for="downloadMode_all">' . get_lang('All submissions') . '</label><br /><br />' . "\n"
    .     '<input type="checkbox" name="downloadOnlyCurrentMembers" id="downloadOnlyCurrentMembers_id" value="yes" checked="checked" /><label for="downloadOnlyCurrentMembers_id">'.get_lang('Download only submissions from current course members').'</label><br /><br />' . "\n"
    .     '<input type="checkbox" name="downloadScore" id="downloadScore_id" value="yes" checked="checked" /><label for="downloadScore_id">'.get_lang('Download score').'</label><br /><br />' . "\n"
    .     '<input type="submit" value="'.get_lang('OK').'" />&nbsp;' . "\n"
    .    claro_html_button('work.php', get_lang('Cancel'))
    .     '</form>'."\n"
    );
}

if ($is_allowedToEdit)
{
    /*--------------------------------------------------------------------
                          CHANGE VISIBILITY
    --------------------------------------------------------------------*/

    // change visibility of an assignment
    if ( $cmd == 'exChVis' )
    {
        if ( isset($_REQUEST['vis']) )
        {
            $_REQUEST['vis'] == 'v' ? $visibility = 'VISIBLE' : $visibility = 'INVISIBLE';

            Assignment::updateAssignmentVisibility($assigId, $visibility);

            // notify eventmanager

            $eventNotifier->notifyCourseEvent('work_updated', claro_get_current_course_id(), claro_get_current_tool_id(), $assigId, claro_get_current_group_id(), '0');

            if ( $_REQUEST['vis'] == 'v')
            {
                $eventNotifier->notifyCourseEvent('work_visible', claro_get_current_course_id(), claro_get_current_tool_id(), $assigId, claro_get_current_group_id(), '0');
            }
            else
            {
                $eventNotifier->notifyCourseEvent('work_invisible', claro_get_current_course_id(), claro_get_current_tool_id(), $assigId, claro_get_current_group_id(), '0');
            }
        }
    }

    /*--------------------------------------------------------------------
                          DELETE AN ASSIGNMENT
    --------------------------------------------------------------------*/

    // delete/remove an assignment
    if ( $cmd == 'exRmAssig' )
    {
        $assignment->delete();

        //notify eventmanager
        $eventNotifier->notifyCourseEvent('work_deleted', claro_get_current_course_id(), claro_get_current_tool_id(), $assigId, claro_get_current_group_id(), '0');

        $dialogBox->success( get_lang('Assignment deleted') );
    }

    /*--------------------------------------------------------------------
                          MODIFY AN ASSIGNMENT
    --------------------------------------------------------------------*/
    /*-----------------------------------
        STEP 2 : check & query
    -------------------------------------*/

    // edit an assignment / form has been sent
    if ( $cmd == 'exEditAssig' )
    {
        // check validity of the data
        if ( !is_null($assigId) && $assignment->validate() )
        {
            $assignment->save();

            $eventNotifier->notifyCourseEvent('work_updated', claro_get_current_course_id(), claro_get_current_tool_id(), $assigId, claro_get_current_group_id(), '0');

            $dialogBox->success( get_lang('Assignment modified') );
        }
        else
        {
            if(claro_failure::get_last_failure() == 'assignment_no_title')
               $dialogBox->error( get_lang('Assignment title required') );
            if(claro_failure::get_last_failure() == 'assignment_title_already_exists')
               $dialogBox->error( get_lang('Assignment title already exists') );
            if(claro_failure::get_last_failure() == 'assignment_incorrect_dates')
               $dialogBox->error( get_lang('Start date must be before end date ...') );

            $cmd = 'rqEditAssig';
        }
    }
    /*-----------------------------------
    STEP 1 : display form
    -------------------------------------*/
    // edit assignment / display the form
    if( $cmd == 'rqEditAssig' )
    {
        require_once(get_path('incRepositorySys') . '/lib/form.lib.php');
        // modify the command 'cmd' sent by the form
        $cmdToSend = 'exEditAssig';
        // ask the display of the form
        $displayAssigForm = true;
    }

    /*--------------------------------------------------------------------
                          CREATE NEW ASSIGNMENT
    --------------------------------------------------------------------*/

    /*-----------------------------------
        STEP 2 : check & query
    -------------------------------------*/
    //--- create an assignment / form has been sent
    if( $cmd == 'exMkAssig' )
    {
        // form data have been handled before this point if the form was sent
        if( $assignment->validate() )
        {
            $lastAssigId = $assignment->save();
            // confirmation message
            $dialogBox->success( get_lang('New assignment created') );

            if($lastAssigId)
            {
                //notify eventmanager that a new assignement is created
                $eventNotifier->notifyCourseEvent("work_added",claro_get_current_course_id(), claro_get_current_tool_id(), $lastAssigId, claro_get_current_group_id(), "0");
            }
        }
        else
        {
            if(claro_failure::get_last_failure() == 'assignment_no_title')
               $dialogBox->error( get_lang('Assignment title required') );
            if(claro_failure::get_last_failure() == 'assignment_title_already_exists')
               $dialogBox->error( get_lang('Assignment title already exists') );
            if(claro_failure::get_last_failure() == 'assignment_incorrect_dates')
               $dialogBox->error( get_lang('Start date must be before end date ...') );

            $cmd = 'rqMkAssig';
        }
    }

    /*-----------------------------------
        STEP 1 : display form
    -------------------------------------*/
    //--- create an assignment / display form
    if( $cmd == 'rqMkAssig' )
    {
        require_once(get_path('incRepositorySys') . '/lib/form.lib.php');
        // modify the command 'cmd' sent by the form
        $cmdToSend = 'exMkAssig';
        // ask the display of the form
        $displayAssigForm = true;
    }
}

/*================================================================
                      DISPLAY
  ================================================================*/

/*--------------------------------------------------------------------
                            HEADER
  --------------------------------------------------------------------*/

JavascriptLanguage::getInstance()->addLangVar('Are you sure to delete %name ?');

JavascriptLoader::getInstance()->load('work');

if ( ( isset($displayAssigForm) && $displayAssigForm ) )
{
    // if there is a form add a breadcrumb to go back to list
    $nameTools = get_lang('Assignment');
    ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd='.$cmd.'&assigId='.$assigId ) );
    ClaroBreadCrumbs::getInstance()->prepend( get_lang('Assignments'), Url::Contextualize('../work/work.php') );
}
else
{
    $noQUERY_STRING = true;
    $nameTools = get_lang('Assignments');
}



/*--------------------------------------------------------------------
                              LIST
  --------------------------------------------------------------------*/
// if user come from a group
if ( claro_is_in_a_group() && claro_is_group_allowed() )
{
    // select only the group assignments
    $sql = "SELECT `id`,
                    `title`,
                    `def_submission_visibility`,
                      `visibility`,
                    `assignment_type`,
                    `authorized_content`,
                    unix_timestamp(`start_date`) as `start_date_unix`,
                    unix_timestamp(`end_date`) as `end_date_unix`
            FROM `" . $tbl_wrk_assignment . "`
            WHERE `assignment_type` = 'GROUP'";

    if( !claro_is_allowed_to_edit() )
    {
        $sql .= " AND `visibility` = 'VISIBLE' ";
    }
    
    if ( isset($_GET['sort']) )
    {
        $sortKeyList[$_GET['sort']] = isset($_GET['dir']) ? $_GET['dir'] : SORT_ASC;
    }
    else
    {
        $sortKeyList['end_date']    = SORT_ASC;
        $sortKeyList['title']    = SORT_ASC;
    }
        
}
else
{
    $sql = "SELECT `id`,
                    `title`,
                    `def_submission_visibility`,
                    `visibility`,
                    `assignment_type`,
                    unix_timestamp(`start_date`) as `start_date_unix`,
                    unix_timestamp(`end_date`) as `end_date_unix`
            FROM `" . $tbl_wrk_assignment . "`";

    if( !claro_is_allowed_to_edit() )
    {
        $sql .= " WHERE `visibility` = 'VISIBLE' ";
    }

    if ( isset($_GET['sort']) )
    {
        $sortKeyList[$_GET['sort']] = isset($_GET['dir']) ? $_GET['dir'] : SORT_ASC;
    }
    else
    {
        $sortKeyList['end_date']    = SORT_ASC;
        $sortKeyList['title']    = SORT_ASC;
    }
        
}

$offset = (isset($_REQUEST['offset']) && !empty($_REQUEST['offset']) ) ? $_REQUEST['offset'] : 0;
$assignmentPager = new claro_sql_pager($sql, $offset, $assignmentsPerPage);

foreach($sortKeyList as $thisSortKey => $thisSortDir)
{
    $assignmentPager->add_sort_key( $thisSortKey, $thisSortDir);
}

$assignmentList = $assignmentPager->get_result_list();

// Help URL
$helpUrl = $is_allowedToEdit ? get_help_page_url('blockAssignmentsHelp','CLWRK') : null;

// Command list
$cmdList = array();

if( $is_allowedToEdit )
{
    $cmdList[] = array(
        'img' => 'assignment',
        'name' => get_lang('Create a new assignment'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=rqMkAssig'))
    );
    
    if( claro_is_platform_admin() || get_conf('allow_download_all_submissions') )
    {
        $cmdList[] = array(
            'img' => 'save',
            'name' => get_lang('Download submissions'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=rqDownload'))
        );
    }
    
    if( get_conf( 'mail_notification', false ) && !get_conf( 'automatic_mail_notification', false ) )
    {
        $cmdList[] = array(
            'img' => 'settings',
            'name' => get_lang('Assignments preferences'),
            'url' => claro_htmlspecialchars(Url::Contextualize('work_settings.php'))
        );
    }
}

$out = '';
$out .= claro_html_tool_title($nameTools, $helpUrl, $cmdList);


if ($is_allowedToEdit)
{
    $out .= $dialogBox->render();
    
    // Form
    if ( isset($displayAssigForm) && $displayAssigForm )
    {
        $out .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data">' . "\n"
              . '<input type="hidden" name="claroFormId" value="' . uniqid('') .'" />' . "\n"
              . '<input type="hidden" name="cmd" value="' . $cmdToSend .'" />' . "\n"
              . claro_form_relay_context() . "\n"
              . '<fieldset>';
        
        if( !is_null($assigId) )
        {
            $out .= '<input type="hidden" name="assigId" value="'. $assigId .'" />' . "\n";
        }
        
        $out .= '<dl>'
              
              . '<dt><label for="title">' . get_lang('Assignment title') . ' <span class="required">*</span></label></dt>'
              . '<dd><input type="text" name="title" id="title" size="50" maxlength="200" value="' . claro_htmlspecialchars($assignment->getTitle()) . '" /></dd>'
              
              . '<dt><label for="description">' . get_lang('Description') . '<br /></label></dt>'
              . '<dd>' . claro_html_textarea_editor('description', $assignment->getDescription()) . '</dd>'
              
              . '<dt>'. get_lang('Submission type') .'</dt>'
              . '<dd>'
              . '<input type="radio" name="authorized_content" id="authorizeFile" value="FILE" '.( $assignment->getSubmissionType() == "FILE" ? 'checked="checked"' : '') . ' />
                <label for="authorizeFile">&nbsp;'. get_lang('File (file required, description text optional)') .'</label>
                <br />
                <input type="radio" name="authorized_content" id="authorizeText" value="TEXT" '. ( $assignment->getSubmissionType() == "TEXT" ?  'checked="checked"' : '') . '/>
                <label for="authorizeText">&nbsp;'. get_lang('Text only (text required, no file)') .'</label>
                <br />
                <input type="radio" name="authorized_content" id="authorizeTextFile" value="TEXTFILE" '. ( $assignment->getSubmissionType() == "TEXTFILE" ? 'checked="checked"' : '') . ' />
                <label for="authorizeTextFile">&nbsp;'. get_lang('Text with attached file (text required, file optional)') .'</label>'
              . '</dd>'
              
              . '<dt>'. get_lang('Assignment type') .'</dt>'
              . '<dd>'
              . '<input type="radio" name="assignment_type" id="individual" value="INDIVIDUAL" '. ( $assignment->getAssignmentType() == "INDIVIDUAL" ? 'checked="checked"' : '') .' />
                <label for="individual">&nbsp;'. get_lang('Individual') .'</label>
                <br />
                <input type="radio" name="assignment_type" id="group" value="GROUP" '. ( $assignment->getAssignmentType() == "GROUP" ?  'checked="checked"' : '' ) . ' />
                <label for="group">&nbsp;' . get_lang('Groups (from groups tool, only group members can post)') . '</label>'
              . '</dd>'
              
              . '<dt>' . get_lang('Start date') .'</dt>'
              . '<dd>'
              . claro_html_date_form('startDay', 'startMonth', 'startYear', $assignment_data['start_date'], 'long') . ' ' . claro_html_time_form('startHour', 'startMinute', $assignment_data['start_date'])
              . '<p class="notice">' . get_lang('(d/m/y hh:mm)') . '</p>'
              . '</dd>'
              
              . '<dt>' . get_lang('End date') . '</dt>'
              . '<dd>'
              . claro_html_date_form('endDay', 'endMonth', 'endYear', $assignment_data['end_date'], 'long') . ' ' . claro_html_time_form('endHour', 'endMinute', $assignment_data['end_date'])
              . '<p class="notice">' . get_lang('(d/m/y hh:mm)') . '</p>'
              . '</dd>'
              
              . '<dt>' . get_lang('Allow late upload') . '</dt>'
              . '<dd>
                <input type="radio" name="allow_late_upload" id="allowUpload" value="YES" ' . ( $assignment->getAllowLateUpload() == "YES" ?  'checked="checked"' : '' ) . ' />
                <label for="allowUpload">&nbsp;' . get_lang('Yes, allow users to submit works after end date') . '</label>
                <br />
                <input type="radio" name="allow_late_upload" id="preventUpload" value="NO" '. ( $assignment->getAllowLateUpload() == "NO" ? 'checked="checked"' : '' ) . ' />
                <label for="preventUpload">&nbsp;' . get_lang('No, prevent users submitting work after the end date') . '</label>'
              . '</dd>'
              
              . '<dt>' . get_lang('Default works visibility') . '</dt>'
              . '<dd>
                <input type="radio" name="def_submission_visibility" id="visible" value="VISIBLE" '.( $assignment->getDefaultSubmissionVisibility() == "VISIBLE" ? 'checked="checked"' : '') . ' />
                <label for="visible">&nbsp;' . get_lang('Visible for all users') . '</label>
                <br />
                <input type="radio" name="def_submission_visibility" id="invisible" value="INVISIBLE" '. ( $assignment->getDefaultSubmissionVisibility() == "INVISIBLE" ? 'checked="checked"' : '') . ' />
                <label for="invisible">&nbsp;'. get_lang('Only visible for teacher(s) and submitter(s)') . '</label>'
              . '<br /><br />
                <input type="checkbox" name="submission_visibility_applies_to_all" id="submission_visibility_applies_to_all_id" value="yes" />
                <label for="submission_visibility_applies_to_all_id">&nbsp;'. get_lang('Apply default visibility also to sumissions already posted') . '</label>'
              . '</dd>'
              
              . '<dt>&nbsp;</dt>'
              . '<dd>'
              . '<input type="submit" name="submitAssignment" value="'. get_lang('Ok') .'" />&nbsp;'
              . claro_html_button((isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'.'), get_lang('Cancel'))
              . '</dd>'
              
              . '</dl>'
              
              . '</fieldset>'
              . '</form>';
    }
}

/*--------------------------------------------------------------------
                            ASSIGNMENT LIST
    --------------------------------------------------------------------*/
// if we don't display assignment form
if ( (!isset($displayAssigForm) || !$displayAssigForm) )
{
    $headerUrl = $assignmentPager->get_sort_url_list($_SERVER['PHP_SELF']);

    $out .= $assignmentPager->disp_pager_tool_bar($_SERVER['PHP_SELF']);

    $out .= '<table class="claroTable" width="100%">' . "\n"
    .     '<tr class="headerX">'
    .     '<th><a href="' . $headerUrl['title'] . '">' . get_lang('Title') . '</a></th>' . "\n"
    .     '<th><a href="' . $headerUrl['assignment_type'] . '">' . get_lang('Type') . '</a></th>' . "\n"
    .     '<th><a href="' . $headerUrl['start_date_unix'] . '">' . get_lang('Start date') . '</a></th>' . "\n"
    .     '<th><a href="' . $headerUrl['end_date_unix'] . '">' . get_lang('End date') . '</a></th>' . "\n";

    $colspan = 4;

    if( isset($_REQUEST['submitGroupWorkUrl']) && !empty($_REQUEST['submitGroupWorkUrl']) )
    {
        $out .= '<th>' . get_lang('Publish') . '</th>' . "\n";
        $colspan++;
    }

    if( $is_allowedToEdit )
    {
        $out .= '<th>' . get_lang('Edit') . '</th>' . "\n"
        .     '<th>' . get_lang('Delete') . '</th>' . "\n"
        .     '<th>' . get_lang('Visibility') . '</th>' . "\n";
        $colspan += 3;
    }


    $out .= '</tr>' . "\n"
    .     '<tbody>' . "\n\n";


    $atLeastOneAssignmentToShow = false;

    if (claro_is_user_authenticated()) $date = $claro_notifier->get_notification_date(claro_get_current_user_id());

    foreach ( $assignmentList as $anAssignment )
    {
        //modify style if the file is recently added since last login and that assignment tool is used with visible default mode for submissions.
        $classItem='';
        if( claro_is_user_authenticated() )
        {
            if ( $claro_notifier->is_a_notified_ressource(claro_get_current_course_id(), $date, claro_get_current_user_id(), '',  claro_get_current_tool_id(), $anAssignment['id'],FALSE) && ($anAssignment['def_submission_visibility']=="VISIBLE"  || $is_allowedToEdit))
        {
            $classItem=' hot';
        }
            else //otherwise just display its name normally and tell notifier that every ressources are seen (for tool list notification consistancy)
        {
            $claro_notifier->is_a_notified_ressource(claro_get_current_course_id(), $date, claro_get_current_user_id(), '', claro_get_current_tool_id(), $anAssignment['id']);
        }
        }

        if ( $anAssignment['visibility'] == "INVISIBLE" )
        {
            if ( $is_allowedToEdit )
            {
                $style=' class="invisible"';
            }
            else
            {
                continue; // skip the display of this file
            }
        }
        else
        {
            $style='';
        }

        $out .= '<tr ' . $style . '>'."\n"
        .    '<td>' . "\n";
        
        $assignmentUrl = Url::Contextualize('work_list.php?assigId=' . $anAssignment['id']);
        
        if ( isset($_REQUEST['submitGroupWorkUrl']) && !empty($_REQUEST['submitGroupWorkUrl']) )
        {
            if( !isset($anAssignment['authorized_content']) || $anAssignment['authorized_content'] != 'TEXT' )
            {
                $assignmentUrl = Url::Contextualize( 'work_list.php?cmd=rqSubWrk&assigId=' . $anAssignment['id']
                . '&submitGroupWorkUrl=' . urlencode($_REQUEST['submitGroupWorkUrl'])
                . '&gidReq=' . claro_get_current_group_id() ) ;
            }
        }
        
        $out .= '<a href="'.claro_htmlspecialchars($assignmentUrl).'" class="item' . $classItem . '">'
        .    '<img src="' . get_icon_url('assignment') . '" alt="" /> '
        .    $anAssignment['title']
        .    '</a>' . "\n"
        .    '</td>' . "\n"
        ;

        $out .= '<td align="center">';

        if( $anAssignment['assignment_type'] == 'INDIVIDUAL' )
            $out .= '<img src="' . get_icon_url('user') . '" alt="' . get_lang('Individual') . '" />' ;
        elseif( $anAssignment['assignment_type'] == 'GROUP' )
            $out .= '<img src="' . get_icon_url('group') . '" alt="' . get_lang('Groups (from groups tool, only group members can post)') . '" />' ;
        else
            $out .= '&nbsp;';

        $out .= '</td>' . "\n"
        .    '<td><small>' . claro_html_localised_date(get_locale('dateTimeFormatLong'),$anAssignment['start_date_unix']) . '</small></td>' . "\n"
        .    '<td><small>' . claro_html_localised_date(get_locale('dateTimeFormatLong'),$anAssignment['end_date_unix']) . '</small></td>' . "\n";
        if ( isset($_REQUEST['submitGroupWorkUrl']) && !empty($_REQUEST['submitGroupWorkUrl']) )
        {
            if( !isset($anAssignment['authorized_content']) || $anAssignment['authorized_content'] != 'TEXT' )
            {
                $out .= '<td align="center">'
                .     '<a href="'.claro_htmlspecialchars($assignmentUrl).'">'
                .      '<small>' . get_lang('Publish') . '</small>'
                .     '</a>'
                .     '</td>' . "\n";
            }
            else
            {
                $out .= '<td align="center">'
                .      '<small>-</small>'
                .     '</td>' . "\n"
                ;
            }
        }

        if ( $is_allowedToEdit )
        {
                        $out .= '<td align="center">'
            .    '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=rqEditAssig&assigId=' . $anAssignment['id'] ) ) . '">'
            .    '<img src="' . get_icon_url('edit') . '" alt="' . get_lang('Modify') . '" /></a>'
            .    '</td>' . "\n"
            .    '<td align="center">'
            .    '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exRmAssig&assigId=' . $anAssignment['id'] ) ). '" onclick="return WORK.confirmationDel(\'' . clean_str_for_javascript($anAssignment['title']) . '\');">'
            .    '<img src="' . get_icon_url('delete') . '" alt="' . get_lang('Delete') . '" /></a>'
            .    '</td>' . "\n"
            .    '<td align="center">'
            ;

            if ( $anAssignment['visibility'] == "INVISIBLE" )
            {
                $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                .    '?cmd=exChVis&assigId=' . $anAssignment['id']
                .    '&vis=v')).'">'
                .    '<img src="' . get_icon_url('invisible') . '" alt="' . get_lang('Make visible') . '" />'
                .    '</a>'
                      ;
            }
            else
            {
                $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exChVis&assigId=' . $anAssignment['id'] . '&vis=i')).'">'
                .    '<img src="' . get_icon_url('visible') . '" alt="' . get_lang('Make invisible') . '" />'
                .    '</a>'
                ;
            }
            $out .= '</td>' . "\n"
            .    '</tr>' . "\n\n"
            ;
        }

        $atLeastOneAssignmentToShow = true;
    }

    if ( ! $atLeastOneAssignmentToShow )
    {
        $out .= '<tr>' . "\n"
        .    '<td colspan=' . $colspan . '>' . "\n"
        .    get_lang('There is no assignment at the moment')
        .    '</td>' . "\n"
        .    '</tr>' . "\n"
        ;
    }
    $out .= '</tbody>' . "\n"
    .     '</table>' . "\n\n";
}

if ( isset($displayAssigForm) && $displayAssigForm )
{
    $out .= '<div style="padding-top: 5px;"><small><span class="required">*</span>'
          . get_lang( 'Denotes required fields' )
          . '</small></div>';
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

<?php // $Id: user_work.php 14450 2013-05-15 12:02:23Z zefredz $

/**
 * CLAROLINE
 *
 * @version     Claroline 1.11 $Revision: 14450 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/CLWRK/
 * @package     CLWRK
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.8
 */

$tlabelReq = 'CLWRK';
require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

require_once './lib/assignment.class.php';
require_once './lib/submission.class.php';

require_once get_path('incRepositorySys') . '/lib/group.lib.inc.php';
include_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileUpload.lib.php';
include_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';
include_once get_path('incRepositorySys') . '/lib/file.lib.php';
include_once get_path('incRepositorySys') . '/lib/learnPath.lib.inc.php';
include_once get_path('incRepositorySys') . '/lib/sendmail.lib.php';
require_once get_path('incRepositorySys') . '/lib/utils/htmlsanitizer.lib.php';

$tbl_mdb_names = claro_sql_get_main_tbl();
$tbl_user      = $tbl_mdb_names['user'];
$tbl_rel_cours_user    = $tbl_mdb_names['rel_course_user'];

$tbl_cdb_names = claro_sql_get_course_tbl();
$tbl_wrk_assignment   = $tbl_cdb_names['wrk_assignment'   ];
$tbl_wrk_submission   = $tbl_cdb_names['wrk_submission'   ];

$tbl_group_team       = $tbl_cdb_names['group_team'       ];
$tbl_group_rel_team_user  = $tbl_cdb_names['group_rel_team_user'];

// use viewMode
claro_set_display_mode_available(true);

/*============================================================================
                     BASIC VARIABLES DEFINITION
  =============================================================================*/

$fileAllowedSize = get_conf('max_file_size_per_works') ;    //file size in bytes
$maxFilledSpace  = get_conf('maxFilledSpace',100000000);

// use with strip_tags function when strip_tags is used to check if a text is empty
// but a 'text' with only an image don't have to be considered as empty
$allowedTags = '<img>';

// initialise a html sanitizer authorizing the style attribute
$san = new Claro_Html_Sanitizer();
$san->allowStyle();

// initialise dialog box to an empty string, all dialog will be concat to it
$dialogBox = new DialogBox();

// initialise default view mode (values will be overwritten if needed)
$dispWrkLst     = true;     // view list is default
$dispWrkForm    = false;
$dispWrkDet     = false;
$is_feedback    = false;

/*============================================================================
                     CLEAN INFORMATIONS SENT BY USER
  =============================================================================*/
$cmd = ( isset($_REQUEST['cmd']) )?$_REQUEST['cmd']:'';

$assignmentId = ( isset($_REQUEST['assigId'])
                    && !empty($_REQUEST['assigId'])
                    && ctype_digit($_REQUEST['assigId'])
                    )
                    ? (int) $_REQUEST['assigId']
                    : false;

$authId = isset($_REQUEST['authId'])?(int)$_REQUEST['authId']:'';

if( !empty($_REQUEST['submitGroupWorkUrl']) )   $submitGroupWorkUrl = urldecode($_REQUEST['submitGroupWorkUrl']);
else                                            $submitGroupWorkUrl = null;

/*============================================================================
                          PREREQUISITES
  =============================================================================*/

/*--------------------------------------------------------------------
                REQUIRED : ASSIGNMENT INFORMATIONS
  --------------------------------------------------------------------*/
$assignment = new Assignment();

if ( !$assignmentId || !$assignment->load($assignmentId) )
{
    // we need to know in which assignment we are
    claro_redirect(Url::Contextualize('work.php'));
    exit();
}


/*--------------------------------------------------------------------
                    REQUIRED : USER INFORMATIONS
  --------------------------------------------------------------------*/
if( isset($_REQUEST['authId']) && !empty($_REQUEST['authId']) )
{
      if( $assignment->getAssignmentType() == 'GROUP')
    {
        $sql = "SELECT `name`
                FROM `" . $tbl_group_team . "`
                WHERE `id` = " . (int) $_REQUEST['authId'];
        $authField = 'group_id';
    }
    else
    {
        $sql = "SELECT CONCAT(`nom`,' ',`prenom`) as `authName`
                FROM `" . $tbl_user . "`
                WHERE `user_id` = " . (int) $_REQUEST['authId'];
        $authField = 'user_id';
    }
    $authName = claro_sql_query_get_single_value($sql);
}

$_user = claro_get_current_user_data();
$currentUserFirstName       = $_user['firstName'];
$currentUserLastName        = $_user['lastName'];

/*--------------------------------------------------------------------
                    CHECK IF WE HAVE USER AND ASSIGNMENT
  --------------------------------------------------------------------*/
if( empty($authName) )
{
    // we also need a user/group
    claro_redirect(Url::Contextualize("work.php"));
    exit();
}

/*--------------------------------------------------------------------
                        WORK INFORMATIONS
  --------------------------------------------------------------------*/

$submission = new Submission();

// if user request a specific submission
if( isset($_REQUEST['wrkId']) && !empty($_REQUEST['wrkId']) )
{
    if( !$submission->load($_REQUEST['wrkId']) )
    {
        $cmd = '';
    }
}


  /*--------------------------------------------------------------------
                        ASSIGNMENT CONTENT
  --------------------------------------------------------------------*/
if( $assignment->getSubmissionType() == "TEXTFILE"
      || ( claro_is_course_manager() && (isset($wrk) && !empty($wrk['original_id']) ) )
      || ( claro_is_course_manager() && ( $cmd == 'rqGradeWrk' || $cmd == 'exGradeWrk') )
  )
{
    // IF text file is the default assignment type
    //    OR this is a teacher modifying a feedback
    //    OR this is a teacher giving feedback to a work
    $assignmentContent = "TEXTFILE";
}
elseif( $assignment->getSubmissionType() == "FILE" )
{
    $assignmentContent = "FILE";
}
else //if( $assignment->getSubmissionType() == "TEXT" )
{
    $assignmentContent = "TEXT";
}
  /*--------------------------------------------------------------------
                        USER GROUP INFORMATIONS
  --------------------------------------------------------------------*/
// if this is a group assignement we will need some group infos about the user
if( $assignment->getAssignmentType() == 'GROUP' && claro_is_user_authenticated() )
{
    // get complete group list
    $sql = "SELECT `id`, `name`
            FROM `" . $tbl_group_team . "`";

    $groupList = claro_sql_query_fetch_all($sql);
    if( is_array($groupList) && !empty($groupList) )
    {
        foreach( $groupList AS $group )
        {
            // yes it is redundant but it is for a easier user later in the script
            $allGroupList[$group['id']]['id'] = $group['id'];
            $allGroupList[$group['id']]['name'] = $group['name'];
        }
    }

    if( claro_is_course_manager() )
    {
        $userGroupList = $allGroupList;
    }
    elseif( !empty($groupList) )
    {
        // get the list of group the user is in (if there is at least one group in course ...)
        $userGroupList = get_user_group_list(claro_get_current_user_id());
    }
    else
    {
        $userGroupList = array();
    }
}

/*============================================================================
                          PERMISSIONS
  =============================================================================*/

$assignmentIsVisible = (bool) ( $assignment->getVisibility() == 'VISIBLE' );

// --
$is_allowedToEditAll  = (bool) claro_is_allowed_to_edit(); // can submit, edit, delete

if( !$assignmentIsVisible && !$is_allowedToEditAll )
{
    // if assignment is not visible and user is not course admin or upper
    claro_redirect(Url::Contextualize("work.php"));
    exit();
}

// upload or update is allowed between start and end date or after end date if late upload is allowed
$uploadDateIsOk = $assignment->isUploadDateOk();

//-- is_allowedToEdit
// TODO check if submission has feedback

$autoFeedbackIsDisplayedForAuthId = (bool)
                                    ( trim(strip_tags($assignment->getAutoFeedbackText(),$allowedTags)) != '' || $assignment->getAutoFeedbackFilename() != '' )
                                    &&
                                    (
                                        $assignment->getAutoFeedbackSubmitMethod() == 'AFTERPOST' && count($assignment->getSubmissionList($_REQUEST['authId']) > 0)
                                        || ( $assignment->getAutoFeedbackSubmitMethod() == 'ENDDATE' && $assignment->getEndDate() <= time() )
                                    );

// if correction is automatically submitted user cannot edit his work
if( claro_is_user_authenticated() && !$autoFeedbackIsDisplayedForAuthId )
{
    if( $assignment->getAssignmentType() == 'GROUP' && claro_is_in_a_group() )
    {
        $userCanEdit = (bool) ( $submission->getGroupId() == claro_get_current_group_id() );
    }
    elseif( $assignment->getAssignmentType() == 'GROUP' )
    {
        // check if user is in the group that owns the work
        $userCanEdit = ( array_key_exists( $submission->getGroupId(), $userGroupList) );
    }
    elseif( $assignment->getAssignmentType() == 'INDIVIDUAL' )
    {
        // a work is set, assignment is individual, user is authed and the work is his work
        $userCanEdit = (bool) ( $submission->getUserId() == claro_get_current_user_id() );
    }
}
else
{
      // user not authed
      // OR a correction has already been made
      $userCanEdit = false;
}

$is_allowedToEdit = (bool)  (  ( $uploadDateIsOk && $userCanEdit ) || $is_allowedToEditAll );

//-- is_allowedToSubmit

if( $assignment->getAssignmentType() == 'INDIVIDUAL' )
{
    // user is authed and allowed
    $userCanPost = (bool) ( claro_is_user_authenticated() && claro_is_course_allowed() && $_REQUEST['authId'] == claro_get_current_user_id());
}
else
{
    $userCanPost = (bool) ( !empty($userGroupList) && isset($userGroupList[$_REQUEST['authId']]) );
}

$is_allowedToSubmit   = (bool) ( $assignmentIsVisible  && $uploadDateIsOk  && $userCanPost ) || $is_allowedToEditAll;


/*============================================================================
                          HANDLING FORM DATA
  =============================================================================*/
// execute this after a form has been send
// this instruction bloc will set some vars that will be used in the corresponding queries
// $wrkForm['filename'] , $wrkForm['wrkTitle'] , $wrkForm['authors'] ...


if ( $cmd == 'exDownload' )
{
    $workId = isset($_REQUEST['workId'])?$_REQUEST['workId']:null;

    $submission = new Submission();

    if ( $submission->load($workId) )
    {
        $submissionUserId = $submission->getUserId();
        $submissionGroupId = $submission->getGroupId();

        $userGroupList = array();

        if ( $assignment->getAssignmentType() == 'GROUP' )
        {
             $userGroupList = get_user_group_list(claro_get_current_user_id());
        }

        $is_allowedToDownload = (bool) $is_allowedToEditAll || $submissionUserId == claro_get_current_user_id() || isset($userGroupList[$submissionGroupId]) ;

        // check permission
        if ( $submission->getVisibility() == 'VISIBLE' || $is_allowedToDownload )
        {
            // read file
            $filePath = $assignment->getAssigDirSys().$submission->getSubmittedFilename();

            if ( claro_send_file($filePath) )
            {
                die();
            }
            else
            {
                $dialogBox->error( get_lang('Not found') );
            }
        }
        else
        {
            $dialogBox->error( get_lang('Not allowed') );
        }
    }
    else
    {
        $dialogBox->error( get_lang('Not found') );
    }

    // Submission not found or not allowed

    header('HTTP/1.1 404 Not Found');
    $interbredcrump[]= array ('url' => Url::Contextualize("../work/work.php"), 'name' => get_lang('Assignments'));
    $interbredcrump[]= array ('url' => Url::Contextualize("../work/work_list.php?authId=".$_REQUEST['authId']."&assigId=".$assignmentId ), 'name' => get_lang('Assignment'));
    
    $claroline->display->body->appendContent($dialogBox->render());

    echo $claroline->display->render();
    
    die();
}

if( isset($_REQUEST['submitWrk']) )
{

    $formCorrectlySent = true;

    // if authorized_content is TEXT or TEXTFILE, a text is required !
    if( $assignmentContent == "TEXT" || $assignmentContent == "TEXTFILE" )
    {
        if( !isset( $_REQUEST['wrkTxt'] ) || trim( strip_tags( $_REQUEST['wrkTxt'] ), $allowedTags ) == "" )
        {
            $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('Answer'))) );
            $formCorrectlySent = false;
            $wrkForm['wrkTxt'] = '';
        }
        else
        {
            $wrkForm['wrkTxt'] = $san->sanitize( $_REQUEST['wrkTxt'] );
        }
    }
    elseif( $assignmentContent == "FILE" )
    {
        // if authorized_content is FILE we don't have to check if txt is empty (not required)
        // but we have to check that the text is not only useless html tags
        if( !isset( $_REQUEST['wrkTxt'] ) || trim( strip_tags( $_REQUEST['wrkTxt'], $allowedTags )) == "" )
        {
            $wrkForm['wrkTxt'] = '';
        }
        else
        {
            $wrkForm['wrkTxt'] = $san->sanitize( $_REQUEST['wrkTxt'] );
        }
    }


    // check if a title has been given
    if( ! isset($_REQUEST['wrkTitle']) || trim($_REQUEST['wrkTitle']) == "" )
    {
        $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('Work title'))) );
        $formCorrectlySent = false;
        $wrkForm['wrkTitle'] = '';
    }
    else
    {
        // do not check if a title is already in use, title can be duplicate
        $wrkForm['wrkTitle'] = $san->sanitize( $_REQUEST['wrkTitle'] );
    }


    // check if a author name has been given
    if ( ! isset($_REQUEST['wrkAuthors']) || trim($_REQUEST['wrkAuthors']) == "")
    {
        if( claro_is_user_authenticated() )
        {
            $wrkForm['wrkAuthors'] = $currentUserFirstName." ".$currentUserLastName;
        }
        else
        {
            $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('Author(s)'))) );
            $formCorrectlySent = false;
            $wrkForm['wrkAuthors'] = '';
        }
    }
    else
    {
        $wrkForm['wrkAuthors'] = $san->sanitize( $_REQUEST['wrkAuthors'] );
    }


    // check if the score is between 0 and 100
    if ( isset($_REQUEST['wrkScore']) && is_numeric($_REQUEST['wrkScore']) )
    {
        if( $_REQUEST['wrkScore'] < -1 || $_REQUEST['wrkScore'] > 100 )
        {
            $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('Score'))) );
            $formCorrectlySent = false;
        }
        else
        {
            $wrkForm['wrkScore'] = $_REQUEST['wrkScore'];
        }
    }
    else
    {
        $wrkForm['wrkScore'] = '';
    }


    // check if a group id has been set if this is a group work type
    if( isset($_REQUEST['wrkGroup']) && $assignment->getAssignmentType() == "GROUP" )
    {
        // check that the group id is one of the student
        if ( array_key_exists($_REQUEST['wrkGroup'], $userGroupList ) || $is_allowedToEditAll )
        {
            $wrkForm['wrkGroup'] = $_REQUEST['wrkGroup'];
        }
        else
        {
            $dialogBox->error( get_lang('You are not a member of this group') );
            $formCorrectlySent = false;
            $wrkForm['wrkGroup'] = '';
        }
    }
    else
    {
        $wrkForm['wrkGroup'] = '';
    }

    // check if a private feedback has been submitted
    if( isset($_REQUEST['wrkPrivFbk']) && trim(strip_tags($_REQUEST['wrkPrivFbk'], $allowedTags)) != '' )
    {
        $wrkForm['wrkPrivFbk'] = $san->sanitize( $_REQUEST['wrkPrivFbk'] );
    }
    else
    {
        $wrkForm['wrkPrivFbk'] = '';
    }

    // no need to check and/or upload the file if there is already an error
    if($formCorrectlySent)
    {
        $wrkForm['filename'] = '';

        if ( isset($_FILES['wrkFile']['tmp_name'])
                && is_uploaded_file($_FILES['wrkFile']['tmp_name'])
                && $assignmentContent != "TEXT"
            )
        {
            if ($_FILES['wrkFile']['size'] > $fileAllowedSize)
            {
                $dialogBox->error( get_lang('You didnt choose any file to send, or it is too big') );
                $formCorrectlySent = false;
            }
            else
            {
                $newFilename = $_FILES['wrkFile']['name'] . add_extension_for_uploaded_file($_FILES['wrkFile']);

                $newFilename = replace_dangerous_char($newFilename);

                $newFilename = get_secure_file_name($newFilename);

                $wrkForm['filename'] = $assignment->createUniqueFilename($newFilename);


                if( !is_dir( $assignment->getAssigDirSys() ) )
                {
                      claro_mkdir( $assignment->getAssigDirSys() , CLARO_FILE_PERMISSIONS );
                }

                if( move_uploaded_file($_FILES['wrkFile']['tmp_name'], $assignment->getAssigDirSys().$wrkForm['filename']) )
                {
                    chmod($assignment->getAssigDirSys().$wrkForm['filename'],CLARO_FILE_PERMISSIONS);
                }
                else
                {
                    $dialogBox->error( get_lang('Cannot copy the file') );
                    $formCorrectlySent = false;
                }

                // remove the previous file if there was one
                if( isset($_REQUEST['currentWrkUrl']) )
                {
                      @unlink($assignment->getAssigDirSys().$_REQUEST['currentWrkUrl']);
                }
            }
        }
        elseif( $assignmentContent == "FILE" )
        {
            if( isset($_REQUEST['currentWrkUrl']) )
            {
                // if there was already a file and nothing was provided to replace it, reuse it
                $wrkForm['filename'] = $_REQUEST['currentWrkUrl'];
            }
            elseif( !is_null($submitGroupWorkUrl) )
            {
                $wrkForm['filename'] = $assignment->createUniqueFilename(basename($submitGroupWorkUrl)) ;

                $groupWorkFile = get_path('coursesRepositorySys') . '/' . claro_get_course_path() . '/group/' . claro_get_current_group_data('directory') . '/' . $submitGroupWorkUrl;

                $groupWorkFile = secure_file_path($groupWorkFile) ;

                if ( file_exists($groupWorkFile) )
                {
                    copy($groupWorkFile,$assignment->getAssigDirSys().$wrkForm['filename']);
                }
                else
                {
                    // if the main thing to provide is a file and that no file was sent
                    $dialogBox->error( get_lang('Unable to copy file : %filename', array('%filename' => basename($submitGroupWorkUrl))) );
                    $formCorrectlySent = false;
                }
            }
            elseif( $submission->getParentId() == 0 ) // do not display an error if this a feedback (file not required)
            {
                // if the main thing to provide is a file and that no file was sent
                $dialogBox->error( get_lang('Field \'%name\' is required', array('%name' => get_lang('File'))) );
                $formCorrectlySent = false;
            }
        }
        elseif( $assignmentContent == "TEXTFILE" )
        {
            // attached file is optionnal if work type is TEXT AND FILE
            // so the attached file can be deleted only in this mode
            if( !is_null($submitGroupWorkUrl) )
            {
                $wrkForm['filename'] = $assignment->createUniqueFilename(basename($submitGroupWorkUrl) . '.url');

                create_link_file($assignment->getAssigDirSys().$wrkForm['filename'], get_path('coursesRepositoryWeb') . claro_get_course_path() . '/' . $submitGroupWorkUrl);
            }

            // if delete of the file is required
            if(isset($_REQUEST['delAttacheDFile']) )
            {
                $wrkForm['filename'] = ''; // empty DB field
                @unlink($assignment->getAssigDirSys().$_REQUEST['currentWrkUrl']); // physically remove the file
            }
        }
    }// if($formCorrectlySent)

} //end if($_REQUEST['submitWrk'])


/*============================================================================
                          ADMIN ONLY COMMANDS
  =============================================================================*/
if($is_allowedToEditAll)
{
    /*--------------------------------------------------------------------
                        CHANGE VISIBILITY
    --------------------------------------------------------------------*/
    // change visibility of a work
    if( $cmd == 'exChVis' && isset($_REQUEST['wrkId']) )
    {
        if( isset($_REQUEST['vis']) )
        {
            $_REQUEST['vis'] == 'v' ? $visibility = 'VISIBLE' : $visibility = 'INVISIBLE';

            Submission::updateSubmissionVisibility($_REQUEST['wrkId'],$visibility);
        }
    }
    /*--------------------------------------------------------------------
                        DELETE A WORK
    --------------------------------------------------------------------*/
    if( $cmd == "exRmWrk" && isset($_REQUEST['wrkId']) )
    {
        // get name of file to delete AND name of file of the feedback of this work
        $sql = "SELECT `id`, `submitted_doc_path`
                  FROM `".$tbl_wrk_submission."`
                  WHERE `id` = ". (int)$_REQUEST['wrkId']."
                     OR `parent_id` = ". (int)$_REQUEST['wrkId'];

        $filesToDelete = claro_sql_query_fetch_all($sql);

        foreach($filesToDelete as $fileToDelete)
        {
            // delete the file
            @unlink($assignment->getAssigDirSys().$fileToDelete['submitted_doc_path']);

            // delete the database data of this work
            $sqlDelete = "DELETE FROM `".$tbl_wrk_submission."`
                              WHERE `id` = ". (int)$fileToDelete['id'];
            claro_sql_query($sqlDelete);
        }
    }
    /*--------------------------------------------------------------------
                        CORRECTION OF A WORK
    --------------------------------------------------------------------*/
    /*-----------------------------------
            STEP 2 : check & query
    -------------------------------------*/
    if( $cmd == "exGradeWrk" && isset($_REQUEST['gradedWrkId']) )
    {
        if( isset($formCorrectlySent) && $formCorrectlySent )
        {
            $submission->setAssignmentId($assignmentId);
            $submission->setUserId(claro_get_current_user_id());
            $submission->setTitle($wrkForm['wrkTitle']);
            $submission->setAuthor($wrkForm['wrkAuthors']);
            $submission->setVisibility($assignment->getDefaultSubmissionVisibility());
            $submission->setSubmittedText($wrkForm['wrkTxt']);
            $submission->setSubmittedFilename($wrkForm['filename']);

            $submission->setParentId($_REQUEST['gradedWrkId']);
            $submission->setPrivateFeedback($wrkForm['wrkPrivFbk']);
            $submission->setOriginalId($_REQUEST['authId']);
            $submission->setScore($wrkForm['wrkScore']);

            $submission->save();

            $dialogBox->success( get_lang('Feedback added') );

            // notify eventmanager that a new correction has been posted
            $eventNotifier->notifyCourseEvent('work_correction_posted',claro_get_current_course_id(), claro_get_current_tool_id(), $_REQUEST['gradedWrkId'], '0', '0');
            // mail notification if required by configuration
            if( get_conf( 'mail_notification' ) && ( claro_get_current_course_data( 'notify_feedbacks' ) || get_conf( 'automatic_mail_notification', false ) ) )
            {
                // get owner(s) email
                $userIdList = array();
                if( $assignment->getAssignmentType() == 'GROUP' )
                {
                    $gradedSubmission = new Submission;
                    $gradedSubmission->load( $submission->getParentId() );
                    $userIdList[] = $gradedSubmission->getUserId();
                }
                else
                {
                    $userIdList[] = $_REQUEST['authId'];
                }

                if( is_array($userIdList) )
                {
                    require_once dirname(__FILE__) . '/../messaging/lib/message/platformmessagetosend.lib.php';
                    require_once dirname(__FILE__) . '/../messaging/lib/recipient/userlistrecipient.lib.php';
                    
                    // subject
                    $subject =  get_lang('New assignment feedback posted');
                    
                    if( $assignment->getAssignmentType() == 'GROUP' && isset($_REQUEST['wrkGroup']) )
                        $authId = $wrkForm['wrkGroup'];
                    else
                        $authId = $_REQUEST['authId'];

                    $url = Url::Contextualize( get_path('rootWeb') . 'claroline/work/user_work.php?authId='.$authId.'&assigId='.$assignmentId );
                    
                    // email content
                    $body = get_lang('New assignment feedback posted') . "\n\n"
                    . $currentUserFirstName.' '.$currentUserLastName . "\n"
                    . '<a href="'.  claro_htmlspecialchars(Url::Contextualize($url)).'">' . $submission->getTitle() .'</a>' . "\n"
                    ;
                    
                    $message = new MessageToSend( claro_get_current_user_id(),$subject,$body );
                    
                    // TODO use official code everywhere : $message->setCourse(claro_get_current_course_data('officialCode'));
                    $message->setCourse(claro_get_current_course_id());
                    $message->setTools('CLWRK');

                    $recipient = new UserListRecipient();
                    $recipient->addUserIdList($userIdList);
                    
                    //$message->sendTo($recipient);
                    $recipient->sendMessage($message);
                }
            }
            // display flags
            $dispWrkLst = true;
        }
        else
        {
            // ask prepare form
            $cmd = "rqGradeWrk";
        }
    }
    /*-----------------------------------
            STEP 1 : prepare form
    -------------------------------------*/
    if( $cmd == "rqGradeWrk" && isset($_REQUEST['gradedWrkId']) )
    {
        $submissionToGrade = new Submission();
        $submissionToGrade->load($_REQUEST['gradedWrkId']);

        // prepare fields
        if( !isset($_REQUEST['submitWrk']) || !$_REQUEST['submitWrk'] )
        {
            // prefill some fields of the form
            $form['wrkTitle'  ] = $submissionToGrade->getTitle()." (".get_lang('Feedback').")";
            $form['wrkAuthors'] = $currentUserLastName." ".$currentUserFirstName;
            $form['wrkTxt'] = '';
            $form['wrkScore'  ] = -1;
            $form['wrkPrivFbk'] = '';
        }
        else
        {
            // there was an error in the form so display it with already modified values
            $form['wrkTitle'] = $san->sanitize( $_REQUEST['wrkTitle'] );
            $form['wrkAuthors'] = $_REQUEST['wrkAuthors'];
            $form['wrkTxt'] = $san->sanitize( $_REQUEST['wrkTxt'] );
            $form['wrkScore'] = $san->sanitize( $_REQUEST['wrkScore'] );
            $form['wrkPrivFbk'] = $san->sanitize( $_REQUEST['wrkPrivFbk'] );
        }

        $cmdToSend = "exGradeWrk";

        $txtForFormTitle = get_lang('Add feedback');
        $isGrade = true;

        // display flags
        $dispWrkLst = false;
        $dispWrkForm = true;
        $dispWrkDet   = true;
        $is_feedback = true;
      }
} // if($is_allowedToEditAll)

/*============================================================================
                        ADMIN AND AUTHED USER COMMANDS
  =============================================================================*/
if ( $is_allowedToEdit )
{
    /*--------------------------------------------------------------------
                        EDIT A WORK
    --------------------------------------------------------------------*/
    /*-----------------------------------
            STEP 2 : check & query
    -------------------------------------*/
    if ( $cmd == "exEditWrk" && isset($_REQUEST['wrkId']) )
    {
        // if there is no error update database
        if ( isset($formCorrectlySent) && $formCorrectlySent )
        {
            $submission->setTitle($wrkForm['wrkTitle']);
            $submission->setAuthor($wrkForm['wrkAuthors']);
            $submission->setSubmittedText($wrkForm['wrkTxt']);
            $submission->setSubmittedFilename($wrkForm['filename']);

            if( !empty($wrkForm['wrkPrivFbk']) )     $submission->setPrivateFeedback($wrkForm['wrkPrivFbk']);

            if( !empty($wrkForm['wrkScore']) || $wrkForm['wrkScore'] == 0 ) $submission->setScore($wrkForm['wrkScore']);

            if( $assignment->getAssignmentType() == 'GROUP' && isset($wrkForm['wrkGroup']) )
            {
                $submission->setGroupId($wrkForm['wrkGroup']);
            }

            $submission->save();

            $dialogBox->success( get_lang('Work modified') );

            // display flags
            $dispWrkLst = true;
        }
        else
        {
            // ask prepare form
            $cmd = "rqEditWrk";
        }
    }

    /*-----------------------------------
        STEP 1 : prepare form
    -------------------------------------*/
    if( $cmd == "rqEditWrk" && isset($_REQUEST['wrkId']) )
    {
        // prepare fields
        if( !isset($_REQUEST['submitWrk']) || !$_REQUEST['submitWrk'] )
        {
            // prefill some fields of the form
            $form['wrkTitle'] = $submission->getTitle();
            $form['wrkAuthors'] = $submission->getAuthor();
            $form['wrkGroup'] = $submission->getGroupId();
            $form['wrkTxt'] = $submission->getSubmittedText();
            $form['wrkUrl'] = $submission->getSubmittedFilename();
            $form['wrkPrivFbk'] = $submission->getPrivateFeedback();
            $form['wrkScore'] = $submission->getScore();
        }
        else
        {
              // there was an error in the form so display it with already modified values
            $form['wrkTitle'] = $wrkForm['wrkTitle'];
            $form['wrkAuthors'] = $wrkForm['wrkAuthors'];
            $form['wrkGroup'] = $wrkForm['wrkGroup'];
            $form['wrkTxt'] = $wrkForm['wrkTxt'];
            $form['wrkUrl'] = (isset($_REQUEST['currentWrkUrl']))?$_REQUEST['currentWrkUrl']:'';
            $form['wrkPrivFbk'] = $wrkForm['wrkPrivFbk'];
            $form['wrkScore'] = $wrkForm['wrkScore'];
        }
        $cmdToSend = "exEditWrk";
        // fill the title of the page
        $txtForFormTitle = get_lang('Modify a work');

        // display flags
        $dispWrkLst = false;
        $dispWrkForm  = true;
        // only if this is a correction
        if( $submission->getParentId() > 0 ) $is_feedback = true;
    }
}
/*============================================================================
 COMMANDS FOR : ADMIN, AUTHED USERS
  =============================================================================*/
if( $is_allowedToSubmit )
{
    /*--------------------------------------------------------------------
                          SUBMIT A WORK
      --------------------------------------------------------------------*/
    /*-----------------------------------
            STEP 2 : check & quey
      -------------------------------------*/
    if( $cmd == "exSubWrk" )
    {
        if( isset($formCorrectlySent) && $formCorrectlySent )
        {
            if ( $assignment->getAssignmentType() != 'GROUP' && claro_is_allowed_to_edit() && $authId )
            {
                $posterId = $authId; 
            }
            else
            {
                $posterId = claro_get_current_user_id();
            }
            
            if ( $posterId != claro_get_current_user_id() )
            {
                Console::info( "CLWORK: user #" 
                    . claro_get_current_user_id() 
                    . " posted a submission in assigment #{$assignmentId} in course "
                    . claro_get_current_course_id() . " in place of user #{$authId}" );
            }
            
            $submission->setAssignmentId($assignmentId);
            $submission->setUserId( $posterId );
            $submission->setTitle($wrkForm['wrkTitle']);
            $submission->setAuthor($wrkForm['wrkAuthors']);
            $submission->setVisibility($assignment->getDefaultSubmissionVisibility());
            $submission->setSubmittedText( $wrkForm['wrkTxt'] );
            $submission->setSubmittedFilename($wrkForm['filename']);

            if( $assignment->getAssignmentType() == 'GROUP' && isset($wrkForm['wrkGroup']) )
            {
                $submission->setGroupId($wrkForm['wrkGroup']);
            }

            $submission->save();

            $dialogBox->success( get_lang('Work added') );

            // notify eventmanager that a new submission has been posted
            $eventNotifier->notifyCourseEvent("work_submission_posted",claro_get_current_course_id(), claro_get_current_tool_id(), $assignmentId, '0', '0');

            if( get_conf( 'mail_notification' ) && ( claro_get_current_course_data( 'notify_submissions' ) || get_conf( 'automatic_mail_notification', false ) ) )
            {
                // get teacher(s) mail
                $sql = "SELECT `U`.`user_id`
                        FROM `".$tbl_rel_cours_user."` AS `CU`,`".$tbl_user."` AS `U`
                        WHERE `CU`.`user_id` = `U`.`user_id`
                        AND `CU`.`code_cours` = '".claro_get_current_course_id()."'
                        AND `CU`.`isCourseManager` = 1";

                $userIdList = claro_sql_query_fetch_all_rows($sql);

                if( is_array($userIdList) && !empty($userIdList) )
                {
                    require_once dirname(__FILE__) . '/../messaging/lib/message/platformmessagetosend.lib.php';
                    require_once dirname(__FILE__) . '/../messaging/lib/recipient/userlistrecipient.lib.php';

                    // subject
                    $subject = $_user['firstName'] . ' ' .$_user['lastName'] . ' : ' . get_lang('New submission posted in assignment tool.');

                    if( $assignment->getAssignmentType() == 'GROUP' && isset($_REQUEST['wrkGroup']) )
                        $authId = $wrkForm['wrkGroup'];
                    else
                        $authId = $_REQUEST['authId'];

                    $url = Url::Contextualize( get_path('rootWeb') . 'claroline/work/user_work.php?authId=' . $authId . '&assigId=' . $assignmentId );

                    // email content
                    $body = get_lang('New submission posted in assignment tool.') . "\n\n"
                    . $_user['firstName'] . ' ' .$_user['lastName'] . "\n"
                    . '<a href="'.claro_htmlspecialchars($url).'">' . $wrkForm['wrkTitle'] .'</a>' . "\n"
                    ;

                    $message = new MessageToSend( claro_get_current_user_id(),$subject,$body );
                    // TODO use official code everywhere : $message->setCourse(claro_get_current_course_data('officialCode'));
                    $message->setCourse(claro_get_current_course_id());
                    $message->setTools('CLWRK');

                    $recipient = new UserListRecipient();
                    foreach( $userIdList as $thisUser )
                    {
                       $recipient->addUserId( (int)$thisUser['user_id'] );
                    }

                    $recipient->sendMessage($message);
                }
            }

            // display flags
            $dispWrkLst = true;
        }
        else
        {
            // ask prepare form
            $cmd = "rqSubWrk";
        }

    }
  /*-----------------------------------
            STEP 1 : prepare form
  -------------------------------------*/
  if( $cmd == "rqSubWrk" )
  {
      // prepare fields
      if( !isset($_REQUEST['submitWrk']) || !$_REQUEST['submitWrk'] )
      {
            // prefill som fields of the form
            $form['wrkTitle'] = "";
            
            if ( claro_is_allowed_to_edit() && $authName )
            {
                $form['wrkAuthors'] = $authName;
            }
            else
            {
                $form['wrkAuthors'] = $currentUserLastName." ".$currentUserFirstName;
            }
            
            $form['wrkGroup'] = "";
            $form['wrkTxt'] = "";
      }
      else
      {
            // there was an error in the form so display it with already modified values
            $form['wrkTitle'] = (!empty($_REQUEST['wrkTitle']))?$_REQUEST['wrkTitle']:'';
            $form['wrkAuthors'] = (!empty($_REQUEST['wrkAuthors']))?$_REQUEST['wrkAuthors']:'';
            $form['wrkGroup'] = (!empty($_REQUEST['wrkGroup']))?$_REQUEST['wrkGroup']:'';
            $form['wrkTxt'] = (!empty($_REQUEST['wrkTxt']))?$_REQUEST['wrkTxt']:'';
      }

    // request the form with correct cmd
    $cmdToSend = "exSubWrk";
    
    // fill the title of the page
    $txtForFormTitle = get_lang('Submit a work');

    // display flags
    $dispWrkLst = false;
    $dispWrkForm  = true;
  }
} // if is_allowedToSubmit

/*============================================================================
                          DISPLAY
  =============================================================================*/
if( !$dispWrkForm && !$dispWrkDet )
{
      // display flags
      $dispWrkLst = true;
}

/*--------------------------------------------------------------------
                    HEADER
    --------------------------------------------------------------------*/
CssLoader::getInstance()->load( 'clwrk', 'screen');

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance()->addLangVar('Are you sure to delete %name ?');

JavascriptLoader::getInstance()->load('work');

$htmlHeadXtra[] =
'<script type="text/javascript">
function confirmation (name)
{
    if (confirm(" '.clean_str_for_javascript(get_lang('Are you sure to delete')).' "+ name + " ?  " ))
        {return true;}
    else
        {return false;}
}
</script>';


if( $dispWrkDet || $dispWrkForm )
{
      // add parameters in query string to prevent the 'refresh' interbredcrump link to display the list of works instead of the form
      $params = "?authId=".$_REQUEST['authId']."&assigId=".$assignmentId
      . ( isset($_REQUEST['wrkId'])?"&wrkId=".$_REQUEST['wrkId']:"" )
      . "&cmd=".$cmd;
      
      $nameTools = get_lang('Submission');
      ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize($_SERVER['PHP_SELF'] . $params ));
      ClaroBreadCrumbs::getInstance()->prepend( $authName, Url::Contextualize('../work/user_work.php?authId='.$_REQUEST['authId'].'&assigId='.$assignmentId) );
}
else
{
      $nameTools = $authName;
      ClaroBreadCrumbs::getInstance()->setCurrent( $nameTools, Url::Contextualize($_SERVER['PHP_SELF'] . '?authId='.$_REQUEST['authId'].'&assigId='.$assignmentId ) );
}

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Assignment'), Url::Contextualize('../work/work_list.php?authId='.$_REQUEST['authId'].'&assigId='.$assignmentId) );
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Assignments'), Url::Contextualize('../work/work.php') );

$out = '';

/*--------------------------------------------------------------------
                    TOOL TITLE
    --------------------------------------------------------------------*/

// Command list
$cmdList = array();

if( $is_allowedToSubmit )
{
    $cmdList[] = array(
        'name' => get_lang('Submit a work'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
              . '?authId=' . $_REQUEST['authId']
              . '&assigId=' . $assignmentId
              . '&cmd=rqSubWrk'))
    );
}

$pageTitle['mainTitle'] = get_lang('Assignment'); $pageTitle['subTitle'] = $assignment->getTitle();

if( $assignment->getAssignmentType() == 'GROUP' )
{
    $pageTitle['supraTitle'] = get_lang('Group') . ' : ' . $authName . "\n";
    if( $is_allowedToEditAll ) $pageTitle['supraTitle'] .=  '<small>(<a href="'.claro_htmlspecialchars( Url::Contextualize('../group/group_space.php?gidReq='.$_REQUEST['authId'] ) ).'">'.get_lang('View group data').'</a>)</small>'."\n";
}
else
{
    $pageTitle['supraTitle'] = get_lang('User') . ' : ' . $authName . "\n";
    if( $is_allowedToEditAll ) $pageTitle['supraTitle'] .=  '<small>(<a href="'.claro_htmlspecialchars( Url::Contextualize('../user/userInfo.php?uInfo='.$_REQUEST['authId'] ) ).'">'.get_lang('View user data').'</a>)</small>'."\n";
}
$out .= claro_html_tool_title($pageTitle, null, $cmdList);

/*--------------------------------------------------------------------
                          FORMS
  --------------------------------------------------------------------*/
if( $is_allowedToSubmit )
{
    $out .= $dialogBox->render();

    if( $dispWrkForm )
    {
            /**
             * ASSIGNMENT INFOS
             */
            $out .= '<p>' . "\n" . '<small>' . "\n"
            . '<b>' . get_lang('Title') . '</b> : ' . "\n"
            . $assignment->getTitle() . '<br />'  . "\n"
            . get_lang('<b>From</b> %start_date <b>until</b> %end_date',
                   array ( '%start_date' => claro_html_localised_date(get_locale('dateTimeFormatLong'), $assignment->getStartDate()),
                           '%end_date' => claro_html_localised_date(get_locale('dateTimeFormatLong'), $assignment->getEndDate()) ) )
            . '<br />'  .  "\n"
            . '<b>' . get_lang('Submission type') . '</b> : ' . "\n";

            if( $assignment->getSubmissionType() == 'TEXT'  )
                $out .= get_lang('Text only (text required, no file)');
            elseif( $assignment->getSubmissionType() == 'TEXTFILE' )
                $out .= get_lang('Text with attached file (text required, file optional)');
            else
                $out .= get_lang('File (file required, description text optional)');


            $out .= '<br />'  .  "\n"

            . '<b>' . get_lang('Submission visibility') . '</b> : ' . "\n"
            . ($assignment->getDefaultSubmissionVisibility() == 'VISIBLE' ? get_lang('Visible for all users') : get_lang('Only visible for teacher(s) and submitter(s)'))

            . '<br />'  .  "\n"

            . '<b>' . get_lang('Assignment type') . '</b> : ' . "\n"
            . ($assignment->getAssignmentType() == 'INDIVIDUAL' ? get_lang('Individual') : get_lang('Groups') )

            . '<br />'  .  "\n"

            . '<b>' . get_lang('Allow late upload') . '</b> : ' . "\n"
            . ($assignment->getAllowLateUpload() == 'YES' ? get_lang('Users can submit after end date') : get_lang('Users can not submit after end date') )

            . '</small>' . "\n" . '</p>' . "\n";

            // description of assignment
            if( trim($assignment->getDescription()) != '' )
            {
                $out .= '<b><small>' . get_lang('Description') . '</small></b><br />' . "\n"
                . '<blockquote>' . "\n" . '<small>' . "\n"
                . claro_parse_user_text($assignment->getDescription())
                . '</small>' . "\n" . '</blockquote>' . "\n"
                . '<br />' . "\n"
                ;
            }

            $out .= '<h4>'.$txtForFormTitle.'</h4>'."\n"
                  . '<p><a class="backLink" href="'.claro_htmlspecialchars( Url::Contextualize( $_SERVER['SCRIPT_NAME'].'?authId='.$_REQUEST['authId'].'&assigId='.$assignmentId ) ).'">'
                  . get_lang('Back').'</a></p>'."\n"
                  . '<form method="post" action="'.$_SERVER['PHP_SELF'].'?assigId='.$assignmentId.'&authId='.$_REQUEST['authId'].'" enctype="multipart/form-data">'."\n"
                  . '<input type="hidden" name="claroFormId" value="'.uniqid('').'" />'."\n"
                  . '<input type="hidden" name="cmd" value="'.$cmdToSend.'" />'."\n"
                  . claro_form_relay_context();

            if( isset($_REQUEST['wrkId']) )
            {
                $out .= '<input type="hidden" name="wrkId" value="'.$_REQUEST['wrkId'].'" />'."\n";
            }
            elseif( isset($_REQUEST['gradedWrkId']) )
            {
                $out .= '<input type="hidden" name="gradedWrkId" value="'.$_REQUEST['gradedWrkId'].'" />'."\n";
            }

            $out .=  '<fieldset>'."\n"
                  .'<dl>'."\n"
                  .'<dt><label for="wrkTitle">'.get_lang('Title').'&nbsp;<span class="required">*</span></label></dt>'."\n"
                  .'<dd><input type="text" name="wrkTitle" id="wrkTitle" size="50" maxlength="200" value="'.claro_htmlspecialchars($form['wrkTitle']).'" /></dd>'."\n"
                  .'<dt><label for="wrkAuthors">'.get_lang('Author(s)').'&nbsp;<span class="required">*</span></label></dt>'."\n"
                  .'<dd><input type="text" name="wrkAuthors" id="wrkAuthors" size="50" maxlength="200" value="'.claro_htmlspecialchars($form['wrkAuthors']).'" /></dd>'."\n";

            // display the list of groups of the user
            if( $assignment->getAssignmentType() == "GROUP" &&
                    !empty($userGroupList) || (claro_is_course_manager() && claro_is_in_a_group() )
                )
            {
                $out .= '<dt><label for="wrkGroup">'.get_lang('Group').'</label></dt>'."\n";

                if( claro_is_in_a_group() )
                {
                    $out .= '<dd>'."\n"
                          .'<input type="hidden" name="wrkGroup" value="' . claro_get_current_group_id() . '" />'
                          .claro_get_current_group_data('name')
                          .'</dd>'."\n";
                }
                elseif(isset($_REQUEST['authId']) )
                {
                    $out .= '<dd>'."\n"
                          .'<input type="hidden" name="wrkGroup" value="'.$_REQUEST['authId'].'" />'
                          .$userGroupList[$_REQUEST['authId']]['name']
                          .'</dd>'."\n";
                }
                else
                {
                    // this part is mainly for courseadmin as he have a link in the workList to submit a work
                    $out .= '<dd>'."\n"
                          . '<select name="wrkGroup" id="wrkGroup">'."\n";
                    foreach( $userGroupList as $group )
                    {
                          $out .= '<option value="'.$group['id'].'"';
                          if( isset($form['wrkGroup']) && $form['wrkGroup'] == $group['id'] || $_REQUEST['authId'] == $group['id'] )
                          {
                                $out .= 'selected="selected"';
                          }
                          $out .= '>'.$group['name'].'</option>'."\n";
                    }
                    $out .= '</select>'."\n"
                          . '</dd>'."\n";
                }
            }

            // display file box
            if( $assignmentContent == "FILE" || $assignmentContent == "TEXTFILE" )
            {
                  // if we are in edit mode and that a file can be edited : display the url of the current file and the file box to change it
                  if( isset($form['wrkUrl']) )
                  {
                        $out .= '<dt>';
                        // display a different text according to the context
                        if( $assignmentContent == "TEXT"  )
                        {
                              // if text is required, file is considered as a an attached document
                              $out .= get_lang('Current attached file');
                        }
                        else
                        {
                              // if the file is required and the text is only a description of the file
                              $out .= get_lang('Current file');
                        }
                        if( !empty($form['wrkUrl']) )
                        {
                            $target = ( get_conf('open_submitted_file_in_new_window') ? 'target="_blank"' : '');

                            // display the name of the file, with a link to it, an explanation of what to to to replace it and a checkbox to delete it

                            $completeWrkUrl = Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exDownload'
                                            . '&authId=' . $_REQUEST['authId']
                                            . '&assigId=' . $assignmentId
                                            . '&workId=' . $_REQUEST['wrkId'] ) ;

                            $out .= '<input type="hidden" name="currentWrkUrl" value="'.$form['wrkUrl'].'" />'
                            .  '</dt>'."\n"
                            .  '<dd>'
                            .  '<a href="'.claro_htmlspecialchars($completeWrkUrl).'" ' . $target . '>'.$form['wrkUrl'].'</a>'
                            .  '<br />';

                            if( $assignmentContent == "TEXTFILE" )
                            {
                                // we can remove the file only if we are in a TEXTFILE context, in file context the file is required !
                                $out .= '<input type="checkBox" name="delAttacheDFile" id="delAttachedFile" />' . "\n"
                                .  '<label for="delAttachedFile">'.get_lang('Check this box to delete the attached file').'</label>' . "\n";
                            }
                            $out .= get_lang('Upload a new file to replace the file').'</td>'."\n"
                            .  '</dd>'."\n\n";
                        }
                        else
                        {
                              $out .= '&nbsp;:'
                                    .'</dd>'
                                    .get_lang('- none -')
                                    .'</dd>'."\n\n";
                        }
                  }

                $out .= '<dt><label for="wrkFile">';

                // display a different text according to the context
                if( $assignmentContent == "TEXTFILE" || $is_feedback )
                {
                    // if text is required, file is considered as a an attached document
                    $out .= get_lang('Attach a file');
                }
                else
                {
                    // if the file is required and the text is only a description of the file
                    $out .= get_lang('Upload document').'&nbsp;<span class="required">*</span>';
                }
                
                $out .= '</label></dt>'."\n";
                
                if( !empty($submitGroupWorkUrl) )
                {
                    // Secure download
                    $file = $submitGroupWorkUrl;

                    // FIXME : secureDocumentDownload ?
                    if ( $GLOBALS['is_Apache'] && get_conf('secureDocumentDownload') )
                    {
                        $groupWorkUrl = Url::Contextualize( get_path('clarolineRepositoryWeb') . 'backends/download.php'.str_replace('%2F', '/', rawurlencode($file)) . '?gidReq=' . claro_get_current_group_id() );
                    }
                    else
                    {
                        $groupWorkUrl = Url::Contextualize( get_path('clarolineRepositoryWeb') . 'backends/download.php?url=' . rawurlencode($file) .'&gidReq=' . claro_get_current_group_id() );
                    }

                    $out .= '<dd>'
                        .'<input type="hidden" name="submitGroupWorkUrl" value="'.claro_htmlspecialchars($submitGroupWorkUrl).'" />'
                        .'<a href="' . claro_htmlspecialchars($groupWorkUrl) .'">'.basename($file).'</a>'
                        .'</dd>'."\n";
                }
                else
                {
                  $maxFileSize = min(get_max_upload_size($maxFilledSpace,$assignment->getAssigDirSys()), $fileAllowedSize);

                  $out .= '<dd>' . "\n"
                        . '<input type="file" name="wrkFile" id="wrkFile" size="30" /><br />'
                        . '<p class="notice">'.get_lang('Max file size : %size', array( '%size' => format_file_size($maxFileSize))).'</p></td>'."\n"
                        . '</dd>'."\n\n";
                }
            }

            if( $assignmentContent == "FILE" && !$is_feedback )
            {
                // display standard html textarea
                // used for description of an uploaded file
                $out .= '<dt>'
                . '<label for="wrkTxt">'
                . get_lang('File description')
                . '<br /></label>'
                . '</dt>'
                . '<dd>'."\n"
                . '<textarea name="wrkTxt" cols="40" rows="10">'.$form['wrkTxt'].'</textarea>'
                . '</dd>'."\n";
            }
            elseif( $assignmentContent == "TEXT" || $assignmentContent == "TEXTFILE" || $is_feedback )
            {
                // display enhanced textarea using claro_html_textarea_editor
                $out .= '<dt>'
                . '<label for="wrkTxt">'
                . get_lang('Answer')
                . '&nbsp;<span class="required">*</span></label></dt>'."\n"
                . '<dd>'
                . claro_html_textarea_editor('wrkTxt', $form['wrkTxt'])
                . '</dd>'."\n\n";
            }

            if( $is_feedback )
            {
                $out .= '<dt>'
                . '<label for="wrkPrivFbk">'
                . get_lang('Private feedback')
                . '<br />'
                . '<small>'.get_lang('Course administrator only').'</small>'
                . '</label></dt>'
                . '<dd>'."\n"
                . '<textarea name="wrkPrivFbk" cols="40" rows="10">'. $san->sanitize( $form['wrkPrivFbk'] ) .'</textarea>'
                . '</dd>'."\n\n";
                
                // if this is a correction we have to add an input for the score/grade/results/points
                $wrkScoreField = '<select name="wrkScore" id="wrkScore">'."\n"
                                    .'<option value="-1"';
                // add selected attribute if needed
                if( $form['wrkScore'] == -1 )
                {
                    $wrkScoreField .= ' selected="selected"';
                }
                $wrkScoreField .= '>'.get_lang('No score').'</option>'."\n";

                for($i=0;$i <= 100; $i++)
                {
                    $wrkScoreField .= '<option value="'.$i.'"';
                    if($i == $form['wrkScore'])
                    {
                        $wrkScoreField .= ' selected="selected"';
                    }
                    $wrkScoreField .= '>'.$i.'</option>'."\n";
                }
                $wrkScoreField .= '</select> %';
                $out .= '<dt><label for="wrkScore">'.get_lang('Score').'</label></dt>'."\n"
                . '<dd>'
                . $wrkScoreField
                . '</dd>'."\n\n";
            }

            $out .= '</dl>'."\n"
            . '</fieldset>'."\n\n"
            . '<input type="submit" name="submitWrk" value="'.get_lang('Ok').'" />'."\n"
            . '</form>'."\n\n"
            . '<p class="notice">'
            . get_lang('<span class="required">*</span> denotes required field')
            . '</p>';
      }
}


/*--------------------------------------------------------------------
                          SUBMISSION LIST
  --------------------------------------------------------------------*/
if( $dispWrkLst )
{
    $showOnlyAuthorCondition = '';
    if( get_conf('show_only_author') && !$is_allowedToEditAll )
    {
        // security check to avoid a user to see others submissions if not permitted
        if( $assignment->getAssignmentType() == 'GROUP' && !isset($userGroupList[$_REQUEST['authId']]) )
        {
            if( ! isset($userGroupIdList) )
            {
                $userGroupIdList = array();
                foreach( $userGroupList as $userGroup )
                {
                    $userGroupIdList[] = $userGroup['id'];
                }
            }
            $showOnlyAuthorCondition = "AND `".$authField."` IN (".implode(',',$userGroupIdList).")";
        }
        elseif( $assignment->getAssignmentType() == 'INDIVIDUAL' && claro_get_current_user_id() != $_REQUEST['authId'] )
        {
            $showOnlyAuthorCondition = "AND `".$authField."` = ". (int)claro_get_current_user_id();
        }
    }


    // select all submissions from this user in this assignment (not feedbacks !)
    // TODO  * would be replace by fieldnames
    $sql = "SELECT *,
                UNIX_TIMESTAMP(`creation_date`) AS `unix_creation_date`,
                UNIX_TIMESTAMP(`last_edit_date`) as `unix_last_edit_date`
            FROM `".$tbl_wrk_submission."`
            WHERE `".$authField."` = ". (int)$_REQUEST['authId']."
                AND `original_id` IS NULL
                AND `assignment_id` = ".(int)$assignmentId."
                ". $showOnlyAuthorCondition . "
            ORDER BY `last_edit_date` ASC";

    $workList = claro_sql_query_fetch_all($sql);

    // build 'parent_id' condition
    $parentCondition = ' ';
    foreach( $workList as $work )
    {
        $parentCondition .= " OR `parent_id` = ". (int) $work['id'];
    }
    // select all feedback relating to the user submission in this assignment
    // TODO  * would be replace by fieldnames
    $sql = "SELECT *,
                UNIX_TIMESTAMP(`creation_date`) AS `unix_creation_date`,
                UNIX_TIMESTAMP(`last_edit_date`) as `unix_last_edit_date`
            FROM `".$tbl_wrk_submission."`
            WHERE 0 = 1
                AND `assignment_id` = ". (int) $assignmentId . "
                " . $parentCondition;

    $feedbackLst = claro_sql_query_fetch_all($sql);

    $wrkAndFeedbackLst = array();
    
    // create an ordered list with all submission directly followed by the related correction(s)
    foreach( $workList as $work )
    {
        $is_allowedToViewThisWrk = (bool) $is_allowedToEditAll
                                || $work['user_id'] == claro_get_current_user_id()
                                || isset($userGroupList[$work['group_id']]);
        
        if( $work['visibility'] == 'VISIBLE' || $is_allowedToViewThisWrk )
        {
            $wrkAndFeedbackLst[] = $work;
            
            foreach( $feedbackLst as $feedback )
            {
                if( $feedback['parent_id'] == $work['id']
                    && ( ( $feedback['visibility'] == 'VISIBLE' && $is_allowedToViewThisWrk ) || $is_allowedToEditAll )
                    )
                {
                    $wrkAndFeedbackLst[] = $feedback;
                }
            }
        }
    }

    if( is_array($wrkAndFeedbackLst) && count($wrkAndFeedbackLst) > 0 )
    {
        $i = 0;
        foreach ( $wrkAndFeedbackLst as $work )
        {
            $is_feedback = !is_null($work['original_id']) && !empty($work['original_id']);

            $has_feedback =     !$is_feedback
                            &&     ( $autoFeedbackIsDisplayedForAuthId
                                ||     (isset($wrkAndFeedbackLst[$i+1]) && $wrkAndFeedbackLst[$i+1]['parent_id'] == $work['id'])
                                );

            $is_allowedToViewThisWrk = (bool)$is_allowedToEditAll || $work['user_id'] == claro_get_current_user_id() || isset($userGroupList[$work['group_id']]);


            $is_allowedToEditThisWrk =
                (bool) $is_allowedToEditAll
                || ( $is_allowedToViewThisWrk
                    && $uploadDateIsOk
                    && !$has_feedback
                    )
                ;
            
            $style = array('item');
            
            if( $work['visibility'] == "INVISIBLE" && $is_allowedToEditAll )
            {
                $style[] = 'hidden';
            }

            if( $is_feedback )  $style[] = 'feedback';
            else                $style[] = 'work';

            // change some displayed text depending on the context
            if( $assignmentContent == "TEXTFILE" || $is_feedback )
            {
                $txtForFile = get_lang('Attached file');
                if( $is_feedback )    $txtForText = get_lang('Public feedback');
                else                $txtForText = get_lang('Answer');
            }
            elseif( $assignmentContent == "TEXT" )
            {
                $txtForText = get_lang('Answer');
            }
            elseif( $assignmentContent == "FILE" )
            {
                $txtForFile = get_lang('Uploaded file');
                $txtForText = get_lang('File description');
            }
            
            // title (and edit links)
            $out .= '<div class="'. implode(' ', $style) .'">' . "\n"
            
            . '<h1 class="'. ( !$is_feedback ? 'claroBlockSuperHeader':'blockHeader') . '">' . "\n"
            . $san->sanitize( $work['title'] ) . "\n"
            . '</h1>' . "\n"
            ;
            
            // content
            $out .= '<div class="content">' . "\n";
            
            // author
            $out .= '<div class="workInfo">' . "\n"
            . '<span class="workInfoTitle">' . get_lang('Author(s)') . '&nbsp;: </span>' . "\n"
            . '<div class="workInfoValue">' . "\n"
            . $san->sanitize( $work['authors'] ) . "\n"
            . '</div>' . "\n"
            . '</div>' . "\n\n"
            ;

            // group
            if( $assignment->getAssignmentType() == 'GROUP' && claro_is_user_authenticated() && !$is_feedback )
            {
                // display group if this is a group assignment and if this is not a correction
                $out .= '<div class="workInfo">' . "\n"
                . '<span class="workInfoTitle">' . get_lang('Group') . '&nbsp;: </span>' . "\n"
                . '<div class="workInfoValue">' . "\n"
                . $allGroupList[$work['group_id']]['name'] . "\n"
                . '</div>' . "\n"
                . '</div>' . "\n\n"
                ;
            }

            // file
            if( $assignmentContent != 'TEXT' )
            {
                if( !empty($work['submitted_doc_path']) )
                {
                    $target = ( get_conf('open_submitted_file_in_new_window') ? 'target="_blank"' : '');
                    // show file if this is not a TEXT only work
                    $out .= '<div class="workInfo">' . "\n"
                    . '<span class="workInfoTitle">' . $txtForFile . '&nbsp;: </span>' . "\n"
                    . '<div class="workInfoValue">' . "\n"
                    . '<a href="' . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=exDownload'
                    . '&authId=' . $_REQUEST['authId']
                    . '&assigId=' . $assignmentId
                    . '&workId=' . $work['id'] ) ) . '" ' . $target . '>' . "\n"
                    . $work['submitted_doc_path'] . "\n"
                    . '<img src="' . get_icon_url('download') . '" alt="'.get_lang('Download').'" />' . "\n"
                    . '</a>' . "\n"
                    . '<small>(' . format_file_size(claro_get_file_size($assignment->getAssigDirSys().$work['submitted_doc_path'])) . ')</small>'
                    . '</div>' . "\n"
                    . '</div>' . "\n\n"
                ;
                }
                else
                {
                    $out .= '<div class="workInfo">' . "\n"
                    . '<span class="workInfoTitle">' . $txtForFile . '&nbsp;: </span>' . "\n"
                    . '<div class="workInfoValue">' . "\n"
                    . get_lang('- none -') . "\n"
                    . '</div>' . "\n"
                    . '</div>' . "\n\n"
                    ;
                }
            }

            // text
            $out .= '<div class="workInfo">' . "\n"
            . '<span class="workInfoTitle">' . $txtForText . '&nbsp;: </span>' . "\n"
            . '<div class="workInfoValue">' . "\n"
            . '<blockquote>' . "\n" . $san->sanitize( $work['submitted_text'] ) . "\n" . '&nbsp;</blockquote>' . "\n"
            . '</div>' . "\n"
            . '</div>' . "\n\n"
            ;

            // private feedback
            if( $is_feedback )
            {
                if( $is_allowedToEditAll )
                {
                    $out .= '<div class="workInfo">' . "\n"
                    . '<span class="workInfoTitle">' . get_lang('Private feedback') . '&nbsp;: </span>' . "\n"
                    . '<div class="workInfoValue">' . "\n"
                    . '<blockquote>' . "\n" . $san->sanitize( $work['private_feedback'] ) . "\n" . '&nbsp;</blockquote>' . "\n"
                    . '</div>' . "\n"
                    . '</div>' . "\n\n"
                    ;
                }
                
                // score
                $out .= '<div class="workInfo">' . "\n"
                . '<span class="workInfoTitle">' . get_lang('Score') . '&nbsp;: </span>' . "\n"
                . '<div class="workInfoValue">' . "\n"
                . ( ( $work['score'] == -1 ) ? get_lang('No score') : $work['score'].' %' )
                . '</div>' . "\n"
                . '</div>' . "\n\n"
                ;
            }
            
            // submission date
            $out .= '<div class="workInfo">' . "\n"
            . '<span class="workInfoTitle">' . get_lang('First submission date') . '&nbsp;: </span>' . "\n"
            . '<div class="workInfoValue">' . "\n"
            . claro_html_localised_date(get_locale('dateTimeFormatLong'), $work['unix_creation_date'])
            ;

            // display an alert if work was submitted after end date and work is not a correction !
            if( $assignment->getEndDate() < $work['unix_creation_date'] && !$is_feedback )
            {
                  $out .= ' <img src="' . get_icon_url('warning') . '" alt="'.get_lang('Late upload').'" />';
            }

            $out .= '</div>' . "\n"
            . '</div>' . "\n\n";
            
            // last edit date
            if( $work['unix_creation_date'] != $work['unix_last_edit_date'] )
            {
                $out .= '<div class="workInfo">' . "\n"
                .  '<span class="workInfoTitle">' . get_lang('Last edit date') . '&nbsp;: </span>' . "\n"
                .  '<div class="workInfoValue">' . "\n"
                . claro_html_localised_date(get_locale('dateTimeFormatLong'), $work['unix_last_edit_date']);
                
                // display an alert if work was submitted after end date and work is not a correction !
                if( $assignment->getEndDate() < $work['unix_last_edit_date'] && !$is_feedback )
                {
                    $out .= ' <img src="' . get_icon_url('warning') . '" alt="'.get_lang('Late upload').'" />';
                }

                $out .= '</div>' . "\n"
                . '</div>' . "\n\n";
            }
            
            // commands
            $out .= '<div class="workCmdList">' . "\n";
            
            // if user is allowed to edit, display the link to edit it
            if( $is_allowedToEditThisWrk )
            {
                // the work can be edited
                $out .= '<a href="' . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
                . '?authId=' . $_REQUEST['authId']
                . '&assigId='.$assignmentId
                . '&cmd=rqEditWrk&wrkId=' . $work['id'] ) ) . '">'
                . '<img src="' . get_icon_url('edit') . '" alt="'.get_lang('Modify').'" />'
                . '</a>' . "\n"
                ;
            }

            if( $is_allowedToEditAll )
            {
                $out .= '<a href="' . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
                . '?authId='.$_REQUEST['authId']
                . '&cmd=exRmWrk'
                . '&assigId=' . $assignmentId
                . '&wrkId=' . $work['id'] ) ) . '" '
                . 'onclick="return WORK.confirmationDel(\'' . clean_str_for_javascript($work['title']) . '\');">'
                . '<img src="' . get_icon_url('delete') . '" alt="'.get_lang('Delete').'" />'
                . '</a>' . "\n"
                ;

                if ($work['visibility'] == "INVISIBLE")
                {
                    $out .= '<a href="' . claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
                    . '?authId=' . $_REQUEST['authId']
                    . '&cmd=exChVis&assigId='.$assignmentId
                    . '&wrkId='.$work['id']
                    . '&vis=v' ) ) .'">'
                    . '<img src="' . get_icon_url('invisible') . '" alt="' . get_lang('Make visible') . '" />'
                    . '</a>' . "\n"
                    ;
                }
                else
                {
                    $out .= '<a href="' . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
                    . '?authId=' . $_REQUEST['authId']
                    . '&cmd=exChVis'
                    . '&assigId=' . $assignmentId
                    . '&wrkId='.$work['id']
                    . '&vis=i' ) ) . '">'
                    . '<img src="' . get_icon_url('visible') . '" alt="' . get_lang('Make invisible') . '" />'
                    . '</a>' . "\n"
                    ;
                }
                
                if( ! $is_feedback )
                {
                    // if there is no correction yet show the link to add a correction if user is course admin
                    $out .= '&nbsp;'
                    . '<a href="' . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
                    . '?authId=' . $_REQUEST['authId']
                    . '&assigId=' . $assignmentId
                    . '&cmd=rqGradeWrk&gradedWrkId='.$work['id'] ) ) . '">'
                    . get_lang('Add feedback')
                    . '</a>' . "\n"
                    ;
                }
            }
            
            $i++;
            
            // end of cmdList div
            $out .= '</div>' . "\n";
            
            // end of content div
            $out .= '</div>' . "\n";
            
            // end of work div
            $out .= '</div>' . "\n";
        }
    }
    else
    {
        $dialogBox->warning( get_lang('No visible submission') );
        $out .= $dialogBox->render();
    }
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

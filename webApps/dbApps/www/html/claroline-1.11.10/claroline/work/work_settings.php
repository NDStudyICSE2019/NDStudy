<?php // $Id: work_settings.php 14068 2012-03-20 14:35:47Z zefredz $
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Course-level properties for CLWRK tool.
 *
 * Currently available properties
 *  1. Send notification to course manager when a new submission is posted
 *  2. Send notification to students when a feedback is added
 *
 * @version     $Revision: 14068 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      FUNDP - WebCampus <webcampus@fundp.ac.be>
 * @author      Jean-Roch Meurisse <jmeuriss@fundp.ac.be>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLWORK
 * @since       1.9.5
 */

$tlabelReq = 'CLWRK';
require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';

//Security checks
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form( true );
if ( ! claro_is_allowed_to_edit() ) claro_die( get_lang( 'Not allowed' ) );

//Loading tool config file and required libraries
require_once claro_get_conf_repository() . 'CLWRK.conf.php';
require_once get_path( 'incRepositorySys' ) . '/lib/course_utils.lib.php';

//init DialogBox object
$dialogBox = new DialogBox();

try
{
    //init user input handler
    $userInput = Claro_UserInput::getInstance();
    $userInput->setValidatorForAll( new Claro_Validator_ValueType( 'string' ) );
    $cmd = $userInput->get( 'cmd' );
    
    if( 'savePrefs' == $cmd )
    {
        $errorCount = 0;
        $notifySubmissions = $userInput->getMandatory( 'submission' );
        $notifyFeedbacks = $userInput->getMandatory( 'feedback' );
        if( false === save_course_property( 'notify_submissions', $notifySubmissions, claro_get_current_course_id() ) ) $errorsCount++;
        if( false === save_course_property( 'notify_feedbacks', $notifyFeedbacks, claro_get_current_course_id() ) ) $errorsCount++;
        if( $errorCount > 0 )
        {
            $dialogBox->error( get_lang( 'Error while saving notification preferences' ) );
        }
        else
        {
            $dialogBox->success( get_lang( 'Notification preferences saved' ) );
        }
        //force refresh of course data in session!
        $courseData = claro_get_course_data( claro_get_current_course_id(), true );
    }
    else
    {
        $courseData = claro_get_course_data( claro_get_current_course_id(), false );
    }
}
catch( Exception $ex )
{
    $dialogBox->error( $ex->getMessage() );
}
//init other vars

if( !isset( $courseData['notify_submissions'] ) )
{
    $courseData['notify_submissions'] = ( get_conf( 'mail_notification', false ) && get_conf( 'automatic_mail_notification', false ) ) ? '1' : '0';
}
if( !isset( $courseData['notify_feedbacks'] ) )
{
    $courseData['notify_feedbacks'] = ( get_conf( 'mail_notification', false ) && get_conf( 'automatic_mail_notification', false ) ) ? '1' : '0';
}

//display
$out = '';
$nameTools = get_lang( 'Assignments preferences' );
$out .= claro_html_tool_title( $nameTools );
$out .= $dialogBox->render();

$out .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">' . "\n"
     . claro_form_relay_context() . "\n"
     . '<table border="0" width="50%" cellspacing="0" cellpadding="4"><tbody>' . "\n"
     . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />' . "\n"
     . '<input name="cmd" type="hidden" value="savePrefs" />' . "\n"
     . '<ul class="tabTitle"><li>' . get_lang( 'Notifications to users' ) . '</li></ul>' . "\n"
     . '<tr>' . "\n"
     . '<td width="35%" valign="top" align="right">' . get_lang( 'Notify course managers of new assignments' ) . "\n"
     . '</td>' . "\n"
     . '<td width="20%" style="text-align:left;padding-left:30px;">' . "\n"
     . '<input id="submissions_yes" type="radio" value="1" name="submission" '
     . ( $courseData['notify_submissions'] == '1' ? 'checked="checked" />' : '/>' ) . "\n"
     . '<label for="submissions_yes">' . get_lang( 'Yes' ) . '</label><br/>' . "\n"
     . '<input id="submissions_no" type="radio" value="0" name="submission" '
     . ( $courseData['notify_submissions'] == '0' ? 'checked="checked" />' : '/>' ) . "\n"
     . '<label for="submissions_no">' . get_lang( 'No' ) . '</label>' . "\n"
     . '</td>' . "\n"
     . '<td align="left"><em>' . get_lang( 'Choose "Yes" to receive an email every time a submission is made' ) . '</em></td>' . "\n"
     . '</tr>' . "\n"
     . '<tr>' . "\n"
     . '<td width="30%" valign="top" align="right">' . get_lang( 'Notify students of feedbacks' ) . "\n"
     . '</td>' . "\n"
     . '<td width="20%" style="text-align:left;padding-left:30px;">' . "\n"
     . '<input id="feedbacks_yes" type="radio" value="1" name="feedback" '
     . ( $courseData['notify_feedbacks'] == '1' ? 'checked="checked" />' : '/>' ) . "\n"
     . '<label for="feedbacks_yes">' . get_lang( 'Yes' ) . '</label><br/>' . "\n"
     . '<input id="feedbacks_no" type="radio" value="0" name="feedback" '
     . ( $courseData['notify_feedbacks'] == '0' ? 'checked="checked" />' : '/>' ) . "\n"
     . '<label for="feedbacks_no">' . get_lang( 'No' ) . '</label>' . "\n"
     . '</td>' . "\n"
     . '<td align="left"><em>' . get_lang( 'Choose "Yes" to notify students when you add feedback information to their works' ) . '</em></td>' . "\n"
     . '</tr>' . "\n"
     . '<tr><td colspan="3">&nbsp;</td></tr>' . "\n"
     . '<tr><td colspan="3" style="text-align:center;">' . "\n"
     . '<input value="' . get_lang ( 'Ok' ) . '" type="submit" name="submit"/>&nbsp;' . "\n"
     . claro_html_button ( Url::Contextualize('work.php') , get_lang ( 'Cancel' ) )
     . '</td>' . "\n"
     . '</tr>' . "\n"
     . '</tbody></table>' . "\n"
     ;

ClaroBreadCrumbs::getInstance()->prepend( get_lang( 'Assignments' ), Url::Contextualize('work.php') );

$claroline->display->body->appendContent( $out );

echo $claroline->display->render();
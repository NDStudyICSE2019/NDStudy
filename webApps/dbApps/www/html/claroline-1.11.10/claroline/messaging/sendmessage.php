<?php // $Id: sendmessage.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Send a message.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

// initialization
require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';
FromKernel::uses('utils/htmlsanitizer.lib');

// move to kernel
$claroline = Claroline::getInstance();

// ------------- Business Logic ---------------------------
if ( ! claro_is_user_authenticated() )
{
    claro_disp_auth_form(true);
}

include claro_get_conf_repository() . 'CLMSG.conf.php';
require_once dirname(__FILE__).'/lib/message/messagetosend.lib.php';
require_once dirname(__FILE__).'/lib/message/receivedmessage.lib.php';
require_once dirname(__FILE__).'/lib/recipient/singleuserrecipient.lib.php';
require_once dirname(__FILE__).'/lib/recipient/courserecipient.lib.php';
require_once dirname(__FILE__).'/lib/recipient/grouprecipient.lib.php';
require_once dirname(__FILE__).'/lib/recipient/allusersrecipient.lib.php';
require_once dirname(__FILE__).'/lib/permission.lib.php';

$acceptedCmdList = array('rqMessageToUser','rqMessageToCourse','rqMessageToAllUsers','rqMessageToGroup', 'exSendMessage');

$addForm = FALSE;
$content = "";

if (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )
{
    if (isset($_REQUEST['subject']))
    {
        $subject = $_REQUEST['subject'];
    }
    else
    {
        $subject = "";
    }
    
    if (isset($_REQUEST['message']))
    {
        $message = $_REQUEST['message'];
    }
    else
    {
        $message = "";
    }
    
    if ($_REQUEST['cmd'] == 'rqMessageToUser' && isset($_REQUEST['userId']))
    {
        $userId = (int)$_REQUEST['userId'];
        
        if (!current_user_is_allowed_to_send_message_to_user($userId))
        {
            claro_die("Not Allowed");
        }
        $typeRecipient = 'user';
        $userRecipient = $userId;
        $groupRecipient = '';
        $courseRecipient = '';
        
        if (isset($_REQUEST['messageId']))
        {
            if (can_answer_message((int)$_REQUEST['messageId']))
            {
                $responseTo = (int)$_REQUEST['messageId'];
                $messageParent = ReceivedMessage::fromId((int)$_REQUEST['messageId'],claro_get_current_user_id());
                
                if (!isset($_REQUEST['subject']))
                {
                    $subject = get_lang('RE:').' '.strip_tags($messageParent->getSubject());
                }
                
                if (!isset($_REQUEST['message']))
                {
                    $message = "<br /><br />----------------------------------------------------<br />"
                    . get_lang('%firstName %lastName', array ('%firstName' =>claro_htmlspecialchars($messageParent->getSenderFirstName()), '%lastName' => claro_htmlspecialchars($messageParent->getSenderLastName()))).' '.get_lang('wrote').':<br />'
                    . claro_parse_user_text($messageParent->getMessage())
                    ;
                }
            }
            else
            {
                claro_die(get_lang('Not allowed'));
            }
        }
        else
        {
            $responseTo = '';
        }
      
        
        $addForm = TRUE;
    }
    
    if ($_REQUEST['cmd'] == 'rqMessageToCourse')
    {
        if (!claro_is_in_a_course())
        {
            claro_die(get_lang('You are not in a course'));
        }
        if (!current_user_is_allowed_to_send_message_to_current_course())
        {
            claro_die(get_lang('Not allowed'));
        }
        
        $typeRecipient = 'course';
        $userRecipient = '';
        $groupRecipient = '';
        $courseRecipient = claro_get_current_course_id();
        $responseTo = '';
        
        $addForm = TRUE;
    }
    
    if ($_REQUEST['cmd'] == 'rqMessageToAllUsers')
    {
        if (!claro_is_platform_admin())
        {
            claro_die(get_lang('Not allowed'));
        }
        $typeRecipient = 'all';
        $userRecipient = '';
        $groupRecipient = '';
        $courseRecipient = '';
        $responseTo = '';
        
        $addForm = TRUE;
        
        // breadcrump
        $claroline->display->banner->breadcrumbs->append(get_lang('Administration'),get_path('rootAdminWeb'));
    }
    
    if ($_REQUEST['cmd'] ==  'rqMessageToGroup')
    {
        if (!claro_is_in_a_group())
        {
            claro_die(get_lang('You must be in a group to send a message to a group'));
        }
        
        $typeRecipient = 'group';
        $userRecipient = '';
        $groupRecipient = claro_get_current_group_id();
        $courseRecipient = claro_get_current_course_id();
        $responseTo = '';
        
        $addForm = TRUE;
    }
    
    if ($_REQUEST['cmd'] == 'exSendMessage')
    {
        if (!isset($_POST['message'])
                || !isset($_POST['subject'])
                || !isset($_POST['typeRecipient'])
                || !isset($_POST['userRecipient'])
                || !isset($_POST['groupRecipient'])
                || !isset($_POST['courseRecipient']))
        {
             header('Location:./index.php');
        }
        else
        {
            
            $message = trim($_POST['message']);
            $subject = trim($_POST['subject']);
            
            
            //test subject is fillin
            if ($subject == "")
            {
                $typeRecipient = strip_tags($_POST['typeRecipient']);
                $userRecipient = (int)$_POST['userRecipient'];
                $groupRecipient = (int)$_POST['groupRecipient'];
                $courseRecipient = strip_tags($_POST['courseRecipient']);
                $responseTo = (int)$_POST['responseTo'];
                
                $dialogBox = new DialogBox();
                $dialogBox->error(get_lang("Subject couldn't be empty"));
                $content .= $dialogBox->render();
                $addForm = TRUE;
            }
            else
            {
                $message = new MessageToSend(claro_get_current_user_id(),$subject,$message);
                if ($_REQUEST['typeRecipient'] == 'user')
                {
                    $recipient = new SingleUserRecipient($_POST['userRecipient']);
                    
                    if (claro_is_in_a_group())
                    {
                        $message->setCourse(claro_get_current_course_id());
                        $message->setGroup(claro_get_current_group_id());
                    }
                    elseif (claro_is_in_a_course())
                    {
                        $message->setCourse(claro_get_current_course_id());
                    }
                    elseif (!empty($_POST['responseTo']))
                    {
                        if (can_answer_message((int)$_POST['responseTo']))
                        {
                            $messageParent = ReceivedMessage::fromId((int)$_POST['responseTo'], claro_get_current_user_id());
                            if (!is_null($messageParent->getCourseCode()))
                            {
                                $message->setCourse($messageParent->getCourseCode());
                            }
                            
                            if (!is_null($messageParent->getGroupId()))
                            {
                                $message->setGroup($messageParent->getGroupId());
                            }
                        }
                        else
                        {
                            claro_die(get_lang('Not allowed'));
                        }
                    }
                    else
                    {
                        //No context to load
                    }
                }
                elseif ( $_REQUEST['typeRecipient'] == 'course' )
                {
                    $recipient = new CourseRecipient($_POST['courseRecipient']);
                    $message->setCourse($_POST['courseRecipient']);
                }
                elseif ($_REQUEST['typeRecipient'] == 'all' )
                {
                    $recipient = new AllUsersRecipient();
                    
                    // breadcrump
                    $claroline->display->banner->breadcrumbs->append(get_lang('Administration'),get_path('rootAdminWeb'));
                }
                elseif ($_REQUEST['typeRecipient'] == 'group')
                {
                    $recipient = new GroupRecipient($_POST['groupRecipient'],$_POST['courseRecipient']);
                    $message->setCourse($_POST['courseRecipient']);
                    $message->setGroup($_POST['groupRecipient']);
                }
                else
                {
                    claro_die(get_lang('unknow recipient type'));
                }
                
                $recipient->sendMessage($message);
                $informationString =
                     get_lang('Message sent') . '<br /><br />'
                    .'<a href="messagebox.php?box=inbox">'.get_lang('Back to inbox').'</a>'
                    ;
                
                $dialogbox = new DialogBox();
                $dialogbox->info($informationString);
                
                if ( $failure = claro_failure::get_last_failure() )
                {
                    $dialogbox->warning( $failure );
                }
        
                $content .= $dialogbox->render();
            }
        }
    }
}

// ------------ Prepare display --------------------
if ($addForm)
{
    $message = claro_html_sanitize_all($message);
    
    $content .= "<br/>";
    
    $content .= '<form method="post" action="sendmessage.php?cmd=exSendMessage'.claro_url_relay_context('&amp;').'">'."\n"
         . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />'."\n"
     . claro_form_relay_context()."\n"
     . '<input type="hidden" name="cmd" value="exSendMessage" />'."\n"
     . '<input type="hidden" name="typeRecipient" value="'.$typeRecipient.'" />'."\n"
     . '<input type="hidden" name="userRecipient" value="'.$userRecipient.'" />'."\n"
     . '<input type="hidden" name="courseRecipient" value="'.$courseRecipient.'" />'."\n"
     . '<input type="hidden" name="groupRecipient" value="'.$groupRecipient.'" />'."\n"
     . '<input type="hidden" name="responseTo" value="'.$responseTo.'" />'."\n"
     . '<label for="message_subject">'.get_lang('Subject').' : </label><br/><input type="text" id="message_subject" name="subject" value="'.claro_htmlspecialchars($subject).'" maxlength="255" size="40" /><br/>'."\n"
     . '<label for="message">'.get_lang('Message').' : </label><br/>'.claro_html_textarea_editor('message', $message).'<br/><br/>'."\n"
     . '<input type="submit" value="'.get_lang('Send').'" name="send" />'."\n"
     . '</form>'."\n\n"
     ;
}

$claroline->display->body->appendContent(claro_html_tool_title(get_lang('Compose a message')));
$claroline->display->body->appendContent($content);

// ------------- Display page -----------------------------
echo $claroline->display->render();
// ------------- End of script ----------------------------
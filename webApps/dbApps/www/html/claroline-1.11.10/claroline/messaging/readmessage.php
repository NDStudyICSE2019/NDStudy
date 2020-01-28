<?php // $Id: readmessage.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Read a message.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

// initializtion
require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';
require_once dirname(__FILE__).'/lib/displaymessage.lib.php';
require_once dirname(__FILE__).'/lib/message/receivedmessage.lib.php';
require_once dirname(__FILE__).'/lib/message/sentmessage.lib.php';
require_once dirname(__FILE__).'/lib/permission.lib.php';
require_once dirname(__FILE__).'/lib/tools.lib.php';

$messageId = isset($_REQUEST['messageId']) ? (int)$_REQUEST['messageId']: NULL;
$type = isset($_REQUEST['type']) ? claro_htmlspecialchars($_REQUEST['type']) : NULL;

$dialogBox = new DialogBox();

// ------------- Business Logic ---------------------------
if ( ! claro_is_user_authenticated() )
{
    claro_disp_auth_form(false);
}



$displayConfirmation = FALSE;
$currentSection = 'inbox';

if (isset($_REQUEST['userId']))
{
    $userId = (int)$_REQUEST['userId'];
}
else
{
    $userId = claro_get_current_user_id();
}

if ($userId != claro_get_current_user_id() && !claro_is_platform_admin())
{
    claro_die(get_lang("Not allowed"));
}

// load the message
if (is_null($messageId)
        || is_null($type)
        || ($type != "received" && $type != "sent"))
{
    claro_die(get_lang('Missing parameter : %param%', array('%param%' => get_lang('message id'))));
}

if ($type == "received")
{
    try
    {
        $message = ReceivedMessage::fromId($messageId,$userId);
        
        if ( $message->isDeleted() )
        {
            $currentSection = 'trashbox';
        }
        else
        {
            $currentSection = 'inbox';
        }
        
        if($message === false)
        {
            claro_die('Message not found');
        }
        
        if (claro_get_current_user_id() == $userId)
        {
            $message->markRead();
        }
    }
    catch (Exeption $e)
    {
        claro_die(get_lang('Message not found'));
    }
}
else
{
    $message = SentMessage::fromId($messageId);
    
    $currentSection = 'outbox';
    
    if($message === false)
    {
        claro_die('Message not found');
    }
    
    // the sender is different from the current user id
    if ($message->getSender() != $userId)
    {
        claro_die(get_lang('Not allowed'));
    }
}

// command
$acceptedCmd = array('exRestore','exDelete','rqDelete','markUnread');

if (isset($_REQUEST['cmd'])
        && in_array($_REQUEST['cmd'],$acceptedCmd)
        && $type == "received")
{
    if ($_REQUEST['cmd'] == 'exRestore')
    {
        $message->moveToInBox();
        header('Location:./messagebox.php?box=trashbox');
    }
    elseif ($_REQUEST['cmd'] == 'exDelete')
    {
        $message->moveToTrashBox();
        header('Location:./messagebox.php?box=inbox');
    }
    elseif ($_REQUEST['cmd'] == 'rqDelete')
    {
        $displayConfirmation = true;
    }
    elseif ($_REQUEST['cmd'] == 'markUnread')
    {
        $message->markUnread();
        if ($message->isDeleted())
        {
            header('Location:./messagebox.php?box=trashbox');
        }
        else
        {
            header('Location:./messagebox.php?box=inbox');
        }
            
    }
}

// ------------ Prepare display --------------------
$content = "";

if ($displayConfirmation)
{
    $dialogBox->question( get_lang('Are you sure to delete').'<br/><br/>'."\n"
    .    '<a href="'.$_SERVER['PHP_SELF'].'?cmd=exDelete&amp;messageId='.$messageId.'&amp;type='.$type.'&amp;userId='.$userId.'">'.get_lang('Yes').'</a>'
    .    ' | '
    .    '<a href="'.$_SERVER['PHP_SELF'].'?messageId='.$messageId.'&amp;type='.$type.'&amp;userId='.$userId.'">'.get_lang('No').'</a>'."\n");
}

$content .= $dialogBox->render();

$action = array();
if ($type == "received")
{
    if (current_user_is_allowed_to_send_message_to_user($message->getSender()) )
    {
        $action[] = '<a href="sendmessage.php?cmd=rqMessageToUser&amp;messageId='.$message->getId().'&amp;userId='.$message->getSender().'">'
        .    '<img src="'.get_icon_url('replymessage').'" alt="" />'
        .    get_lang('Reply')
        .    '</a>';
    }
    
    if ($message->getRecipient() > 0 || claro_is_platform_admin())
    {
        if ($message->isDeleted())
        {
            if ($message->getRecipient() == $userId || claro_is_platform_admin())
            {
                $action[] = ' <a href="'.$_SERVER['PHP_SELF'].'?cmd=exRestore&amp;messageId='.$messageId.'&amp;type='.$type.'&amp;userId='.$userId.'">'.get_lang('Restore').'</a>';
            }
        }
        else
        {
            $javascriptDelete = '
            <script type="text/javascript">
            function deleteMessage ( localPath )
            {
                if (confirm("'.get_lang('Are you sure to delete').'"))
                {
                    window.location=localPath;
                    return false;
                }
                else
                {
                    return false;
                }
            }
            </script>';
            $claroline->display->header->addHtmlHeader($javascriptDelete);
            
            $action[] = ' <a href="'.$_SERVER['PHP_SELF'].'?cmd=rqDelete&amp;messageId='.$messageId.'&amp;type='.$type.'&amp;userId='.$userId.'"
             onclick="return deleteMessage(\''.$_SERVER['PHP_SELF'].'?cmd=exDelete&amp;messageId='.$messageId.'&amp;type='.$type.'&amp;userId='.$userId.'\')">'
            .    '<img src="' . get_icon_url('user-trash-full') . '" alt="" />'
            .    get_lang('Move to trash')
            .    '</a>';
        }
    }
    else
    {
        //tothing to do
    }
}
else
{
    // nothing to do
}

$content .= DisplayMessage::display($message, claro_html_menu_horizontal($action));

if ($type == "received")
{
    if ($message->isDeleted())
    {
        $claroline->display->banner->breadcrumbs->append(get_lang('My messages'),'./messagebox.php?box=trashbox&amp;userId='.$userId);
    }
    else
    {
        $claroline->display->banner->breadcrumbs->append(get_lang('My messages'),'./messagebox.php?box=inbox&amp;userId='.$userId);
    }
}
else
{
    $claroline->display->banner->breadcrumbs->append(get_lang('My messages'),'./messagebox.php?box=outbox&amp;userId='.$userId);
}

$claroline->display->banner->breadcrumbs->append(get_lang('Message'));
$claroline->display->body->appendContent(claro_html_tool_title(get_lang('Message')));
$claroline->display->body->appendContent(getBarMessageBox($userId, $currentSection ));
$claroline->display->body->appendContent($content);

// ------------- Display page -----------------------------
echo $claroline->display->render();
// ------------- End of script ----------------------------
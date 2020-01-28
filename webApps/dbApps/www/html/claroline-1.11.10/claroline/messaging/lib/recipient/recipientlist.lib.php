<?php // $Id: recipientlist.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * recipient list class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

//load messagetoSend class
require_once dirname(__FILE__) . '/../message/messagetosend.lib.php';
//load notifier class
require_once dirname(__FILE__) . '/../notification/notifier.lib.php';

abstract class RecipientList
{
    /**
     * Return an array of the users identification (int)
     *
     * @return array the identification of the users
     */
    abstract public function getRecipientList();
    
    /**
     * add the userId as recpient of the message id
     *
     * @param int $messageId message id
     * @param int $userId user id
     */
    abstract protected function addRecipient($messageId,$userId);
    
    /**
     * send the current message to the user in the recipientList
     *
     * @param messageToSend 
     * @return int id of the message
     */
    public final function sendMessage($message)
    {
        $recipientListID = $this->getRecipientList();

        if (!is_array($recipientListID))
        {
            throw new Exception("");
        }

        if (empty($recipientListID))
        {
            return false;
        }

        $messageId = $this->addMessage($message);
        
        $this->sendMessageToUser($recipientListID,$messageId);
        
        MessagingUserNotifier::notify($recipientListID,$message,$messageId);

        return $messageId;

    }

    /**
     * create the message in the message table and return the identification of this
     *
     * @return int message identification
     */
    private final function addMessage($messageToSend)
    {
        //create an array of the name of the table needed
        $tableName = get_module_main_tbl(array('im_message'));
        
        $subject = claro_sql_escape($messageToSend->getSubject());
        $message = claro_sql_escape($messageToSend->getMessage());
        
        if ( is_null( $messageToSend->getSender() ) )
        {
            $sender = claro_get_current_user_id();
        }
        else
        {
            $sender = (int) $messageToSend->getSender();
        }

        if (!is_null($messageToSend->getCourseCode()))
        {
            $course = "'".claro_sql_escape($messageToSend->getCourseCode())."'";
        }
        else
        {
            $course = "NULL";
        }
        
        if (!is_null($messageToSend->getGroupId()))
        {
            $group = (int)$messageToSend->getGroupId();
        }
        else
        {
            $group = "NULL";
        }
        
        if (!is_null($messageToSend->getToolsLabel()))
        {
            $tools = "'".claro_sql_escape($messageToSend->getToolsLabel())."'";
        }
        else
        {
            $tools = "NULL";
        }

        // add the message in the table of messages and retrieves the ID
        $addInternalMessageSQL =
            "INSERT INTO `".$tableName['im_message']."` \n"
            . "(sender, subject, message, send_time, course, `group` , tools) \n"
            . "VALUES ($sender,'".$subject."','".$message."', '\n" 
            . date( "Y-m-d H:i:s", claro_time() ) . "',".$course.",".$group.",".$tools.")\n"
            ;

        // try to read the last ID inserted if the request pass
        if (claro_sql_query($addInternalMessageSQL))
        {
            return claro_sql_insert_id();
        }
        else
        {
            throw new Exception(claro_sql_errno().":".claro_sql_error());
        }
    }

    /**
     * Send the message to member of user list
     *
     * @param array of userId $recipientListID list of user identification
     * @param int $messageId message identification
     */
    private final function sendMessageToUser($recipientListId,$messageId)
    {
        $tableName = get_module_main_tbl(array('im_message_status'));
        
        //send a message to each user
        foreach ($recipientListId as $currentRecipient)
        {
            $addInternalMessageSQL =
                "INSERT INTO `" . $tableName['im_message_status'] . "` "
                . "(user_id, message_id, is_read, is_deleted) \n"
                . "values (" . (int)$currentRecipient . "," . (int)$messageId . ",0 , 0)\n"
                ;
            
            if (!claro_sql_query($addInternalMessageSQL))
            {
                throw new Exception(claro_sql_errno().":".claro_sql_error());
            }
            
            $this->addRecipient($messageId,$currentRecipient);
        }
    }
}

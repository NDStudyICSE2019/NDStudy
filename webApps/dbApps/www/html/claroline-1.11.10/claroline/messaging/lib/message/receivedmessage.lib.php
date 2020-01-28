<?php // $Id: receivedmessage.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * received message class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


//load storedmessage class
require_once dirname(__FILE__) . '/storedmessage.lib.php';

class ReceivedMessage extends StoredMessage
{
    protected $isRead;
    protected $isDeleted;
    protected $userId;

    /**
     * create a received message
     *
     * @param int $messageId message identification
     * @param int $userId user identification 
     */
    public function __construct($messageId, $userId = NULL)
    {
        if (is_null($userId))
        {
            $userId = claro_get_current_user_id();
        }

            
        $this->userId = (int)$userId;
        $this->messageId = (int)$messageId;
        
    }

    /**
     * return the user recepient
     *
     * @return int user identification
     */
    public function getRecipient()
    {
        return $this->userId;
    }
    
    /**
     * @return bool true if the message is read
     * false if the message is not read
     */
    public function isRead()
    {
        
        if ($this->isRead === 0)
        { 
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * 
     * @return bool true if the message is deleted
     * false if the message is not deleted
     */
    public function isDeleted()
    {
        if ($this->isDeleted === 0)
        {
            return false;
        }
        else
        {
            return true;    
        }
    }

    /**
     * delete the current message
     */
    public function moveToTrashBox()
    {
        $this->moveMessage(1);
    }

    /**
     * restore the current message
     */
    public function moveToInBox()
    {
        $this->moveMessage(0);
    }

    /**
     * change the deleted_flag in the database and the field in the current message
     *
     * @param int $deledeStatus value of the deleted_flag in the database
     */
    private function moveMessage($deledeStatus)
    {
        $this->isDeleted = $deledeStatus;
         
        $tableName = get_module_main_tbl(array('im_message_status'));
         
        $moveSQL =
             "UPDATE `".$tableName['im_message_status']."` \n"
            ."SET is_deleted = " . (int)$deledeStatus . " \n"
            ."WHERE user_id = ".(int)$this->userId." AND message_id = ". (int)$this->getId()."\n"
            ;
        claro_sql_query($moveSQL);
    }

    /**
     * mark the current message as unread
     *
     */
    public function markUnread()
    {
        $this->changeReadStatus(0);
    }

    /**
     * mark the current message as read
     *
     */
    public function markRead()
    {
        $tableName = get_module_main_tbl(array('im_message_status'));
        
        if ($this->getRecipient() != 0)
        {
            $this->changeReadStatus(1);
        }
    }

    /**
     * change the read status of the current message
     *
     * @param int $isRead value of the readStatus
     */
    protected function changeReadStatus($isRead)
    {
        $tableName = get_module_main_tbl(array('im_message_status'));
         
        $sql =
            "UPDATE `" . $tableName['im_message_status'] ."`\n"
            .   " SET is_read = " . (int)$isRead."\n"
            .   " WHERE user_id = " . (int)$this->userId."\n"
            .      " AND message_id = " . (int)$this->getId()."\n"
            ;
        claro_sql_query($sql);
    }

    /**
     * set fields of the current message
     *
     * @param array $messageData
     *   $messageData['message_id']
     *   $messageData['subject']
     *   $messageData['message']
     *   $messageData['sender']
     *   $messageData['send_time']
     *   $messageData['course']
     *   $messageData['group']
     *   $messageData['tools']
     *   $messageData['is_read']
     *   $messageData['is_deleted']
     *   $messageData['user_id']
     *   $messageData['firstName']
     *   $messageData['lastName']
     */
    protected function setFromArray($messageData)
    {
        parent::setFromArray($messageData);
         
        if (isset($messageData['is_read']) && !is_null($messageData['is_read']))
        {
            $this->isRead = (int) $messageData['is_read'];
        }
        else
        {
            throw new Exception("\$messageData['is_read'] is not defined: All data must be defined and not null");
        }
         
        if (isset($messageData['is_deleted']) && !is_null($messageData['is_deleted']))
        {
            $this->isDeleted = (int) $messageData['is_deleted'];
        }
        else
        {
            throw new Exception("\$messageData['is_deleted'] is not defined: All data must be defined and not null");
        }
        
        if (isset($messageData['user_id']) && !is_null($messageData['user_id']))
        {
            $this->userId = (int) $messageData['user_id'];
        }
        else
        {
            throw new Exception("\$messageData['user_id'] is not defined: All data must be defined and not null");
        }

        if (isset($messageData['sent_to']) && !is_null($messageData['sent_to']))
        {
            $this->sentTo = $messageData['sent_to'];
        }
        else
        {
            throw new Exception("\$messageData['user_id'] is not defined: All data must be defined and not null");
        }

        if (array_key_exists("firstName", $messageData))//could be  null if the user is deleted
        {
            $this->setSenderFirstName($messageData['firstName']);
        }
        else
        {
            throw new Exception("\$messageData['nom'] is not defined: All data must be defined");
        }
        
        if (array_key_exists("lastName", $messageData))//could be  null if the user is deleted
        {
            $this->setSenderLastName($messageData['lastName']);
        }
        else
        {
            throw new Exception("\$messageData['lastName'] is not defined: All data must be defined");
        }
    }

    /**
     * create a new ReceviedMessage with the information in the parameter
     *
     * @param array $messageData
     *   $messageData['message_id']
     *   $messageData['subject']
     *   $messageData['message']
     *   $messageData['sender']
     *   $messageData['send_time']
     *   $messageData['course']
     *   $messageData['group']
     *   $messageData['tools']
     *   $messageData['is_read']
     *   $messageData['is_deleted']
     *   $messageData['user_id']
     *   $messageData['firstName']
     *   $messageData['lastName']
     * 
     * @return ReceivedMessage the message created
     */
    public static function fromArray($messageData)
    {
        $message = new ReceivedMessage($messageData['message_id']);
        
        $message->setFromArray($messageData);       
         
        return $message;
    }

    /**
     * create a new message
     *
     * @param int $messageId message identification
     * @param int $userId user identification
     * if it not defined it use the current user identification
     * @return ReceivedMessage the message created
     */
    public static function fromId($messageId, $userId = NULL)
    {
        if ( is_null( $userId ) )
        {
            $userId = claro_get_current_user_id();
        }
        
        if ( ! claro_is_platform_admin() )
        {
            $userSql = " AND R.user_id = " . (int) $userId ."\n";
        }
        else
        {
            $userSql = "";
        }
         
        $tableName = get_module_main_tbl(array('im_message','im_message_status','user','im_recipient'));
         
        $messageSQL =
            "SELECT U.nom AS lastName, U.prenom AS firstName, M.message_id, M.sender,M.subject,  \n"
                ."M.message, M.send_time, R.is_read, R.is_deleted, R.user_id, M.course, M.group, M.tools, \n"
                ."RE.sent_to\n"
                .    "FROM `" . $tableName['im_message'] . "` as M \n"
                .    " LEFT JOIN `".$tableName['im_message_status']."` AS R ON M.message_id = R.message_id\n"
                .    " LEFT JOIN `".$tableName['user']."` AS U ON M.sender = U.user_id\n"
                ." LEFT JOIN `".$tableName['im_recipient']."` AS RE ON M.message_id = RE.message_id\n"
                .    " WHERE M.message_id = " . (int) $messageId."\n"
                . $userSql
                ;

        $resultMessage = claro_sql_query_fetch_single_row($messageSQL);
        
        if (!$resultMessage)
        {
            $messageSQL =
                "SELECT U.nom AS lastName, U.prenom AS firstName, M.message_id, M.sender, M.subject, \n"
                ."M.message, M.send_time, R.is_read, R.is_deleted, R.user_id, M.course, M.group, M.tools, \n"
                ."RE.sent_to\n"
                ."FROM `" . $tableName['im_message'] . "` as M\n"
                ." LEFT JOIN `".$tableName['im_message_status']."` AS R ON M.message_id = R.message_id\n"
                ." LEFT JOIN `".$tableName['user']."` AS U ON M.sender = U.user_id\n"
                ." LEFT JOIN `".$tableName['im_recipient']."` AS RE ON M.message_id = RE.message_id\n"
                ." WHERE R.user_id = 0" 
                ." AND M.message_id = " . (int) $messageId
                ;
            $resultMessage = claro_sql_query_fetch_single_row($messageSQL);
        }
        if(!$resultMessage)
        {
            return false;
        }
        else
        {
            return self::fromArray($resultMessage);
        }
    }

    /**
     * load the current message from the data base
     */
    public function load()
    {
        $tableName = get_module_main_tbl(array('im_message','im_message_status','user','im_recipient'));
        
        $messageSQL =
            "SELECT U.nom AS lastName, U.prenom AS firstName, M.message_id, M.sender, M.subject, \n"
            ."M.message, M.send_time, R.is_read, R.is_deleted, R.user_id , M.course, M.group, M.tools, \n"
            ."RE.sent_to"
            ." FROM `" . $tableName['im_message'] . "` as M \n"
            ." LEFT JOIN `".$tableName['im_message_status'] . "` as R ON M.message_id = R.message_id\n"
            ." LEFT JOIN `".$tableName['user']."` AS U ON M.sender = U.user_id\n"
            ." LEFT JOIN `".$tableName['im_recipient']."` AS RE ON M.message_id = RE.message_id\n"
            ." WHERE R.user_id = " . (int) $this->userId."\n"
            ." AND M.message_id = " . (int) $this->messageId."\n"
            ;
        
        $this->setFromArray(claro_sql_query_fetch_single_row($messageSQL));
    }

    public function isPlatformMessage()
    {
        return $this->sentTo == 'allUser';
    }
}

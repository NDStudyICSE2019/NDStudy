<?php // $Id: receivedmessagebox.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Received message box  class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


//load messagebox class
require_once dirname(__FILE__) . '/messagebox.lib.php';
//load receivedmessage class
require_once dirname(__FILE__) . '/../message/receivedmessage.lib.php';

class ReceivedMessageBox extends MessageBox
{
    protected $numberOfUnreadMessage = FALSE;
    protected $numberOfPlatformMessage = FALSE;

    /**
     * mark the message (by the message id) as unread for the user (user identifiation) in parameter
     *
     * @param int $messageId message identification
     * @param int $userId user identification
     */
    public function markUnread($messageId, $userId = NULL)
    {
        $message = new ReceivedMessage($messageId, $userId);
        $message->markUnread();
    }
    
    /**
     * mark the message (by the message id) as read for the user (user identifiation) in parameter
     *
     * @param int $messageId message identification
     * @param int $userId user identification
     */
    public function markRead($messageId, $userId = NULL)
    {
        $message = new ReceivedMessage($messageId, $userId);
        $message->markRead();
    }

    /**
     * Set the deleted status as deleted for the message in parameter
     *
     * @param int $messageId message identification
     * @param int $userId user identification
     * if it not defined it use current user id
     */
    public function moveMessageToTrashBox($messageId, $userId = NULL)
    {
        $message = new ReceivedMessage($messageId, $userId);
        $message->moveToTrashBox();
    }

    /**
     * Set the deleted status as not deleted in the data base
     *
     * @param int $messageId message identification
     * @param int $userId user identification
     * if it not defined it use current user id
     */
    public function moveMessageToInBox($messageId, $userId = NULL)
    {
        $message = new ReceivedMessage($messageId, $userId);
        $message->moveToInBox();
    }

    /**
     * @see MessageBox
     *
     */
    public function loadMessageList()
    {
        if (!$this->messageList)
        {
            $tableName = get_module_main_tbl(array('im_message','im_message_status','user','im_recipient'));

            if ( ! is_null($this->messageFilter))
            {
                $strategy = $this->messageFilter->getStrategy();
                $order = $this->messageFilter->getOrder();
                $limit = $this->messageFilter->getLimit();
            }
            else
            {
                $strategy = "";
                $order = "";
                $limit = "";
            }
            
            $sql =
                "SELECT  U.nom AS lastName, U.prenom AS firstName, M.message_id, M.sender, M.subject,\n"
                ."M.message, M.send_time, R.is_read, R.is_deleted, R.user_id, M.course, M.group, M.tools,\n"
                ."RE.sent_to"
                . " FROM `" . $tableName['im_message'] . "` as M\n"
                . " LEFT JOIN `" . $tableName['im_message_status'] . "` as R ON M.message_id = R.message_id\n"
                . " LEFT JOIN `".$tableName['user']."` AS U ON M.sender = U.user_id\n"
                ." LEFT JOIN `".$tableName['im_recipient']."` AS RE ON M.message_id = RE.message_id AND RE.user_id = R.user_id\n"
                . " WHERE (R.user_id = " . (int)$this->getUserId() . " OR R.user_id = 0) \n" // 0 platforme message
                .    " " . $strategy
                .    " " . $order
                .    " " . $limit
                ;
            
            $this->messageList = claro_sql_query_fetch_all_rows($sql);
            
            //load number of message if its necessary
            $this->loadNumberOfMessage();
        }
    }

    /**
     * return the current message
     *
     * @return ReceivedMessage the current message
     */
    public function current()
    {
        return ReceivedMessage::fromArray($this->messageList[$this->index]);
    }

    /**
     * @see MessageBox
     */
    public function getNumberOfMessage()
    {
        //load number of message if its necessary
        $this->loadNumberOfMessage();
        
        return $this->numberOfMessage;
    }
    
    protected function loadNumberOfMessage()
    {
        if (!$this->numberOfMessage)
        {
            $tableName = get_module_main_tbl(array('im_message','im_message_status','user'));
                
            if ( ! is_null($this->messageFilter))
            {
                $strategy = $this->messageFilter->getStrategy();
            }
            else{
                $strategy = "";
            }

            $sql =
                "SELECT count(*)" 
                ." FROM `" . $tableName['im_message'] . "` as M\n"
                ." LEFT JOIN `" . $tableName['im_message_status'] . "` as "
                ."R ON M.message_id = R.message_id\n"
                ." LEFT JOIN `".$tableName['user']."` AS U ON M.sender = U.user_id\n"
                ." WHERE (R.user_id = " . (int)$this->getUserId() . " OR R.user_id = 0) \n"
                ." " . $strategy
                ;
                
            $this->numberOfMessage = claro_sql_query_fetch_single_value($sql);
        }
    }
    
    /**
     * return the number of unread message
     * 
     * @return int the number of unread message
     */
    public function numberOfUnreadMessage()
    {
        if (!$this->numberOfUnreadMessage)
        {
            $tableName = get_module_main_tbl(array('im_message','im_message_status','user'));
            
            if ( ! is_null($this->messageFilter) )
            {
                $strategy = $this->messageFilter->getStrategy();
            }
            else
            {
                $strategy = "";
            }

            $sql =
                "SELECT count(*)\n" 
                ." FROM `" . $tableName['im_message'] . "` as M\n"
                ." LEFT JOIN `" . $tableName['im_message_status'] . "` as R ON M.message_id = R.message_id\n"
                ." LEFT JOIN `".$tableName['user']."` AS U ON M.sender = U.user_id\n"
                ." WHERE R.user_id = " . (int)$this->getUserId()."\n"
                ." AND R.is_read = 0\n"
                ." " . $strategy
                ;

            $this->numberOfUnreadMessage = claro_sql_query_fetch_single_value($sql);
        }
        
        return $this->numberOfUnreadMessage;
    }

    /**
     * return the number of platform message
     * 
     * @return int the number of platform message
     */    
    public function numberOfPlatformMessage()
    {
        if (!$this->numberOfUnreadMessage)
        {
            $tableName = get_module_main_tbl(array('im_message','im_message_status','user'));
            
            if ( ! is_null($this->messageFilter) )
            {
                $strategy = $this->messageFilter->getStrategy();
            }
            else
            {
                $strategy = "";
            }

            $sql =
                "SELECT count(*)\n" 
                ." FROM `" . $tableName['im_message'] . "` as M\n"
                ." LEFT JOIN `" . $tableName['im_message_status'] . "` as R ON M.message_id = R.message_id\n"
                ." LEFT JOIN `".$tableName['user']."` AS U ON M.sender = U.user_id\n"
                ." WHERE R.user_id = 0"."\n"
                ." " . $strategy
                ;
                
            $this->numberOfPlatformMessage = claro_sql_query_fetch_single_value($sql);
        }
        
        return $this->numberOfPlatformMessage;
    }
    
    /**
     * return the number of page of the current message box
     *
     * @return int number of page
     */
    public function getNumberOfPage()
    {
        return ceil($this->getNumberOfMessage() / $this->getMessageStrategy()->getNumberOfMessagePerPage());
    }

    /**
     * empty the trashbox
     *
     */
    public function empyTrashBox()
    {
        $tableName = get_module_main_tbl(array('im_message_status'));
        
        $sql = 
            "DELETE FROM `" . $tableName['im_message_status'] . "`\n"
            ." WHERE is_deleted = 1\n"
            ." AND user_id = " . (int)$this->getUserId()."\n"
            ;

        claro_sql_query($sql);
    }
}

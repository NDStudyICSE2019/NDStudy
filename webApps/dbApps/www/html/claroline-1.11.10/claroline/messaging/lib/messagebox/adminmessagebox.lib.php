<?php // $Id: adminmessagebox.lib.php 14493 2013-07-10 13:50:41Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Received adminmessagebox class
 *
 * @version     1.9 $Revision: 14493 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

// load SentMessage class
require_once dirname(__FILE__) . '/../message/sentmessage.lib.php';
// load administration strategy (order,where,limit, clause)
require_once dirname(__FILE__) . '/../selectorstrategy/adminboxstrategy.lib.php';

class AdminMessageBox implements Iterator 
{
    protected $selector = false;
    protected $numberOfMessage = false;
    protected $messageList = false;
    protected $index = 0;
    
    public function __contruct($selector = false)
    {
        $this->selector = $selector;
    }
    
    public function setSelector($selector)
    {
        $this->selector = $selector;
    }
    
    public function getSelector()
    {
        if ( ! $this->selector )
        {
            $this->selector = new AdminBoxStrategy();
        }
        
        return $this->selector;
    }
    
    public function current()
    {
        $message = SentMessage::fromArray($this->messageList[$this->index]);
        $message->setSenderFirstName($this->messageList[$this->index]['firstName']);
        $message->setSenderLastName($this->messageList[$this->index]['lastName']);
        
        return $message;
    }

    public function key()
    {
        // If message list not loaded, load it !
        $this->loadMessageList();
        
        return $this->messageList[0]['message_id'];
    }

    public function next()
    {
        // If message list not loaded, load it !
        $this->loadMessageList();
        
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        // If message list not loaded, load it !
        $this->loadMessageList();
        
        return ($this->index < count($this->messageList));
    } 
    
    /**
     * load the message list if it is not loaded
     *
     */
    protected function loadMessageList()
    {
        // If message list not loaded, load it !
        if ( ! $this->messageList)
        {
            $strategy = $this->getSelector();
            
            $limitClause = $strategy->getLimit();
            $orderClause = $strategy->getOrder();
            $whereClause = $strategy->getStrategy();
            
            $tableName = get_module_main_tbl(array('im_message','user','im_recipient'));
            
            $sql =
                 "SELECT distinct M.message_id,M.sender,M.subject,M.message,M.send_time,M.course,\n"
                ."M.group,M.tools,U.nom AS lastName,U.prenom AS firstName\n"
                . " FROM `" . $tableName['im_message'] . "` AS M\n"
                . " LEFT JOIN `" . $tableName['user'] . "` AS U ON M.sender = U.user_id\n"
                . " LEFT JOIN `" . $tableName['im_recipient'] . "` AS R ON R.message_id = M.message_id"
                . " " . $whereClause."\n"
                . " " . $orderClause."\n"
                . " " . $limitClause."\n"
                ;
            
            $this->messageList = claro_sql_query_fetch_all_rows($sql);
            
            //load number of message if its necessary
            $this->loadNumberOfMessage();
        }
    }

    public function getNumberOfMessage()
    {
        //load number of message if its necessary
        $this->loadNumberOfMessage();
        
        return $this->numberOfMessage;
    }
    
    /**
     * load the number of message the message box
     *
     */
    protected function loadNumberOfMessage()
    {
        if (!$this->numberOfMessage)
        {
            $strategy = $this->getSelector();
                
            $whereClause = $strategy->getStrategy();
            
            $tableName = get_module_main_tbl(array('im_message','user','im_recipient'));
            
            $sql =
                 "SELECT count(distinct M.message_id)\n"
                . " FROM `" . $tableName['im_message'] . "` AS M\n"
                . " LEFT JOIN `" . $tableName['user'] . "` as U ON M.sender = U.user_id\n"
                . " LEFT JOIN `" . $tableName['im_recipient'] . "` AS R ON R.message_id = M.message_id"
                . " " . $whereClause
                ;
                
            $this->numberOfMessage = claro_sql_query_fetch_single_value($sql);
        }
    }
    
    public function getNumberOfPage()
    {
        return ceil( $this->getNumberOfMessage() / $this->getSelector()->getNumberOfMessagePerPage() );
    }
    
    /**
     * delete from the database all messages
     *
     */
    public function deleteAllMessages()
    {
        $tableName = get_module_main_tbl(array('im_message_status','im_recipient','im_message'));
        
        $sql = "DELETE FROM `".$tableName['im_message_status']."`";
        claro_sql_query($sql);
        
        $sql = "DELETE FROM `".$tableName['im_recipient']."`";
        claro_sql_query($sql);
        
        $sql = "DELETE FROM `".$tableName['im_message']."`";
        claro_sql_query($sql);
    }
    
     /**
      * delete message all user's messages (user in parameter) 
      *
      * @param int $uid user di
      */
    public function deleteAllMessageFromUser($uid)
    {
        $tableName = get_module_main_tbl(array('im_message_status','im_recipient','im_message'));
        
        // get all message id sent by the user
        $sql = 
            "SELECT M.message_id\n"
            ." FROM `".$tableName['im_message']."` AS M\n"
            ." WHERE M.sender=".(int)$uid
            ;

        $messageIdList = claro_sql_query_fetch_all_cols($sql);
        
        // delete all message of the trashbox and the inbox
        $sql = "DELETE FROM `".$tableName['im_message_status']."`\n WHERE `user_id` = ".(int)$uid;
        claro_sql_query($sql);
        
        $this->deleteMessageList($messageIdList);
    }
    
    /**
     * delete all message older than 
     *
     * @param int $date date in unixtime format
     */
    public function deleteMessageOlderThan($date) // timestamp
    {
        $tableName = get_module_main_tbl(array('im_message_status','im_recipient','im_message'));
        $sql =
            "SELECT M.message_id\n"
            . " FROM `".$tableName['im_message']."` AS M\n"
            . " WHERE DATEDIFF(M.send_time,FROM_UNIXTIME(".$date.")) < 0\n"
            ;
        $messageId = claro_sql_query_fetch_all_cols($sql);
        
        $this->deleteMessageList($messageId['message_id']);
    }

    /**
     * delete all platform message
     *
     */
    public function deletePlatformMessage()
    {
        $tableName = get_module_main_tbl(array('im_message_status','im_recipient','im_message'));
        $sql =
            "SELECT M.message_id\n"
            . " FROM `".$tableName['im_recipient']."` AS M\n"
            . " WHERE M.user_id = 0\n"
            ;
        $messageId = claro_sql_query_fetch_all_cols($sql);
        
        $this->deleteMessageList($messageId['message_id']);
    }
    
    
    /**
     * delete all message of the list in parameter
     *
     * @param array of int $messageIdList array of message_id
     */
    public function deleteMessageList($messageIdList)
    {
        $tableName = get_module_main_tbl(array('im_message_status','im_recipient','im_message'));
        
        $messageIdList = array_map('intval', $messageIdList);
        
        $messageIdString = implode(',',$messageIdList);
        
        // delete completely all message of the list
        if ($messageIdString != "")
        {
            // delete status message (remove from received messagebox)
            $sql = 
                 "DELETE FROM `" . $tableName['im_message_status'] . "`\n"
                ."WHERE message_id IN(".$messageIdString.")"
                ;

            claro_sql_query($sql);
            
            // remove all recipient
            $sql = 
                "DELETE FROM `" . $tableName['im_recipient'] . "`\n"
                ."WHERE message_id IN(".$messageIdString.")"
                ;

            claro_sql_query($sql);
            
            // remove from outbox
            $sql = 
                "DELETE FROM `" . $tableName['im_message'] . "`\n"
                ."WHERE message_id IN(".$messageIdString.")"
                ;

            claro_sql_query($sql);
        }
    }

}

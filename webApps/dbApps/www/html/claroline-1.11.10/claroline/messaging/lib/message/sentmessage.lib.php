<?php // $Id: sentmessage.lib.php 14493 2013-07-10 13:50:41Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * sent message message class
 *
 * @version     1.9 $Revision: 14493 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


//load storedmessage class
require_once dirname(__FILE__) . '/storedmessage.lib.php';

class SentMessage extends StoredMessage
{
    protected $recipientList = false;

    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * create a new SentMessage with the information in parameter
     *
     * @param array $messageData 
     *  $messageData['message_id']
     *  $messageData['subject']
     *  $messageData['message']
     *  $messageData['sender']
     *  $messageData['send_time']
     *  $messageData['course']
     *  $messageData['group']
     *  $messageData['tools']
     * @return SentMessage the new message
     */
    public static function fromArray($messageData)
    {
        $message = new SentMessage($messageData['message_id']);

        $message->setFromArray($messageData);

        return $message;
    }

    /**
     * return an array of user information
     *
     * @return array
     * ['user_id']
     * ['lastName']
     * ['firstName']
     * ['username']
     */
    public function getRecipientList()
    {
        if (!$this->recipientList)
        {
            $this->loadRecipientList();
        }
        
        return $this->recipientList;
    }

    /**
     * load the list of recipient list of the current message
     */
    protected function loadRecipientList()
    {
        
        $tableName = get_module_main_tbl(array('im_recipient','user'));
        
        $recipientListSQL =
                "SELECT U.user_id, U.nom as lastName, U.prenom as firstName, U.username\n"
                .    " FROM `" . $tableName['im_recipient'] . "` AS R\n"
                .    " LEFT JOIN `" . $tableName['user'] . "` AS U ON R.user_id = U.user_id\n"
                .    " WHERE R.message_id = " . (int) $this->getId()." AND R.user_id > 0\n" 
                ;
        
        $userList = claro_sql_query_fetch_all_rows($recipientListSQL);

        $sentToSQL =
                "SELECT DISTINCT (R.sent_to)\n"
                .    " FROM `" . $tableName['im_recipient'] . "` AS R\n"
                .    " WHERE R.message_id = " . (int) $this->getId()."\n"
                .    " LIMIT 1\n" 
                ;

        $sentTo = claro_sql_query_fetch_single_value($sentToSQL);

        $this->recipientList = array();
        $this->recipientList['sentTo'] = $sentTo;
        $this->recipientList['userList'] = $userList;
    }

    /**
     * create a new SentMessage
     *
     * @param int $messageId identification of the message to create
     * @return SentMessage message created
     */
    public static function fromId($messageId)
    {
        $tableName = get_module_main_tbl(array('im_message'));

        $readDataSQL =
                "SELECT M.message_id, M.sender, M.subject, M.message, M.send_time, M.course, M.`group`, M.tools\n"
                .   " FROM `" . $tableName['im_message'] . "` AS M\n"
                .   " WHERE M.message_id = " . (int) $messageId
                ;
        
        $result = claro_sql_query_fetch_single_row($readDataSQL);
        
        if (!$result)
        {
            return false;
        }
        else
        {
            return self::fromArray($result);
        }
    }

    /**
     * @see StoredMessage
     */
    public function load()
    {
        $tableName = get_module_main_tbl(array('im_message'));
        $readDataSQL =
            "SELECT M.message_id, M.sender, M.subject, M.message, M.send_time\n"
            .   " FROM `" . $tableName['im_message'] . "` AS M\n"
            .   " WHERE message_id = " . (int) $this->messageId
            ;

        $this->setFromArray(claro_sql_query_fetch_single_row($readDataSQL));
    }


    //------------------------ admin function -------------------
    
    public function delete()
    {
        $tableName = get_module_main_tbl(array('im_message_status','im_recipient','im_message'));
       
        // delete status message (remove from receaved messagebox)
        $sql = 
            "DELETE FROM `" . $tableName['im_message_status'] . "`\n"
            ."WHERE message_id = " . (int) $this->getId()."\n"
            ;

        claro_sql_query($sql);
        
        // remove all recipient
        $sql = 
            "DELETE FROM `" . $tableName['im_recipient'] . "`\n"
            ."WHERE message_id = " . (int) $this->getId()."\n"
            ;

        claro_sql_query($sql);
        
        // remove from outbox
        $sql = 
            "DELETE FROM `" . $tableName['im_message'] . "`\n"
            ."WHERE message_id = " . (int) $this->getId()."\n"
            ;

        claro_sql_query($sql);
    }
}

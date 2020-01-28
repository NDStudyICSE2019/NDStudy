<?php // $Id: userlistrecipient.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * userlistrecipient class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

//load recipientlist class
require_once dirname(__FILE__) . '/recipientlist.lib.php';

class UserListRecipient extends RecipientList
{
    private $userIdList = array();

    /**
     * @see recipientList
     */
    public function getRecipientList()
    {
        $this->userIdList = array_unique($this->userIdList);
        
        return $this->userIdList;
    }
    
    /**
     * add a user to the recipient list
     *
     * @param int $userId user identification of the user to add
     */
    public function addUserId($userId)
    {
        if (is_numeric($userId))
        {
            $this->userIdList[] = $userId;
        }
    }
    
    /**
     * add users to the recipient list
     *
     * @param array of id $userIdList array of user Identification
     */
    public function addUserIdList($userIdList)
    {
        if (is_array($userIdList))
        {
            $this->userIdList = array_merge($this->userIdList,$userIdList);
        }
    }

    /**
     * @see RecpientList
     */
    protected function addRecipient($messageId,$userId)
    {
        $tableName = get_module_main_tbl(array('im_recipient'));
        
        $sql = "INSERT INTO `".$tableName['im_recipient']."` "
            . "(message_id, user_id, sent_to) \n"
            . "VALUES (" . (int)$messageId . ", " . (int)$userId . ", 'toUser')\n"
            ;

        claro_sql_query($sql);
    }
}

<?php // $Id: allusersrecipient.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * all user recipient class
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

class AllUsersRecipient extends RecipientList
{
    private $userIdList = array('0');

     /**
     * @see recipientList
     */
    public function getRecipientList()
    {
        return $this->userIdList;
    }
    
    /**
     * add a user in the table of recipient
     *
     * @param int $messageId message id
     * @param int $userId user id (recipient id)
     */
    protected function addRecipient($messageId,$userId)
    {
        $tableName = get_module_main_tbl(array('im_recipient'));
        
        $sql = "INSERT INTO `".$tableName['im_recipient']."` "
            . "(message_id, user_id, sent_to) \n"
            . "VALUES (" . (int)$messageId . ", " . (int)$userId . ", 'toAll')\n"
            ;

        claro_sql_query($sql);
    }
}

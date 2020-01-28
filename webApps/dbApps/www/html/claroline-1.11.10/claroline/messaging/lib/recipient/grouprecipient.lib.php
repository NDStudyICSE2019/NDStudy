<?php // $Id: grouprecipient.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * group recipient class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

// used to get members of a group
require_once get_path('incRepositorySys') . '/lib/group.lib.inc.php';
//load recipientlist class
require_once dirname(__FILE__) . '/recipientlist.lib.php';

class GroupRecipient extends RecipientList
{
    private $groupId = NULL;
    private $courseId = NULL;

    /**
     * create a groupe recipient
     *
     * @param int $groupId groupe identification
     * @param string $courseId course identification
     *
     * @throws Exception if a paramter is NULL
     */
    public function __construct($groupId, $courseId)
    {
        if (is_null($groupId))
        {
            throw new Exception("group id cannot be null");
        }
        
        if (is_null($courseId))
        {
            throw new Exception("course id cannot be null");
        }
        
        $this->groupId = $groupId;
        $this->courseId = $courseId;
    }

    /**
     * return the tutor and the member of the group
     *
     * @return array of int: user identification
     */
    public function getRecipientList()
    {
        $userList = array();

        // add user to the list
        $userGroupList = get_group_user_list($this->groupId, $this->courseId);
        if (is_array($userGroupList))
        {
            foreach ($userGroupList as $user)
            {
                $userList[] = $user['id'];
            }
        }

        //add tutor to the list if he exist

        $dataGroup = claro_get_group_data(array(CLARO_CONTEXT_COURSE => $this->courseId, CLARO_CONTEXT_GROUP => $this->groupId),'tutorId');
        if ($dataGroup['tutorId'] != 0)
        {
            $userList[] = $dataGroup['tutorId'];
        }

        return $userList;
    }

    /**
     * @see RecipientList
     */
    protected function addRecipient($messageId,$userId)
    {
        $tableName = get_module_main_tbl(array('im_recipient'));
        
        $sql = "INSERT INTO `".$tableName['im_recipient']."` "
                . "(message_id, user_id, sent_to) \n"
                . "VALUES (" . (int)$messageId . ", " . (int)$userId . ", 'toGroup')\n"
                ;
        claro_sql_query($sql);
    }
}

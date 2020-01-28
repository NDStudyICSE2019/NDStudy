<?php // $Id: courserecipient.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * course recipient class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

// used to get users of a course
require_once get_path('incRepositorySys')  . '/lib/course_user.lib.php';
//load recipientlist class
require_once dirname(__FILE__) . '/recipientlist.lib.php';

class CourseRecipient extends RecipientList
{
    private $courseId;

    /**
     * create a course recipient
     *
     * @param string $courseId course identification
     */
    public function __construct($courseId)
    {
        $this->courseId = $courseId;
    }

    /**
     * return the membre user list of the course
     *
     * @return array of user identification
     */
    public function getRecipientList()
    {
        $userList = array();

        $userCourseList = claro_get_course_user_list($this->courseId);

        if ($userCourseList)
        {
            foreach ($userCourseList as $user)
            {
                $userList[] = (int) $user['user_id'];
            }
        }
        
        return $userList;
    }

    /**
     * @see RecpientList
     */
    protected function addRecipient($messageId,$userId)
    {
        $tableName = get_module_main_tbl(array('im_recipient'));
        
        $sql = "INSERT INTO `".$tableName['im_recipient']."` "
            . "(message_id, user_id, sent_to) \n"
            . "VALUES (" . (int)$messageId . ", " . (int)$userId . ", 'toCourse')\n"
            ;
        claro_sql_query($sql);
    }
}

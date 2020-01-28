<?php // $Id: permission.lib.php 14672 2014-01-27 11:51:13Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * function used for to know if the current user is allowed to send a message
 *
 * @version     1.9 $Revision: 14672 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


/**
 * return the autorisation of the current user to send a message to the user in parameter
 *
 * @param int $userId user id of the recipient
 * @return bool true if the current user is autorised do send a message to the user in parameter
 *                 flase if the current user is not autorised do send a message to the user in parameter
 */
function current_user_is_allowed_to_send_message_to_user($userId)
{
    if (claro_is_platform_admin())
    {
        return true;
    }
    
    if (claro_is_in_a_group())
    {
        if (claro_is_group_tutor() || claro_is_course_manager())
        {
            $userList = get_group_user_list(claro_get_current_group_id(),claro_get_current_course_id());
            for ($count=0; $count<count($userList); $count++)
            {
                if ($userList[$count]['id'] == $userId)
                {
                    return true;
                }
            }
        }
        
        return false;
    }
    elseif (claro_is_in_a_course())
    {
        if (claro_is_course_manager())
        {
            $userList = claro_get_course_user_list();
            for ($count=0; $count<count($userList); $count++)
            {
                if ($userList[$count]['user_id'] == $userId)
                {
                    return true;
                }
            }
        }
        return false;
    }
    else
    {
        // can answerd to a user
        $tableName = get_module_main_tbl(array('im_message','im_recipient'));
        
        $select =
           "SELECT count(*)\n"
           .    " FROM `" . $tableName['im_message'] . "` as M\n"
           .    " INNER JOIN `" . $tableName['im_recipient'] . "` as R ON R.message_id = M.message_id\n"
           .    " WHERE (R.user_id = " . (int)claro_get_current_user_id() . " OR R.user_id = 0)\n"
           .        " AND M.sender = " . (int)$userId
           ;
           
        $nbMessage = claro_sql_query_fetch_single_value($select);
        
        if ($nbMessage > 0)
        {
            return true;
        }
        elseif( get_conf('userCanSendMessage') );
        {
            return true;
        }
        
        return false;
    }
}

/**
 * return the autorisation of the current user to send a message to the current course
 *
 * @return bool true if the current user is autorised do send a message to the current course
 *                 false if the current user is not autorised do send a message to the current course
 */
function current_user_is_allowed_to_send_message_to_current_course()
{
    if (claro_is_platform_admin())
    {
        return true;
    }
    
    if (claro_is_course_manager())
    {
        return true;
    }
    
    return false;
}

/**
 * return the autorisation of the current user to send a message to the current group
 *
 * @return bool true if the current user is autorised do send a message to the current group
 *                 false if the current user is not autorised do send a message to the current group
 */
function current_user_is_allowed_to_send_message_to_current_group()
{
    if (claro_is_platform_admin())
    {
        return true;
    }
    
    if (claro_is_group_tutor() || claro_is_course_admin())
    {
        return true;
    }
    
    return false;
    
}

function can_answer_message($messageId)
{
    $tableName = get_module_main_tbl(array('im_message_status'));
        
        $select =
           "SELECT count(*)\n"
           .    " FROM `" . $tableName['im_message_status'] . "` as M\n"
           .    " WHERE (M.user_id = " . (int)claro_get_current_user_id() . " OR M.user_id = 0)\n"
           .        " AND M.message_id = " . (int)$messageId
           ;
           
    
    $nbMessage = claro_sql_query_fetch_single_value($select);
    return $nbMessage>0? true : false;
}

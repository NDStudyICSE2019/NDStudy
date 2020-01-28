<?php // $Id: chatUserList.class.php 521 2008-09-30 13:16:04Z fragile_be $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 521 $
 *
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package  CHAT
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */
/**
 * Get html to display the message list
 *
 * @author Sebastien Piraux <pir@cerdecam.be>
 * @param boolean $onlyLastMsg true : get only the messages posted after connection, false : get all recorded messages
 * @return string html output
 */ 


class ChatUserList
{
    private $userList = array();
    
    private $courseId = null;
    private $groupId = null;
    
    private $tblChatUsers = '';
    private $tblUser = '';
    private $tblRelCourseUser = '';
    
    public function __construct($courseId, $groupId = null)
    {
        $this->courseId = $courseId;
        $this->groupId = $groupId;
        
        $tblNameList = array(
            'chat_users'
        );

        $tbl_chat_names = get_module_course_tbl( $tblNameList, $this->courseId ); 
        $this->tblChatUsers = $tbl_chat_names['chat_users'];
        
        $tbl_mdb_names = claro_sql_get_main_tbl();
        $this->tblUser = $tbl_mdb_names['user'];
        $this->tblRelCourseUser = $tbl_mdb_names['rel_course_user'];
    }

    /**
     * Load the current user list from DB
     *
     * @return boolean result of operation
     */
    public function load()
    {
    
        $sql = "SELECT 
                `U`.`prenom` as `firstname`, 
                `U`.`nom` as `lastname`,
                `RCU`.`isCourseManager` 
            FROM `".$this->tblChatUsers."` as `CU`, 
                `".$this->tblUser."` as `U`,
                `".$this->tblRelCourseUser."` as `RCU` 
            WHERE `CU`.user_id = U.user_id
              AND `U`.`user_id` = `RCU`.`user_id` 
              AND `RCU`.`code_cours` = '".$this->courseId."'";

        if( !is_null($this->groupId) )  
        {
            $sql .= " AND `CU`.`group_id` = ".(int) $this->groupId . " ";
        } 
        else
        {
            $sql .= " AND `CU`.`group_id` IS NULL ";
        }

        $sql .= " ORDER BY `RCU`.`isCourseManager` DESC,
                  `U`.`prenom` ASC,
                  `U`.`nom` ASC";

        $userList = claro_sql_query_fetch_all_rows($sql);
        
        if( $userList )
        {
            $this->userList = $userList;
            return true;
        }
        else
        {
            return false;
        }
        
    }
    
    /**
     * Produce html required to display the list
     *
     * @return string html output
     */
    public function render()
    {
        $html = '';
        
        foreach( $this->userList as $user )
        {
            if( $user['isCourseManager'] == '1' )
            {
                $userClass = ' clchat_manager';    
            }
            else
            {
                $userClass = ' clchat_student';
            }
            
            $html .= "\n" . '<span class="clchat_user '.$userClass.'">' 
            .    get_lang('%firstname %lastname', array('%firstname' => $user['firstname'], '%lastname' => $user['lastname']))
            .    '</span>' . "\n";
        }
        
        return $html;
    }

    /**
     * clean user list DB. Remove user without activity
     *  
     * @return boolean result of operation
     */
    public function prune()
    {
        
        $sql = "DELETE FROM `" . $this->tblChatUsers . "`
                      WHERE `last_action` < '" . claro_date('Y-m-d H:i:s', claro_time() - 30 )  . "'";

        if( !is_null($this->groupId) )  
        {
            $sql .= " AND `group_id` = ".(int) $this->groupId . " ";
        }
        else
        {
            $sql .= " AND `group_id` IS NULL ";
        }

        claro_sql_query($sql);
    }
    
    /**
     * tell to user list that user $userId is still active
     *
     * @param integer $userId
     * @return boolean result of operation
     */
    public function ping($userId)
    {
        
        $sql = "DELETE FROM `" . $this->tblChatUsers . "`
                      WHERE `user_id` = " . (int) $userId;
    
        if( !is_null($this->groupId) )  
        {
            $sql .= " AND `group_id` = ".(int) $this->groupId . " ";
        }
        else
        {
            $sql .= " AND `group_id` IS NULL ";
        }

        claro_sql_query($sql);

        $sql = "INSERT INTO `" . $this->tblChatUsers . "`
                SET `user_id` = " . (int) $userId . ",";
                
        if( !is_null($this->groupId) )  
        {
            $sql .= " `group_id` = ".(int) $this->groupId . ", ";
        }

        $sql .= " `last_action` = '" . claro_date('Y-m-d H:i:s') . "'";

        claro_sql_query($sql);
        return true;
    }
}

?>
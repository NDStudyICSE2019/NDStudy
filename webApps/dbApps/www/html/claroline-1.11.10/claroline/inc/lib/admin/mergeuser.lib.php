<?php // $Id: mergeuser.lib.php 14340 2012-12-05 06:45:40Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Merge User Library
 *
 * @version     $Revision: 14340 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.admin.mergeuser
 */

/**
 * Merge User Class
 */
class MergeUser
{
    protected  $hasError = false;
    
    public function hasError()
    {
        return $this->hasError;
    }
    
    public function merge( $uidToRemove, $uidToKeep )
    {
        $mainTbl = claro_sql_get_main_tbl();
        
        // inherit platform admin status ? harmful !
        /*$toKeep_isPlatformAdmin = claro_sql_query_fetch_single_value("
            SELECT isPlatformAdmin FROM `{$mainTbl['user']}` WHERE user_id = " . (int) $uidToKeep . "
        ");

        $toRemove_isPlatformAdmin = claro_sql_query_fetch_single_value("
            SELECT isPlatformAdmin FROM `{$mainTbl['user']}` WHERE user_id = " . (int) $uidToRemove . "
        ");

        if ( $toKeep_isPlatformAdmin && ! $toRemove_isPlatformAdmin )
        {
            claro_sql_query("UPDATE `{$mainTbl['user']}` SET `isPlatformAdmin` = 1 WHERE user_id = ".(int) $uidToKeep );
        }*/

        // inherit course creator status
        $toKeep_isCourseCreator = claro_sql_query_fetch_single_value("
            SELECT isCourseCreator FROM `{$mainTbl['user']}` WHERE user_id = " . (int) $uidToKeep . "
        ");

        $toRemove_isCourseCreator = claro_sql_query_fetch_single_value("
            SELECT isCourseCreator FROM `{$mainTbl['user']}` WHERE user_id = " . (int) $uidToRemove . "
        ");

        if ( $toRemove_isCourseCreator && ! $toKeep_isCourseCreator )
        {
            claro_sql_query("UPDATE `{$mainTbl['user']}` SET `isCourseCreator` = 1 WHERE user_id = ".(int) $uidToKeep );
        }

        // Get course list for the user to remove
        $sql = "
            SELECT
                c.`code` AS `code`,
                cu.`isCourseManager`,
                cu.`profile_id`
            FROM
                `{$mainTbl['course']}` c,
                `{$mainTbl['rel_course_user']}` cu
            WHERE
                cu.user_id = ".(int)$uidToRemove."
              AND
                c.code = cu.code_cours";

        $courseList = claro_sql_query_fetch_all_rows($sql);
        
        foreach ( $courseList as $thisCourse )
        {
            // Check if the user to keep is registered to the course
            $sql = "
                SELECT
                    `code_cours`,
                    `isCourseManager`,
                    `profile_id`
                FROM
                    `{$mainTbl['rel_course_user']}`
                WHERE
                    code_cours = '".claro_sql_escape($thisCourse['code'])."'
                AND
                    user_id = ".(int)$uidToKeep;

            $userToKeepCourseList = claro_sql_query_fetch_single_row($sql);
            
            if ( !empty( $userToKeepCourseList ) )
            {
                // inherit isCourseManager
                if ( ( $thisCourse['isCourseManager'] == 1 ) && ( $userToKeepCourseList['isCourseManager'] != 1 ) )
                {
                    if ( ! claro_sql_query("
                        UPDATE `{$mainTbl['rel_course_user']}`
                        SET `isCourseManager` = 1
                        WHERE code_cours = '".claro_sql_escape($thisCourse['code'])."'
                        AND user_id = ".(int) $uidToKeep ) )
                    {
                        Console::error("Cannot change rel_course_user from -{$uidToRemove} to +{$uidToKeep} isCourseManager in {$thisCourse['code']}");
                        $this->hasError = true;
                    }
                }

                // inherit profile
                if ( $thisCourse['profile_id'] > $userToKeepCourseList['profile_id'] )
                {
                    if ( ! claro_sql_query("
                        UPDATE `{$mainTbl['rel_course_user']}`
                        SET `profile_id` = ".(int) $thisCourse['profile_id']."
                        WHERE code_cours = '".claro_sql_escape($thisCourse['code'])."'
                        AND user_id = ".(int) $uidToKeep ) )
                    {
                        Console::error("Cannot change rel_course_user from -{$uidToRemove} to +{$uidToKeep} profile in {$thisCourse['code']}");
                        $this->hasError = true;
                    }
                }

                // Remove the user to remove from the course
                $sql = "DELETE FROM `{$mainTbl['rel_course_user']}`
                    WHERE user_id    = ".(int)$uidToRemove."
                      AND code_cours = '".claro_sql_escape($thisCourse['code'])."'";

                if ( ! claro_sql_query($sql) )
                {
                    Console::error("Cannot change rel_course_user from -{$uidToRemove} to +{$uidToKeep}  in {$thisCourse['code']}");
                    $this->hasError = true;
                }
            }
            else
            {
                // Replace the user id of the user to remove
                $sql = "UPDATE `{$mainTbl['rel_course_user']}`
                    SET   user_id    = ".(int)$uidToKeep."
                    WHERE user_id    = ".(int)$uidToRemove."
                      AND code_cours = '".claro_sql_escape($thisCourse['code'])."'";

                if ( ! claro_sql_query($sql) )
                {
                    Console::error("Cannot change rel_course_user from -{$uidToRemove} to +{$uidToKeep} in {$thisCourse['code']}");
                    $this->hasError = true;
                }
            }
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot change rel_course_user from -{$uidToRemove} to +{$uidToKeep} in {$thisCourse['code']}");
                $this->hasError = true;
            }
            
            $sql = "UPDATE `{$mainTbl['rel_class_user']}`
                SET   user_id    = ".(int)$uidToKeep."
                WHERE user_id    = ".(int)$uidToRemove;

            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot change rel_class_user from -{$uidToRemove} to +{$uidToKeep} in {$thisCourse['code']}");
                $this->hasError = true;
            }
            
            
            // Update course
            
            self::mergeCourseUsers( $uidToRemove, $uidToKeep, $thisCourse['code'] );
            self::mergeCourseModuleUsers( $uidToRemove, $uidToKeep, $thisCourse['code'] );
            
            // update course messaging
            self::mergeCourseMessaging( $uidToRemove, $uidToKeep, $thisCourse['code'] );
        }
        
        // Update modules
        self::mergeModuleUsers( $uidToRemove, $uidToKeep );
        
        // Update main tracking
        self::mergeMainTrackingUsers( $uidToRemove, $uidToKeep );
        
        // updtae main messaging
        self::mergeMainMessaging( $uidToRemove, $uidToKeep );
        
        // Delete old user
        $sql = "DELETE FROM `{$mainTbl['user']}`
            WHERE user_id = ".(int)$uidToRemove;

        if ( ! claro_sql_query($sql) )
        {
            Console::error("Cannot delete old user -{$uidToRemove}");
            $this->hasError = true;
        }
        
        return !self::hasError();
    }
    
    protected function mergeMainMessaging( $uidToRemove, $uidToKeep )
    {
        $tableName = get_module_main_tbl(array('im_message','im_message_status','im_recipient'));
            
        $getUserMessagesInCourse = "SELECT M.message_id AS id"
            . " FROM `" . $tableName['im_message'] . "` as M\n"
            . " LEFT JOIN `" . $tableName['im_recipient'] . "` as R ON M.message_id = R.message_id\n"
            . " WHERE R.user_id = " . (int)$uidToKeep
            . " AND M.course IS NULL";
            
        $userToKeepMsgList = claro_sql_query_fetch_all($getUserMessagesInCourse);
        
        if ( !empty( $userToKeepMsgList ) )
        {
            $messageListToRemoveArr = array();
            
            foreach ( $userToKeepMsgList as $message )
            {
                $messageListToRemoveArr[] = (int)$message['id'];
            }
            
            $messageListToRemove = implode(',', $messageListToRemoveArr);
            
            // Remove the user to remove from the course
            $sql = "DELETE FROM `{$tableName['im_recipient']}`
                WHERE user_id = " . (int)$uidToRemove . "
                AND message_id IN ({$messageListToRemove})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot delete duplicate im_recipient for -{$uidToRemove}");
                $this->hasError = true;
            }
            
            $sql = "DELETE FROM `{$tableName['im_message_status']}`
                WHERE user_id = " . (int)$uidToRemove . "
                AND message_id IN ({$messageListToRemove})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot delete duplicate im_message_status for -{$uidToRemove}");
                $this->hasError = true;
            }
        }
        
        $getUserMessagesInCourse = "SELECT M.message_id AS id"
            . " FROM `" . $tableName['im_message'] . "` as M\n"
            . " LEFT JOIN `" . $tableName['im_recipient'] . "` as R ON M.message_id = R.message_id\n"
            . " WHERE R.user_id = " . (int)$uidToRemove
            . " AND M.course IS NULL";
            
        $userToKeepMsgList = claro_sql_query_fetch_all($getUserMessagesInCourse);
        
        if ( !empty( $userToKeepMsgList ) )
        {
            $messageListToUpdateArr = array();
            
            foreach ( $userToKeepMsgList as $message )
            {
                $messageListToUpdateArr[] = (int)$message['id'];
            }
            
            $messageListToUpdate = implode(',', $messageListToUpdateArr);
        
            // Replace the user id of the user to remove
            $sql = "UPDATE `{$tableName['im_recipient']}`
                SET   user_id    = ".(int)$uidToKeep."
                WHERE user_id    = ".(int)$uidToRemove."
                  AND message_id IN ({$messageListToUpdate})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot change im_recipient from -{$uidToRemove} to +{$uidToKeep}");
                $this->hasError = true;
            }
            
            $sql = "UPDATE `{$tableName['im_message_status']}`
                SET   user_id    = ".(int)$uidToKeep."
                WHERE user_id    = ".(int)$uidToRemove."
                  AND message_id IN ({$messageListToUpdate})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot change im_message_status from -{$uidToRemove} to +{$uidToKeep}");
                $this->hasError = true;
            }
        }
    }
    
    protected function mergeCourseMessaging( $uidToRemove, $uidToKeep, $thisCourseCode )
    {
        // update messaging
        
        $tableName = get_module_main_tbl(array('im_message','im_message_status','im_recipient'));
        
        $getUserMessagesInCourse = "SELECT M.message_id AS id"
            . " FROM `" . $tableName['im_message'] . "` as M\n"
            . " LEFT JOIN `" . $tableName['im_recipient'] . "` as R ON M.message_id = R.message_id\n"
            . " WHERE R.user_id = " . (int)$uidToKeep
            . " AND M.course = '".claro_sql_escape($thisCourseCode)."'";
            
        $userToKeepMsgList = claro_sql_query_fetch_all($getUserMessagesInCourse);
        
        if ( !empty( $userToKeepMsgList ) )
        {
            $messageListToRemoveArr = array();
            
            foreach ( $userToKeepMsgList as $message )
            {
                $messageListToRemoveArr[] = (int)$message['id'];
            }
            
            $messageListToRemove = implode(',', $messageListToRemoveArr);
            
            // Remove the user to remove from the course
            $sql = "DELETE FROM `{$tableName['im_recipient']}`
                WHERE user_id = " . (int)$uidToRemove . "
                AND message_id IN ({$messageListToRemove})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot delete duplicate im_recipient for -{$uidToRemove} in {$thisCourseCode}");
                $this->hasError = true;
            }
            
            $sql = "DELETE FROM `{$tableName['im_message_status']}`
                WHERE user_id = " . (int)$uidToRemove . "
                AND message_id IN ({$messageListToRemove})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot delete duplicate im_message_status for -{$uidToRemove} in {$thisCourseCode}");
                $this->hasError = true;
            }
        }
        
        $getUserMessagesInCourse = "SELECT M.message_id AS id"
            . " FROM `" . $tableName['im_message'] . "` as M\n"
            . " LEFT JOIN `" . $tableName['im_recipient'] . "` as R ON M.message_id = R.message_id\n"
            . " WHERE R.user_id = " . (int)$uidToRemove
            . " AND M.course = '".claro_sql_escape($thisCourseCode)."'";
            
        $userToKeepMsgList = claro_sql_query_fetch_all($getUserMessagesInCourse);
        
        if ( !empty( $userToKeepMsgList ) )
        {
            $messageListToUpdateArr = array();
            
            foreach ( $userToKeepMsgList as $message )
            {
                $messageListToUpdateArr[] = (int)$message['id'];
            }
            
            $messageListToUpdate = implode(',', $messageListToUpdateArr);
        
            // Replace the user id of the user to remove
            $sql = "UPDATE `{$tableName['im_recipient']}`
                SET   user_id    = ".(int)$uidToKeep."
                WHERE user_id    = ".(int)$uidToRemove."
                  AND message_id IN ({$messageListToUpdate})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot change im_recipient from -{$uidToRemove} to +{$uidToKeep} in {$thisCourseCode}");
                $this->hasError = true;
            }
            
            $sql = "UPDATE `{$tableName['im_message_status']}`
                SET   user_id    = ".(int)$uidToKeep."
                WHERE user_id    = ".(int)$uidToRemove."
                  AND message_id IN ({$messageListToUpdate})";
            
            if ( ! claro_sql_query($sql) )
            {
                Console::error("Cannot change im_message_status from -{$uidToRemove} to +{$uidToKeep} in {$thisCourseCode}");
                $this->hasError = true;
            }
        }
    }
    
    protected function mergeCourseUsers( $uidToRemove, $uidToKeep, $courseId )
    {
        $courseTbl = claro_sql_get_course_tbl( claro_get_course_db_name_glued( $courseId ) );
        
        // Get groups for the user to remove
        $sql = "SELECT team
                FROM `{$courseTbl['group_rel_team_user']}`
                WHERE user= ".(int)$uidToRemove;

        $result   = claro_sql_query_fetch_all_cols($sql);
        $teamList = $result['team'];
        
        foreach ( $teamList as $thisTeam )
        {
            $toKeep_team_entry = claro_sql_query_fetch_single_row("
                    SELECT user, team, role, status
                    FROM `{$courseTbl['group_rel_team_user']}`
                    WHERE user = ".(int)$uidToKeep."
                      AND team = ".(int)$thisTeam );

            $toRemove_team_entry = claro_sql_query_fetch_single_row("
                    SELECT user, team, role, status
                    FROM `{$courseTbl['group_rel_team_user']}`
                    WHERE user = ".(int)$uidToRemove."
                      AND team = ".(int)$thisTeam );
            
            if ( $toKeep_team_entry )
            {
                $status = $toKeep_team_entry['status'] > $toRemove_team_entry['status']
                    ? null
                    : $toRemove_team_entry['status']
                    ;
                
                $role = empty( $toKeep_team_entry['role'] )
                    ? $toRemove_team_entry['role']
                    : null
                    ;
                
                if ( !is_null($role) || !is_null($status) )
                {
                    if ( ! claro_sql_query("UPDATE `{$courseTbl['group_rel_team_user']}`
                           SET role = '".$role."',
                               status = ".$status."
                         WHERE user = ".(int)$uidToKeep."
                           AND team = ".(int)$thisTeam) )
                    {
                        Console::error("Cannot update user group status for +{$uidToKeep} in group_rel_team_user in {$courseId}:{$thisTeam}");
                        $this->hasError = true;
                    }
                }
                
                if ( ! claro_sql_query("DELETE FROM `{$courseTbl['group_rel_team_user']}`
                         WHERE user  = ".(int)$uidToRemove."
                           AND team  = ".(int)$thisTeam) )
                {
                    Console::error("Cannot delete user -{$uidToRemove} in group_rel_team_user in {$courseId}:{$thisTeam}");
                    $this->hasError = true;
                }
            }
            else
            {
                if ( ! claro_sql_query( "UPDATE `{$courseTbl['group_rel_team_user']}`
                           SET user = ".(int)$uidToKeep."
                         WHERE user = ".(int)$uidToRemove."
                           AND team = ".(int)$thisTeam ) )
                {
                    Console::error("Cannot replace -{$uidToRemove} with +{$uidToKeep} in group_rel_team_user {$courseId}:{$thisTeam}");
                    $this->hasError = true;
                }
            }
        }
        
        // Update tracking
        $sql = "UPDATE `{$courseTbl['tracking_event']}`
                SET   user_id = ".(int)$uidToKeep."
                WHERE user_id = ".(int)$uidToRemove;

        if ( ! claro_sql_query($sql) )
        {
            Console::error("Cannot replace -{$uidToRemove} with +{$uidToKeep} in tracking_event in course {$courseId}");
            $this->hasError = true;
        }

        
        $qwz_tbl_names = get_module_course_tbl( array( 'qwz_tracking' ), $courseId );
        
        $sql = "UPDATE `{$qwz_tbl_names['qwz_tracking']}`
                SET   user_id  = ".(int)$uidToKeep."
                WHERE user_id  = ".(int)$uidToRemove;

        if ( ! claro_sql_query($sql) )
        {
            Console::error("Cannot replace -{$uidToRemove} with +{$uidToKeep} in qwz_tracking in {$courseId}");
            $this->hasError = true;
        }

        // Update user info in course
        $sql = "DELETE FROM `{$courseTbl['userinfo_content']}`
                WHERE user_id = ".(int)$uidToRemove;
        
        if ( ! claro_sql_query($sql) )
        {
            Console::error("Cannot remove user info for user -{$uidToRemove} in {$courseId}");
            $this->hasError = true;
        }
    }
    
    protected function mergeMainTrackingUsers( $uidToRemove, $uidToKeep )
    {
        $mainTbl = claro_sql_get_main_tbl();
        
        $sql = "UPDATE `{$mainTbl['tracking_event']}`
            SET   user_id = ".(int)$uidToKeep."
            WHERE user_id = ".(int)$uidToRemove;

        if ( ! claro_sql_query($sql) )
        {
            Console::error("Cannot replace -{$uidToRemove} with +{$uidToKeep} in tracking_event in main database");
            $this->hasError = true;
        }

    }
    
    protected function mergeCourseModuleUsers( $uidToRemove, $uidToKeep, $courseId )
    {
        $courseModuleList = module_get_course_tool_list( $courseId );
        
        foreach ( $courseModuleList as $courseModule )
        {
            $moduleMergeUserPath = get_module_path( $courseModule['label'] ) . '/connector/mergeuser.cnr.php';
            
            if ( file_exists( $moduleMergeUserPath ) )
            {
                require_once $moduleMergeUserPath;
                $moduleMergeClass = $courseModule['label'].'_MergeUser';
                
                if ( class_exists( $moduleMergeClass ) )
                {
                    $moduleMerge = new $moduleMergeClass;
                    
                    if ( method_exists( $moduleMerge, 'mergeCourseUsers' ) )
                    {
                        try 
                        {
                            if ( ! $moduleMerge->mergeCourseUsers( $uidToRemove, $uidToKeep, $courseId ) )
                            {
                                $this->hasError = true;
                            }
                        }
                        catch ( Exception $e )
                        {
                            Console::error($e->getMessage());
                            $this->hasError = true;
                        }
                    }
                }
            }
        }
    }
    
    protected function mergeModuleUsers( $uidToRemove, $uidToKeep )
    {
        $courseModuleList = get_module_label_list();
        
        foreach ( $courseModuleList as $courseModule )
        {
            $moduleMergeUserPath = get_module_path( $courseModule['label'] ) . '/connector/mergeuser.cnr.php';
            
            if ( file_exists( $moduleMergeUserPath ) )
            {
                require_once $moduleMergeUserPath;
                $moduleMergeClass = $courseModule['label'].'_MergeUser';
                
                if ( class_exists( $moduleMergeClass ) )
                {
                    $moduleMerge = new $moduleMergeClass;
                    
                    if ( method_exists( $moduleMerge, 'mergeUsers' ) )
                    {
                        try
                        {
                            if ( !$moduleMerge->mergeUsers( $uidToRemove, $uidToKeep ) )
                            {
                                $this->hasError = true;
                            }
                        }
                        catch ( Exception $e )
                        {
                            Console::error($e->getMessage());
                            $this->hasError = true;
                        }
                    }
                }
            }
        }
    }
}

interface Module_MergeUser
{
    public function mergeUsers( $uidToRemove, $uidToKeep );
    public function mergeCourseUsers( $uidToRemove, $uidToKeep, $courseId );
}
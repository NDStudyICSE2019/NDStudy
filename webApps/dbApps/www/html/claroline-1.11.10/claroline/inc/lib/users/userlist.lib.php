<?php

// $Id: userlist.lib.php 14688 2014-02-13 11:07:25Z zefredz $

require_once dirname(__FILE__) . '/claroclass.lib.php';
require_once dirname(__FILE__) . '/../connectors/adminuser.lib.php';

/**
 * Set of PHP classes for user batch registration and enrolment
 *
 * @version Claroline 1.11 $Revision: 14688 $
 * @copyright (c) 2013 Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package kernel
 * @author Frederic Minne <zefredz@claroline.net>
 * @todo move to Claroline kernel
 */

/**
 * Class to store the result of a batch registration
 */
class Claro_BatchRegistrationResult
{
    // status masks
    const 
        STATUS_NOT_SET = 0,
        STATUS_ERROR_UPDATE_FAIL = 1,
        STATUS_ERROR_INSERT_FAIL = 2,
        STATUS_ERROR_DELETE_FAIL = 4,
        STATUS_ERROR_NOTHING_TO_DO = 8;
    
    private
        $insertedUserList,
        $failedUserList,
        $updateUserList,
        $deletedUserList,
        $status,
        $errLog;
    
    public function __construct ()
    {
        $this->status = self::STATUS_NOT_SET;
        $this->errLog = array();
        $this->insertedUserList = array();
        $this->deletedUserList = array();
        $this->failedUserList = array();
        $this->updateUserList = array();
    }
    
    /**
     * set the status mask
     * @param int $status
     * @return Claro_BatchRegistrationResult $this
     */
    public function setStatus( $status )
    {
        if ( $this->status === self::STATUS_NOT_SET )
        {
            $this->status = $status;
        }
        else
        {
            $this->status = $this->status | $status;
        }
        
        return $this;
    }
    
    /**
     * check the status mask
     * @param int $status
     * @return boolean
     */
    public function statusSet( $status )
    {
        // SUCCESS is only set when there is no error !
        if ( $status === self::STATUS_NOT_SET && $this->status === self::STATUS_NOT_SET )
        {
            return true;
        }
        else
        {
            return ( ( $this->status & $status ) == $status );
        }
    }
    
    /**
     * add an error message
     * @param string $error
     * @return Claro_BatchRegistrationResult $this
     */
    public function addError( $error )
    {
        $this->errLog[] = $error;
        
        return $this;
    }
    
    /**
     * merge error messages array
     * @param array $errors
     */
    public function mergeErrors( $errors = array() )
    {
        $this->errLog = array_merge($this->errLog, $errors);
        
        return $this;
    }
    
    /**
     * add inserted users
     * @param array $inserted
     * @return Claro_BatchRegistrationResult $this
     */
    public function addInserted( $inserted )
    {
        $this->insertedUserList = array_merge($this->insertedUserList, $inserted);
        
        return $this;
    }
    
    /**
     * add deleted users
     * @param array $deleted
     * @return Claro_BatchRegistrationResult $this
     */
    public function addDeleted( $deleted )
    {
        $this->deletedUserList = array_merge( $this->deletedUserList, $deleted );
        
        return $this;
    }
    
    /**
     * add updated users
     * @param array $updated
     * @return Claro_BatchRegistrationResult $this
     */
    public function addUpdated( $updated )
    {
        $this->updateUserList = array_merge( $this->updateUserList, $updated );
        
        return $this;
    }
    
    /**
     * add failed users
     * @param array $failed
     * @return Claro_BatchRegistrationResult $this
     */
    public function addFailed( $failed )
    {
        $this->failedUserList = array_merge( $this->failedUserList, $failed );
        
        return $this;
    }
    
    /**
     * Merge with another result
     * @param Claro_BatchRegistrationResult $otherResult
     */
    public function mergeResult( Claro_BatchRegistrationResult $otherResult )
    {
        $this->setStatus($otherResult->getStatus());
        $this->addDeleted($otherResult->getDeletedUserList());
        $this->addFailed($otherResult->getFailedUserList());
        $this->addInserted($otherResult->getInsertedUserList());
        $this->addUpdated($otherResult->getUpdatedUserList());
        $this->mergeErrors($otherResult->getErrorLog());
    }
    
    /**
     * Get the status of the operation
     * @return int : STATUS_SUCCESS, STATUS_ERROR_UPDATE_FAIL, 
     *  STATUS_ERROR_INSERT_FAIL or STATUS_ERROR_DELETE_FAIL
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Check if the operation ended with errors
     * @return bool
     */
    public function hasError()
    {
        return $this->status > 0 && $this->status !== self::STATUS_ERROR_NOTHING_TO_DO;
    }
    
    /**
     * Get the error log
     * @return array
     */
    public function getErrorLog()
    {
        return $this->errLog;
    }
    
    /**
     * Get the list of users newly inserted in the course
     * @return array of user_id => user
     */
    public function getInsertedUserList()
    {
        return $this->insertedUserList;
    }
    
    /**
     * Get the list of users with updated registration in the course
     * @return array of user_id => user
     */
    public function getUpdatedUserList()
    {
        return $this->updateUserList;
    }
    
    /**
     * Get the list of users for which the insertion or deletion failed
     * @return array of user_id => user
     */
    public function getFailedUserList()
    {
        return $this->failedUserList;
    }
    
    /**
     * Get the list of users removed from the course
     * @return array of user_id => user
     */
    public function getDeletedUserList()
    {
        return $this->deletedUserList;
    }
}

/**
 * Utility class to add or remove users into or from a course by batch
 * @since Claroline 1.11.9
 */
class Claro_BatchCourseRegistration
{
    private 
        $database, 
        $course, 
        $result, 
        $tableNames;
    
    /**
     * 
     * @param Claro_Course $course
     * @param mixed $database Database_Connection instance or null, if null, the default database connection will be used
     */
    public function __construct( $course, $database = null, $result = null )
    {
        $this->course = $course;
        $this->database = $database ? $database : Claroline::getDatabase();
        $this->tableNames = get_module_main_tbl(array('rel_course_user'));
        $this->tableNames = array_merge( $this->tableNames, 
            get_module_course_tbl( 
                array( 'bb_rel_topic_userstonotify', 'group_team', 'userinfo_content', 'group_rel_team_user', 'tracking_event' ), 
                $this->course->courseId ) );
        
        $this->result = $result ? $result : new Claro_BatchRegistrationResult();
    }
    
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     * Get the status of the operation
     * @return int : STATUS_SUCCESS, STATUS_ERROR_UPDATE_FAIL, 
     *  STATUS_ERROR_INSERT_FAIL or STATUS_ERROR_DELETE_FAIL
     */
    public function getStatus()
    {
        return $this->result->getStatus ();
    }
    
    /**
     * Check if the operation ended with errors
     * @return bool
     */
    public function hasError()
    {
        return $this->result->hasError ();
    }
    
    /**
     * Get the error log
     * @return array
     */
    public function getErrorLog()
    {
        return $this->result->getErrorLog ();
    }
    
    /**
     * Get the list of users newly inserted in the course
     * @return array of user_id => user
     */
    public function getInsertedUserList()
    {
        return $this->result->getInsertedUserList ();
    }
    
    /**
     * Get the list of users with updated registration in the course
     * @return array of user_id => user
     */
    public function getUpdatedUserList()
    {
        return $this->result->getUpdatedUserList ();
    }
    
    /**
     * Get the list of users for which the insertion or deletion failed
     * @return array of user_id => user
     */
    public function getFailedUserList()
    {
        return $this->result->getFailedUserList ();
    }
    
    /**
     * Get the list of users removed from the course
     * @return array of user_id => user
     */
    public function getDeletedUserList()
    {
        return $this->result->getDeletedUserList();
    }
    
    /**
     * Get the list of users in $userIdList already registered to the course
     * @param array $userIdList
     * @param string $courseCode
     * @return array of user_id => [ user_id => int,count_user_enrol => int,count_class_enrol => int ]
     */
    protected function getUsersAlreadyInCourse ( $userIdList, $courseCode )
    {
        $ids = array();
        
        $courseUserIdListResultSet = $this->database->query( "
                SELECT
                    user_id, count_user_enrol, count_class_enrol, isPending
                FROM
                    `{$this->tableNames['rel_course_user']}`
                WHERE
                    code_cours = " . $this->database->quote($courseCode) . "
                AND 
                    user_id IN (" . implode( ',', $userIdList ) .")" );
        
        foreach ( $courseUserIdListResultSet as $user )
        {
            $ids[$user['user_id']] = $user;
        }
        
        return $ids;
    }
    
    /**
     * Add a list of users given their user id to the course
     * @param array $userIdList list of user ids to add
     * @param Claro_Class $class execute class registration instead of individual registration if given (default:null)
     * @param bool $forceClassRegistrationOfExistingClassUsers transform individual registration to class registration if set to true (default: false)
     * @param array $userListAlreadyInClass user already in class as an array of user_id => user
     * @param bool $forceValidationOfPendingUsers pending user enrollments will be validated if set to true (default: false)
     * @return boolean
     */
    public function addUserIdListToCourse( $userIdList, $class = null, $forceClassRegistrationOfExistingClassUsers = false, $userListAlreadyInClass = array(), $forceValidationOfPendingUsers = false )
    {
        if ( ! count( $userIdList ) )
        {
            return false;
        }
        
        $classMode = is_null( $class ) ? false : true;
        
        $courseCode = $this->course->courseId;
        $sqlCourseCode = $this->database->quote( $courseCode );
        
        $updateUserList = array();
        $failedUserList = array();
        
        // 1. PROCESS USERS ALREADY IN COURSE
        
        // get user id already in course
        
        $usersAlreadyInCourse = $this->getUsersAlreadyInCourse( $userIdList, $courseCode );
                    
        // update registration of existing users if classMode
                    
        if ( $classMode )
        {
            // register class to course if not already done
            if ( ! $class->isRegisteredToCourse ( $courseCode ) )
            {
                $class->registerToCourse( $courseCode );
            }
            
            foreach ( $usersAlreadyInCourse as $userId => $courseUser )
            {
                if ( $forceClassRegistrationOfExistingClassUsers /* || $courseUser['count_class_enrol'] != 0 */ )
                {
                    $courseUser['count_user_enrol'] = 0;                 
                }
                
                if ( ! array_key_exists( $courseUser['user_id'], $userListAlreadyInClass ) || $courseUser['count_class_enrol'] <= 0 )
                {
                    $courseUser['count_class_enrol']++;
                }
                
                $pending = $forceValidationOfPendingUsers ? '`isPending` = 0,' : '';
                
                // update user in DB
                if ( !$this->database->exec("
                    UPDATE
                        `{$this->tableNames['rel_course_user']}`
                    SET
                        {$pending}
                        `count_user_enrol` = " . $courseUser['count_user_enrol'] . ",
                        `count_class_enrol` = " . $courseUser['count_class_enrol'] . "
                    WHERE
                        user_id = " . Claroline::getDatabase()->escape($userId) . "
                    AND
                        code_cours = {$sqlCourseCode}"
                ) )
                {
                    $failedUserList[$courseUser['user_id']] = $courseUser;
                }
                
                $updateUserList[$courseUser['user_id']] = $courseUser;
            }
            
            if ( count ( $failedUserList ) )
            {
                $this->result->addFailed($failedUserList);
                $this->result->setStatus( Claro_BatchRegistrationResult::STATUS_ERROR_UPDATE_FAIL );
                $this->result->addError( get_lang( 
                    "Cannot update course registration information for users %userlist% in course %course%", array( 
                        '%userlist%' =>  implode(",",$failedUserList ), 
                        '%course%' => $courseCode ) ) );
                Console::error( "Cannot update course registration information for users " . implode(",",$failedUserList ) . " in course {$courseCode}" );
            }
            
            $this->result->addUpdated($updateUserList);
        }
        
        // 2. PROCESS USERS NOT ALREADY IN COURSE
                    
        // construct the query for insertion of new users
        
        $sqlProfileId = $this->database->escape( claro_get_profile_id(USER_PROFILE) );
        
        $userNewRegistrations = array();
        $userListToInsert = array();
        
        foreach ( $userIdList as $userId )
        {
            if ( !array_key_exists ( $userId, $usersAlreadyInCourse ) )
            {
                if ( $classMode )
                {
                    $userNewRegistration = array(
                        'user_id' => $this->database->escape( $userId ),
                        'count_user_enrol' => 0,
                        'count_class_enrol' => 1,
                        'isPending' => 0
                    );
                }
                else
                {
                    $userNewRegistration = array(
                        'user_id' => $this->database->escape( $userId ),
                        'count_user_enrol' => 1,
                        'count_class_enrol' => 0,
                        'isPending' => 0
                    );
                }

                // user_id, profile_id, isCourseManager, isPending, tutor, count_user_enrol, count_class_enrol, enrollment_date
                $userNewRegistrations[] = "({$userNewRegistration['user_id']},{$sqlCourseCode}, {$sqlProfileId}, 0, 0, 0, {$userNewRegistration['count_user_enrol']},{$userNewRegistration['count_class_enrol']}, NOW())";
                $userListToInsert[$userId] = $userNewRegistration;
            }
        }
        
        // execute the query
        
        if ( count($userNewRegistrations) )
        {  
            if ( !$this->database->exec("
                INSERT INTO
                    `{$this->tableNames['rel_course_user']}`
                        (user_id, code_cours, profile_id, isCourseManager, isPending, tutor, count_user_enrol, count_class_enrol, enrollment_date)
                VALUES\n" . implode( ",\n\t", $userNewRegistrations ) ) )
            {
                $this->result->setStatus( Claro_BatchRegistrationResult::STATUS_ERROR_INSERT_FAIL);
                $this->result->addError ( get_lang( 
                    "Cannot insert userlist %userlist% in course %course%", array( 
                        '%userlist%' =>  implode( ",", $userListToInsert ), 
                        '%course%' => $courseCode ) ) );
                Console::error( "Cannot insert userlist " . implode( ",", $userListToInsert ) . " in  course  {$courseCode}" );
                
                $this->result->addFailed( $userListToInsert );
            }
            else
            {
                $this->result->addInserted ( $userListToInsert );
            }
            
        }
        
        if ( $this->course->hasSourceCourse () )
        {
            $sourceCourse = $this->course->getSourceCourse();
            $sourceReg = new Claro_BatchCourseRegistration($sourceCourse, $this->database, $this->result);
            $sourceReg->addUserIdListToCourse($userIdList, $class, $forceClassRegistrationOfExistingClassUsers, $userListAlreadyInClass, $forceValidationOfPendingUsers);
            $this->result->mergeResult( $sourceReg->getResult () );
        }
        
        return !$this->result->hasError();
    }
    
    /**
     * Force remove a list of users given their user id from the cours. All users' registrations (even by class) to the course will be removed
     * @param array $userIdList list of user ids to add
     * @param bool $keepTrackingData tracking data will be deleted if set to false (default:true, i.e. keep data)
     * @param array $moduleDataToPurge list of module_label => (purgeTracking => bool, purgeData => bool)
     * @param bool $unregisterFromSourceIfLastSession remove users that are in no other session course from the source course if any
     * @return boolean
     */
    public function forceRemoveUserIdListFromCourse( $userIdList, $keepTrackingData = true, $moduleDataToPurge = array(), $unregisterFromSourceIfLastSession = true )
    {
        if ( ! count( $userIdList ) )
        {
            return false;
        }
        
        $courseCode = $this->course->courseId;
        $sqlCourseCode = $this->database->quote( $courseCode );
        
        // var_dump($userIdList);
        
        $this->database->exec("
            UPDATE
                `{$this->tableNames['rel_course_user']}`
            SET
                `count_class_enrol` = 0,
                `count_user_enrol` = 0
            WHERE
                `code_cours` = {$sqlCourseCode}
            AND
                `user_id` IN (".implode( ',', $userIdList ).")
        ");
                
                
        // get the user ids to remove
        
        $userListToRemove = $this->database->query("
            SELECT 
                `user_id`
            FROM
                `{$this->tableNames['rel_course_user']}`
            WHERE
                `count_class_enrol` <= 0
            AND
                `count_user_enrol` <= 0
            AND
                `code_cours` = {$sqlCourseCode}
        ");
        
        if ( $userListToRemove->numRows() )
        {
            $userIdListToRemove = array();
            
            foreach ( $userListToRemove as $user )
            {
                $userIdListToRemove[] = $user['user_id'];
            }
            
            $sqlList = array();
            
            $sqlList[] = "DELETE FROM `{$this->tableNames['bb_rel_topic_userstonotify']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).")";
            $sqlList[] = "DELETE FROM `{$this->tableNames['userinfo_content']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).")";
            $sqlList[] = "UPDATE `{$this->tableNames['group_team']}` SET `tutor` = NULL WHERE `tutor` IN (".implode( ',', $userIdListToRemove ).")";
            $sqlList[] = "DELETE FROM `{$this->tableNames['group_rel_team_user']}` WHERE user IN (".implode( ',', $userIdListToRemove ).")";
            
            if ( !$keepTrackingData )
            {
                $sqlList[] = "DELETE FROM `{$this->tableNames['tracking_event']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).")";
            }
            
            $sqlList[] = "DELETE FROM `{$this->tableNames['rel_course_user']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).") AND `code_cours` = {$sqlCourseCode}";
            
            foreach ( $sqlList as $sql )
            {
                $this->database->exec( $sql );
            }
            
            if ( !empty( $moduleDataToPurge ) )
            {
                foreach ( $moduleDataToPurge as $moduleData )
                {
                    $connectorPath = get_module_path( $moduleData['label'] ) . '/connector/adminuser.cnr.php';
                    
                    if ( file_exists( $connectorPath ) )
                    {
                        require_once $connectorPath;
                        
                        $connectorClass = $moduleData['label'] . '_AdminUser';
                        
                        if ( class_exist ( $connectorClass ) )
                        {
                            $connector = new $connectorClass( $this->database );

                            if ( $moduleData['purgeTracking'] )
                            {
                                $connector->purgeUserListCourseTrackingData( $userIdListToRemove, $this->course->courseId );
                            }

                            if ( $moduleData['purgeResources'] )
                            {
                                $connector->purgeUserListCourseResources( $userIdListToRemove, $this->course->courseId );
                            }
                        }
                        else
                        {
                            Console::warning("Class {$connectorClass} not found");
                        }
                    }
                    else
                    {
                        Console::warning("No user delete connector found for module {$moduleData['label']}");
                    }
                }
            }
            
            $this->result->addDeleted ( $userIdListToRemove );
            
            if ( $this->course->isSourceCourse () )
            {
                $sessionCourseIterator = $this->course->getChildren();
                
                foreach ( $sessionCourseIterator as $sessionCourse )
                {
                    $batchReg = new self( $sessionCourse, $this->database );
                    $batchReg->forceRemoveUserIdListFromCourse( $userIdListToRemove, $keepTrackingData, $moduleDataToPurge, $unregisterFromSourceIfLastSession );
                    $this->result->mergeResult($batchReg->getResult () );
                }
            }
            
            if ( $this->course->hasSourceCourse () && $unregisterFromSourceIfLastSession )
            {
                $sourceCourse = $this->course->getSourceCourse();
                
                $sessionCourseIterator = $sourceCourse->getChildren();
                                 
                // get userids registered in other sessions than the current one

                $sessionList = $sourceCourse->getChildrenList();
                
                if ( count( $sessionList ) )
                {  
                    $userIdListToRemoveFromSource = array();

                    $sessionIdList = array_keys( $sessionList );

                    $sqlCourseCode = $this->database->quote($this->course->courseId);

                    $usersInOtherSessions = $this->database->query("
                        SELECT
                            user_id
                        FROM
                            `{$this->tableNames['rel_course_user']}`
                        WHERE
                            user_id IN (".implode( ',', $userIdListToRemove ).")
                        AND
                            code_cours IN ('".implode( "','", $sessionIdList )."')
                        AND
                            code_cours != {$sqlCourseCode}
                    ");


                    // loop on $userIdList and keep only those who are not in another session and inject them in $userIdListToRemoveFromSource

                    $usersInOtherSessionsList = array();

                    foreach ( $usersInOtherSessions as $userNotToRemove  )
                    {
                        $usersInOtherSessionsList[$userNotToRemove['user_id']] = $userNotToRemove['user_id'];
                    }

                    foreach ( $userListToRemove as $userIdToRemove )
                    {
                        if ( ! isset( $usersInOtherSessionsList[$userIdToRemove['user_id']] ) )
                        {
                            $userIdListToRemoveFromSource[] = $userIdToRemove['user_id'];
                        }
                    }

                    if ( count( $userIdListToRemoveFromSource ) )
                    {
                        $batchReg = new self( $sourceCourse, $this->database );
                        $batchReg->forceRemoveUserIdListFromCourse( $userIdListToRemoveFromSource, $keepTrackingData, $moduleDataToPurge, $unregisterFromSourceIfLastSession );
                        $this->result->mergeResult($batchReg->getResult () );
                    }
                }
            }
            
        }
        else
        {
            $this->result->setStatus(Claro_BatchRegistrationResult::STATUS_ERROR_NOTHING_TO_DO);
            $this->result->addError(get_lang("No user to delete"));
        }
        
        return !$this->result->hasError();
    }
    
    /**
     * Remove a list of users given their user id from the cours
     * @param array $userIdList list of user ids to add
     * @param Claro_Class $class execute class unregistration instead of individual registration if given (default:null)
     * @param bool $keepTrackingData tracking data will be deleted if set to false (default:true, i.e. keep data)
     * @param array $moduleDataToPurge list of module_label => (purgeTracking => bool, purgeData => bool)
     * @param bool $unregisterFromSourceIfLastSession remove users that are in no other session course from the source course if any
     * @return boolean
     */
    public function removeUserIdListFromCourse( $userIdList, $class = null, $keepTrackingData = true, $moduleDataToPurge = array(), $unregisterFromSourceIfLastSession = true )
    {
        if ( ! count( $userIdList ) )
        {
            return false;
        }
        
        $classMode = is_null( $class ) ? false : true;
        
        $courseCode = $this->course->courseId;
        $sqlCourseCode = $this->database->quote( $courseCode );
        
        if ( $classMode && !$class->isRegisteredToCourse($courseCode) )
        {
            $this->result->addError(get_lang("Class not registered to course"));
            $this->result->setStatus(Claro_BatchRegistrationResult::STATUS_ERROR_NOTHING_TO_DO);
            return false;
        }
        
        // update user registration counts
        $cntToChange = $classMode ? 'count_class_enrol' : 'count_user_enrol';
        
        $this->database->exec("
            UPDATE
                `{$this->tableNames['rel_course_user']}`
            SET
                `{$cntToChange}` = `{$cntToChange}` - 1
            WHERE
                `code_cours` = {$sqlCourseCode}
            AND
                `{$cntToChange}` > 0
            AND
                `user_id` IN (".implode( ',', $userIdList ).")
        ");
                
                
        // get the user ids to remove
        
        $userListToRemove = $this->database->query("
            SELECT 
                `user_id`
            FROM
                `{$this->tableNames['rel_course_user']}`
            WHERE
                `count_class_enrol` <= 0
            AND
                `count_user_enrol` <= 0
            AND
                `code_cours` = {$sqlCourseCode}
        ");
                
        if ( $userListToRemove->numRows() )
        {
            $userIdListToRemove = array();
            
            foreach ( $userListToRemove as $user )
            {
                $userIdListToRemove[] = $user['user_id'];
            }
            
            $sqlList = array();
            
            $sqlList[] = "DELETE FROM `{$this->tableNames['bb_rel_topic_userstonotify']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).")";
            $sqlList[] = "DELETE FROM `{$this->tableNames['userinfo_content']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).")";
            $sqlList[] = "UPDATE `{$this->tableNames['group_team']}` SET `tutor` = NULL WHERE `tutor` IN (".implode( ',', $userIdListToRemove ).")";
            $sqlList[] = "DELETE FROM `{$this->tableNames['group_rel_team_user']}` WHERE user IN (".implode( ',', $userIdListToRemove ).")";
            
            if ( !$keepTrackingData )
            {
                $sqlList[] = "DELETE FROM `{$this->tableNames['tracking_event']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).")";
            }
            
            $sqlList[] = "DELETE FROM `{$this->tableNames['rel_course_user']}` WHERE user_id IN (".implode( ',', $userIdListToRemove ).") AND `code_cours` = {$sqlCourseCode}";
            
            foreach ( $sqlList as $sql )
            {
                $this->database->exec( $sql );
            }
            
            if ( !empty( $moduleDataToPurge ) )
            {
                foreach ( $moduleDataToPurge as $moduleData )
                {
                    $connectorPath = get_module_path( $moduleData['label'] ) . '/connector/adminuser.cnr.php';
                    
                    if ( file_exists( $connectorPath ) )
                    {
                        require_once $connectorPath;
                        
                        $connectorClass = $moduleData['label'] . '_AdminUser';
                        
                        if ( class_exist ( $connectorClass ) )
                        {
                            $connector = new $connectorClass( $this->database );

                            if ( $moduleData['purgeTracking'] )
                            {
                                $connector->purgeUserListCourseTrackingData( $userIdListToRemove, $this->course->courseId );
                            }

                            if ( $moduleData['purgeResources'] )
                            {
                                $connector->purgeUserListCourseResources( $userIdListToRemove, $this->course->courseId );
                            }
                        }
                        else
                        {
                            Console::warning("Class {$connectorClass} not found");
                        }
                    }
                    else
                    {
                        Console::warning("No user delete connector found for module {$moduleData['label']}");
                    }
                }
            }
            
            $this->result->addDeleted ( $userIdListToRemove );
            
            if ( $this->course->isSourceCourse () )
            {
                $sessionCourseIterator = $this->course->getChildren();
                
                foreach ( $sessionCourseIterator as $sessionCourse )
                {
                    $batchReg = new self( $sessionCourse, $this->database );
                    $batchReg->removeUserIdListFromCourse( $userIdListToRemove, $class, $keepTrackingData, $moduleDataToPurge, $unregisterFromSourceIfLastSession );
                    $this->result->mergeResult($batchReg->getResult () );
                }
            }
            
            if ( $this->course->hasSourceCourse () && $unregisterFromSourceIfLastSession )
            {
                $sourceCourse = $this->course->getSourceCourse();
                
                $sessionCourseIterator = $sourceCourse->getChildren();
                
                $foundSessionWithClass = false;
                
                if ( $classMode )
                {
                    foreach ( $sessionCourseIterator as $sessionCourse )
                    {
                        if ( ( $sessionCourse->courseId != $this->course->courseId )
                            && $class->isRegisteredToCourse ( $sessionCourse->courseId ) )
                        {
                            $foundSessionWithClass = true;
                        }
                    }
                    
                    if ( ! $foundSessionWithClass )
                    {
                        $batchReg = new self( $sourceCourse, $this->database );
                        $batchReg->removeUserIdListFromCourse( $userIdListToRemove, $class, $keepTrackingData, $moduleDataToPurge, $unregisterFromSourceIfLastSession );
                    }
                
                }
                else
                {                    
                    // get userids registered in other sessions than the current one
                    
                    $sessionList = $sourceCourse->getChildrenList();
                    
                    if ( count( $sessionList ) )
                    {
                        $userIdListToRemoveFromSource = array();
                        
                        $sessionIdList = array_keys( $sessionList );

                        $sqlCourseCode = $this->database->quote($this->course->courseId);

                        $usersInOtherSessions = $this->database->query("
                            SELECT
                                user_id
                            FROM
                                `{$this->tableNames['rel_course_user']}`
                            WHERE
                                user_id IN (".implode( ',', $userIdListToRemove ).")
                            AND
                                code_cours IN ('".implode( "','", $sessionIdList )."')
                            AND
                                code_cours != {$sqlCourseCode}
                        ");


                        // loop on $userIdList and keep only those who are not in another session and inject them in $userIdListToRemoveFromSource

                        $usersInOtherSessionsList = array();

                        foreach ( $usersInOtherSessions as $userNotToRemove  )
                        {
                            $usersInOtherSessionsList[$userNotToRemove['user_id']] = $userNotToRemove['user_id'];
                        }

                        foreach ( $userListToRemove as $userIdToRemove )
                        {
                            if ( ! isset( $usersInOtherSessionsList[$userIdToRemove['user_id']] ) )
                            {
                                $userIdListToRemoveFromSource[] = $userIdToRemove['user_id'];
                            }
                        }
                    
                        if ( count( $userIdListToRemoveFromSource ) )
                        {
                            $batchReg = new self( $sourceCourse, $this->database );
                            $batchReg->removeUserIdListFromCourse( $userIdListToRemoveFromSource, $class, $keepTrackingData, $moduleDataToPurge, $unregisterFromSourceIfLastSession );
                            $this->result->mergeResult($batchReg->getResult () );
                        }
                    }
                }
            }
        }
        else
        {
            $this->result->setStatus(Claro_BatchRegistrationResult::STATUS_ERROR_NOTHING_TO_DO);
            $this->result->addError(get_lang("No user to delete"));
        }        
        
        return !$this->result->hasError();
    }
    
    /**
     * Helper to remove all users from a course
     * @param bool $keepClasses if true, all classes will be removed too
     * @param array $profilesToDelete array of profile labels
     * @param string $registeredBefore date yyyy-mm-dd hh:mm:ss
     * @param string $registeredAfter date yyyy-mm-dd hh:mm:ss
     * @return bool false if an error occured
     * @throws Exception
     */
    public function removeAllUsers( $keepClasses = true, $profilesToDelete = array(), $registeredBefore = null, $registeredAfter = null )
    {
        if ( $keepClasses && ( !is_null( $registeredBefore ) || !is_null($registeredAfter) ) )
        {
            throw new Exception(get_lang('Cannot combine enrolment date filters and class deletion, please delete the classes independently'));
        }
        
        $courseUserList = new Claro_CourseUserList($this->course->courseId, $this->database );

        if ( empty( $profilesToDelete ) )
        {
            $profilesToDelete = array( USER_PROFILE );
        }

        $courseUserIdList = $courseUserList->getFilteredUserIdList( $profilesToDelete, $registeredBefore, $registeredAfter );

        $this->removeUserIdListFromCourse($courseUserIdList);

        if ( ! $keepClasses )
        {
            $courseClassList = new Claro_CourseClassList( $this->course, $this->database );

            foreach ($courseClassList->getClassListIterator() as $class )
            {
                $classUserList = new Claro_ClassUserList( $class, $this->database );

                $this->removeUserIdListFromCourse( $classUserList->getClassUserIdList(), $class );
            }
        }

        return !$this->result->hasError();
    }
}

/**
 * Add a list of users to the platform
 * @since Claroline 1.11.9
 */
class Claro_PlatformUserList
{

    protected $database, $tables;
    
    protected 
        $userSuccessList = array(), 
        $userFailureList = array(), 
        $userInsertedList = array(),
        $userConvertedList = array(),
        $userDisabledList = array(),
        $userAlreadyThere = array();
    
    /**
     * 
     * @param mixed $database Database_Connection instance or null, if null, the default database connection will be used
     */
    public function __construct ( $database = null )
    {
        $this->database = is_null ( $database ) ? Claroline::getDatabase () : $database;
        $this->tables = claro_sql_get_main_tbl ();
    }
    
    /**
     * Get the list of valid users i.e. users registered to the platform
     * @return array of username => user_id
     */
    public function getValidUserIdList()
    {
        return $this->userSuccessList;
    }
    
    /**
     * Get the list of newly inserted users
     * @return array of username => user_id
     */
    public function getInsertedUserIdList()
    {
        return $this->userInsertedList;
    }
    
    public function getFailedUserInsertionList()
    {
        return $this->userFailureList;
    }
    
    /**
     * Get the list of converted users i.e. users for which the authSource/password has been changed
     * @return array of username => user_id
     */
    public function getConvertedUserIdList()
    {
        return $this->userConvertedList;
    }
    
    /**
     * Get the list of valid users for which the account has been disabled
     * @return array of username => user_id
     */
    public function getDisabledUserIdList()
    {
        return $this->userConvertedList;
    }
    
    /**
     * Get the list of users that were alredy in the platform
     * @return array of username => user_id
     */
    public function getAlreadyThereUserIdList()
    {
        return $this->userAlreadyThere;
    }
    
    /**
     * 
     * @param Iterator $userList
     * @param string $overwriteAuthSourceWith change the auth source for existing users with the given one, set to null if you want to keep the original auth source (default:null)
     * @param bool $emptyPasswordForOverWrittenAuthSource empty (i.e. set to string value 'empty') users for which the auth source is changed
     * @return boolean false if empty list given
     */
    public function registerUserList ( $userList, $overwriteAuthSourceWith = null, $emptyPasswordForOverWrittenAuthSource = false )
    {
        if ( ! count( $userList ) )
        {
            return false;
        }
        
        foreach ( $userList as $user )
        {
            try
            {
                $userFound = $this->getUserIfAlreadyExists ( $user );

                if ( false !== $userFound )
                {
                    if ( $userFound['email'] == $user->email )
                    {
                        if ( $overwriteAuthSourceWith 
                            && ( $userFound['authSource']  !== $overwriteAuthSourceWith ) )
                        {
                            if ( $emptyPasswordForOverWrittenAuthSource )
                            {
                                $emptyPassword = ",
                                    `password` = 'empty'
                                ";
                            }
                            else
                            {
                                $emptyPassword = '';
                            }
                            
                            $this->database->exec( "
                            UPDATE
                                `{$this->tables['user']}`
                            SET
                                `authSource` = ".$this->database->quote( $overwriteAuthSourceWith )."
                                {$emptyPassword}
                            WHERE
                                user_id = " . Claroline::getDatabase ()->escape ( $userFound['user_id'] )
                            );
                            
                            $this->userConvertedList[$userFound['username']] = $userFound['user_id'];
                            Console::info ( "Change authSource to {$overwriteAuthSourceWith} for user ".var_export($userFound,true) );
                        }
                        else
                        {
                            // user already there, nothing to be done
                            $this->userAlreadyThere[$userFound['username']] = $userFound['user_id'];
                        }

                        $this->userSuccessList[$userFound['username']] = $userFound['user_id'];
                    }
                    else
                    {
                        if ( $userFound['authSource']  !== $overwriteAuthSourceWith )
                        {
                            // disable old account by changing the username
                            $this->database->exec( "
                            UPDATE
                                `{$this->tables['user']}`
                            SET
                                `authSource` = 'disabled',
                                `username` = CONCAT('*EPC*', username )
                            WHERE
                                user_id = " . Claroline::getDatabase ()->escape ( $userFound['user_id'] )
                            );

                            $this->userDisabledList[$userFound['username']] = $userFound['user_id'];

                            Console::info ( "Disable account for user ".var_export($userFound,true)." : conflict with ldap account " .var_export($user,true) );

                            $this->insertUserAsNew($user);
                        }
                        else
                        {
                            // this is the same user and we trust the authentication source over the user list data
                            Console::info('User already there with same authsource but different email : trust authsource [' . var_export($userFound,true).']');
                            $this->userAlreadyThere[$userFound['username']] = $userFound['user_id'];
                            $this->userSuccessList[$userFound['username']] = $userFound['user_id'];
                        }
                    }
                }
                else
                {
                    $this->insertUserAsNew($user);
                }
            }
            catch ( Exception $e )
            {
                $this->userFailureList[] = $user;
                Console::error ( "Cannot add user {$user->username} : EXCEPTION '{$e->getMessage()}' with stack {$e->getTraceAsString ()}" );
            }
        }
        
        Console::info( "Add user to platform from EPC : converted=" . count( $this->userConvertedList )
                . " disabled=" .count( $this->userDisabledList )
                . " inserted=" . count( $this->userInsertedList )
                . " alreadythere=" . count( $this->userAlreadyThere )
                . " failed=" . count( $this->userFailureList ) );
        
        if ( count( $this->userInsertedList ) )
        {
            Console::info( "Add user to platform from EPC : userid created " . implode(',', $this->userInsertedList ) );
        }
        
        return true;
        
    }
    
    /**
     * Insert a user record as new user in the platform user list
     * @param stdClass $user (lastname,firstname,username,email,officialCode)
     */
    protected function insertUserAsNew( $user )
    {
        $this->database->exec( "
        INSERT INTO `{$this->tables['user']}`
        SET nom             = ". $this->database->quote($user->lastname) .",
            prenom          = ". $this->database->quote($user->firstname) .",
            username        = ". $this->database->quote($user->username) .",
            language        = '',
            email           = ". $this->database->quote($user->email) .",
            officialCode    = ". $this->database->quote($user->officialCode) .",
            officialEmail   = ". $this->database->quote($user->email) .",
            authSource      = 'ldap', 
            phoneNumber     = '',
            password        = 'empty',
            isCourseCreator = 0,
            isPlatformAdmin = 0,
            creatorId    = " . claro_get_current_user_id() );
        
        $key = (string) $user->username;

        $this->userInsertedList[$key] = $this->userSuccessList[$key] = $this->database->insertId();
    }
    
    /**
     * Returns a user if it is found, false otherwise
     * @param stdClass $user (username,...)
     * @return mixed false or the found user
     */
    public function getUserIfAlreadyExists ( $user )
    {
        $foundUser = $this->database->query ( "
            SELECT 
                u.username,
                u.user_id,
                u.authSource,
                u.email
            FROM 
                `{$this->tables['user']}` AS u 
            WHERE 
                u.`username` = " . $this->database->quote ( $user->username )
        );

        if ( !$foundUser->numRows () )
        {
            return false;
        }
        else
        {
            return $foundUser->fetch();
        }
    }
    
    /**
     * Delete a user list
     * @param array $userIdList
     * @return int
     */
    public function deleteUserList ( $userIdList )
    {
        if ( ! count( $userIdList ) )
        {
            return false;
        }
        
        $affectedRows = $this->database->exec("
            DELETE FROM
                `{$this->tables['user']}`
            WHERE
                `user_id` IN (".implode( ',', $userIdList ).")
        ");
                
        // do we need to delete user tracking or not ?
        
        Console::info("Removed {$affectedRows} users : " . implode( ',', $userIdList ) );
                
        return $affectedRows;
    }
    
    /**
     * Disable the accounts for the given user list
     * @param array $userIdList
     * @return int
     */
    public function disableUserList ( $userIdList )
    {
        if ( ! count( $userIdList ) )
        {
            return false;
        }
        
        $affectedRows = $this->database->exec("
            UPDATE
                `{$this->tables['user']}`
            SET
                `authSource` = 'disabled'
            WHERE
                `user_id` IN (".implode( ',', $userIdList ).")
        ");
        
        Console::info("Disabled {$affectedRows} users : " . implode( ',', $userIdList ) );
                
        return $affectedRows;
    }

}

/**
 * USer list of a course
 * @since Claroline 1.11.9
 */
class Claro_CourseUserList
{
    const
        USE_KEY_USERID = 'user_id',
        USE_KEY_USERNAME = 'username';

    protected $cid, $course, $database, $tables;
    protected $courseUserList, $courseUserIdList, $courseUsernameList;
    
    protected $hasPendingUsers = null;

    /**
     * 
     * @param string $cid id(code) of the course
     * @param mixed $database Database_Connection instance or null, if null, the default database connection will be used
     */
    public function __construct ( $cid = null, $database = null )
    {
        $this->cid = is_null ( $cid ) ? claro_get_current_course_id () : $cid;
        $this->database = is_null ( $database ) ? Claroline::getDatabase () : $database;

        $this->course = new Claro_course ( $cid );
        $this->course->load ();
        
        $this->tables = claro_sql_get_main_tbl ();
    }
    
    /**
     * Get the list of users registered in the course
     * @param bool $forceRefresh
     * @return array of user_id => (username,user_id,count_user_enrol,count_class_enrol,isPending)
     */
    public function getUserList ( $forceRefresh = false )
    {
        if ( !is_array ( $this->courseUserList ) || $forceRefresh )
        {
            $resultSet = $this->getUserListIteratorUsingKey( self::USE_KEY_USERID );

            $this->courseUserList = array ( );

            foreach ( $resultSet as $userId => $user )
            {
                $this->courseUserList[ $userId ] = $user;
            }
        }

        return $this->courseUserList;
    }
    
    protected function getUserListIteratorUsingKey( $key )
    {
        if ( $key != self::USE_KEY_USERID && $key != self::USE_KEY_USERNAME )
        {
            throw new Exception('Invalid key : must be Claro_CourseUserList::USE_KEY_USERID or Claro_CourseUserList::USE_KEY_USERNAME');
        }

        $cid = $this->database->quote ( $this->cid );

        $resultSet = $this->database->query ( "
            SELECT 
                u.username, 
                cu.user_id, 
                cu.count_user_enrol, 
                cu.count_class_enrol,
                cu.isPending
            FROM
                `{$this->tables['rel_course_user']}` AS cu
            JOIN
                `{$this->tables['user']}` AS u
            ON
                cu.user_id = u.user_id
            WHERE
                cu.code_cours = {$cid}
        " );

        $resultSet->useId ( $key );
        
        return $resultSet;
    }
    
    /**
     * Get the list of the ids of the users registered in the course
     * @param bool $forceRefresh
     * @return array of user_id => user_id so it can be used a a list or a set
     */
    public function getUserIdList ( $forceRefresh = false )
    {
        if ( !is_array ( $this->courseUserIdList ) || $forceRefresh )
        {
            $cid = $this->database->quote ( $this->cid );

            $resultSet = $this->database->query ( "
                SELECT
                    cu.user_id
                FROM
                    `{$this->tables['rel_course_user']}` AS cu

                WHERE
                    cu.code_cours = {$cid}
                
            " );

            $resultSet->useId ( 'user_id' );

            $this->courseUserIdList = array ( );

            foreach ( $resultSet as $user_id => $user )
            {
                $this->courseUserIdList[ $user_id ] = $user_id;
            }
        }

        return $this->courseUserIdList;
    }
    
    /**
     * Get the list of the usernames of the users registered in the course
     * @param bool $forceRefresh
     * @return array of username => (username,user_id,count_user_enrol,count_class_enrol,isPending)
     */
    public function getUsernameList( $forceRefresh = false )
    {
        if ( !is_array ( $this->courseUserList ) || $forceRefresh )
        {
            $resultSet = $this->getUserListIteratorUsingKey( self::USE_KEY_USERNAME );

            $this->courseUsernameList = array ( );

            foreach ( $resultSet as $username => $user )
            {
                $this->courseUsernameList[ $username ] = $user;
            }
        }

        return $this->courseUsernameList;
    }
    
    /**
     * Check if the given user is already in the course
     * @param int $userId
     * @return boolean
     */
    public function is_userIdAlreadyInCourse( $userId )
    {
        $userIdList = $this->getUserIdList();
        
        return isset( $userIdList[$userId] ) ? true : false;
    }
    
    /**
     * Check if a course has pending user registrations
     * @param boolean $forceRefresh
     * @return boolean
     */
    public function has_registrationPending( $forceRefresh = false )
    {
        if ( is_null($this->hasPendingUsers) || $forceRefresh )
        {
            $cid = $this->database->quote ( $this->cid );

            $this->hasPendingUsers = $this->database->query ( "
                SELECT 
                    u.username, 
                    cu.user_id, 
                    cu.count_user_enrol, 
                    cu.count_class_enrol,
                    cu.isPending
                FROM
                    `{$this->tables['rel_course_user']}` AS cu
                JOIN
                    `{$this->tables['user']}` AS u
                ON
                    cu.user_id = u.user_id
                WHERE
                    cu.code_cours = {$cid}
                AND    
                    cu.isPending = 1
                AND
                    cu.isCourseManager = 0
            " )->numRows();
        }
        
        return $this->hasPendingUsers;
    }
    
    /**
     * Get the list of user ids filtered
     * @param array $profileList list of profile labels
     * @param string $registeredBefore date yyyy-mm-dd hh:mm:ss
     * @param string $registeredAfter date yyyy-mm-dd hh:mm:ss
     * @return array of id => id
     */
    public function getFilteredUserIdList( $profileList = array(), $registeredBefore = null, $registeredAfter = null )
    {
        $sqlCourseCode = $this->database->quote( $this->course->courseId );

        $sqlDateFilterArray = array();

        if ( $registeredAfter )
        {
            $sqlDateFilterArray[] = "`registration_date` >= " . $this->database->quote( $registeredAfter );
        }

        if ( $registeredBefore )
        {
            $sqlDateFilterArray[] = "`registration_date` <= " . $this->database->quote( $registeredBefore );
        }

        if ( count($sqlDateFilterArray) == 2 )
        {
            $sqlDateFilter = "
                AND
                    (" . implode( ' OR ', $sqlDateFilterArray ) . ")
            ";
        }
        elseif ( count($sqlDateFilterArray) == 1 )
        {
            $sqlDateFilter = "
                AND
                    {$sqlDateFilterArray[0]}
            ";
        }
        else
        {
            $sqlDateFilter = "";
        }

        if ( count( $profileList ) )
        {
            foreach ( $profileList as $key => $value )
            {
                $profileList[$key] = claro_get_profile_id( $value );
            }

            // profileId not in profileToKeep
            $sqlProfilesToDelete = "
                AND 
                    `profile_id` IN (".implode(',',$profileList).")
            ";
        }
        else
        {
            $sqlProfilesToDelete = "";
        }
        
        $userList = $this->database->query( "
            SELECT
                `user_id` AS `id`
            FROM
                `{$this->tables['rel_course_user']}`
            WHERE
                `code_cours` = {$sqlCourseCode}
            {$sqlProfilesToDelete}
            {$sqlDateFilter}

        ");
        
        $userIdList = array();
        
        foreach ( $userList as $user )
        {
            $userIdList[$user['id']] = $user['id'];
        }
        
        return $userIdList;
    }

}

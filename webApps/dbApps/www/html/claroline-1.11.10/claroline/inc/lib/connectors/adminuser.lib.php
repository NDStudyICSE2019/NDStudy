<?php // $Id: adminuser.lib.php 14502 2013-08-07 07:54:51Z zefredz $

/**
 * User administration connector
 * @since Claroline 1.11.6
 */
interface Module_AdminUser
{
    /**
     * Delete user tracking data in course
     * @param int $userId
     * @param string $courseCode
     */
    public function deleteUserCourseTrackingData( $userId, $courseCode );
    
    /**
     * Delete resources posted by user in course
     * @param int $userId
     * @param string $courseCode
     */
    public function deleteUserCourseResources( $userId, $courseCode );
    
    /**
     * Delete tracking data in course for the list of users
     * @param int $userIdList list of user ids
     * @param string $courseCode
     */
    public function deleteUserListCourseTrackingData( $userIdList, $courseCode );
    
    /**
     * Delete resources posted in course by the users in the list
     * @param int $userIdList list of user ids
     * @param string $courseCode
     */
    public function deleteUserListCourseResources( $userIdList, $courseCode );
}

/**
 * Abstract generic User administration connector
 * @since Claroline 1.11.6
 */
abstract class GenericModule_AdminUser implements Module_AdminUser
{
    private $database;
    
    public function __construct ( $database )
    {
        $this->database = $database ? $database : Claroline::getDatabase ();
    }
    
    /**
     * Return the list of tracking tables
     * @param string $courseCode
     * @return array ( table_name => user_id_field_name
     */
    abstract public function getCourseTrackingTables( $courseCode );
    
    /**
     * Return the list of resource tables
     * @param string $courseCode
     * @return array ( table_name => user_id_field_name
     */
    abstract public function getCourseResourceTables( $courseCode );
    
    public function deleteUserListCourseTrackingData ( $userIdList, $courseCode )
    {
        foreach ( $this->getCourseTrackingTables( $courseCode ) as $tableName => $userIdColumn )
        {
            $this->database->exec("DELETE FROM `{$tableName}` WHERE {$userIdColumn} IN (".implode( ',', $userIdList ).")");
        }
    }
    
    public function deleteUserListCourseResources ( $userIdList, $courseCode )
    {
        foreach ( $this->getCourseResourceTables( $courseCode ) as $tableName => $userIdColumn )
        {
            $this->database->exec("DELETE FROM `{$tableName}` WHERE {$userIdColumn} IN (".implode( ',', $userIdList ).")");
        }
    }
    
    public function deleteUserCourseTrackingData ( $userId, $courseCode )
    {
        return $this->deleteUserListCourseTrackingData ( array( (int) $userId ), $courseCode );
    }
    
    public function deleteUserCourseResources ( $userId, $courseCode )
    {
        return $this->deleteUserListCourseResources ( array( (int) $userId ), $courseCode );
    }
}

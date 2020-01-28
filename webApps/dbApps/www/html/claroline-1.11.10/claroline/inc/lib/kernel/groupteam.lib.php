<?php // $Id: groupteam.lib.php 14328 2012-11-16 09:47:37Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Objects used to represent groups in the platform.
 *
 * @version     Claroline 1.11 $Revision: 14328 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.objects
 */

require_once dirname(__FILE__) . '/object.lib.php';
require_once dirname(__FILE__) . '/../core/claroline.lib.php';
require_once dirname(__FILE__) . '/../database/database.lib.php';

/**
 * Claro_GroupTeam represents a Group/Team
 *
 * @author zefredz <zefredz@claroline.net>
 * @since 1.10
 */
class
    Claro_GroupTeam
extends
    KernelObject
implements
    Countable
{
    //put your code here
    protected $_courseObj, $_groupId, $_userList;

    /**
     * @param Claro_Course $courseObj
     * @param int $groupId
     */
    public function __construct( Claro_Course $courseObj, $groupId )
    {
        $this->_groupId = $groupId;
        $this->_courseObj = $courseObj;
        $this->_userList = null;
        $this->sessionVarName = '_group';
    }

    /**
     * Load course properties and group properties from database
     * @param bool $forceReload
     */
    protected function loadFromDatabase()
    {
        $this->loadGroupCourseProperties();
        $this->loadGroupTeamProperties();
        $this->_userList = null;
    }

    /**
     * Load group properties defined for the course
     */
    protected function loadGroupCourseProperties()
    {   
        $grouProperties = $this->_courseObj->getGroupProperties();
        
        if ( ! $grouProperties )
        {
            throw new Exception("Cannot load group properties for {$this->_courseObj->courseId}");
        }
        
        $this->_rawData = array_merge( $this->_rawData, $grouProperties );
    }

    /**
     * Load group specific properties
     */
    protected function loadGroupTeamProperties()
    {
        $tbl = claro_sql_get_course_tbl( $this->_courseObj->dbNameGlu );

        $sql = "
            SELECT
                g.id               AS id          ,
                g.id               AS groupId          ,
                g.name             AS name        ,
                g.description      AS description ,
                g.tutor            AS tutorId     ,
                g.secretDirectory  AS directory   ,
                g.maxStudent       AS maxMember
            FROM
                `{$tbl['group_team']}`  AS g
            WHERE
                g.id = {$this->_groupId};
        ";
                
        $groupData = Claroline::getDatabase()
            ->query( $sql )
            ->fetch();
        
        if ( ! $groupData  )
        {
            throw new Exception("Cannot load group data for {$this->_groupId}");
        }

        $this->_rawData = array_merge( $this->_rawData,
            iterator_to_array( $groupData ) );
    }

    /**
     * Get the properties of the user in the current Group/Team
     * @param Claro_User $userObj
     * @return stdClass user properties record :
     *      $userProperties->isGroupMember : boolean
     *      $userProperties->status : boolean
     *      $userProperties->role : string or null
     *      $userProperties->isGroupTutor : boolean
     */
    public function getUserPropertiesInGroup( Claro_User $userObj )
    {
        if ( !$this->_rawData )
        {
            throw new Exception("Group data not loaded !");
        }

        $tbl = claro_sql_get_course_tbl( $this->_courseObj->dbNameGlu );

        $sql = "SELECT
                    status,
                    role
                FROM
                    `{$tbl['group_rel_team_user']}`
                WHERE
                    `user` = {$userObj->userId}
                AND
                    `team`   = {$this->_groupId};";

        $result = Claroline::getDatabase()
            ->query( $sql )
            ->fetch();

        $userProperties = new stdClass();

        if ( ! $result )
        {
            $userProperties->isGroupMember = false;
            $userProperties->status = false;
            $userProperties->role = null;
            $userProperties->isGroupTutor = $this->_rawData['tutorId'] == $userId;
        }
        else
        {
            $userProperties->isGroupMember = true;
            $userProperties->status = $result['status'];
            $userProperties->role = $result['role'];
            $userProperties->isGroupTutor = $this->_rawData['tutorId'] == $userId;
        }

        return $userProperties;
    }

    /**
     * Get the list of users in the group
     * @return Database_ResultSet group members
     */
    public function getGroupMembers()
    {
        if ( ! $this->_userList )

        {
            $mainTableName = get_module_main_tbl(array('user','rel_course_user'));
            $courseTableName = get_module_course_tbl(array('group_rel_team_user'), $this->_courseObj->courseId);

            $sql = "
                SELECT
                    `user`.`user_id` AS `id`,
                    `user`.`nom` AS `lastName`,
                    `user`.`prenom` AS `firstName`,
                    `user`.`email`
                FROM
                    `{$mainTableName['user']}` AS `user`
                INNER JOIN
                    `{$courseTableName['group_rel_team_user']}` AS `user_group`
                ON
                    `user`.`user_id` = `user_group`.`user`
                INNER JOIN
                    `{$mainTableName['rel_course_user']}` AS `course_user`
                ON
                    `user`.`user_id` = `course_user`.`user_id`
                WHERE
                    `user_group`.`team`= {$this->_groupId}
                AND
                    `course_user`.`code_cours` = '{$this->_courseObj->sysCode}'";

            $this->_userList = Claroline::getDatabase()->query($sql);
        }

        return $this->_userList;
    }

    /**
     * Get the course object the group belongs to
     * @return Claro_Course
     */
    public function getCourse()
    {
        return $this->_courseObj;
    }

    /**
     * Get the user object for the group tutor
     * @return Claro_User
     */
    public function getTutor()
    {
        $tutor = null;

        if ( $this->tutorId )
        {
            $tutor = new Claro_User($this->tutorId);
            $tutor->load();
        }

        return $tutor;
    }

    /**
     * Get the number of members in the group
     * @see Countable
     * @return int
     */
    public function count()
    {
        return count($this->getGroupMembers());
    }
}

/**
 * Claro_CurrentGroupTeam represents the current Group/Team
 *
 * @author zefredz <zefredz@claroline.net>
 * @since 1.10
 */
class Claro_CurrentGroupTeam extends Claro_GroupTeam
{
    public function __construct( $groupId = null )
    {
        $groupId = empty( $groupId )
            ? claro_get_current_group_id()
            : $groupId
            ;

        parent::__construct( $groupId );
    }
    
    protected static $instance = false;

    /**
     * Singleton constructor
     * @todo avoid using the singleton pattern and use a factory instead ?
     * @param int $uid user id
     * @param boolean $forceReload force reloading the data
     * @return Claro_CurrentUser current user
     */
    public static function getInstance( $groupId = null, $forceReload = false )
    {
        if ( $forceReload || ! self::$instance )
        {
            self::$instance = new self( $groupId );
            
            if ( !$forceReload && claro_is_in_a_group() )
            {
                self::$instance->loadFromSession();
            }
            else
            {
                self::$instance->load( $forceReload );
            }
        }
        
        return self::$instance;
    }
}

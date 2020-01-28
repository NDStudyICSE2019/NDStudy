<?php // $Id: course.lib.php 14684 2014-02-11 10:00:41Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Claroline Course objects.
 *
 * @version     Claroline 1.11 $Revision: 14684 $
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
 * Represents a course in the platform
 */
class Claro_Course extends KernelObject
{
    protected $_courseId;
    protected $sourceCourse = null;
    protected $_children = null;
    
    /**
     * Constructor
     * @todo use course id (int) instead of course code to identify a course.
     * @param string $courseId course code
     */
    public function __construct( $courseId )
    {
        $this->_courseId = $courseId;
        $this->sessionVarName = '_course';
    }
    
    /**
     * Load course data properties from an array
     */
    public function loadFromArray($array)
    {
        $this->_rawData = $array;
        
        // set bool values
        $this->_rawData['visibility'] = ('visible' == $array['visibility'] );
        $this->_rawData['registrationAllowed'] = ('open' == $array['registration'] );
        
        // set dbNameGlu
        $this->_rawData['dbNameGlu'] =
            get_conf('courseTablePrefix')
            . $array['dbName']
            . get_conf('dbGlu');
    }
    
    /**
     * Load course properties and group properties from database
     */
    protected function loadFromDatabase()
    {
        $this->_rawData = array();
        $this->loadCourseKernelData();
        //$this->loadCourseCategories();
        //$this->loadCourseProperties();
        //$this->loadGroupProperties();
    }
    
    /**
     * Load course main properties from database
     */
    protected function loadCourseKernelData()
    {
        // get course data from main
        $tbl =  claro_sql_get_main_tbl();
        
        $sqlCourseId = Claroline::getDatabase()->quote($this->_courseId);

        $sql_getCourseData = "
            SELECT
                c.code                  AS courseId,
                c.code                  AS sysCode,
                c.cours_id              AS id,
                c.isSourceCourse        AS isSourceCourse,
                c.sourceCourseId        AS sourceCourseId,
                c.intitule              AS name,
                c.administrativeNumber  AS officialCode,
                c.administrativeNumber  AS administrativeNumber,
                c.directory             AS path,
                c.dbName                AS dbName,
                c.titulaires            AS titular,
                c.email                 AS email,
                c.language              AS language,
                c.extLinkUrl            AS extLinkUrl,
                c.extLinkName           AS extLinkName,
                c.visibility            AS visibility,
                c.access                AS access,
                c.registration          AS registration,
                c.registrationKey       AS registrationKey,
                c.diskQuota             AS diskQuota,
                UNIX_TIMESTAMP(c.creationDate)          AS publicationDate,
                UNIX_TIMESTAMP(c.expirationDate)        AS expirationDate,
                c.status                AS status,
                c.userLimit             AS userLimit
            FROM
                `{$tbl['course']}`   AS c
            WHERE
                c.code = {$sqlCourseId};
        ";

        $courseDataList = Claroline::getDatabase()
            ->query( $sql_getCourseData )
            ->fetch();
        
        if ( ! $courseDataList )
        {
            throw new Exception("Cannot load course data for {$this->_courseId}");
        }
        
        // set bool values
        $courseDataList['visibility'] = ('visible' == $courseDataList['visibility'] );
        $courseDataList['registrationAllowed'] = ('open' == $courseDataList['registration'] );
        
        // set dbNameGlu
        $courseDataList['dbNameGlu'] =
            get_conf('courseTablePrefix')
            . $courseDataList['dbName']
            . get_conf('dbGlu')
            ;
        
        $this->_rawData = $courseDataList;
    }

    /**
     * Load course categories
     */
    protected function loadCourseCategories()
    {
        $tbl = claro_sql_get_main_tbl();
        
        $categoriesDataList = Claroline::getDatabase()->query("
            SELECT
                cat.id      AS categoryId,
                cat.name    AS categoryName,
                cat.code    AS categoryCode,
                cat.visible AS visibility,
                cat.rank    AS categoryRank
            FROM
                `{$tbl['category']}` AS cat
            LEFT JOIN
                `{$tbl['rel_course_category']}` AS rcc
            ON
                cat.id = rcc.categoryId
            WHERE
                rcc.courseId = {$this->_rawData['id']};
        ");
        
        $this->_rawData['categories'] = array();
        
        foreach ( $categoriesDataList as $category )
        {
            $category['visibility'] = ($category['visibility'] == 1);
            $this->_rawData['categories'][] = $category;
        }
    }

    /**
     * Load course additionnal properties from database
     */
    protected function loadCourseProperties()
    {
        // get extra course properties
        $tbl = claro_sql_get_course_tbl( $this->_rawData['dbNameGlu'] );

        $courseProperties = Claroline::getDatabase()
            ->query("
                SELECT
                    name,
                    value
                FROM
                    `{$tbl['course_properties']}`
                WHERE
                    category = 'MAIN';
            ")
            ->fetch();
        
        $coursePropertyList = array();

        if ( is_array( $courseProperties ) )
        {
            foreach ( $courseProperties as $currentProperty )
            {
                $coursePropertyList[$currentProperty['name']] = $currentProperty['value'];
            }
        }
        
        $this->_rawData['courseProperties'] = $coursePropertyList;
    }

    /**
     * Load course group properties from database
     */
    protected function loadGroupProperties()
    {
        $tbl = claro_sql_get_course_tbl( $this->_rawData['dbNameGlu'] );

        $db_groupProperties = Claroline::getDatabase()
            ->query("
                SELECT
                    name,
                    value
                FROM
                    `{$tbl['course_properties']}`
                WHERE
                    category = 'GROUP';
            ");
        
        if ( ! $db_groupProperties )
        {
            // throw new Exception
            Console::warning("Cannot load group properties for {$courseId}");
        }
        
        $groupProperties = array();
        
        foreach($db_groupProperties as $currentProperty)
        {
            $groupProperties[$currentProperty['name']] = (int) $currentProperty['value'];
        }
        
        $groupProperties ['registrationAllowed'] =  (isset($groupProperties['self_registration']) && $groupProperties['self_registration'] == 1);
        unset($groupProperties['self_registration']);
        
        $groupProperties ['unregistrationAllowed'] =  (isset($groupProperties['self_unregistration']) && $groupProperties['self_unregistration'] == 1);
        unset($groupProperties['self_unregistration']);

        $groupProperties ['private'] =  ( isset( $groupProperties['private'] ) && $groupProperties['private'] == 1 );

        $groupProperties['tools'] = array();
        
        $groupToolList = get_activated_group_tool_label_list( $this->_courseId );
        
        foreach ( $groupToolList as $thisGroupTool )
        {
            $groupTLabel = $thisGroupTool['label'];
            
            if ( array_key_exists( $groupTLabel, $groupProperties ) )
            {
                $groupProperties ['tools'] [$groupTLabel] = ($groupProperties[$groupTLabel] == 1);
                
                unset ( $groupProperties[$groupTLabel] );
            }
            else
            {
                $groupProperties ['tools'] [$groupTLabel] = false;
            }
        }
        
        $this->_rawData['groupProperties'] = $groupProperties;
    }
    
    protected function loadSourceCourseCode()
    {
        if ( $this->_rawData['sourceCourseId'] )
        {
            $tbl =  claro_sql_get_main_tbl();
        
            $sqlCourseId = Claroline::getDatabase()->escape($this->_rawData['sourceCourseId']);
            
            $this->_rawData['sourceCourseCode'] = Claroline::getDatabase()->query("
                SELECT
                    c.code
                FROM
                    `{$tbl['course']}`   AS c
                WHERE
                    c.cours_id = {$sqlCourseId}
            ")->setFetchMode ( Database_ResultSet::FETCH_VALUE )->fetch();
        }
        else
        {
            $this->_rawData['sourceCourseCode'] = null;
        }
    }

    /**
     * Get group properties in the course
     * @return array
     */
    public function getGroupProperties()
    {
        if ( !isset($this->_rawData['groupProperties']) )
        {
            $this->loadGroupProperties();
        }
        
        return $this->_rawData['groupProperties'];
    }

    /**
     * Get course additional properties
     * @return array
     */
    public function getCourseProperties()
    {
        if ( !isset($this->_rawData['courseProperties']) )
        {
            $this->loadCourseProperties();
        }
        
        return $this->_rawData['courseProperties'];
    }
    
    /**
     * Get course additional properties
     * @return array
     */
    public function getCourseCategories()
    {
        if ( !isset($this->_rawData['categories']) )
        {
            $this->loadCourseCategories();
        }
        
        return $this->_rawData['categories'];
    }
    
    /**
     * Get the course code of the parent course
     * @return string or null
     * @since API version 1.11.5
     */
    public function getSourceCourseCode()
    {
        if ( !isset($this->_rawData['sourceCourseCode']) )
        {
            $this->loadSourceCourseCode();
        }
        
        return $this->_rawData['sourceCourseCode'];
    }
    
    /**
     * Check if a course has a source course
     * @return boolean
     */
    public function hasSourceCourse()
    {
        $sourceCourseCode  = $this->getSourceCourseCode();
        
        if ( !empty( $sourceCourseCode ) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Get the course code of the parent course
     * @return string or null
     * @since API version 1.11.5
     */
    public function getSourceCourse()
    {
        if ( $this->hasSourceCourse() )
        {
            $sourceCourse = new Claro_Course( $this->_rawData['sourceCourseCode'] );
            $sourceCourse->load();

            return $sourceCourse;
        }
        else 
        {
            throw new Exception("The course {$this->courseId} has no source course");
        }
    }
    
    /**
     * Check is the course is a source cours
     * @return boolean
     * @since API version 1.11.9
     */
    public function isSourceCourse()
    {
        if ( $this->hasSourceCourse() )
        {
            return false;
        }
        else 
        {
            return $this->_rawData['isSourceCourse'] === '1';
        }
    }
    
    protected function _getChildrenList( $forceRefresh = false )
    {
        if ( ! $this->_children || $forceRefresh )
        {
            $tbl =  claro_sql_get_main_tbl();

            $parentId = Claroline::getDatabase()->escape($this->_rawData['id']);

            $this->_children = Claroline::getDatabase()->query("
                SELECT
                    c.code                  AS courseId,
                    c.code                  AS sysCode,
                    c.cours_id              AS id,
                    c.isSourceCourse        AS isSourceCourse,
                    c.sourceCourseId        AS sourceCourseId,
                    c.intitule              AS name,
                    c.administrativeNumber  AS officialCode,
                    c.administrativeNumber  AS administrativeNumber,
                    c.directory             AS path,
                    c.dbName                AS dbName,
                    c.titulaires            AS titular,
                    c.email                 AS email,
                    c.language              AS language,
                    c.extLinkUrl            AS extLinkUrl,
                    c.extLinkName           AS extLinkName,
                    c.visibility            AS visibility,
                    c.access                AS access,
                    c.registration          AS registration,
                    c.registrationKey       AS registrationKey,
                    c.diskQuota             AS diskQuota,
                    UNIX_TIMESTAMP(c.creationDate)          AS publicationDate,
                    UNIX_TIMESTAMP(c.expirationDate)        AS expirationDate,
                    c.status                AS status,
                    c.userLimit             AS userLimit
                FROM
                    `{$tbl['course']}`   AS c
                WHERE
                    c.sourceCourseId = {$parentId}
            ");
                
        }
                    
        return $this->_children;
    }
    
    /**
     * Get children id list
     */
    
    public function getChildrenList( $forceRefresh = false )
    {
        if ( $this->isSourceCourse() )
        {
            $childrenList = array();
            
            $children = $this->_getChildrenList ( $forceRefresh );
            
            foreach ( $children as $child )
            {
                $childrenList[$child['courseId']] = $child;
            }
            
            return $childrenList;
        }
        else 
        {
            throw new Exception("The course {$this->courseId} has no session courses");
        }
    }
    
    /**
     * Get the iterator of children courses from a source course
     * @return Claro_CourseIterator
     * @since API version 1.11.9
     * @throws Exception if not a source course
     */
    public function getChildren( $forceRefresh = false )
    {
        if ( $this->isSourceCourse() )
        {
            $childrenIterator = new Claro_CourseIterator( $this->_getChildrenList ( $forceRefresh ) );
            
            return $childrenIterator;
        }
        else 
        {
            throw new Exception("The course {$this->courseId} has no session courses");
        }
    }
    
    /**
     * Return true if the course is activated, false otherwise.
     *
     * A course is deactivated if:
     * - its status is 'disable' OR 'pending' OR 'trash'
     * - its status is 'date' AND
     *   - the current date is < to its publication date OR
     *     the current date is > to its expiration date
     *
     * @return boolean
     */
    public function isActivated()
    {
        $currentDate = claro_mktime();
        
        if ($this->status == 'disable' ||
            $this->status == 'pending' ||
            $this->status == 'trash')
        {
            return false;
        }
        elseif ($this->status == 'date')
        {
            if ($currentDate < $this->publicationDate ||
                $currentDate > $this->expirationDate)
            {
                return false;
            }
        }
        else
        {
            return true;
        }
    }
    
    /**
     * Return true if the course is visible, false otherwise.
     *
     * @return boolean
     */
    public function isVisible()
    {
        return (bool) $this->visibility;
    }
    
    /**
     * Overwrite KernelObjet::__get to get properties from both main properties
     * and additionnal properties.
     * @param string $nm property name
     * @return mixed property value or null
     */
    public function __get( $nm )
    {
        if ( $nm == 'categories' )
        {
            return $this->getCourseCategories();
        }
        elseif ( $nm == 'courseProperties' )
        {
            return $this->getCourseProperties();
        }
        elseif ( $nm == 'groupProperties' )
        {
            return $this->getGroupProperties();
        }
        elseif ( $nm == 'sourceCourseCode' )
        {
            return $this->getSourceCourseCode();
        }
        else
        {
            if ( isset ( $this->_rawData[$nm] ) )
            {
                return $this->_rawData[$nm];
            }
            else
            {
                return null;
            }
        }
    }
}

/**
 * Represents the current course object. This class is a singleton.
 */
class Claro_CurrentCourse extends Claro_Course
{
    public function __construct( $courseId = null )
    {
        $courseId = empty( $courseId )
            ? claro_get_current_course_id()
            : $courseId
            ;
            
        parent::__construct( $courseId );
    }
    
    protected static $instance = false;

    /**
     * Singleton constructor
     * @param int $courseId course code
     * @param boolean $forceReload force relaoding the course
     * @return Claro_CurrentCourse
     */
    public static function getInstance( $courseId = null, $forceReload = false )
    {
        if ( $forceReload || ! self::$instance )
        {
            self::$instance = new self( $courseId );
            
            if ( !$forceReload && claro_is_in_a_course() )
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

class Claro_CourseIterator extends RowToObjectIteratorIterator
{
    public function current ()
    {
        $courseData = $this->internalIterator->current();
        
        $course = new Claro_Course($courseData['courseId']);
        $course->loadFromArray($courseData);
        
        return $course;
    }    
}

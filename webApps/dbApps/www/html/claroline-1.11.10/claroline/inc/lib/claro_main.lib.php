<?php // $Id: claro_main.lib.php 14711 2014-02-14 14:24:31Z zefredz $

if ( count( get_included_files() ) == 1 ) die( basename(__FILE__) );

/**
 * Claroline main functions library
 *
 * This lib contain many parts of frequently used function.
 * This is not a thematic lib
 *
 * @version     $Revision: 14711 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GENERAL PUBLIC LICENSE
 *              version 2 or later
 * @author      Claro Team <cvs@claroline.net>
 * @package     kernel
 *
 * @todo why do we need that much identifiers for a module ?!?
 * @todo use Exceptions instead of claro_failure
 */

require_once dirname(__FILE__) . '/compat_php54.lib.php';
require_once dirname(__FILE__) . '/language.lib.php';
require_once dirname(__FILE__) . '/core/core.lib.php';
require_once dirname(__FILE__) . '/core/context.lib.php';

/**
 * SECTION :  Function to access the sql datas
 */
require_once dirname(__FILE__) . '/sql.lib.php';

/**
 * SECTION :  Class & function to prepare a normalised html output.
 */
require_once dirname(__FILE__) . '/init.lib.php';

/**
 * SECTION :  Class & function to prepare a normalised html output.
 */
require_once dirname(__FILE__) . '/path.lib.php';


/**
 * SECTION :  File handling functions
 */
require_once dirname(__FILE__) . '/file.lib.php';

/**
 * SECTION : PHP COMPAT For PHP backward compatibility
 */
require_once dirname(__FILE__) . '/compat.lib.php';

/**
 * SECTION :  Class & function to prepare a normalised html output.
 */
require_once dirname(__FILE__) . '/html.lib.php';

/**
 * SECTION :  Class & function to get text zone contents.
 */
require_once dirname(__FILE__) . '/textzone.lib.php';

/**
 * SECTION :  Modules functions
 */
require_once dirname(__FILE__) . '/module.lib.php';
require_once dirname(__FILE__) . '/module/manage.lib.php';

/**
 * SECTION :  Icon functions
 * depends on module.lib.php
 */
require_once dirname(__FILE__) . '/icon.lib.php';

/**
 * SECTION :  Get kernel
 * SUBSECTION datas for courses
 */


/**
 * Get unique keys of a course.
 *
 * @param  string $courseId (optionnal)  If not set, it use the current course
 *         will be taken.
 * @return array list of unique keys (sys, db & path) of a course
 * @author Christophe Gesche <moosh@claroline.net>
 * @author Frederic Minne <zefredz@claroline.net>
 * @since 1.7
 */
function claro_get_course_data($courseId = NULL, $force = false )
{
    static $cachedDataList = array();
    
    $useCurrentCourseData = false;
    
    if ( is_null( $courseId ) && claro_is_in_a_course() )
    {
        $courseId =  claro_get_current_course_id();
        $useCurrentCourseData = true;
    }
    
    if ( ! array_key_exists( $courseId, $cachedDataList ) || true === $force )
    {
        if ( $useCurrentCourseData )
        {
            $courseDataList = $GLOBALS['_course'];
        }
        else
        {
            $tbl_mdb_names              = claro_sql_get_main_tbl();
            $tbl_courses                = $tbl_mdb_names['course'];
            $tbl_category               = $tbl_mdb_names['category'];
            $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
            
            // Get course datas
            $sql =  "SELECT
                    c.cours_id              AS id,
                    c.code                  AS sysCode,
                    c.isSourceCourse        AS isSourceCourse,
                    c.sourceCourseId        AS sourceCourseId,
                    c.intitule              AS name,
                    c.administrativeNumber  AS officialCode,
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
                    
                    FROM `" . $tbl_courses . "` AS c
                    
                    WHERE c.code = '" . claro_sql_escape($courseId) . "'";
            
            $courseDataList = claro_sql_query_get_single_row($sql);
            
            if ( ! $courseDataList ) return claro_failure::set_failure('course_not_found');
            
            $courseDataList['access'             ] = $courseDataList['access'];
            $courseDataList['visibility'         ] = (bool) ('visible' == $courseDataList['visibility'] );
            $courseDataList['registrationAllowed'] = $courseDataList['registration'];
            $courseDataList['dbNameGlu'          ] = get_conf('courseTablePrefix') . $courseDataList['dbName'] . get_conf('dbGlu'); // use in all queries
            
            // Get categories datas
            $sql = "SELECT cat.id  AS categoryId
                    FROM `" . $tbl_category . "` AS cat
                    LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
                    ON ( cat.id = rcc.categoryId )
                    WHERE rcc.courseId = " . $courseDataList['id'];
            
            $categoriesDataList = claro_sql_query_fetch_all($sql);
            
            $courseDataList['categories'         ] = $categoriesDataList;

            // Doesn't work claro_sql_get_tbl need a tool id and is not for a tool
            // kernel table would be in mainDB.
            // $tbl =  claro_sql_get_tbl('course_properties', array(CLARO_CONTEXT_COURSE=>$courseDataList['sysCode']));
            $tbl = claro_sql_get_course_tbl( $courseDataList['dbNameGlu'] );
            $tbl_course_properties = $tbl['course_properties'];
            
            $sql = "SELECT name, value
                    FROM `" . $tbl_course_properties . "`
                    WHERE category = 'MAIN'";

            $extraDataList = claro_sql_query_fetch_all($sql);

            if (is_array($extraDataList) )
            {
                foreach($extraDataList as $thisData)
                {
                    $courseDataList[$thisData['name']] = $thisData['value'];
                }
            }
        }

        $cachedDataList[$courseId] = $courseDataList; // cache for the next time ...
    }

    return $cachedDataList[$courseId];
}


/**
 * Get all courses datas in data base.
 *
 * @param int           category identifier (default: null)
 * @param bool          $visibility (1 = only visible, 0 = only invisible, null = all; default: null)
 * @return array        collection of courses ordered by label (asc)
 * @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since 1.10
 */
function claro_get_all_courses ($categoryId = null, $visibility = null)
{
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_course                 = $tbl_mdb_names['course'];
    $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
    
    $curdate = date('Y-m-d H:i:s', time());
    
    $sql = "SELECT c.cours_id               AS id,
                   c.titulaires             AS titular,
                   c.code                   AS sysCode,
                   c.isSourceCourse         AS isSourceCourse,
                   c.sourceCourseId         AS sourceCourseId,
                   c.intitule               AS title,
                   c.administrativeNumber   AS officialCode,
                   c.language,
                   c.directory,
                   c.visibility,
                   c.access,
                   c.registration,
                   c.email,
                   c.status,
                   c.userLimit
                   
            FROM `" . $tbl_course . "` AS c";
    
    if (!is_null($categoryId))
    {
        $sql .= "
            LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
            ON c.cours_id = rcc.courseId
            
            WHERE (rcc.categoryId = " . (int) $categoryId . "
            OR c.sourceCourseId IS NOT NULL)";
    }

    if (!is_null($visibility))
    {
        if (!is_null($categoryId))
            $sql .= "AND ";
        else
            $sql .= "WHERE ";
            
        if ($visibility)
            $sql .= "c.visibility = 'visible'";
        else
            $sql .= "c.visibility = 'invisible'";
    }

    $sql .= "
            ORDER BY c.intitule ASC";
    
    return claro_sql_query_fetch_all($sql);
}


/**
 * Get courses datas in data base.
 *
 * @param int           category identifier (default: null)
 * @param int           identifier of the user
 * @return array        array of courses ordered by label (asc)
 * @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since 1.10
 */
function claro_get_restricted_courses ($categoryId, $userId)
{
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_course                 = $tbl_mdb_names['course'];
    $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
    $tbl_rel_course_user        = $tbl_mdb_names['rel_course_user'];
    
    $curdate = claro_mktime();
    
    $sql = "SELECT c.cours_id               AS id,
                    c.titulaires            AS titular,
                    c.code                  AS sysCode,
                    c.isSourceCourse        AS isSourceCourse,
                    c.sourceCourseId        AS sourceCourseId,
                    c.intitule              AS title,
                    c.administrativeNumber  AS officialCode,
                    c.dbName                AS db,
                    c.intitule              AS title,
                    UNIX_TIMESTAMP(c.expirationDate)    AS expirationDate,
                    UNIX_TIMESTAMP(c.creationDate)      AS creationDate,
                    c.language,
                    c.directory,
                    c.visibility,
                    c.access,
                    c.registration,
                    c.email,
                    c.status,
                    c.userLimit";
    
    if (!is_null($categoryId))
        $sql .= ",
                    rcc.categoryId  AS categoryId,
                    rcc.rootCourse  AS rootCourse";
    
    if (!is_null($userId))
        $sql .= ",
                    rcu.isCourseManager,
                    rcu.isPending,
                    rcu.user_id              AS enroled";
    
    $sql .= "
            FROM `" . $tbl_course . "` AS c";
    
    if (!is_null($userId))
        $sql .= "
            
            LEFT JOIN `" . $tbl_rel_course_user . "` AS rcu
            ON c.code = rcu.code_cours AND rcu.user_id = " . (int) $userId;
    
    if (!is_null($categoryId))
        $sql .= "
            
            LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
            ON c.cours_id = rcc.courseId";
    

    $sql .= "
            
            WHERE visibility = 'visible' ";
    
    if (!is_null($categoryId))
        $sql .= "
            AND rcc.rootCourse != 1";
    
    
    // User logged can't see source courses
    if (!is_null($userId))
            $sql .= "
            AND (isSourceCourse = 0 OR rcu.isCourseManager = 1)";
    
    // User anonymous can't see session courses
    else
            $sql .= "
            AND sourceCourseId IS NULL";
            
    $sql .= "
            AND (
                  (c.status = 'enable'
                    OR (c.status = 'date'
                         AND (UNIX_TIMESTAMP(c.creationDate) < '". $curdate ."'
                                OR c.creationDate IS NULL OR UNIX_TIMESTAMP(c.creationDate) = 0)
                         AND ('". $curdate ."' < UNIX_TIMESTAMP(c.expirationDate)  OR c.expirationDate IS NULL)
                       )
                  )";
    
    if (!is_null($userId))
        $sql .= "
                  OR NOT (rcu.user_id IS NULL)";
        
    $sql .= "
                )";
    
    if (!is_null($categoryId))
        $sql .= "
            AND ( rcc.categoryId = " . (int) $categoryId . ")";
    
    if ( !get_conf('userCourseListGroupByCategories') )
    {
        $sql .= " GROUP BY c.code";
    }

    if ( get_conf('course_order_by') == 'official_code' )
    {
        $sql .= " ORDER BY UPPER(c.`administrativeNumber`), c.`intitule`";
    }
    else
    {
        $sql .= " ORDER BY c.`intitule`, UPPER(c.`administrativeNumber`)";
    }
    
    return claro_sql_query_fetch_all($sql);
}


/**
 * Return session courses (if any) for the specified course.
 *
 * @param int       identifier of the specified course
 * @return array    collection of session courses
 * @since 1.10
 */
function get_session_courses($id)
{
    // Declare needed tables
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_course                 = $tbl_mdb_names['course'];
    
    $sql = "SELECT c.cours_id               AS id,
                   c.titulaires             AS titular,
                   c.code                   AS sysCode,
                   c.sourceCourseId         AS sourceCourseId,
                   c.intitule               AS title,
                   c.administrativeNumber   AS officialCode,
                   c.language,
                   c.directory,
                   c.visibility,
                   c.access,
                   c.registration,
                   c.email,
                   c.status,
                   c.userLimit
            FROM `" . $tbl_course . "` AS c
            WHERE c.sourceCourseId = " . (int) $id;
    
    return claro_sql_query_fetch_all($sql);
}


/**
 * Return the source course (if any) for the specified course.
 *
 * @param int       identifier of the specified course
 * @return array    datas of the source course
 * @since 1.10
 */
function get_source_course($id)
{
    // Declare needed tables
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_course                 = $tbl_mdb_names['course'];
    
    $sql = "SELECT c1.cours_id               AS id,
                   c1.titulaires             AS titular,
                   c1.code                   AS sysCode,
                   c1.intitule               AS title,
                   c1.administrativeNumber   AS officialCode,
                   c1.language,
                   c1.directory,
                   c1.visibility,
                   c1.access,
                   c1.registration,
                   c1.email,
                   c1.status,
                   c1.userLimit
            FROM `" . $tbl_course . "` AS c1, `" . $tbl_course . "` AS c2
            WHERE c1.cours_id = c2.sourceCourseId
            AND c2.cours_id = " . (int) $id;
    
    return claro_sql_query_get_single_row($sql);
}


/**
 * This function return properties for groups in a given course context.
 *
 * @param string $courseId sysCode of the course.
 *
 * @return array ('registrationAllowed' ,
                  'self_registration',
                  'private',
                  'nbGroupPerUser',
                  'tools' => array ('CLFRM',
                                    'CLDOC',
                                    'CLWIKI',
                                    'CLCHT')
                                    )


 * The 4th first properties  are course properties dedicated to groups as default value.
 * The 'tool' array is like course.tool_list.
 */
function claro_get_main_group_properties($courseId)
{
    $tbl_cdb_names = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseId) );
    $tbl_course_properties   = $tbl_cdb_names['course_properties'];

    $sql = "SELECT name,
                   value
            FROM `" . $tbl_course_properties . "`
            WHERE category = 'GROUP'";

    $dbDataList = claro_sql_query_fetch_all($sql);

    if (is_array($dbDataList) )
    {
        foreach($dbDataList as $thisData)
        {
            $tempList[$thisData['name']] = (int) $thisData['value'];
        }

        $propertyList = array();

        $propertyList ['registrationAllowed'] =  isset( $tempList['self_registration'] ) && $tempList['self_registration'] == 1;
        $propertyList ['unregistrationAllowed'] =  isset($tempList['self_unregistration']) && $tempList['self_unregistration'] == 1;
        $propertyList ['tutorRegistrationAllowed'] =  isset( $tempList['tutor_registration'] ) && $tempList['tutor_registration'] == 1;
        $propertyList ['private'            ] =  !isset( $tempList['private'] ) || $tempList['private']  == 1;
        $propertyList ['nbGroupPerUser'     ] =  isset( $tempList['nbGroupPerUser'] ) ? $tempList['nbGroupPerUser'] : 1;

        $propertyList['tools'] = array();
        
        $groupToolList = get_activated_group_tool_label_list( $courseId );
        
        foreach ( $groupToolList as $thisGroupTool )
        {
            $thisGroupToolLabel = $thisGroupTool['label'];
            $propertyList['tools'][$thisGroupToolLabel] = isset( $tempList[$thisGroupToolLabel] ) ? ($tempList[$thisGroupToolLabel] == 1) : false;
        }
        
        return $propertyList;
    }
    else
    {
        return false;
    }
}

/**
 * Get the db name of a course.
 * @param  string $course_id (optionnal)  If not set, it use the current course
 *         will be taken.
 * @return string db_name
 * @author Christophe Gesche <moosh@claroline.net>
 * @since 1.7
 */
function claro_get_course_db_name($cid=NULL)
{
    $k = claro_get_course_data($cid);

    if (isset($k['dbName'])) return $k['dbName'];
    else                     return NULL;

}

/**
 * Get the glued db name of a course. Ready to be use in claro_get_course_table_name.
 * @param  string $course_id (optionnal)  If not set, it use the current course
 *         will be taken.
 * @return string db_name glued
 * @author Christophe Gesche <moosh@claroline.net>
 * @since 1.7
 */
function claro_get_course_db_name_glued($cid=NULL)
{
    $k = claro_get_course_data($cid);

    if (isset($k['dbNameGlu'])) return $k['dbNameGlu'];
    else                        return NULL;
}

/**
 * Get the path of a course.
 * @param  string $course_id (optionnal)  If not set, it use the current course
 *         will be taken.
 * @return string path
 * @author Christophe Gesche <moosh@claroline.net>
 * @since 1.7
 */
function claro_get_course_path($cid=NULL)
{
    $k = claro_get_course_data($cid);
    if (isset($k['path'])) return $k['path'];
    else                   return NULL;
}


/**
 * SECTION :  Get kernel
 * SUBSECTION datas for groups
 */

/**
 * Get unique keys of a course.
 *
 * @param  string $course_id (optionnal)  If not set, it use the current course
 *         will be taken.
 * @return array list of unique keys (sys, db & path) of a course
 * @author Christophe Gesche <moosh@claroline.net>
 * @since 1.7
 */
function claro_get_group_data($context, $force = false )
{
    if (is_array($context) && array_key_exists(CLARO_CONTEXT_COURSE,$context))
    {
        $cid = $context[CLARO_CONTEXT_COURSE];
    }

    if (is_array($context) && array_key_exists(CLARO_CONTEXT_GROUP,$context))
    {
        $gid = $context[CLARO_CONTEXT_GROUP];
    }
    $groupDataList = null;

    static $cachedGroupDataList = null;
/*
    if ( ! $force)
    {
        if ( $cachedGroupDataList && $groupId == $cachedGroupDataList['sysCode'] )
        {
            $groupDataList = $cachedGroupDataList;
        }
        elseif ( ( is_null($groupId) && $GLOBALS['_gid']) )
        {
            $groupDataList = $GLOBALS['_group'];
        }
    }
*/
    if ( ! $groupDataList )
    {
        $tbl_c_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($cid) );

        $sql = "SELECT g.id               AS id          ,
                       g.name             AS name        ,
                       g.description      AS description ,
                       g.tutor            AS tutorId     ,
                       f.forum_id         AS forumId     ,
                       g.secretDirectory  AS directory   ,
                       g.maxStudent       AS maxMember

                FROM `" . $tbl_c_names['group_team'] . "`      g
                LEFT JOIN `" . $tbl_c_names['bb_forums'] . "`   f

                   ON    g.id = f.group_id
                WHERE    `id` = '". (int) $gid."'";

        $groupDataList = claro_sql_query_get_single_row($sql);

        if ( ! $groupDataList ) return claro_failure::set_failure('group_not_found');

        $cachedGroupDataList = $groupDataList; // cache for the next time ...
    }

    return $groupDataList;
}

/**
 * Get the path of a group in a course.
 * @param  array $context
 * @return string path
 * @author Christophe Gesche <moosh@claroline.net>
 * @var $gData use to get groupdata
 * @since 1.8.1
 */
function claro_get_course_group_path($context)
{
    if (is_array($context) && array_key_exists(CLARO_CONTEXT_COURSE,$context))
    {
        $cid = $context[CLARO_CONTEXT_COURSE];
    }

    $coursePath = claro_get_course_path($cid);
    $gData = claro_get_group_data($context);
    if (isset($gData['directory'])) return $coursePath . '/group/' . $gData['directory'];
    else                   return NULL;
}

/**
 * SECTION :  Get kernel
 * SUBSECTION datas for tools
 */

/**
 * Get names  of tools in an array where key are Claro_label
 * @return array list of localised name of tools
 * @todo with plugin, this lis would be read in a dynamic datasource
 */
function claro_get_tool_name_list()
{
    return claro_get_module_name_list();
}

/**
 * Get a list of tag names of some tools
 * This is a bad named function because they return only tool type modules
 *
 * Returned tagname is the "Developpers english name" this tag would be passed to get_lang
 *
 * @param boolean $active true filter to keep only tools activated in platform
 * @return array( `label`=>`tagname`)
 */
function claro_get_module_name_list($active = true)
{
    static $toolNameList;

    $tbl_mdb_names   = claro_sql_get_main_tbl();
    $tbl_module      = $tbl_mdb_names['module'];

    if( ! isset( $toolNameList ) )
    {
        $toolNameList = array('CLANN' => 'Announcements'
        ,                     'CLFRM' => 'Forums'
        ,                     'CLCAL' => 'Agenda'
        ,                     'CLCHT' => 'Chat'
        ,                     'CLDOC' => 'Documents and Links'
        ,                     'CLDSC' => 'Course description'
        ,                     'CLGRP' => 'Groups'
        ,                     'CLLNP' => 'Learning path'
        ,                     'CLQWZ' => 'Exercises'
        ,                     'CLWRK' => 'Assignments'
        ,                     'CLUSR' => 'Users'
        ,                     'CLWIKI' => 'Wiki'
        );
    }

    //add in result the module of type 'tool'
    //see if we take only activated modules or all of them

    if ($active)
    {
        $activationSQL = " AND `activation`='activated'";
    }
    else
    {
        $activationSQL = "";
    }

    //find tool modules

    $sql = "SELECT `label`, `name` FROM `" . $tbl_module . "`
                            WHERE `type`='tool'
                              ".$activationSQL;

    $result = claro_sql_query_fetch_all($sql);

    //add them in result array

    foreach ($result as $tool)
    {

        if (!isset($toolNameList[$tool['label']]))
        {
            $toolNameList[$tool['label']] = $tool['name'];
        }
    }

    return $toolNameList;
}

/**
 * SECTION :  Get kernel
 * SUBSECTION datas for rel tool courses
 */

/**
 * Return the list of tool installed on the platform
 *
 * @param  boolean $force (optionnal) - reset the result cache, default is false
 *
 * @return array the main course list array ( $tid => 'label','name','url','icon','activation' )
 */
function claro_get_main_course_tool_list ( $force = false )
{
    static $courseToolList = null ;

    if ( is_null($courseToolList) || $force )
    {
        // Initialise course tool list
        $courseToolList = array();

        // Get name of the tables
        $tbl_mdb_names        = claro_sql_get_main_tbl();
        $tbl_tool_list        = $tbl_mdb_names['tool'];
        $tbl_module           = $tbl_mdb_names['module'];

        // Find module tools
        $sql = "SELECT t.id,
                       t.claro_label as label,
                       m.name,
                       m.activation,
                       t.icon,
                       t.access_manager,
                       t.script_url as url
                FROM `" . $tbl_module . "` as m,
                     `" . $tbl_tool_list . "` as t
               WHERE t.claro_label = m.label
                 AND m.type = 'tool'
               ORDER BY t.def_rank";

        $courseToolResult = claro_sql_query_fetch_all($sql);

        // Fill course tool list
        foreach ( $courseToolResult as $courseTool )
        {
            $toolId = $courseTool['id'];

            $courseToolList[$toolId]['label'] = $courseTool['label'];
            $courseToolList[$toolId]['name'] = $courseTool['name'];
            $courseToolList[$toolId]['url'] = get_module_url($courseTool['label']) . '/' . $courseTool['url'] ;

            if ( !empty($courseTool['icon']) )
            {
                $courseToolList[$toolId]['icon'] = get_module_url($courseTool['label']) . '/'. $courseTool['icon'];
            }
            else
            {
                $courseToolList[$toolId]['icon'] = $GLOBALS['imgRepositoryWeb'] .'/tool.png';
            }

            if ( $courseTool['activation'] == 'activated' )
            {
                $courseToolList[$toolId]['activation'] = true;
            }
            else
            {
                $courseToolList[$toolId]['activation'] = false;
            }

            if ( $courseTool['access_manager'] == 'PLATFORM_ADMIN' )
            {
                $courseToolList[$toolId]['activable'] = false;
            }
            else
            {
                $courseToolList[$toolId]['activable'] = true;
            }
        }
    }

    return $courseToolList ;
}

/**
 * Return the tool list for a course according a certain access level.
 *
 * @param string    $courseIdReq - the requested course id
 * @param int       $profileIdReq - the requested profile id
 * @param boolean   $force (optionnal)  - reset the result cache, default is false
 * @param boolean   $active (optionnal) - get the list of active tool only if set
 *                  to true (default behaviour)
 * @param mixed     $courseActive (optional) - set to true (default behaviour) to get
 *                  only activated course tools, set to false to get all course tools
 *
 * @return array    the courses list
 */

function claro_get_course_tool_list( $courseIdReq,
                                    $profileIdReq,
                                    $force = false,
                                    $active = true,
                                    $courseActive = true )
{
    static $courseToolList = null, $courseId = null, $profileId = null;

    if (   is_null($courseToolList)
    || $courseId    != $courseIdReq
    || $profileId   != $profileIdReq
    || $force )
    {
        $courseId   = $courseIdReq;
        $profileId  = $profileIdReq;

        $tbl_mdb_names        = claro_sql_get_main_tbl();
        $tbl_tool_list        = $tbl_mdb_names['tool'];
        $tbl_module           = $tbl_mdb_names['module'];
        $tbl_cdb_names        = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseIdReq) );
        $tbl_course_tool_list = $tbl_cdb_names['tool'];

        /*
        * Search all the tool corresponding to this access levels
        */

        // find module or claroline existing tools

        $sql = "SELECT DISTINCT ctl.id            AS id,
                      pct.id                      AS tool_id,
                      pct.claro_label             AS label,
                      ctl.script_name             AS external_name,
                      ctl.visibility              AS visibility,
                      IFNULL(pct.icon,'tool.png') AS icon,
                      ISNULL(ctl.tool_id)         AS external,
                      m.activation ,
                      m.name                      AS name,
                      ctl.activated               AS activated,
                      ctl.installed               AS installed,
                      IFNULL( ctl.script_url ,
                              pct.script_url )    AS url
               FROM `" . $tbl_course_tool_list . "` AS ctl,
                    `" . $tbl_module . "`           AS m,
                    `" . $tbl_tool_list . "`        AS pct

               WHERE pct.id = ctl.tool_id
                 AND pct.claro_label = m.label
                 ". ($active ? " AND m.activation = 'activated' " :"") . "
                 ". ($courseActive ? " AND ctl.activated = 'true' " :"") . "
               ORDER BY external, pct.def_rank, ctl.rank";

        $courseToolList = claro_sql_query_fetch_all($sql);

        // right profile management

        $size = count($courseToolList);

        for ( $i=0 ; $i<$size ; $i++ )
        {
            $toolId = $courseToolList[$i]['tool_id'];
            $visibility = (bool) $courseToolList[$i]['visibility'];

            // delete tool from course tool list if :
            // 1. tool is invisible and profile has no right to edit tool
            // 2. profile has no right to view tool
            if ( ( $visibility == false && ! claro_is_allowed_tool_edit($toolId,$profileId,$courseId) )
            || ! claro_is_allowed_tool_read($toolId,$profileId,$courseId) )
            {
                unset($courseToolList[$i]);
            }
        }

        // find external url added by teacher

        $sql = "SELECT DISTINCT ctl.id            AS id,
                      NULL                        AS tool_id,
                      NULL                        AS label,
                      ctl.script_name             AS external_name,
                      ctl.visibility              AS visibility,
                      'tool.png'                  AS icon,
                      ISNULL(ctl.tool_id)         AS external,
                      NULL                        AS name,
                      ctl.script_url              AS url

               FROM `" . $tbl_course_tool_list . "` AS ctl
               WHERE ISNULL(ctl.tool_id) ";

        if ( ! get_init('is_courseAdmin') )
        {
            $sql .= 'AND ctl.visibility = 1';
        }

        $result = claro_sql_query_fetch_all($sql);

        $courseToolList = array_merge($courseToolList,$result);
    }

    return $courseToolList;
}

/**
 * Return the tool list for a course according a certain access level
 *
 * @param  boolean $force (optionnal) - reset the result cache, default is false
 *
 * @return array the main course list array ( $id => 'name','url','icon','visibility' )
 */

function claro_get_course_external_link_list ( $courseIdReq = null, $force = false )
{
    static $courseExtLinkList = null, $courseId = null ;

    if ( is_null($courseIdReq) )
    {
        $courseIdReq = get_init('_cid');
    }

    if ( is_null($courseExtLinkList)
    || $courseIdReq != $courseId
    || $force )
    {
        // Initialise course tool list
        $courseId = $courseIdReq;
        $courseExtLinkList = array();

        // Get name of the tables
        $tbl_cdb_names        = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseIdReq) );
        $tbl_course_tool_list = $tbl_cdb_names['tool'];

        // Find external link
        $sql = "SELECT id,
                       script_name,
                       script_url,
                       visibility
               FROM `" . $tbl_course_tool_list . "`
               WHERE ISNULL(tool_id) ";

        if ( ! ( get_init('is_courseAdmin') || get_init('is_platformAdmin') ) )
        {
            $sql .= 'AND visibility = 1';
        }

        $courseExtLinkResult = claro_sql_query_fetch_all($sql);

        foreach ( $courseExtLinkResult as $courseExtLink )
        {
            $id = $courseExtLink['id'];
            $courseExtLinkList[$id]['name'] = $courseExtLink['script_name'];
            $courseExtLinkList[$id]['url'] = $courseExtLink['script_url'];
            $courseExtLinkList[$id]['icon'] = $GLOBALS['imgRepositoryWeb'] .'/tool.png';
            $courseExtLinkList[$id]['visibility'] = (bool) $courseExtLink['visibility'];
        }
    }

    return $courseExtLinkList;
}

/**
 * Get the name of a tool
 * @param string identifier is tool_id or tool_label
 * @return string tool name
 */

function claro_get_tool_name ( $identifier )
{
    return claro_get_module_name($identifier);
}

/**
 * Return the name of a given module
 *
 * @param mixed $identifier
 *        interger for a module id
 *        string for a claro label
 * @return string translated tool name;
 */
function claro_get_module_name ( $identifier )
{
    static $cachedModuleIdList = null ;
    if ( is_numeric($identifier) )
    {
        // identifier is a tool_id
        if ( ! $cachedModuleIdList )
        {
            $tbl = claro_sql_get_main_tbl();

            $sql = "SELECT id      AS toolId,
                       claro_label AS claroLabel
                    FROM `" . $tbl['tool'] . "`";

            $moduleList = claro_sql_query_fetch_all($sql);

            foreach ($moduleList as $thisModule)
            {
                $toolId = $thisModule['toolId'];
                $claroLabel =  $thisModule['claroLabel'];
                $cachedModuleIdList[$toolId] = $claroLabel;
            }
        }
        // get tool label of the tool
        $toolLabel = isset( $cachedModuleIdList[$identifier] )
            ? $cachedModuleIdList[$identifier]
            : false
            ;
    }
    else
    {
        // identifier is a tool label
        $toolLabel = $identifier;
    }

    $toolNameList = claro_get_tool_name_list();

    if ( isset($toolNameList[$toolLabel]) )
    {
        return get_lang($toolNameList[$toolLabel]);
    }
/*
    if ( isset($toolNameList[$toolLabel]) )
    {

        $moduleData = get_module_data($toolLabel);
        if (is_array($moduleData))
        {
            $moduleName = $moduleData['moduleName'];
            return  get_lang($moduleName);
        }
    }
*/
    return get_lang('No tool name') ;

}

/**
 * SECTION : CLAROLINE FAILURE MANGEMENT
 */


$claro_failureList = array();

/**
 * collects and manage failures occuring during script execution
 * The main purpose is allowing to manage the display messages externaly
 * from functions or objects. This strengthens encapsulation principle
 *
 * @example :
 *
 *  function my_function()
 *  {
 *      if ($succeeds) return true;
 *      else           return claro_failure::set_failure('my_failure_type');
 *  }
 *
 *  if ( my_function() )
 *  {
 *      SOME CODE ...
 *  }
 *  else
 *  {
 *      $failure_type = claro_failure::get_last_failure()
 *  }
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @package failure
 * @deprecated since 1.9, use Exceptions instead
 */
class claro_failure
{
    /*
    * IMPLEMENTATION NOTE : For now the $claro_failureList list is set to the
    * global scope, as PHP 4 is unable to manage static variable in class. But
    * this feature is awaited in PHP 5. The class is already written to
    * minimize the changes when static class variable will be possible. And the
    * API won't change.
    */

    // var $claro_failureList = array();

    /**
     * Pile the last failure in the failure list
     *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
     * @param  string $failureType the type of failure
     * @global array  claro_failureList
     * @return boolean false to stay consistent with the main script
     */

    public static function set_failure($failureType)
    {
        global $claro_failureList;

        $claro_failureList[] = $failureType;

        pushClaroMessage('set failure : ' . var_export($failureType,1),'set_failure');

        return false;
    }


    /**
     * get the last failure stored
     *
     * @author Hugues Peeters <hugues.peeters@claroline.net>
     * @return string the last failure stored
     */

    public static function get_last_failure()
    {
        global $claro_failureList;

        if( isset( $claro_failureList[ count($claro_failureList) - 1 ] ) )
        return $claro_failureList[ count($claro_failureList) - 1 ];
        else
        return '';
    }
}


/**
 * SECTION :  "view AS"
 */


/**
 * Set if  the  access level switcher is aivailable
 *
 * @global boolean claro_toolViewOptionEnabled
 * @return true
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 */

function claro_enable_tool_view_option()
{
    global $claro_toolViewOptionEnabled;
    $claro_toolViewOptionEnabled = true;
    return true;
}


/**
 * Set if  the  access level switcher is aivailable
 *
 * @param  $viewMode 'STUDENT' or 'COURSE_ADMIN'
 * @return true if set succeed.
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 */

function claro_set_tool_view_mode($viewMode)
{
    $viewMode = strtoupper($viewMode); // to be sure ...

    if ( in_array($viewMode, array('STUDENT', 'COURSE_ADMIN') ) )
    {
        $_SESSION['claro_toolViewMode'] = $viewMode;
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * Display options to switch between student view and course manager view
 * This function is mainly used by the claro_init_banner.inc.php file
 * The display mode command will only be displayed if
 * claro_set_tool_view_mode(true) has been previously called.
 * This will affect the return value of claro_is_allowed_to_edit() function.
 * It will ten return false as the user is a simple student.
 *
 * @author Roan Embrechts
 * @author Hugues Peeters
 * @param string - $viewModeRequested.
 *                 For now it can be 'STUDENT' or 'COURSE_ADMIN'
 * @see claro_is_allowed_to_edit()
 * @see claro_is_display_mode_available()
 * @see claro_set_display_mode_available()
 * @see claro_get_tool_view_mode()
 * @see claro_set_tool_view_mode()
 * @return true;
 */


function claro_disp_tool_view_option($viewModeRequested = false)
{
    pushClaroMessage( (function_exists('claro_html_debug_backtrace')
             ? claro_html_debug_backtrace()
             : 'claro_html_debug_backtrace() not defined'
             )
             .'claro_disp_tool_view_option is deprecated , use claro_html_tool_view_option','error');

    return claro_html_tool_view_option($viewModeRequested);
}

function claro_html_tool_view_option($viewModeRequested = false)
{
    if ( ! claro_is_course_manager() || ! claro_is_display_mode_available() ) return false;

    if ($viewModeRequested) claro_set_tool_view_mode($viewModeRequested);

    $currentViewMode = claro_get_tool_view_mode();

    /*------------------------------------------------------------------------
    PREPARE URL
    ------------------------------------------------------------------------*/

    /*
    * check if the REQUEST_URI contains already URL parameters
    * (thus a questionmark)
    */

    if ( strstr($_SERVER['REQUEST_URI' ], '?') )
    {
        $url = $_SERVER['REQUEST_URI' ];
    }
    else
    {
        $url = $_SERVER['PHP_SELF'] . '?';
    }

    /*
     * convert & to &amp;
     */

    $url = str_replace( '&amp;', '&', $url );
    $url = claro_htmlspecialchars( strip_tags( $url ) );

    /*
    * remove previous view mode request from the url
    */

    $url = str_replace('&amp;viewMode=STUDENT'     , '', $url);
    $url = str_replace('&amp;viewMode=COURSE_ADMIN', '', $url);
    $url = str_replace('?viewMode=STUDENT'     , '?', $url);
    $url = str_replace('?viewMode=COURSE_ADMIN', '?', $url);
    $url = str_replace('?&amp;', '?', $url );

    /*------------------------------------------------------------------------
    INIT BUTTONS
    -------------------------------------------------------------------------*/
    
    if ( substr( $url, -1, 1) === '?' )
    {
        $sep = '';
    }
    else
    {
        $sep = '&amp;';
    }


    switch ($currentViewMode)
    {
        case 'COURSE_ADMIN' :

            $studentButton = '<a href="' . claro_htmlspecialchars( Url::Contextualize($url . $sep . 'viewMode=STUDENT' ) ) . '">'
            .                get_lang('Student')
            .                '</a>'
            ;
            $courseAdminButton = '<b class="userName">' . get_lang('Course manager') . '</b>';

            break;

        case 'STUDENT' :

            $studentButton     = '<b class="userName">'.get_lang('Student').'</b>';
            $courseAdminButton = '<a href="'.claro_htmlspecialchars( Url::Contextualize($url . $sep . 'viewMode=COURSE_ADMIN' ) ) . '">'
            . get_lang('Course manager')
            . '</a>';
            break;
    }

    /*------------------------------------------------------------------------
    DISPLAY COMMANDS MENU
    ------------------------------------------------------------------------*/

    return get_lang('View mode') . ' : '
    .    $studentButton
    .    ' | '
    .    $courseAdminButton
    ;
}



/**
 * return the current mode in tool able to handle different view mode
 *
 * @return string 'COURSE_ADMIN' or 'STUDENT'
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 */

function claro_get_tool_view_mode()
{
    // check first if a viewMode has been requested
    // if one was requested change the current viewMode to the mode asked
    // if there was no change requested and there is nothing in session
    // concerning view mode set the default viewMode
    // if there was something in session and nothing
    // in request keep the session value ( == nothing to do)
    if( isset($_REQUEST['viewMode']) )
    {
        claro_set_tool_view_mode($_REQUEST['viewMode']);
    }
    elseif( ! isset($_SESSION['claro_toolViewMode']) )
    {
        claro_set_tool_view_mode('COURSE_ADMIN'); // default
    }

    return $_SESSION['claro_toolViewMode'];
}


/**
 * Function that removes the need to directly use is_courseAdmin global in
 * tool scripts. It returns true or false depending on the user's rights in
 * this particular course.
 *
 * @version 1.1, February 2004
 * @return boolean true: the user has the rights to edit, false: he does not
 * @author Roan Embrechts
 * @author Patrick Cool
 */

function claro_is_allowed_to_edit()
{
    if ( claro_is_course_manager() )
    {
        $isAllowedToEdit = true;
    }
    else
    {
        if ( claro_is_in_a_tool() )
        {
            $isAllowedToEdit = claro_is_allowed_tool_edit();
        }
        else
        {
            $isAllowedToEdit = false;
        }
    }

    if ( claro_is_display_mode_available() )
    {
        return $isAllowedToEdit && (claro_get_tool_view_mode() != 'STUDENT');
    }
    else
    {
        return $isAllowedToEdit ;
    }
}

/**
 *
 *
 * @return boolean
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 */

function claro_is_display_mode_available()
{
    global $is_display_mode_available;
    return $is_display_mode_available;
}

/**
 *
 *
 * @param boolean $mode state to set in mode
 * @return boolean mode
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 */


function claro_set_display_mode_available($mode)
{
    global $is_display_mode_available;
    $is_display_mode_available = $mode;
}


/**
 * Compose currentdate with server time shift
 *
 * @param string $format date() format
 * @param integer $timestamp timestamp or default  -1 for "now()"
 * @return date()
 *
 * @author Christophe Gesche <moosh@claroline.net>
 *
 */
function claro_date($format, $timestamp = -1)
{
    if ($timestamp == -1) return date($format, claro_time());
    else                  return date($format, $timestamp);

}

/**
 * Compose currentdate with server time shift
 *
 * @return timestamp shifted by mainTimeShift config value
 *
 * @author Christophe Gesche <moosh@claroline.net>
 *
 */
function claro_time()
{
    $mainTimeShift = (int) get_conf('mainTimeShift',0);
    return time()+(3600 * $mainTimeShift);
}

/**
 * Equivalent to mktime but taking the mainTimeShift into account
 *
 *  Usage :
 *      claro_mktime ( [int hour [, int minute [, int second [, int month [
 *          , int day [, int year [, int is_dst]]]]]]] )
 *
 * @see mktime()
 * @return timestamp corresponding to the given arguments shifted by
 *  mainTimeShift config value
 * @author Frederic Minne <zefredz@claroline.net>
 */
function claro_mktime()
{
    if ( 0 < func_num_args() )
    {
        $args = func_get_args();

        return call_user_func_array( 'mktime', $args );
    }
    else
    {
        // shift
        $mainTimeShift = (int) get_conf('mainTimeShift',0);
        return time()+(3600 * $mainTimeShift);
    }
}
//////////////////////////////////////////////////////////////////////////////
//                              INPUT HANDLING
//
//////////////////////////////////////////////////////////////////////////////

/**
 * checks if the javascript is enabled on the client browser
 * Actually a cookies is set on the header by a javascript code.
 * If this cookie isn't set, it means javascript isn't enabled.
 *
 * @return boolean enabling state of javascript
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 */

function claro_is_javascript_enabled()
{
    global $_COOKIE;

    if ( isset( $_COOKIE['javascriptEnabled'] ) && $_COOKIE['javascriptEnabled'] == true)
    {
        return true;
    }
    else
    {
        return false;
    }
}



/**
 * get the list  of aivailable languages on the platform
 *
 * @author Christophe Gesche <moosh@claroline.net>
 *
 * @return array( langCode => langLabel) with aivailable languages
 */
function claro_get_language_list()
{
    $langNameOfLang = get_locale('langNameOfLang');
    $dirname = get_path('incRepositorySys') . '/../lang/';

    if($dirname[strlen($dirname)-1]!='/')
    $dirname .= '/';

    if (!file_exists($dirname)) trigger_error('lang repository not found',E_USER_WARNING);

    $handle = opendir($dirname);

    while ( ($entries = readdir($handle) ) )
    {
        if ($entries == '.' || $entries == '..' || $entries == 'CVS' || $entries == '.svn')
        {
            continue;
        }
        
        if (is_dir($dirname . $entries))
        {
            if (isset($langNameOfLang[$entries]))
            {
                $language_list[$entries]['langNameCurrentLang'] = $langNameOfLang[$entries];
            }
            
            $language_list[$entries]['langNameLocaleLang']  = $entries;
        }
    }
    closedir($handle);
    return $language_list;
}

/**
 * Return the config ropisitory for a given context
 *
 * All platform config are stored in platform/conf/
 * But a course or a group can overide some config values
 *
 * This function return the repository ignoring if it's  existing or empty
 *
 * @param array $context
 * @return string
 */
function claro_get_conf_repository($context=array())
{
    if (!isset($context) || !is_array($context) || empty($context) || is_null($context))
        return get_path('rootSys') . 'platform/conf/';

    if (array_key_exists(CLARO_CONTEXT_COURSE, $context))
    {
        if (array_key_exists(CLARO_CONTEXT_GROUP, $context))
        {
            return claro_get_course_group_path($context) . '/conf/';
        }
        return get_path('coursesRepositorySys') . claro_get_course_path($context[CLARO_CONTEXT_COURSE]) . '/conf/';

    }


    pushClaroMessage('Unknown context passed to claro_get_conf_repository : ' . var_export($context,1), 'warning');
    return null;

}

/**
 * Return the value of a Claroline configuration parameter
 * @param string $param config parameter
 * @param mixed $default (optionnal) - set a defaut to return value
 *                                     if no paramater with such a name is found.
 * @return string param value
 * @todo http://www.claroline.net/forum/viewtopic.php?t=4579
*/

function get_conf($param, $default = null)
{
    /* if ( ! isset($GLOBALS['_conf'][$param]) && ! isset($GLOBALS[$param]) && !defined($param))
    {
        static $paramList = array();

        if (!in_array($param,$paramList))
        {
            $paramList[]=$param;
            pushClaroMessage( __FUNCTION__ .  ' : ' . claro_htmlspecialchars($param) . ' use but not set. use default :' . var_export($default,1),'debug');
        }
    } */

    if     ( isset($GLOBALS['_conf'][$param]) )  return $GLOBALS['_conf'][$param];
    elseif ( isset($GLOBALS[$param]) )           return $GLOBALS[$param];
    elseif ( defined($param)         )           return constant($param);
    else                                         return $default;
}

/**
 * SECTION : security
 */

/**
 * Terminate the script and display message
 *
 * @param string message
 */

function claro_die($message)
{
    FromKernel::uses( 'display/dialogBox.lib' );
    $dialogBox = new DialogBox;
    $dialogBox->error( $message );
    
    Claroline::getInstance()->display->setContent( $dialogBox->render() );
    
    if ( claro_debug_mode () )
    {
        
        pushClaroMessage(  var_export(  debug_backtrace (), true ), 'debug' );
    }
    
    echo Claroline::getInstance()->display->render();

    die(); // necessary to prevent any continuation of the application
}


/**
 * HTTP response splitting security flaw filter
 * @author Frederic Minne <zefredz@gmail.com>
 * @return string clean string to filter http_response_splitting attack
 * @see http://www.saintcorporation.com/cgi-bin/demo_tut.pl?tutorial_name=HTTP_Response_Splitting.html
 */

function http_response_splitting_workaround( $str )
{
    $dangerousCharactersPattern = '~(\r\n|\r|\n|%0a|%0d|%0D|%0A)~';
    return preg_replace( $dangerousCharactersPattern, '', $str );
}

/**
 * Strip the slashes coming from browser request
 *
 * If the php.ini setting MAGIC_QUOTE_GPC is set to ON, all the variables
 * content comming frome the browser are automatically quoted by adding
 * slashes (default setting before PHP 4.3). claro_unquote_gpc() removes
 * these slashes. It needs to be called just once at the biginning
 * of the script.
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @return void
 */

function claro_unquote_gpc()
{
    if ( ! defined('CL_GPC_UNQUOTED') )
    {
        if ( get_magic_quotes_gpc() )
        {
            /*
            * The new version is written in a safer approach inspired by Ilia
            * Alshanetsky. The previous approach which was using recursive
            * function permits to smash the stack and crash PHP. For example if
            * the user supplies a very deep multidimensional array, such as
            * foo[][][][] ..., the recursion can reach the point of exhausting
            * the stack. Generating such an attack is quite trivial, via the
            * use of :
            *
            *    str_repeat() function example $str = str_repeat("[]", 100000);
            *    file_get_contents("http://sitre.com.scriptphp?foo={$str}");
            */

            $inputList = array(&$_REQUEST, &$_GET, &$_POST, &$_COOKIE);

            while ( list($topKey, $array) = each($inputList) )
            {
                foreach( $array as $childKey => $value)
                {
                    if ( ! is_array($value) )
                    {
                        $inputList[$topKey][$childKey] = stripslashes($value);
                    }
                    else
                    {
                        $inputList[] =& $inputList[$topKey][$childKey];
                    }
                }
            }

            define('CL_GPC_UNQUOTED', true);

        } // end if get_magic_quotes_gpc
    }
}

/**
 * @param $contextKeys array or null
 *
 * array can contain course, group, user and/or toolInstance
 *
 * return array of context requested containing current id fors these context.
 */
function claro_get_current_context($contextKeys = null)
{
   return Claro_Context::getCurrentContext();
}


/**
 * Developper function to push a message in stack of devs messages
 * in debug mod this stack is output in footer
 * @author Christophe Gesche <moosh@claroline.net>
 */
if (!isset($claroErrorList)) $claroErrorList= array();
function pushClaroMessage($message,$errorClass='error')
{
    global $claroErrorList;
    $claroErrorList[$errorClass][]= $message;
    return true;
}

/**
 * get stack of devel message
 */
function getClaroMessageList($errorClass=null)
{
    if (isset($GLOBALS['claroErrorList']))
    {
        $claroErrorList = $GLOBALS['claroErrorList'];
        if (is_null($errorClass)) $returnedClaroErrorList = $claroErrorList;
        else
        {
            if (array_key_exists($errorClass,$claroErrorList))
            {
                $returnedClaroErrorList[$errorClass] = $claroErrorList[$errorClass];
            }
            else $returnedClaroErrorList[]=array();
        }
    }
    else $returnedClaroErrorList[]=array();

    return $returnedClaroErrorList;
}

/**
 * Return the list of tools for a user
 *
 *  in 1.8 only  CLCAL are both  course tool and user tool.
 *  ie : profile is'nt view as module,
 *  and other course tool can't work outside a course for a user.
 *
 * @param boolean $activeOnly default true
 * @return array of tools
 */
function claro_get_user_tool_list($activeOnly=true)
{
    $toolDataList= array();
    $toolData = get_module_data('CLCAL');

    if (false !== $toolData && (!$activeOnly || $toolData['activation'] != 'desactivated'))
    {
        $toolData['entry'] = 'myagenda.php';
        $toolDataList[]=$toolData;
    }
    return $toolDataList;
}

/**
 * Safe redirect
 * Works around IIS Bug
 */

function claro_redirect($location)
{
    // IIS prefers Refresh over Location
    /*if ( $GLOBALS['is_IIS'] )
    {
        header("Refresh: 0;url=$location");
    }*/
    
    // Issue with non utf-8 url under Apache 2 on Windows
    if ( $GLOBALS['is_Apache2'] )
    {
        if ( strtolower( substr( PHP_OS, 0, 3) ) == 'win' )
        {
            $location = utf8_encode($location);
        }
    }
    
    header("Location: " . $location);
}

/**
 * Generate some informations in HTML format over the execution context.\n
 * Informations are placed into hidden inputs.
 */
function claro_form_relay_context($context=null)
{
    $html = '';
    if ( is_null($context) )
    {
        $context = Claro_Context::getCurrentUrlContext();
    }
    
    if ( array_key_exists( 'cid', $context )
        && ! array_key_exists( 'cidReq', $context ) )
    {
        $context['cidReq'] = $context['cid'];
        unset( $context['cid'] );
    }

    if ( array_key_exists( 'gid', $context )
        && ! array_key_exists( 'gidReq', $context ) )
    {
        $context['gidReq'] = $context['gid'];
        unset( $context['gid'] );
    }
    
    foreach ( $context as $key => $value )
    {
        $html .= '<input type="hidden" name="'.claro_htmlspecialchars(strip_tags($key)).'" value="'.claro_htmlspecialchars(strip_tags($value)).'" />';
    }

    return $html;
}

/**
 * Get the string needed to relay the current context in urls
 * @param string $prepend string to prepend to the relayed context
 * @param array $context
 * @return string
 * @deprecated since 1.9 use Url::Contextualize instead
 */
function claro_url_relay_context($prepend='',$context=null)
{
    if(is_null($context))
    {
        $context = Claro_Context::getCurrentUrlContext();
    }
    
    if ( array_key_exists( 'cid', $context )
        && ! array_key_exists( 'cidReq', $context ) )
    {
        $context['cidReq'] = $context['cid'];
        unset( $context['cid'] );
    }

    if ( array_key_exists( 'gid', $context )
        && ! array_key_exists( 'gidReq', $context ) )
    {
        $context['gidReq'] = $context['gid'];
        unset( $context['gid'] );
    }

    if (count($context)>0) return $prepend . http_build_query($context);
    else                    return '';
}

/**
 * Get (and not display !) the debug banner html code
 * @return string
 */
function claro_disp_debug_banner()
{
    require_once dirname( __FILE__ ) . '/backlog.class.php';

    $html = '';

    $claroMsgList = getClaroMessageList();

    if ( is_array($claroMsgList) && count($claroMsgList) > 0)
    {
        $claroMsgCount = 0;

        $html .= '<div class="debugBar">' . "\n"
              .                         get_lang('Debug') .  "\n" ;

        $html .= get_lang('Messages') . ' : ';

        foreach ($claroMsgList as $bloc=>$msgList )
        {
            $html .= Backlog_Reporter::report( $bloc . ' : ' . count($msgList),
                                               claro_html_msg_list($msgList),
                                               '+',
                                               true );
            $claroMsgCount += count($msgList);
            $html .= ' | ';
        }
        $html .= get_lang('%nb message(s)',array('%nb'=> $claroMsgCount));

        $html .= '<div class="spacer"></div>' . "\n\n"
        .        '</div>' . "\n"
        .        '<!-- end of debugBanner -->' . "\n\n"
        ;
    }

    return $html;
}

/**
 * Protect $_SERVER[PHP_SELF] against HTTP response splitting and XSS
 * @return string
 */
function php_self()
{
    // remove html tags
    $url = strip_tags($_SERVER['PHP_SELF']);
    // protect against XSS
    $url = preg_replace( '~(\r\n|\r|\n|%0a|%0d|%0D|%0A)~', '', $url );
    // entify remaining special chars
    $url = claro_htmlspecialchars( strip_tags( $url ) );

    return $url;
}

/**
 * Get the URI of the current page : PHP_SELF + QUERY_STRING, protected against
 * HTTP Response Splitting and XSS
 * @param   boolean $html if set to true (default) the returned URI is passed
 *              through claro_htmlspecialchars before being returned
 * @return  string
 */
function page_uri( $html = true )
{
    $uri = Url::Contextualize( php_self() . "?" . strip_tags($_SERVER['QUERY_STRING']) );
    
    return $html ? claro_htmlspecialchars( $uri ) : $uri;
}

/**
 * @return bool, true if the platform is in debug mode, false else
 */
function claro_debug_mode()
{
    return ( defined ( 'CLARO_DEBUG_MODE' ) && CLARO_DEBUG_MODE )
        || ( get_conf('triggerDebugMode', false) && isset($_SESSION['claro_debug_mode']) && $_SESSION['claro_debug_mode'] );
}

/**
 * Is the given tool activated in the given course
 * @param string $courseId course code
 * @param string $toolId tool id in the course, not the main tool id !
 * @return boolean
 */
function claro_is_course_tool_activated( $courseId, $toolId )
{
    static $activatedCourseToolList = false;

    if ( ! $activatedCourseToolList )
    {
        $activatedCourseToolList = array();

    $tbl_cdb_names        = claro_sql_get_course_tbl( claro_get_course_db_name_glued($courseId) );
    $tbl_course_tool_list = $tbl_cdb_names['tool'];

    /*
    * Search all the tool corresponding to this access levels
    */

    // find module or claroline existing tools

        $sql = "SELECT ctl.id, ctl.activated\n"
            ."FROM `" . $tbl_course_tool_list . "` AS ctl"
        ;
    
        $toolList = claro_sql_query_fetch_all_rows($sql);
    
        foreach ( $toolList as $tool )
        {
            $activatedCourseToolList[$tool['id']] = $tool['activated'];
        }
    }

    return $activatedCourseToolList[$toolId] == 'true';
}

/**
 * Get the main tool_id for a given course tool from its tid in the course
 * @param   int $tid id of the tool instance in the course
 * @param   string $courseId id (sysCode) of the course (optional, current course used if missing)
 * @param   int $profileId profile of the user to get the tool list from (optional, current user used if missing)
 * @return  int tool_id, main tool id
 */
function claro_get_tool_id_from_course_tid( $tid, $courseId = null, $profileId = null )
{
    $courseId = empty( $courseId )
        ? claro_get_current_course_id()
        : $courseId
        ;
    
    $profileId = empty( $profileId )
        ? claro_get_current_user_profile_id_in_course( $courseId )
        : $profileId
        ;
    
    $courseToolList = claro_get_course_tool_list( $courseId, $profileId );
    
    foreach ( $courseToolList as $courseTool )
    {
        if ( $courseTool['id'] == $tid )
        {
            return $courseTool['tool_id'];
        }
    }
    
    return false;
}

/**
 * Get the course tid for a given course tool from its main tool id
 * @param   int $tool_id id of the tool instance in the platform
 * @param   string $courseId id (sysCode) of the course (optional, current course used if missing)
 * @param   int $profileId profile of the user to get the tool list from (optional, current user used if missing)
 * @return  int tid, course tool id
 */
function claro_get_course_tid_from_tool_id( $tool_id, $courseId = null, $profileId = null )
{
    $courseId = empty( $courseId )
        ? claro_get_current_course_id()
        : $courseId
        ;
    
    $profileId = empty( $profileId )
        ? claro_get_current_user_profile_id_in_course( $courseId )
        : $profileId
        ;
    
    $courseToolList = claro_get_course_tool_list( $courseId, $profileId );
    
    foreach ( $courseToolList as $courseTool )
    {
        if ( $courseTool['tool_id'] == $tool_id )
        {
            return $courseTool['id'];
        }
    }
    
    return false;
}

/**
 * Load configuration file given its name
 * @param string $name
 */
function load_kernel_config( $name )
{
    $name = secure_file_path( $name );
    
    if ( file_exists( claro_get_conf_repository() . $name . '.conf.php' ) )
    {
        include claro_get_conf_repository() . $name . '.conf.php';
    }
}

/**
 * Check if a course is required. Set property using $courseRequired = true 
 *  (or false) before calling claro_init_global.inc.php By default, this property 
 *  is considered to be set to false for backward compatibility.
 * @since Claroline 1.11.0-rev14026
 * @return boolean 
 */
function claro_is_course_required()
{
    if ( isset($GLOBALS['courseRequired']) && $GLOBALS['courseRequired'] == true )
    {
        return true;
    }

    return false;
}

/**
 * Check if a group is required. Set property using $groupRequired = true 
 *  (or false) before calling claro_init_global.inc.php By default, this property 
 *  is considered to be set to false for backward compatibility
 * @since Claroline 1.11.0-rev14026
 * @return boolean 
 */
function claro_is_group_required()
{
    if ( isset($GLOBALS['groupRequired']) && $GLOBALS['groupRequired'] == true )
    {
        return true;
    }

    return false;
}

/**
 * Secure a backlink url by replacing it with a platform url, when the given 
 * url is not from the platform
 * @param string $url
 * @return string
 */
function secure_backlink_url( $url )
{
    // cleanup url of potential html injection
    $url = strip_tags($url);
    
    // if url does not start with urlAppend or rootWeb, we need to "secure" it
    if ( !preg_match( "!^".get_path('url')."!", $url ) 
        && !preg_match (  "!^".get_path('rootWeb')."!", $url )
        && !preg_match ( "!^".  str_replace ( 'http://', 'https://', get_path('rootWeb') )."!", $url )
        && !preg_match ( "!^".  str_replace ( 'http://', '', get_path('rootWeb') )."!", $url )
    )
    {
        if ( stristr ( $_SERVER['HTTP_HOST'], ':' ) )
        {
            $http_hostArr = explode(":", $_SERVER['HTTP_HOST']);
            $http_host = $http_hostArr[0];
        }
        else
        {
            $http_host = $_SERVER['HTTP_HOST'];
        }
        
        // if url starts with HTTP_HOST -> OK
        if( stristr( $url, $http_host ) )
        {
            return $url;
        }
        // else replace with context root url
        else
        {
            if ( isset( $GLOBALS['tlabelReq'] ) )
            {
                return Url::Contextualize(get_module_entry_url($GLOBALS['tlabelReq']));
            }
            elseif ( claro_is_in_a_course () )
            {
                return get_path('clarolineRepositoryWeb').'course/index.php?cid='.  claro_get_current_course_id ();
            }
            else
            {
                return get_path('url');
            }
        }
    }
    // else url is OK -> return it
    else
    {
        return $url;
    }
}

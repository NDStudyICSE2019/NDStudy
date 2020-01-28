<?php // $Id: admin.lib.inc.php 14191 2012-06-29 11:29:11Z jrm_ $

/**
 * CLAROLINE
 *
 *     THIS LIBRARY script propose some basic function to administrate the campus :
 *
 *     register a user,
 *     delete a user of the plateform,
 *     unregister a user form a specific course,
 *     remove a user fro ma group,
 *     delete a course of the plateform,
 *     back up a hole course,
 *     change status of a user : admin, prof or student,
 *     Add users with CSV files
 *     ...see details of pre/post for each function's proper use.
 *
 * @version 1.9 $Revision: 14191 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/CLCRS/
 *
 * @package COURSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Christophe Gesche <moosh@claroline.net>
 *
 */

include_once( dirname(__FILE__) . '/fileManage.lib.php');
include_once( dirname(__FILE__) . '/right/courseProfileToolAction.class.php');

/**
 * delete a course of the plateform
 *
 * TODO detect failure with claro_failure
 *
 * @param string $cid
 *
 * @return boolean TRUE        if suceed
 *         boolean FALSE       otherwise.
 */
function delete_course($code, $sourceCourseId)
{
    global $eventNotifier;

    // Declare needed tables
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_course                 = $tbl_mdb_names['course'];
    $tbl_rel_course_user        = $tbl_mdb_names['rel_course_user'];
    $tbl_course_class           = $tbl_mdb_names['rel_course_class'];
    $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
    $tbl_rel_course_portlet     = $tbl_mdb_names['rel_course_portlet'];

    $this_course = claro_get_course_data($code);
    
    if ( ! $this_course )
    {
        // This is bad !
        throw new Exception("Course not found");
    }
    
    $currentCourseId = trim( $this_course['sysCode'] );
    
    if ( empty( $currentCourseId ) )
    {
        // This is bad !
        throw new Exception("Missing course id");
    }

    // Delete user registrations into this course
    $sql = 'DELETE FROM `' . $tbl_rel_course_user . '`
            WHERE code_cours="' . $currentCourseId . '"';

    claro_sql_query($sql);

    // Remove any recording in rel_cours_class
    $sql = "DELETE FROM `" . $tbl_course_class . "`
            WHERE courseId ='" . claro_sql_escape($currentCourseId) . "'";

    claro_sql_query($sql);
    
    // Remove links between this course and categories
    $sql = "DELETE FROM `" . $tbl_rel_course_category . "`
            WHERE courseId ='" . $this_course['id'] . "'";

    claro_sql_query($sql);
    
    // Remove links between this course and portlets
    $sql = "DELETE FROM `" . $tbl_rel_course_portlet . "`
            WHERE courseId ='" . $this_course['id'] . "'";

    claro_sql_query($sql);

    // Delete the course inside the platform course registery
    $sql = 'DELETE FROM `' . $tbl_course . '`
            WHERE code= "' . claro_sql_escape($currentCourseId) . '"';

    claro_sql_query($sql);
    
    // Is it a session course ?
    if (!is_null($sourceCourseId))
    {
        // Does the source course still have session courses ?
        $sql = "SELECT COUNT(cours_id) AS nbSessionCourses
                FROM `" . $tbl_course . "`
                WHERE sourceCourseId = " . (int) $sourceCourseId;
        
        $result = claro_sql_query_get_single_row($sql);
        
        if ( $result['nbSessionCourses'] == 0 )
        {
            $sql = "UPDATE `" . $tbl_course . "`
                    SET isSourceCourse = 0
                    WHERE cours_id = " . (int) $sourceCourseId;
            
            claro_sql_query($sql);
        }
    }

    // Delete course right
    RightCourseProfileToolRight::resetAllRightProfile($currentCourseId);

    // Delete course module tables
    // FIXME handle errors
    list( $success, $log ) = delete_all_modules_from_course( $currentCourseId );

    // Notify the course deletion event
    $args['cid'] = $this_course['sysCode'];
    $args['tid'] = null;
    $args['rid'] = null;
    $args['gid'] = null;
    $args['uid'] = $GLOBALS['_uid'];

    $eventNotifier->notifyEvent("course_deleted",$args);

    if ( $currentCourseId == $code )
    {
        $currentCourseDbName    = trim($this_course['dbName']);
        $currentCourseDbNameGlu = trim($this_course['dbNameGlu']);
        $currentCoursePath      = trim( $this_course['path'] );
        
        if ( empty( $currentCourseDbName ) )
        {
            // This is bad !
            throw new Exception("Missing db name");
        }
        
        if ( empty( $currentCourseDbNameGlu ) )
        {
            // This is bad !
            throw new Exception("Missing db name glu");
        }

        if( get_conf( 'singleDbEnabled' ) )
        // IF THE PLATFORM IS IN MONO DATABASE MODE
        {
            // SEARCH ALL TABLES RELATED TO THE CURRENT COURSE
            claro_sql_query("use " . get_conf('mainDbName'));
            $tbl_to_delete = claro_sql_get_course_tbl(claro_get_course_db_name_glued($currentCourseId));
            foreach($tbl_to_delete as $tbl_name)
            {
                $sql = 'DROP TABLE IF EXISTS `' . $tbl_name . '`';
                claro_sql_query($sql);
            }
            
            // Underscores must be replaced because they are used as wildcards in LIKE sql statement
            $cleanCourseDbNameGlu = str_replace("_","\_", $currentCourseDbNameGlu);
            $sql = 'SHOW TABLES LIKE "' . $cleanCourseDbNameGlu . '%"';

            $result = claro_sql_query($sql);
            
            // DELETE ALL TABLES OF THE CURRENT COURSE
            $tblSurvivor = array();
            
            while( false !== ($courseTable = mysql_fetch_array($result,MYSQL_NUM ) ))
            {
                $tblSurvivor[]=$courseTable[0];
                //$tblSurvivor[$courseTable]='not deleted';
            }
            
            if ( sizeof( $tblSurvivor ) > 0 )
            {
                Claroline::getInstance()->log( 
                    'DELETE_COURSE', 
                    array_merge(
                        array (
                            'DELETED_COURSE_CODE' => $code,
                            'UNDELETED_TABLE_COUNTER' => sizeof( $tblSurvivor )
                        ), 
                        
                        $tblSurvivor 
                    )
                );
            }
        }
        else
        // IF THE PLATFORM IS IN MULTI DATABASE MODE
        {
            $sql = "DROP DATABASE `" . $currentCourseDbName . "`";
            claro_sql_query($sql);
        }

        // MOVE THE COURSE DIRECTORY INTO THE COURSE GARBAGE COLLECTOR

        if( !empty( $currentCoursePath ) )
        {
            if ( file_exists(get_conf('coursesRepositorySys') . $currentCoursePath . '/') )
            {
                claro_mkdir( get_conf('garbageRepositorySys'), CLARO_FILE_PERMISSIONS, true );

                rename (
                    get_conf('coursesRepositorySys') . $currentCoursePath . '/',
                    get_conf('garbageRepositorySys','garbage') . '/' . $currentCoursePath . '_' . date('YmdHis')
                );
            }
            else
            {
                Console::warning( "DELETE_COURSE : Course directory not found {$currentCoursePath} for course {$currentCourseId}");
            }
            
            Claroline::log( 'COURSE_DELETED', array(
                'courseCode' => $currentCourseId,
                'courseDbName' => $currentCourseDbName,
                'courseDbNameGlu' => $currentCourseDbNameGlu,
                'coursePath' => $currentCoursePath
            ) );
            
            return true;
        }
        else
        {
            Console::error("DELETE_COURSE : Try to delete a course repository with no folder name {$currentCourseId} !");
            
            return true;
        }
    }
    else
    {
        return false ;
    }
}


/**
 * create link between a course and categories.
 *
 * @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @param  int      id of course
 * @param  array    collection of categories
 * @return boolean  success
 * @since  1.10
 */
function link_course_categories ( $courseId, $categories )
{
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_rel_course_category   = $tbl_mdb_names['rel_course_category'];
    
    // Insert categories
    $sql = "INSERT INTO `" . $tbl_rel_course_category . "` (courseId, categoryId, rootCourse)
            VALUES ";
    
    $catArr = array();
    
    foreach( $categories as $category )
    {
        $catArr[] =  "(" . $courseId . ", " . $category->id . ", 0)";
    }
    
    $sql .= implode( ',' , $catArr );
    
    if ( ! empty( $catArr ) )
    {
        return claro_sql_query($sql);
    }
}


/**
 * delete link between a course and its associated categories.
 *
 * @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @param  int      id of course
 * @param  array    collection of categories
 *                  (leave it empty to unlink ALL categories)
 * @return boolean  success
 * @since  1.10
 */
function unlink_course_categories ( $courseId, $categories = array() )
{
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_rel_course_category   = $tbl_mdb_names['rel_course_category'];
    
    // Include specified categories identifiers in the request
    $categoriesIdsRestriction = "";
    if ( !empty($categories) )
    {
        $categoriesIds = '';
        for ( $i = 0; $i < count($categories); $i++ )
        {
            $categoriesIds .= $categories[$i]->id;
            // More elements to come ?  Add a comma
            if ( $i < count($categories)-1 )
            {
                $categoriesIds .= ", ";
            }
        }
        $categoriesIdsRestriction = " AND categoryId IN (" . $categoriesIds . ")";
    }
    
    $sql = "DELETE FROM `" . $tbl_rel_course_category . "`
            WHERE courseId=" . (int) $courseId . $categoriesIdsRestriction;
    
    return claro_sql_query($sql);
}


/**
 * count the number of categories associated to a course
 *
 * @author Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @param  int      id of course
 * @return int      number of categories associated
 * @since  1.10
 */
function count_course_categories ( $courseId )
{
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_rel_course_category   = $tbl_mdb_names['rel_course_category'];
    
    $sql = "SELECT COUNT(courseId) AS nbCategoriesLined
            FROM `" . $tbl_rel_course_category . "`
            WHERE courseId = " . (int) $courseId;
    
    if ($result = claro_sql_query_get_single_row($sql))
    {
        return $result['nbCategoriesLined'];
    }
    else
    {
        return false;
    }
}


/**
 * to know if user is registered to a course or not
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @param  int     id of user in DB
 * @param  int     id of course in DB
 * @return boolean true if user is enrolled false otherwise
 */
function is_registered_to($user_id, $course_id)
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];

    $sql = "SELECT count(*) `user_reg`
                 FROM `" . $tbl_rel_course_user . "`
                 WHERE `code_cours` = '" . claro_sql_escape($course_id) . "' AND `user_id` = '" . (int)$user_id . "'";
    $res = claro_sql_query_fetch_all($sql);
    return (bool) ($res[0]['user_reg']>0);
}


/**
 * Transfrom a key word into a usable key word ina SQL : "*" must be replaced by "%" and "%" by "\%"
 * @param  the string to transform
 * @return the string modified
 */
function pr_star_replace($string)
{
    $string = str_replace("%",'\%', $string);
    $string = str_replace("*",'%', $string);
    return $string;
}


<?php // $Id: backlog.class.php 12923 2011-03-03 14:23:57Z abourguignon $

/**
 * CLAROLINE
 *
 * SQL requests for claroCategory class.
 *
 * @version     $Revision: 11894 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since       1.10
 */


/**
 * Get datas for a category.
 *
 * @param int       identifier of the category
 * @return array    category's datas
 */
function claro_get_cat_datas($id)
{
    // Get table name
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_category               = $tbl_mdb_names['category'];
    $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
            
    $sql = "SELECT
            c.id                    AS id,
            c.name                  AS name,
            c.code                  AS code,
            c.idParent              AS idParent,
            c.rank                  AS rank,
            c.visible               AS visible,
            c.canHaveCoursesChild   AS canHaveCoursesChild,
            rcc.courseId            AS rootCourse

            FROM `" . $tbl_category . "` AS c
            
            LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
            ON rcc.categoryId = c.id
            AND rcc.rootCourse = 1
            
            WHERE c.id = " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    
    return $result->fetch();
}


/**
 * Return the predecessor of a specified category (based on the rank attribute).  Ranks can
 * be discontinued (1, 2, 4, 7, ...), so we can't just perform a $rank-1 to get the direct
 * predecessor of a category.
 *
 * @param int       rank of the category that you want the predecessor
 * @param int       identifier of the category that you want the predecessor
 * @return int      identifier of the direct predecessor (if any)
 */
function claro_get_previous_cat_datas($rank, $idParent)
{
    // Get table name
    $tbl_mdb_names   = claro_sql_get_main_tbl();
    $tbl_category    = $tbl_mdb_names['category'];
    
    // Retrieve all the predecessors
    $sql = "SELECT id
            FROM `" . $tbl_category . "`
            WHERE idParent = " . (int) $idParent . "
            AND rank < " . (int) $rank . "
            ORDER BY `rank` DESC";
    
    $result = Claroline::getDatabase()->query($sql);
    
    // Are there any predecessors ?
    $nbPredecessors = count( $result );
    
    if ( $nbPredecessors > 0 )
    {
        // Get the closest predecessor
        $result->rewind();
        return $result->fetch(Database_ResultSet::FETCH_VALUE);
    }
    else
    {
        return false;
    }
}


/**
 * Return the successor of a specified category (based on the rank attribute).
 * Ranks can be discontinued (1, 2, 4, 7, ...), so we can't just perform a
 * $rank+1 to get the direct successor of a category.
 *
 * @param int       rank of the category that you want the successor
 * @param int       identifier of the category that you want the successor
 * @return int      identifier of the direct successor (if any)
 */
function claro_get_following_cat_datas($rank, $idParent)
{
    // Get table name
    $tbl_mdb_names   = claro_sql_get_main_tbl();
    $tbl_category    = $tbl_mdb_names['category'];
    
    // Retrieve all the successors
    $sql = "SELECT id
            FROM `" . $tbl_category . "`
            WHERE idParent = " . (int) $idParent . "
            AND rank > " . (int) $rank . "
            ORDER BY `rank` ASC";
    
    $result = Claroline::getDatabase()->query($sql);
    
    // Are there any successors ?
    $nbSuccessors = count( $result );
    
    if ( $nbSuccessors > 0 )
    {
        // Get the closest successor
        $result->rewind();
        return $result->fetch(Database_ResultSet::FETCH_VALUE);
    }
    else
    {
        return false;
    }
}


/**
 * Return categories from the node $parent.  Also returns the number
 * of courses (nbCourses) directly linked to each category.
 *
 * @param int       identifier of the parent from wich we want to
 *                  get the categories
 * @param bool      $visibility (1 = only visible, 0 = only invisible, null = all; default: null)
 * @return iterator collection of categories ordered by rank
 */
function claro_get_categories($parent, $visibility)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_category              = $tbl_mdb_names['category'];
    $tbl_rel_course_category   = $tbl_mdb_names['rel_course_category'];
    
    // Retrieve all children of the id $parent
    $sql = "SELECT c.id, c.name,
            c.code, c.idParent, c.rank, c.visible, c.canHaveCoursesChild
            FROM `" . $tbl_category . "` AS c
            
            WHERE c.idParent = " . (int) $parent;
    
    if (!is_null($visibility))
    {
        $sql .= "
            AND c.visible = " . $visibility;
    }
    
    if (get_conf('categories_order_by', 'rank') == 'rank')
    {
        $sql .= "
            ORDER BY c.`rank`";
    }
    elseif  (get_conf('categories_order_by') == 'alpha_asc')
    {
        $sql .= "
            ORDER BY c.`name` ASC";
    }
    else
    {
        $sql .= "
            ORDER BY c.`name` DESC";
    }
    
    return Claroline::getDatabase()->query($sql);
}


/**
 * Return all the categories from the node $parent (recursivly).  Also returns
 * the number of courses (nbCourses) directly linked to each category and the
 * level of each category.
 *
 * @param int       identifier of the parent from wich we want to get
 *                  the categories tree (default: 0)
 * @param int       level where we start (default: 0)
 * @param bool      visibility (1 = only visible, 0 = only invisible, null = all; default: null)
 * @return array    collection of all the categories organized hierarchically and ordered by rank
 */
function claro_get_all_categories($parent = 0, $level = 0, $visibility = null)
{
    // Get table name
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_category               = $tbl_mdb_names['category'];
    $tbl_course                 = $tbl_mdb_names['course'];
    $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
    
    // Retrieve all children of the id $parent
    $sql = "SELECT COUNT(rcc.courseId) AS nbCourses,
            ca.id, ca.name,
            ca.code,
            ca.idParent,
            ca.rank,
            ca.visible,
            ca.canHaveCoursesChild,
            co.intitule AS dedicatedCourse,
            co.code AS dedicatedCourseCode
            
            FROM `" . $tbl_category . "` AS ca
            
            LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
            ON rcc.categoryId = ca.id
            
            LEFT JOIN `" . $tbl_course . "` AS co
            ON rcc.courseId = co.cours_id AND rcc.rootCourse = 1
            
            WHERE ca.idParent = " . (int) $parent;
    
    if ( !is_null($visibility) )
    {
        $sql .= "
            AND ca.visible = " . $visibility;
    }
    
    $sql .=  "
            GROUP BY ca.`id`";
    
    if ( get_conf('categories_order_by') == 'rank' )
        $sql .= "
            ORDER BY ca.`rank`";
    elseif ( get_conf('categories_order_by') == 'alpha_asc' )
        $sql .= "
            ORDER BY ca.`name` ASC";
    elseif ( get_conf('categories_order_by') == 'alpha_desc' )
        $sql .= "
            ORDER BY ca.`name` DESC";
    
    $result = Claroline::getDatabase()->query($sql);
    $result_array = array();
    
    // Get each child
    foreach ( $result as $row )
    {
        $row['level'] = $level;
        $result_array[] = $row;
        // Call this function again to get the next level of the tree
        $result_array = array_merge( $result_array, claro_get_all_categories($row['id'], $level+1) );
    }
    
    return $result_array;
}


/**
 * Return the identifiers of all the parents of a category.
 * Reserved category 0 never has any parent.
 *
 * @param int       identifier of the specified category
 * @return array    collection of all the identifiers of the parents
 */
function claro_get_parents_ids($id)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_category              = $tbl_mdb_names['category'];
    
    // Retrieve parent of the category
    $sql = "SELECT idParent
            FROM `" . $tbl_category . "`
            WHERE id = " . (int) $id . "";
    
    $result = Claroline::getDatabase()->query($sql);
    
    if (!$result->isEmpty())
    {
        $parentId = $result->fetch(Database_ResultSet::FETCH_VALUE);
        
        $result_array = array();
        
        // Keep going up until reaching the root
        if ( $parentId != 0 )
        {
            $result_array[] = $parentId;
            $result_array = array_merge( $result_array, claro_get_parents_ids($parentId) );
        }
    
        return $result_array;
    }
    else
    {
        return array();
    }
}


/**
 * Insert a category in database (with rank following the last category of
 * the same parent)
 *
 * @param string        name of the category
 * @param string        code of the category
 * @param int           identifier of the parent category (default: 0)
 * @param int           rank, position in the tree's level // Not used
 * @param bool          visibility (default: 1)
 * @param bool          canHaveCoursesChild (authorized to possess courses) (default: 1)
 * @return handler
 */
function claro_insert_cat_datas($name, $code, $idParent, $rank, $visible, $canHaveCoursesChild)
{
    // Get table name
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_category               = $tbl_mdb_names['category'];
    $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
    
    // Get the highest rank for the designated parent
    $sql = "SELECT MAX(rank) AS maxRank
            FROM `" . $tbl_category . "`
            WHERE idParent=" . (int) $idParent;
    
    $result = Claroline::getDatabase()->query($sql);
    $result->rewind();
    
    $newRank = $result->fetch(Database_ResultSet::FETCH_VALUE) + 1;
    
    $sql = "INSERT INTO `" . $tbl_category . "` SET
            `name`                  = " . Claroline::getDatabase()->quote($name) . ",
            `code`                  = " . Claroline::getDatabase()->quote($code) . ",
            `idParent`              = " . (int) $idParent . ",
            `rank`                  = " . $newRank. ",
            `visible`               = " . (int) $visible . ",
            `canHaveCoursesChild`   = " . (int) $canHaveCoursesChild;
    
    return Claroline::getDatabase()->exec($sql);
}


/**
 * Update datas of a category.  If the parent ($idParent) is modified,
 * category's rank will follow the last category of the new parent.
 *
 * @param int       identifier of the category
 * @param string    name of the category
 * @param string    code of the category
 * @param int       identifier of the parent category (default: 0)
 * @param int       rank, position in the tree's level
 * @param bool      visibility (default: 1)
 * @param bool      canHaveCoursesChild (authorized to possess courses) (default: 1)
 * @param int           identifier of the root course/dedicated course
 */
function claro_update_cat_datas($id, $name, $code, $idParent, $rank, $visible, $canHaveCoursesChild, $rootCourse)
{
    // Get table name
    $tbl_mdb_names   = claro_sql_get_main_tbl();
    $tbl_category    = $tbl_mdb_names['category'];
    $tbl_rel_course_category   = $tbl_mdb_names['rel_course_category'];
    
    // New root course ?
    if (!is_null($rootCourse))
    {
        $sql = "SELECT rcc.courseId
                FROM `" . $tbl_rel_course_category . "` AS rcc
                WHERE rcc.categoryId = " . (int) $id . "
                AND rootCourse = 1";
        
        $result = Claroline::getDatabase()->query($sql);
        $course = $result->fetch(Database_ResultSet::FETCH_VALUE);
        
        // Unset the previous rootCourse
        if (isset($course['courseId']) && $course['courseId'] != $rootCourse)
        {
            $sql = "UPDATE `" . $tbl_rel_course_category . "` SET
                    rootCourse = 0
                    WHERE categoryId = " . (int) $id . "
                    AND courseId = " . $course['courseId'];
            
            Claroline::getDatabase()->exec($sql);
        }
        
        // Set the new rootCourse
        $sql = "UPDATE `" . $tbl_rel_course_category . "` SET
                rootCourse = 1
                WHERE categoryId = " . (int) $id . "
                AND courseId = " . $rootCourse;
        
        Claroline::getDatabase()->exec($sql);
    }
    
    // New parent ?
    $sql = "SELECT c.idParent
            FROM `" . $tbl_category . "` AS c
            WHERE c.id = " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    
    if ($result->fetch(Database_ResultSet::FETCH_VALUE) == $idParent) // Parent hasn't changed
    {
        $sql = "UPDATE `" . $tbl_category . "` SET
                `name`                  = " . Claroline::getDatabase()->quote($name) . ",
                `code`                  = " . Claroline::getDatabase()->quote($code) . ",
                `rank`                  = " . (int) $rank . ",
                `visible`               = " . (int) $visible . ",
                `canHaveCoursesChild`   = " . (int) $canHaveCoursesChild . "
                WHERE id = " . (int) $id;
    }
    else // Parent has changed
    {
        // Get the highest rank for the designated new parent
        $sql = "SELECT MAX(rank) AS maxRank
                FROM `" . $tbl_category . "`
                WHERE idParent=" . (int) $idParent;
        
        $result = Claroline::getDatabase()->query($sql);
        
        $newRank = $result->fetch(Database_ResultSet::FETCH_VALUE) + 1;
        
        // Update datas
        $sql = "UPDATE `" . $tbl_category . "` SET
                `name`                  = " . Claroline::getDatabase()->quote($name) . ",
                `code`                  = " . Claroline::getDatabase()->quote($code) . ",
                `idParent`              = " . (int) $idParent . ",
                `rank`                  = " . $newRank. ",
                `visible`               = " . (int) $visible . ",
                `canHaveCoursesChild`   = " . (int) $canHaveCoursesChild . "
                WHERE id = " . (int) $id;
    }
    
    return Claroline::getDatabase()->exec($sql);
}


/**
 * Delete datas of a category and unlinks all courses linked to it.
 *
 * @param int       identifier of the category
 * @return handler
 */
function claro_delete_cat_datas($id)
{
    // Get table name
    $tbl_mdb_names              = claro_sql_get_main_tbl();
    $tbl_category               = $tbl_mdb_names['category'];
    $tbl_rel_course_category    = $tbl_mdb_names['rel_course_category'];
    
    //Unlink the courses
    $sql = "SELECT courseId, categoryId
            FROM `" . $tbl_rel_course_category . "`
            WHERE categoryId = " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    
    while ($link = $result->fetch(Database_ResultSet::FETCH_ASSOC))
    {
        $sql = "SELECT COUNT(courseId) AS nbLinks
                FROM `" . $tbl_rel_course_category . "`
                WHERE courseId = " . $link['courseId'];
        
        $result2 = Claroline::getDatabase()->query($sql);
        $nbLinks = $result2->fetch(Database_ResultSet::FETCH_VALUE);
        
        //If there are multiple links for this course, just delete the one we want
        if ($nbLinks > 1)
        {
            $sql = "DELETE FROM `" . $tbl_rel_course_category . "`
                    WHERE categoryId = " . (int) $id . "
                    AND courseId = " . $link['courseId'];
            Claroline::getDatabase()->exec($sql);
        }
        //If there is only one link for this course, link it to the root category
        else
        {
            $sql = "UPDATE `" . $tbl_rel_course_category . "`
                    SET
                    categoryId = 0,
                    rootCourse = 0
                    WHERE categoryId = " . (int) $id . "
                    AND courseId = " . $link['courseId'];
            Claroline::getDatabase()->exec($sql);
        }
    }
    
    //Finaly, delete the category
    $sql = "DELETE FROM `" . $tbl_category . "`
            WHERE id = " . (int) $id;
    
    return Claroline::getDatabase()->exec($sql);
}


/**
 * Update the visibility value for a category.
 *
 * @param int       identifier of the category
 * @param bool      visibility
 */
function claro_set_cat_visibility($id, $visible)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_category              = $tbl_mdb_names['category'];
    
    $sql = "UPDATE `" . $tbl_category . "` SET
            visible    = " . (int) $visible . "
            WHERE id = '" . (int) $id . "'";
    
    return Claroline::getDatabase()->exec($sql);
}


/**
 * Count the number of courses directly attached to the category.
 * The count does NOT include root courses.
 *
 * @param int       identifier of the category
 * @return int      number of courses
 */
function claro_count_courses($id)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_rel_course_category   = $tbl_mdb_names['rel_course_category'];
    
    $sql = "SELECT COUNT(courseId) as nbCourses
            FROM `" . $tbl_rel_course_category . "`
            WHERE categoryId = " . (int) $id . "
            AND rootCourse != 1";
    
    $result = Claroline::getDatabase()->query($sql);
    
    return $result->fetch(Database_ResultSet::FETCH_VALUE);
}


/**
 * Count the number of courses belonging to the category and all its
 * subcategories (recursivly).
 * The count does NOT include root courses.
 *
 * @param int       identifier of the category
 * @param int       count, starting value of counting (default: 0)
 * @return int      number of courses
 */
function claro_count_all_courses($id, $count = 0)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_category              = $tbl_mdb_names['category'];
    $tbl_rel_course_category   = $tbl_mdb_names['rel_course_category'];
    
    // Count number of courses for this category
    $sql = "SELECT COUNT(rcc.courseId) AS nbCourses
            FROM `" . $tbl_rel_course_category . "` AS rcc
            WHERE rcc.categoryId = " . (int) $id . "
            AND rootCourse != 1";
    
    $result = Claroline::getDatabase()->query($sql);
    $nbCourses = $result->fetch(Database_ResultSet::FETCH_VALUE);
    $count = ($nbCourses > 0) ? ($count + $nbCourses) : ($count);
    
    // Retrieve all children of this category
    $sql = "SELECT id
            FROM `" . $tbl_category . "`
            WHERE idParent = " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    
    // Get each child
    foreach ( $result as $row )
    {
        $count =+ claro_count_all_courses($row['id'], $count);
    }
    
    return $count;
}


/**
 * Count the number of sub categories directly attached to the category.
 *
 * @param int       identifier of the category
 * @return int      number of sub categories
 */
function claro_count_sub_categories($id)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_category              = $tbl_mdb_names['category'];
    
    $sql = "SELECT COUNT(id) as nbSubCategories
            FROM `" . $tbl_category . "`
            WHERE idParent = " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    $result->rewind();
    
    return $result->fetch(Database_ResultSet::FETCH_VALUE);
}


/**
 * Count the number of sub categories belonging to a category (recursivly).
 *
 * @param int       identifier of the category
 * @return int      number of sub categories
 */
function claro_count_all_sub_categories($id, $count = 0)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_category              = $tbl_mdb_names['category'];
    
    // Count number of courses for this category
    $sql = "SELECT COUNT(c.id) AS nbSubCategories
            FROM `" . $tbl_category . "` AS c
            WHERE c.idParent = " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    $nbSubCategories = $result->fetch(Database_ResultSet::FETCH_VALUE);
    $count = ($nbSubCategories > 0) ? ($count + $nbSubCategories) : ($count);
    
    // Retrieve all subcategories
    $sql = "SELECT id
            FROM `" . $tbl_category . "`
            WHERE idParent = " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    
    // Get each child
    foreach ( $result as $row )
    {
        $count =+ claro_count_all_sub_categories($row['id'], $count);
    }
    
    return $count;
}


/**
 * Count the number of categories having a specific value for the code
 * attribute.  You can ignore a specific id in the counting.
 *
 * @param int       identifier that we want to ignore in the request
 * @param string    code's value we search for
 * @return int      number of categories matching this value
 */
function claro_count_code($id = null, $code)
{
    // Get table name
    $tbl_mdb_names             = claro_sql_get_main_tbl();
    $tbl_category              = $tbl_mdb_names['category'];
    
    $sql = "SELECT COUNT(id) nbMatching
            FROM `" . $tbl_category . "`
            WHERE code = " . Claroline::getDatabase()->quote($code);

    if (!is_null($id))
        $sql .= " AND id != " . (int) $id;
    
    $result = Claroline::getDatabase()->query($sql);
    $result->rewind();
    
    return $result->fetch(Database_ResultSet::FETCH_VALUE);
}
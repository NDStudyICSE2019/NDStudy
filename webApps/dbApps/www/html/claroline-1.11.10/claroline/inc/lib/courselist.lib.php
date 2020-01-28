<?php

// $Id: courselist.lib.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLCOURSELIST
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.9
 */
require_once dirname ( __FILE__ ) . '/categorybrowser.class.php';

/**
 * Search a specific course based on his course code.  If the user isn't
 * a platform admin, this function will not return source courses having
 * session courses.
 *
 * @param  string       $keyword course code from the cours table
 * @param  mixed        $userId  null or valid id of a user (default:null)
 * @return array        course parameters
 * @deprecated use SearchedCourseList instead
 */
function search_course ( $keyword, $userId = null )
{
    $tbl_mdb_names = claro_sql_get_main_tbl ();
    $tbl_course = $tbl_mdb_names[ 'course' ];
    $tbl_rel_course_user = $tbl_mdb_names[ 'rel_course_user' ];

    $keyword = trim ( $keyword );

    if ( empty ( $keyword ) )
        return array ( );

    $upperKeyword = addslashes ( strtoupper ( $keyword ) );

    $curdate = date ( 'Y-m-d H:i:s', time () );

    $sql = "SELECT c.cours_id             AS id,
                   c.intitule             AS title,
                   c.titulaires           AS titular,
                   c.code                 AS sysCode,
                   c.sourceCourseId       AS souceCourseId,
                   c.administrativeNumber AS officialCode,
                   c.directory            AS directory,
                   c.code                 AS code,
                   c.language             AS language,
                   c.email                AS email,
                   c.sourceCourseId,
                   c.visibility,
                   c.access,
                   c.registration,
                   c.status,
                   c.creationDate,
                   c.expirationDate"
        . ($userId ? ",
                   cu.user_id AS enroled,
                   cu.isCourseManager" : "")
        . " \n "
        . "FROM `" . $tbl_course . "` c "
        . " \n "
        . ($userId ? "LEFT JOIN `" . $tbl_rel_course_user . "` AS cu
                        ON  c.code = cu.code_cours
                        AND cu.user_id = " . (int) $userId : "")
        . " \n "
        . "WHERE ( "
        . (claro_is_platform_admin () ? '' :
            "(visibility = 'VISIBLE'
                AND ( `status`='enable'
                        OR ( `status` = 'date'
                            AND ( `creationDate` < '" . $curdate . "'
                                OR `creationDate` IS NULL
                                OR UNIX_TIMESTAMP(`creationDate`) = 0
                                )
                            AND ( '" . $curdate . "' < `expirationDate`
                                OR `expirationDate` IS NULL
                                )
                            )
                    )
            "
            . ( $userId ? " OR cu.user_id " : "")
            . " ) AND "
        )
        . "
            ( UPPER(administrativeNumber)   LIKE '%" . $upperKeyword . "%'
                OR UPPER(intitule)              LIKE '%" . $upperKeyword . "%'
                OR UPPER(titulaires)            LIKE '%" . $upperKeyword . "%'
                )"
        . "
            )
            ORDER BY officialCode";

    $coursesList = claro_sql_query_fetch_all ( $sql );

    if ( count ( $coursesList ) > 0 )
    {
        //If not platform admin, remove source courses
        if ( !claro_is_platform_admin () )
        {
            // Find the source courses identifiers
            $sourceCoursesIds = array ( );
            foreach ( $coursesList as $course )
            {
                if ( !is_null ( $course[ 'sourceCourseId' ] )
                    && !in_array ( $course[ 'sourceCourseId' ], $sourceCoursesIds ) )
                {
                    $sourceCoursesIds[ ] = $course[ 'sourceCourseId' ];
                }
            }

            $filteredCoursesList = array ( );
            foreach ( $coursesList as $course )
            {
                if ( !in_array ( $course[ 'id' ], $sourceCoursesIds ) )
                    $filteredCoursesList[ ] = $course;
            }

            return $filteredCoursesList;
        }
        else
        {
            return $coursesList;
        }
    }
    else
    {
        return array ( );
    }
}

/**
 * Return course list of a user.
 *
 * @param int $userId valid id of a user
 * @param boolean $renew whether true, force to read databaseingoring an existing cache (default: false)
 * @param boolean $categories wheter true, get categories informations (default: false)
 * @return array (list of course) of array (course settings) of the given user
 * @todo search and merge other instance of this functionality (claro_get_user_course_list())
 * @deprecated use UserCourseList instead
 */
function get_user_course_list ( $userId, $renew = false, $categories = false )
{
    static $cached_uid = null, $userCourseList = null;

    if ( $cached_uid != $userId || is_null ( $userCourseList ) || $renew )
    {
        $cached_uid = $userId;

        $tbl_mdb_names = claro_sql_get_main_tbl ();
        $tbl_courses = $tbl_mdb_names[ 'course' ];
        $tbl_rel_user_courses = $tbl_mdb_names[ 'rel_course_user' ];
        $tbl_rel_course_category = $tbl_mdb_names[ 'rel_course_category' ];

        $curdate = claro_mktime ();

        $sql = "SELECT course.cours_id,
                       course.code                  AS `sysCode`,
                       course.directory             AS `directory`,
                       course.administrativeNumber  AS `officialCode`,
                       course.dbName                AS `db`,
                       course.intitule              AS `title`,
                       course.titulaires            AS `titular`,
                       course.language              AS `language`,
                       course.access                AS `access`,
                       course.status,
                       course.sourceCourseId,
                       UNIX_TIMESTAMP(course.expirationDate) AS expirationDate,
                       UNIX_TIMESTAMP(course.creationDate)   AS creationDate,
                       rcu.isCourseManager";

        if ( $categories )
            $sql .= ",
                       rcc.categoryId               AS `categoryId`,
                       rcc.rootCourse";

        $sql .= "
                
                FROM `" . $tbl_courses . "` AS course
                
                LEFT JOIN `" . $tbl_rel_user_courses . "` AS rcu
                ON rcu.user_id = " . (int) $userId . " ";

        if ( $categories )
            $sql .= "
                
                LEFT JOIN `" . $tbl_rel_course_category . "` AS rcc
                ON course.cours_id = rcc.courseId";

        $sql .= "
                
                WHERE course.code = rcu.code_cours
                AND (course.`status`='enable'
                      OR (course.`status` = 'date'
                           AND (UNIX_TIMESTAMP(`creationDate`) < '" . $curdate . "'
                                 OR `creationDate` IS NULL OR UNIX_TIMESTAMP(`creationDate`)=0
                               )
                           AND ('" . $curdate . "' < UNIX_TIMESTAMP(`expirationDate`) OR `expirationDate` IS NULL)
                         )
                    )";

        if ( $categories )
            $sql .= "
                AND rcc.rootCourse != 1";

        if ( !get_conf ( 'userCourseListGroupByCategories' ) )
        {
            $sql .= " GROUP BY course.code";
        }

        if ( get_conf ( 'course_order_by' ) == 'official_code' )
        {
            $sql .= " ORDER BY UPPER(`administrativeNumber`), `title`";
        }
        else
        {
            $sql .= " ORDER BY `title`, UPPER(`administrativeNumber`)";
        }

        $userCourseList = claro_sql_query_fetch_all ( $sql );
    }

    return $userCourseList;
}

/**
 * Return the list of disabled or unpublished course of a user.
 *
 * @param int $userId valid id of a user
 * @param boolean $renew whether true, force to read databaseingoring an existing cache.
 * @return array (list of course) of array (course settings) of the given user.
 * @todo search and merge other instance of this functionality
 * @deprecated use UserCourseList instead
 */
function get_user_course_list_desactivated ( $userId, $renew = false )
{
    static $cached_uid = null, $userCourseList = null;

    $curdate = claro_mktime ();

    if ( $cached_uid != $userId || is_null ( $userCourseList ) || $renew )
    {
        $cached_uid = $userId;

        $tbl_mdb_names = claro_sql_get_main_tbl ();
        $tbl_courses = $tbl_mdb_names[ 'course' ];
        $tbl_link_user_courses = $tbl_mdb_names[ 'rel_course_user' ];

        $sql = "SELECT course.cours_id,
                       course.code                 AS `sysCode`,
                       course.directory            AS `directory`,
                       course.administrativeNumber AS `officialCode`,
                       course.dbName               AS `db`,
                       course.intitule             AS `title`,
                       course.titulaires           AS `titular`,
                       course.language             AS `language`,
                       course.access               AS `access`,
                       course_user.isCourseManager,
                       course.status,
                       UNIX_TIMESTAMP(course.expirationDate) AS expirationDate,
                       UNIX_TIMESTAMP(course.creationDate)     AS creationDate
                       
                       FROM `" . $tbl_courses . "`           AS course,
                            `" . $tbl_link_user_courses . "` AS course_user
                       
                       WHERE course.code         = course_user.code_cours
                         AND course_user.user_id = " . (int) $userId . "
                         AND (course.`status` = 'disable'
                              OR course.`status` = 'pending'
                              OR (course.`status` = 'date'
                                  AND (UNIX_TIMESTAMP(`creationDate`) > '" . $curdate . "'
                                       OR '" . $curdate . "'> UNIX_TIMESTAMP(`expirationDate`)
                                       )
                                  )
                              ) ";

        if ( get_conf ( 'course_order_by' ) == 'official_code' )
        {
            $sql .= " ORDER BY UPPER(`administrativeNumber`), `title`";
        }
        else
        {
            $sql .= " ORDER BY `title`, UPPER(`administrativeNumber`)";
        }

        $userCourseListDesactivated = claro_sql_query_fetch_all ( $sql );
    }

    return $userCourseListDesactivated;
}

/**
 * Return the editable textzone for a course where subscript are denied.
 *
 * @param string        $course_id
 * @return string       html content
 */
function get_locked_course_explanation ( $course_id = null )
{
    $courseExplanation = claro_text_zone::get_content ( 'course_subscription_locked', array ( CLARO_CONTEXT_COURSE => $course_id ) );

    if ( !empty ( $courseExplanation ) )
    {
        return $courseExplanation;
    }
    else
    {
        $globalExplanation = claro_text_zone::get_content ( 'course_subscription_locked' );

        if ( !empty ( $globalExplanation ) )
        {
            return $globalExplanation;
        }
        else
        {
            return get_lang ( 'Subscription not allowed' );
        }
    }
}

/**
 * Return the editable textzone for a course where subscript are locked.
 *
 * @param string        $course_id
 * @return string       html content
 */
function get_locked_course_by_key_explanation ( $course_id = null )
{
    $courseExplanation = claro_text_zone::get_content ( 'course_subscription_locked_by_key', array ( CLARO_CONTEXT_COURSE => $course_id ) );

    if ( !empty ( $courseExplanation ) )
    {
        return $courseExplanation;
    }
    else
    {
        $globalExplanation = claro_text_zone::get_content ( 'course_subscription_locked_by_key' );

        if ( !empty ( $globalExplanation ) )
        {
            return $globalExplanation;
        }
        else
        {
            return get_lang ( 'Subscription not allowed' );
        }
    }
}

/**
 * Return trail (path) for a given category.
 *
 * @param array         list of categories
 * @param int           id of the category for which we want the trail
 * @return string       trail
 */
function build_category_trail ( $categoriesList, $requiredId )
{
    $trail = array ( );

    if ( is_array ( $categoriesList ) && !empty ( $categoriesList ) )
    {
        foreach ( $categoriesList as $category )
        {
            if ( $category[ 'id' ] == $requiredId )
            {
                if ( !is_null ( $category[ 'idParent' ] ) && ($category[ 'idParent' ]) )
                {
                    $trail[ ] = build_category_trail ( $categoriesList, $category[ 'idParent' ] );
                    $trail[ ] = $category[ 'name' ];
                }
                else
                {
                    return $category[ 'name' ];
                }
            }
        }
    }

    return implode ( ' &gt; ', $trail );
}

function is_user_allowed_to_see_desactivated_course ( $course )
{
    return claro_is_platform_admin () || $course[ 'isCourseManager' ] == '1'
        || ( $course[ 'status' ] == 'pending' && get_conf ( 'crslist_DisplayPendingToAllUsers', false ) )
        || ( $course[ 'status' ] == 'disable' && get_conf ( 'crslist_DisplayDisableToAllUsers', false ) )
        || ( $course[ 'status' ] == 'date' && $course[ 'creationDate' ] > claro_mktime () && get_conf ( 'crslist_DisplayExpiredToAllUsers', false ) )
        || ( $course[ 'status' ] == 'date' && isset ( $course[ 'expirationDate' ] ) && $course[ 'expirationDate' ] < claro_mktime () && get_conf ( 'crslist_DisplayUnpublishedToAllUsers', false ) );
}

/**
 * Returns a courses list for the current user.
 *
 * @return string       list of courses (HTML format)
 * @deprecated use UserCourseList and CourseTreeView instead
 */
function render_user_course_list_desactivated ()
{
    $personnalCourseList = get_user_course_list_desactivated ( claro_get_current_user_id () );

    $deactivatedCoursesDiplayed = 0;

    $out = '';

    //display list
    if ( !empty ( $personnalCourseList ) && is_array ( $personnalCourseList ) )
    {
        $out .= '<dl class="courseList">' . "\n";

        foreach ( $personnalCourseList as $course )
        {
            if ( !is_user_allowed_to_see_desactivated_course ( $course ) )
            {
                continue;
            }

            $deactivatedCoursesDiplayed++;


            if ( get_conf ( 'course_order_by' ) == 'official_code' )
            {
                $courseTitle = $course[ 'officialCode' ] . ' - ' . $course[ 'title' ];
            }
            else
            {
                $courseTitle = $course[ 'title' ] . ' (' . $course[ 'officialCode' ] . ')';
            }

            $url = get_path ( 'url' ) . '/claroline/course/index.php?cid='
                . claro_htmlspecialchars ( $course[ 'sysCode' ] );

            $urlSettings = Url::Contextualize ( get_path ( 'url' ) . '/claroline/course/settings.php?cidReq='
                    . claro_htmlspecialchars ( $course[ 'sysCode' ] . '&cmd=exEnable' ) );

            $out .= '<dt>' . "\n"
                . '<img class="iconDefinitionList" src="' . get_icon_url ( 'course_deactivated' )
                . '" alt="' . get_lang ( 'Course deactivated' ) . '" /> ';

            if ( $course[ 'status' ] == 'pending' )
            {
                if ( claro_is_platform_admin () || $course[ 'isCourseManager' ] == '1' )
                {
                    $out.= '<a href="' . claro_htmlspecialchars ( $url ) . '">'
                        . claro_htmlspecialchars ( $courseTitle )
                        . '</a>' . "\n"
                        . '<img class="qtip" src="' . get_icon_url ( 'manager' ) . '" alt="' . get_lang ( 'You are manager of this course' ) . '" /> '
                        . '[<a href="' . $urlSettings . '">' . get_lang ( 'Reactivate it' ) . '</a>]';
                }
                elseif ( get_conf ( 'crslist_DisplayPendingToAllUsers', false ) )
                {
                    $out.= claro_htmlspecialchars ( $courseTitle )
                        . ' <em><small>' . get_lang ( 'You cannot access this course until the course manager has reactivated it' ) . '</small></em>'
                        . "\n";
                }
            }

            if ( $course[ 'status' ] == 'disable' )
            {
                if ( claro_is_platform_admin () )
                {
                    $out .= '<a href="' . claro_htmlspecialchars ( $url ) . '">'
                        . claro_htmlspecialchars ( $courseTitle )
                        . '</a> '
                        . '<img src="' . get_icon_url ( 'platformadmin' ) . '" alt="" /> '
                        . '[<a href="' . $urlSettings . '"> ' . get_lang ( 'Reactivate it' ) . '</a>]'
                        . "\n";
                }
                else
                {
                    if ( $course[ 'isCourseManager' ] == '1' )
                    {
                        $out.= claro_htmlspecialchars ( $courseTitle )
                            . ' ' . get_lang ( 'Contact your administrator to reactivate it. ' );
                    }
                    elseif ( get_conf ( 'crslist_DisplayDisableToAllUsers', false ) )
                    {
                        $out.= claro_htmlspecialchars ( $courseTitle )
                            . ' <em><small>' . get_lang ( 'You cannot access this course it has been deactivated' ) . '</small></em>'
                            . "\n";
                    }
                }
            }

            if ( $course[ 'status' ] == 'date' )
            {
                if ( $course[ 'creationDate' ] > claro_mktime () )
                {
                    if ( claro_is_platform_admin () || $course[ 'isCourseManager' ] == '1' )
                    {
                        $out.= '<a href="' . claro_htmlspecialchars ( $url ) . '">'
                            . claro_htmlspecialchars ( $courseTitle )
                            . '</a>' . "\n"
                            . ' ' . get_lang ( 'Will be published on ' ) . date ( 'd-m-Y', $course[ 'creationDate' ] );
                    }
                    elseif ( get_conf ( 'crslist_DisplayUnpublishedToAllUsers', false ) )
                    {
                        $out.= claro_htmlspecialchars ( $courseTitle )
                            . ' ' . get_lang ( 'Will be published on ' ) . date ( 'd-m-Y', $course[ 'creationDate' ] );
                    }
                }

                if ( isset ( $course[ 'expirationDate' ] ) AND ($course[ 'expirationDate' ] < claro_mktime ()) )
                {
                    if ( claro_is_platform_admin () || $course[ 'isCourseManager' ] == '1' )
                    {
                        $out.= '<a href="' . claro_htmlspecialchars ( $url ) . '">'
                            . claro_htmlspecialchars ( $courseTitle )
                            . '</a>' . "\n"
                            . ' ' . get_lang ( 'Expired since ' ) . date ( 'd-m-Y', $course[ 'expirationDate' ] );
                    }
                    elseif ( get_conf ( 'crslist_DisplayExpiredToAllUsers', false ) )
                    {
                        $out.= claro_htmlspecialchars ( $courseTitle )
                            . ' ' . get_lang ( 'Expired since ' ) . date ( 'd-m-Y', $course[ 'expirationDate' ] );
                    }
                }
            }

            $out .= '</dt>' . "\n";

            $out .= '<dd>'
                . claro_htmlspecialchars ( $course[ 'titular' ] )
                . '</dd>' . "\n";
        }

        $out .= '</dl>' . "\n";
    }

    if ( $deactivatedCoursesDiplayed == 0 )
    {
        return '';
    }
    else
    {
        return $out;
    }
}

/**
 * Returns a courses list for the current user.
 *
 * @return string       list of courses (HTML format)
 * @deprecated use UserCourseList and CourseTreeView instead
 */
function render_user_course_list ()
{
    // Get the list of personnal courses marked as contening new events
    $date = Claroline::getInstance ()->notification->get_notification_date ( claro_get_current_user_id () );
    $modified_course = Claroline::getInstance ()->notification->get_notified_courses ( $date, claro_get_current_user_id () );

    // Get all the user's courses
    $userCourseList = claro_get_user_course_list ();


    // Use the course id as array index, exclude disable courses
    // and flag hot courses
    $reorganizedUserCourseList = array ( );
    $tempSessionCourses = array ( );

    foreach ( $userCourseList as $course )
    {
        // Do not include "disable", "pending", "trash" or "date" courses
        // (if we're not in the date limits)
        $curdate = claro_mktime ();
        $courseIsEnable = (bool) (
            $course[ 'status' ] == 'enable' ||
            (
            $course[ 'status' ] == 'date' &&
            (!isset ( $course[ 'creationDate' ] ) || strtotime ( $course[ 'creationDate' ] ) <= $curdate) &&
            (!isset ( $course[ 'expirationDate' ] ) || strtotime ( $course[ 'expirationDate' ] ) >= $curdate)
            )
            );

        // Flag hot courses
        $course[ 'hot' ] = (bool) in_array ( $course[ 'sysCode' ], $modified_course );

        if ( !isset ( $reorganizedUserCourseList[ $course[ 'courseId' ] ] ) && $courseIsEnable )
        {
            // If it's not a session course, include it in the final list
            if ( empty ( $course[ 'sourceCourseId' ] ) )
            {
                $reorganizedUserCourseList[ $course[ 'courseId' ] ] = $course;
            }
            // If it's a session course, put it aside for now,
            // we'll get back to it later
            else
            {
                $tempSessionCourses[ $course[ 'sourceCourseId' ] ][ ] = $course;
            }
        }
    }
    
    unset ( $userCourseList );

    // Merge courses and their session courses (if any)
    foreach ( $tempSessionCourses as $sourceCourseId => $sessionCourses )
    {
        /*
         * Sometimes, a session course could not find its associated source
         * course in the user course list.  Simply because, for some reason,
         * this user isn't registered to the source course anymore, but is
         * still registered in the session course.
         */
        if ( !empty ( $reorganizedUserCourseList[ $sourceCourseId ] ) )
        {
            $reorganizedUserCourseList[ $sourceCourseId ][ 'sessionCourses' ] = $sessionCourses;
        }
        else
        {
            foreach ( $sessionCourses as $course )
            {
                $reorganizedUserCourseList[ $course[ 'courseId' ] ] = $course;
            }
        }
    }
    unset ( $tempSessionCourses );


    // Now, $reorganizedUserCourseList contains all user's courses and, for
    // each course, its eventual session courses.
    // Display
    $out = '';

    // Courses organized by categories
    if ( get_conf ( 'userCourseListGroupByCategories' ) )
    {
        // Get all the categories names (used to build trails)
        $categoryList = ClaroCategory::getAllCategories ( 0, 0, 1 );

        // Get the categories informations for these courses
        $userCategoryList = ClaroCategory::getCoursesCategories ( $reorganizedUserCourseList );

        // Use the category id as array index
        $reorganizedUserCategoryList = array ( );

        foreach ( $userCategoryList as $category )
        {
            // Flag root courses and put it aside
            $reorganizedUserCourseList[ $category[ 'courseId' ] ][ 'rootCourse' ] = 0;

            if ( $category[ 'rootCourse' ] )
            {
                $reorganizedUserCourseList[ $category[ 'courseId' ] ][ 'rootCourse' ] = 1;
            }

            if ( !isset ( $reorganizedUserCategoryList[ $category[ 'categoryId' ] ] ) )
            {
                $reorganizedUserCategoryList[ $category[ 'categoryId' ] ] =
                    $category;

                //We won't need that key anymore
                unset ( $reorganizedUserCategoryList[ $category[ 'categoryId' ] ][ 'courseId' ] );
            }

            // Initialise the category's course list
            $reorganizedUserCategoryList[ $category[ 'categoryId' ] ][ 'courseList' ] = array ( );
        }

        // Place courses in the right categories and build categories' trails
        $currentCategoryId = null;

        foreach ( $userCategoryList as $category )
        {
            // Build the full trail for each category (excepted root category)
            if ( $category[ 'categoryId' ] == 0 )
            {
                $trail = $category[ 'name' ];
            }
            else
            {
                $trail = build_category_trail ( $categoryList, $category[ 'categoryId' ] );
            }
            
            $reorganizedUserCategoryList[ $category[ 'categoryId' ] ][ 'trail' ] = $trail;

            // Put root courses aside
            if ( $reorganizedUserCourseList[ $category[ 'courseId' ] ][ 'rootCourse' ] )
            {
                $reorganizedUserCategoryList[ $category[ 'categoryId' ] ][ 'rootCourse' ] =
                    $reorganizedUserCourseList[ $category[ 'courseId' ] ];
            }
            else
            {
                // Do not include source courses (only display session courses)
                // (excepted if the user is manager of the course)
                if ( !($reorganizedUserCourseList[ $category[ 'courseId' ] ][ 'isSourceCourse' ])
                    || $reorganizedUserCourseList[ $category[ 'courseId' ] ][ 'isCourseManager' ] )
                {
                    $reorganizedUserCategoryList[ $category[ 'categoryId' ] ][ 'courseList' ][ ] =
                        $reorganizedUserCourseList[ $category[ 'courseId' ] ];
                }
            }
        }

        unset ( $userCategoryList );

        if ( count ( $reorganizedUserCategoryList ) > 0 )
        {
            $out .= '<dl class="courseList">';

            foreach ( $reorganizedUserCategoryList as $category )
            {

                if ( !empty ( $category[ 'courseList' ] ) || !empty ( $category[ 'rootCourse' ] ) )
                {
                    $out .= '<dt>' . "\n"
                        . '<h4 id="' . $category[ 'categoryId' ] . '">'
                        . $category[ 'trail' ]
                        . (!empty ( $category[ 'rootCourse' ] ) ?
                            ' [<a href="'
                            . get_path ( 'url' ) . '/claroline/course/index.php?cid='
                            . claro_htmlspecialchars ( $category[ 'rootCourse' ][ 'sysCode' ] )
                            . '">' . get_lang ( 'Infos' ) . '</a>]' :
                            '')
                        . '</h4>' . "\n"
                        . '</dt>' . "\n";

                    if ( !empty ( $category[ 'courseList' ] ) )
                    {
                        foreach ( $category[ 'courseList' ] as $course )
                        {
                            $out .= render_course_in_dl_list ( $course, $course[ 'hot' ] );
                        }
                    }
                    else
                    {
                        $out .= '<dt>' . get_lang ( 'There are no courses in this category' ) . '</dt>';
                    }
                }
            }
        }

        $out .= '</dl>';
    }
    // Simple course list
    else
    {
        if ( count ( $reorganizedUserCourseList ) > 0 )
        {
            $out .= '<dl class="courseList">';

            foreach ( $reorganizedUserCourseList as $course )
            {
                $displayIconAccess = ($course[ 'isCourseManager' ] || claro_is_platform_admin ()) ?
                    (true) : (false);
                $out .= render_course_in_dl_list ( $course, $course[ 'hot' ], $displayIconAccess );
            }

            $out .= '</dl>' . "\n";
        }
    }

    return $out;
}

/**
 * Return course informations in <dt> and <dd> html tags.
 * Use it in a <dl> tag (description list).
 *
 * @param $course
 * @param $hot
 * @param $displayIconAccess
 * @return string
 * @deprecated use UserCourseList and CourseTreeView instead
 */
function render_course_in_dl_list ( $course, $hot = false, $displayIconAccess = true )
{
    $out = '';

    $classItem = ($hot) ? 'hot' : '';

    $langNameOfLang = get_locale ( 'langNameOfLang' );

    // Display a manager icon if the user is manager of the course
    $userStatusImg = (isset ( $course[ 'isCourseManager' ] ) && $course[ 'isCourseManager' ] == 1 ) ?
        ('&nbsp;&nbsp;<img class="qtip role" src="' . get_icon_url ( 'manager' ) . '" alt="' . get_lang ( 'You are manager of this course' ) . '" />') :
        ('');

    // Show course language if not the same of the platform
    if ( (get_conf ( 'platformLanguage' ) != $course[ 'language' ]) || get_conf ( 'showAlwaysLanguageInCourseList', false ) )
    {
        $courseLanguageTxt = (!empty ( $langNameOfLang[ $course[ 'language' ] ] )) ?
            (' &ndash; ' . claro_htmlspecialchars ( ucfirst ( $langNameOfLang[ $course[ 'language' ] ] ) )) :
            (' &ndash; ' . claro_htmlspecialchars ( ucfirst ( $course[ 'language' ] ) ));
    }
    else
    {
        $courseLanguageTxt = '';
    }

    // Display the course title following the platform configuration requirements
    $courseTitle = (get_conf ( 'course_order_by' ) == 'official_code') ?
        ($course[ 'officialCode' ] . ' - ' . $course[ 'title' ]) :
        ($course[ 'title' ] . ' (' . $course[ 'officialCode' ] . ')');

    $url = get_path ( 'url' ) . '/claroline/course/index.php?cid='
        . claro_htmlspecialchars ( $course[ 'sysCode' ] );

    // Display an icon following the course's access settings
    $iconUrl = ($displayIconAccess) ?
        (get_course_access_icon ( $course[ 'access' ] )) :
        (get_icon_url ( 'course' ));

    // Display course's manager email
    $managerString = (isset ( $course[ 'email' ] ) && claro_is_user_authenticated ()) ?
        ('<a href="mailto:' . $course[ 'email' ] . '">' . $course[ 'titular' ] . '</a>') :
        ( $course[ 'titular' ] . $courseLanguageTxt );

    // Don't give a link to the course if the user is in pending state
    $isUserPending = isset ( $course[ 'isPending' ] ) && $course[ 'isPending' ] == 1 ?
        (true) :
        (false);

    $courseLink = '<a href="'
        . claro_htmlspecialchars ( $url ) . '">'
        . claro_htmlspecialchars ( $courseTitle )
        . '</a>'
        . $userStatusImg . "\n";

    if ( $isUserPending )
    {
        $courseLink .= ' [' . get_lang ( 'Pending registration' ) . ']' . "\n";
    }

    // Make a nice explicit sentence about the course's access
    if ( $course[ 'access' ] == 'public' )
    {
        $courseAccess = get_lang ( 'Access allowed to anybody (even without login)' );
    }
    elseif ( $course[ 'access' ] == 'platform' )
    {
        $courseAccess = get_lang ( 'Access allowed only to platform members (user registered to the platform)' );
    }
    elseif ( $course[ 'access' ] == 'private' )
    {
        $courseAccess = get_lang ( 'Access allowed only to course members (people on the course user list)' );
    }
    else
    {
        $courseAccess = $course[ 'access' ];
    }


    $out .= '<dt>' . "\n"
        . '<img class="qtip iconDefinitionList" src="' . $iconUrl . '" alt="' . $courseAccess . '" /> '
        . '<span' . (!empty ( $classItem ) ? ' class="' . $classItem . '"' : '') . '>' . $courseLink . '</span>' . "\n"
        . '</dt>' . "\n"
        . '<dd>' . "\n"
        . '<span class="managerString">' . $managerString . "</span>\n";

    if ( !empty ( $course[ 'sessionCourses' ] ) )
    {
        $out .= '<dl>' . "\n";

        foreach ( $course[ 'sessionCourses' ] as $sessionCourse )
        {
            $out .= render_course_in_dl_list ( $sessionCourse, $sessionCourse[ 'hot' ] ) . "\n";
        }

        $out .= '</dl>' . "\n";
    }

    $out .= '</dd>' . "\n\n";

    return $out;
}

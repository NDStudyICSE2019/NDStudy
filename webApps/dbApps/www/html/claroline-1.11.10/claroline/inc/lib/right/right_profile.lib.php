<?php // $Id: right_profile.lib.php 13302 2011-07-11 15:19:09Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Library profile.
 *
 * @version     $Revision: 13302 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     RIGHT
 * @author      Claro Team <cvs@claroline.net>
 */

require_once 'constants.inc.php';
require_once 'courseProfileToolAction.class.php';

/**
 * Get all names of profile in an array where key are profileId
 * return array assoc profileId => profileName
 */

function claro_get_all_profile_name_list ()
{
    $profileList = null;

    static $cachedProfileList = null ;

    if ( $cachedProfileList )
    {
        $profileList = $cachedProfileList;
    }
    else
    {
        $tbl_mdb_names = claro_sql_get_main_tbl();
        $tbl_profile = $tbl_mdb_names['right_profile'];

        $sql = "SELECT profile_id, name, label, description
                FROM `" . $tbl_profile . "`
                ORDER BY profile_id ";

        $result = claro_sql_query_fetch_all($sql);

        foreach ( $result as $profile)
        {
            $profile_id = $profile['profile_id'];
            $profileList[$profile_id]['name'] = $profile['name'];
            $profileList[$profile_id]['label'] = $profile['label'];
            $profileList[$profile_id]['description'] = $profile['description'];
        }

        $cachedProfileList = $profileList ; // cache for the next time ...
    }

    return $profileList ;
}

/**
 * Get profileId
 */

function claro_get_profile_id ($profileLabel)
{
    $profileList = claro_get_all_profile_name_list();

    foreach ( $profileList as $profileId => $profileInfo)
    {
        if ( $profileInfo['label'] ==  $profileLabel )
        {
            return $profileId;
        }
    }
    return false;
}

/**
 * Get profileName
 * @param integer $profileId profile identifier
 * @return array ['tool_id']['action_name'] value
 */

function claro_get_profile_name ($profileId)
{
    $profileList = claro_get_all_profile_name_list();

    if ( isset($profileList[$profileId]['name']) )
    {
        return get_lang($profileList[$profileId]['name']);
    }
    else
    {
        return false;
    }
}

/**
 * Get profileName
 * @param integer $profileId profile identifier
 * @return array ['tool_id']['action_name'] value
 */

function claro_get_profile_label ($profileId)
{
    $profileList = claro_get_all_profile_name_list();

    if ( isset($profileList[$profileId]['label']) )
    {
        return $profileList[$profileId]['label'];
    }
    else
    {
        return false;
    }
}

/**
 * Get course/profile right
 *
 * @param integer $profileId profile identifier
 * @param integer $courseId course identifier
 * @return array ['tool_id']['action_name'] value
 */

function claro_get_course_profile_right ($profileId = null, $courseId = null)
{
    $courseProfileRightList = null;

    static $cachedProfileId = null ;
    static $cachedCourseId = null ;
    static $cachedCourseProfileRightList = null ;

    // load courseId
    if ( is_null($courseId) )
    {
        if ( claro_is_in_a_course() ) $courseId = claro_get_current_course_id();
        else                             return false ;
    }

    // load profile id
    if ( is_null($profileId) )
    {
        if ( !empty($GLOBALS['_profileId']) ) $profileId = $GLOBALS['_profileId'];
        else                                  return false ;
    }

    if ( !empty($cachedCourseProfileRightList) &&
         ( $cachedProfileId == $profileId ) &&
         ( $cachedCourseId == $courseId )
       )
    {
        $courseProfileRightList = $cachedCourseProfileRightList;
    }

    if ( empty($courseProfileRightList) )
    {
        $profile = new RightProfile();

        if ( $profile->load($profileId) )
        {
            $courseProfileToolRight = new RightCourseProfileToolRight();
            $courseProfileToolRight->setCourseId($courseId);
            $courseProfileToolRight->load($profile);

            $courseProfileRightList = $courseProfileToolRight->getToolActionList();

            // cache for the next time ...
            $cachedProfileId = $profileId;
            $cachedCourseId = $courseId;
            $cachedCourseProfileRightList = $courseProfileRightList;
        }
        else
        {
            return false;
        }
    }

    return $courseProfileRightList ;
}

/**
 * Is tool action allowed
 *
 * @param string $actionName name of the action
 * @param integer $tid tool identifier
 * @param integer $profileId profile identifier
 * @param string $courseId course identifier
 * @return boolean 'true' if it's allowed
 */

function claro_is_allowed_tool_action ($actionName, $tid = null, $profileId = null, $courseId = null)
{
    global $_mainToolId;
    global $_profileId;

    // load tool id
    if ( is_null($tid) )
    {
        if ( !empty($_mainToolId) ) $tid = $_mainToolId ;
        else                        return false ;
    }

    // load profile id
    if ( is_null($profileId) )
    {
        if ( !empty($_profileId) ) $profileId = $_profileId ;
        else                        return false ;
    }

    // load course id
    if ( is_null($courseId) )
    {
        if ( claro_is_in_a_course() ) $courseId = claro_get_current_course_id() ;
        else                 return false ;
    }

    // FIXME
    if ( claro_is_platform_admin() ) return true;

    // get course profile right
    $courseProfileRight = claro_get_course_profile_right($profileId,$courseId);

    // return value for tool/action
    if ( isset($courseProfileRight[$tid][$actionName]) )
    {
        return $courseProfileRight[$tid][$actionName];
    }
    else
    {
        return false;
    }
}

/**
 * Is tool read action allowed
 *
 * @param string $actionName name of the action
 * @param integer $tid tool identifier
 * @param integer $profileId profile identifier
 * @param string $courseId course identifier
 * @return boolean 'true' if it's allowed
 */

function claro_is_allowed_tool_read ($tid = null, $profileId = null, $courseId = null)
{
    if ( claro_is_tool_activated($tid,$courseId) )
    {
        if ( claro_is_allowed_tool_action('read',$tid,$profileId,$courseId) )
        {
            if ( claro_is_tool_visible($tid,$courseId) )
            {
                return true ;
            }
            else
            {
                // if tool isn't visible, only user with edit right have read right
                if ( claro_is_allowed_tool_edit($tid,$profileId,$courseId) )
                {
                    return true ;
                }
                else
                {
                    return false ;
                }
            }
        }
        else
        {
            // no read right
            return false ;
        }
    }
    else
    {
        // tool deactivated
        return false;
    }
}

/**
 * Is tool edit action allowed
 *
 * @param string $actionName name of the action
 * @param integer $tid tool identifier
 * @param integer $profileId profile identifier
 * @param string $courseId course identifier
 * @return boolean 'true' if it's allowed
 */

function claro_is_allowed_tool_edit ($tid = null, $profileId = null, $courseId = null)
{
    if ( claro_is_tool_activated($tid,$courseId) )
    {
        return claro_is_allowed_tool_action('edit',$tid,$profileId,$courseId);
    }
    else
    {
        return false ;
    }
}

/**
 * Is tool activate
 *
 * @param integer $tid tool identifier
 * @param string courseId
 *
 * @return boolean 'true' if it's activated
 */

function claro_is_tool_activated ($tid, $courseId)
{
    global $_mainToolId;

    static $activation = false;
    static $toolCourseActivation = false;

    // load tool id
    if ( is_null($tid) )
    {
        if ( !empty($_mainToolId) ) $tid = $_mainToolId ;
        else                        return false ;
    }

    // load course id
    if ( is_null($courseId) )
    {
        if ( claro_is_in_a_course() ) $courseId = claro_get_current_course_id() ;
        else                 return false ;
    }

    // tool platform activation cache
    if ( !$activation )
    {
        $activation = array();

        $tbl_mdb_names = claro_sql_get_main_tbl();

        $sql = " SELECT t.id, m.activation
                 FROM `" . $tbl_mdb_names['module'] . "` as m,
                      `" . $tbl_mdb_names['tool'] . "` as t
                 WHERE t.claro_label = m.label";

        $tool_activationTmp = claro_sql_query_fetch_all_rows($sql);

        foreach ( $tool_activationTmp as $tool )
        {
            $activation[$tool['id']] = $tool['activation'];
        }
    }

    $tool_activation = $activation[$tid];

    if ( $tool_activation == 'activated' )
    {
        if ( claro_is_in_a_course())
        {
            // tool course activation cache
            if ( ! $toolCourseActivation )
            {
                $tbl_cdb_names = claro_sql_get_course_tbl();

                $sql = " SELECT ctl.tool_id AS tool_id, ctl.activated AS activated
                     FROM `" . $tbl_cdb_names['tool'] . "` as ctl ";

                $tools_activatedInCourse = claro_sql_query_fetch_all_rows($sql);

                $toolCourseActivation = array();

                foreach ( $tools_activatedInCourse as $tool_activatedInCourse )
                {
                    $toolCourseActivation[$tool_activatedInCourse['tool_id']] = $tool_activatedInCourse['activated'];
                }
            }

            return isset($toolCourseActivation[$tid]) && $toolCourseActivation[$tid]  == 'true';
        }

        return true;
    }
    else
    {
        return false;
    }

}

/**
 * Is tool visible
 *
 * @param integer $tid tool identifier
 * @param string courseId
 * @todo to move in a lib
 *
 * @return boolean 'true' if it's visible
 */

function claro_is_tool_visible ($tid, $courseId)
{
    global $_mainToolId;

    static $toolVisibilityCache = false;

    // load tool id
    if ( is_null($tid) )
    {
        if ( !empty($_mainToolId) ) $tid = $_mainToolId ;
        else                        return false ;
    }

    // load course id
    if ( is_null($courseId) )
    {
        if ( claro_is_in_a_course() ) $courseId = claro_get_current_course_id() ;
        else                 return false ;
    }

    if ( !$toolVisibilityCache )
    {
        $toolVisibilityCache = array();

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($courseId));
        $sql = " SELECT tool_id, visibility
                 FROM `" . $tbl_cdb_names['tool'] . "`";

        $tool_visibilityTmp = claro_sql_query_fetch_all_rows($sql);

        foreach ( $tool_visibilityTmp as $tool )
        {
            $toolVisibilityCache[$tool['tool_id']] = $tool['visibility'];
        }

    }

    return (boolean) $toolVisibilityCache[$tid] ;
}

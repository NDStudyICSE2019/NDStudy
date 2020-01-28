<?php // $Id: init.lib.php 14315 2012-11-08 14:51:17Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Get and set value of current session.
 *
 * @version     1.9 $Revision: 14315 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      see 'credits' file
 * @since       claroline 1.8.3
 * @package     KERNEL
 *
 */


/// GET VALUES FROM INIT
/// 5 types of  values/Function
/// 1. Is in the context
/// 2. Get ID
/// 3. Get Values/properties
/// 4. Get right
/// 5. get_init : generic function  to prepare 4st previous during  developpement
/// 6. read data in DB

/// 1. Is in the context

/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
function claro_is_user_authenticated()
{
    return ! is_null($GLOBALS['_uid']);
}

/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
// replace is_null(claro_get_current_course_id())
function claro_is_in_a_course()
{
    return ! is_null(claro_get_current_course_id());
}

/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
// replace is_null(claro_get_current_course_id())
function claro_is_in_a_group()
{
    return isset($GLOBALS['_gid']) && ! is_null($GLOBALS['_gid']);
}

/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
// replace is_null(get_current_tool_id()) , isset($_tid) ....
function claro_is_in_a_tool()
{
    if (!isset($GLOBALS['_tid'])) $GLOBALS['_tid']=null;
    return ! is_null($GLOBALS['_tid']);
}

/// 2. Get ID


/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
function claro_get_current_user_id()
{
    return get_init('_uid');
}

/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function claro_get_current_course_id()
{
    return get_init('_cid');
}

/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function claro_get_current_group_id()
{
    return get_init('_gid');
}

/**
 * Return the init status
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function claro_get_current_tool_id()
{
    return get_init('_tid');
}

/**
 * Returns the label of the current tool module
 * FIXME: non-course module hack
 * FIXME: applet module hack
 * FIXME: switch tlbelReq and _courseTool lookup ?
 * @return string module label
 *         boolean false if no module currently in use or not in a tool module
 */
function get_current_module_label()
{
    return Claroline::getInstance()->currentModuleLabel();
}

/**
 * Set the current module label at run time
 *  @param string label module label
 *  @return string old label
 *          boolean false if no old label defined
 *  FIXME : use it in docks and kernel
 */
function set_current_module_label( $label )
{
    $old = get_current_module_label();
    
    Claroline::getInstance()->pushModuleLabel( $label );
    
    return $old;
}

/**
 * Unset the current module label at run time
 *  (Warning : does not alter tlabelReq or _courseTool)
 *  @param string label module label
 *  @return string old label or false if no old label defined
 */
function clear_current_module_label()
{
    return Claroline::getInstance()->popModuleLabel();
}

/**
 * Return the value of a Claroline configuration parameter
 * @param string $param config parameter
 * @param mixed $default (optionnal) - set a defaut to return value
 *                                     if no paramater with such a name is found.
 * @author Christophe Gesche <moosh@claroline.net>
 * @return string param value
 * @todo http://www.claroline.net/forum/viewtopic.php?t=4579
*/

/// 3. Get Values/properties

/**
 * Return data of the current course
 *
 * @param string or null $dataName name of field, or null to keep an array of all fields
 * @author Christophe Gesche <moosh@claroline.net>
 * @return string or array of string
 */
function claro_get_current_course_data($dataName=null)
{
    $c = get_init('_course');
    if (is_null($dataName)) return $c;
    elseif (is_array($c) && array_key_exists($dataName,$c)) return $c[$dataName];
    else
    {
        pushClaroMessage( __FUNCTION__ . ' : ' . claro_htmlspecialchars($dataName) . ' does not exist for course data','warning');
        return null;
    }
}

/**
 * Return group properties for the current course
 *
 * @param string or null $dataName property name, or null to keep an array of all properties
 * @author Christophe Gesche <moosh@claroline.net>
 * @return array of all value or value of given property name
 */
function claro_get_current_group_properties_data($dataName=null)
{
    $gp = get_init('_groupProperties');
    if (is_null($dataName)) return $gp;
    elseif (is_array($gp) && array_key_exists($dataName,$gp)) return $gp[$dataName];
    else
    {
        pushClaroMessage( ' -' . $dataName . '- does not exist for group properties data','error');
        return null;
    };



}

/**
 * Return data of the current user
 *
 * @param string or null $dataName name of field, or null to keep an array of all fields
 * @author Christophe Gesche <moosh@claroline.net>
 * @return string or array of string
 */

function claro_get_current_user_data($dataName=null)
{
    $u = get_init('_user');
    if (is_null($dataName)) return $u;
    elseif (is_array($u) && array_key_exists($dataName,$u)) return $u[$dataName];
    else
    {
        pushClaroMessage( ' -' . $dataName . '- does not exist for user data','error');
        return null;
    };
}

/**
 * Return properties for the current group
 *
 * @param string or null $dataName property name, or null to keep an array of all properties
 * @author Christophe Gesche <moosh@claroline.net>
 * @return array of all value or value of given property name
 */
function claro_get_current_group_data($dataName=null)
{
    $g = get_init('_group');
    if (is_null($dataName)) return $g;
    elseif (is_array($g) && array_key_exists($dataName,$g)) return $g[$dataName];
    else
    {
        pushClaroMessage( ' -' . $dataName . '- does not exist for group data','error');
        return null;
    };

}

/**
 * Return properties for the current user in the current course
 *
 * @param string or null $dataName property name, or null to keep an array of all properties
 * @author Christophe Gesche <moosh@claroline.net>
 * @return array of all value or value of given property name
 */

function claro_get_current_course_user_data($dataName=null)
{
    // $cu = claro_get_course_user_data(claro_get_current_course_id(),claro_get_current_user_id());
    // not very ready because claro_get_course_user_privilege are in another array

    $cu = get_init('_courseUser');

    if (is_null($dataName)) return $cu;
    elseif (is_array($cu) && array_key_exists($dataName,$cu)) return $cu[$dataName];
    else
    {
        pushClaroMessage( ' -' . $dataName . '- does not exist for course user relation data','error');
        return null;
    };
}

/**
 * Return properties for the current tool
 *
 * @param string or null $dataName property name, or null to keep an array of all properties
 * @author Christophe Gesche <moosh@claroline.net>
 * @return array of all value or value of given property name
 */

function claro_get_current_course_tool_data($dataName=null)
{
    $ct = get_init('_courseTool');
    if (is_null($dataName)) return $ct;
    elseif (is_array($ct) && array_key_exists($dataName,$ct)) return $ct[$dataName];
    else
    {
        pushClaroMessage( ' -' . $dataName . '- does not exist for course tool relation data' ,'error');
        return null;
    };
}

/**
 * Return tool listbfor the current course
 *
 * @param string or null $dataName property name, or null to keep an array of all properties
 * @author Christophe Gesche <moosh@claroline.net>
 * @return array of all value or value of given property name
 */

function claro_get_current_course_tool_list_data($dataName=null)
{
    $ctl = get_init('_courseToolList');
    
    if ( is_null($dataName) )
    {
        return $ctl;
    }
    elseif ( is_array($ctl) && array_key_exists($dataName,$ctl) )
    {
        return $ctl[$dataName];
    }
    else
    {
        pushClaroMessage(claro_html_debug_backtrace(),'error');
        pushClaroMessage( ' -' . $dataName . '- does not exist for course tool list data','error');
        return null;
    };

}

/// 4. Get right

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function claro_is_course_member()
{
    return get_init('is_courseMember');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return
boolean
 */
function claro_is_course_tutor()
{
    return get_init('is_courseTutor');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
function  claro_is_platform_admin()
{
    return get_init('is_platformAdmin');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
function  claro_is_course_admin()
{
    pushClaroMessage('use claro_is_course_manager() instead of claro_is_course_admin()','code review');
    return claro_is_course_manager();
}


/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */
function  claro_is_course_manager()
{
    return get_init('is_courseAdmin');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function claro_is_course_allowed()
{
    return get_init('is_courseAllowed') && claro_is_course_enable();
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function  claro_is_allowed_to_create_course()
{
    return get_init('is_allowedCreateCourse');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function  claro_is_group_member()
{
    return get_init('is_groupMember');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function  claro_is_group_tutor()
{
    return get_init('is_groupTutor');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function  claro_is_group_allowed()
{
    return is_null(get_init('is_groupAllowed'))? false : get_init('is_groupAllowed');
}

/**
 * Return the right of the current user
 *
 * @author Christophe Gesche <moosh@claroline.net>
 * @return boolean
 */

function claro_is_tool_allowed()
{
    return get_init('is_toolAllowed');
}

function claro_is_module_allowed()
{
    if ( ! array_key_exists( 'tlabelReq', $GLOBALS ) )
    {
        return claro_failure::set_failure('MISSING TOOL LABEL');
    }
    
    $moduleLabel = $GLOBALS['tlabelReq'];
    
    $moduleData = get_module_data( $moduleLabel );

    if ( $moduleData['type'] == 'tool' )
    {
        $contextList = get_module_context_list( $moduleLabel );
        
        // pushClaroMessage(var_export(iterator_to_array($contextList), true),'kernel');
        
        if ( !claro_is_in_a_course() && in_array( 'platform', iterator_to_array($contextList) ) )
        {
            return get_module_data( $moduleLabel,'activation' ) == 'activated';
        }
        
        // if a course tool, use claro_is_tool_allowed
        return claro_is_tool_allowed();
    }
    else
    {
        // if an applet "tool", return true if activated
        // and let module manage it's access by itself
        return ( $moduleData['activation'] == 'activated' );
    }
}

// 5. Generic get_init
/**
 * Return the value of a Claroline configuration parameter
 * @param string $param config parameter
 * @param mixed $default (optionnal) - set a defaut to return value
 *                                     if no paramater with such a name is found.
 * @author Christophe Gesche <moosh@claroline.net>
 * @return string param value
 * @todo http://www.claroline.net/forum/viewtopic.php?t=4579
 */

function get_init($param)
{
    static $initValueList = array( '_uid'                   // claro_get_current_user_id()
    , '_cid'                   // claro_get_current_course_id()
    , '_gid'                   // claro_get_current_group_id()
    , '_tid'                   // claro_get_current_tool_id()
    , 'is_authenticated'       // is_authenticated()
    , 'in_course_context'      // is_in_course_context()
    , 'in_group_context'       // is_in_group_context()
    , 'is_platformAdmin'       // claro_is_platformAdmin()
    , '_course'                // claro_get_current_course_data(field=all)
    , '_user'                  // claro_get_current_user_data(field=all)
    , '_group'                 // claro_get_current_group_data(field=all)
    , '_groupProperties'       // claro_get_current_group_properties_data(field=all)
    , '_courseUser'            // claro_get_current_course_user_data(field=all)
    , '_courseTool'            // claro_get_current_course_tool_data(field=all)
    , '_courseToolList'        // claro_get_current_course_tool_list_data(field=all)
    , 'is_courseMember'        // claro_is_course_member()
    , 'is_courseTutor'         // claro_is_course_tutor()
    , 'is_courseAdmin'         // claro_is_course_admin()
    , 'is_courseAllowed'       // claro_is_course_allowed()
    , 'is_allowedCreateCourse' // claro_is_allowed_to_create_course() or claro_is_course_creator()
    , 'is_groupMember'         // claro_is_groupMember()
    , 'is_groupTutor'          // claro_is_groupTutor()
    , 'is_groupAllowed'        // claro_is_groupAllowed()
    , 'is_toolAllowed'         // claro_is_toolAllowed()
    , 'calledFrom'             // claro_called_from()
    );

    if(!in_array($param, $initValueList )) trigger_error( claro_htmlentities($param) . ' is not a know init value name ', E_USER_NOTICE);
    //TODO create a real auth function to eval this state
    if ( $param == 'is_authenticated') return !(bool) is_null($GLOBALS['_uid']);
    //TODO create a real course function to eval this state
    if ( $param == 'in_course_context') return !(bool) is_null(claro_get_current_course_id());
    if     ( array_key_exists($param,$GLOBALS) )  return $GLOBALS[$param];
    elseif ( defined($param)         )            return constant($param);
    return null;
}


/// 6. read data in DB

/**
 * Fetch datas of the given user in the given course
 *
 * @param string $cid course id
 * @param integer $uid user id
 * @param bool $ignoreCache true to for read in database instead of cache
 * @return array('role')
 * @author Christophe Gesche <moosh@claroline.net>
 */

function claro_get_course_user_data($cid,$uid,$ignoreCache=false)
{
    $properties = claro_get_course_user_properties($cid,$uid,$ignoreCache);
    return $properties['data'];
}

/**
 * Fetch privileges of the given user in the given course
 *
 * @param string $cid course id
 * @param integer $uid user id
 * @param bool $ignoreCache true to for read in database instead of cache
 * @return array('_profileId','is_courseMember','is_courseTutor','is_courseAdmin')
 * @author Christophe Gesche <moosh@claroline.net>
 */

function claro_get_course_user_privilege($cid,$uid,$ignoreCache=false)
{
    $properties = claro_get_course_user_properties($cid,$uid,$ignoreCache);
    return $properties['privilege'];
}


/**
 * Fetch data and privileges of the given user in the given course
 *
 * U don't have enough of this function
 * use claro_get_course_user_data($cid,$uid,$ignoreCache=false)
 *  or claro_get_course_user_privilege($cid,$uid,$ignoreCache=false)
 *
 * @param string $cid course id
 * @param integer $uid user id
 * @param bool $ignoreCache true to for read in database instead of cache
 * @return array(data( array('role')), 'privilege'(array('_profileId','is_courseMember','is_courseTutor','is_courseAdmin')))
 * @see claro_get_course_user_data($cid,$uid,$ignoreCache=false)
 * @see claro_get_course_user_privilege($cid,$uid,$ignoreCache=false)
 * @author Christophe Gesche <moosh@claroline.net>
 */


function claro_get_course_user_properties($cid,$uid,$ignoreCache=false)
{
    $admin = claro_is_platform_admin();
    
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];
    
    static $course_user_cache       = null;
    static $course_user_data        = null;
    static $course_user_privilege   = array();
    
    if (($course_user_cache != array('uid'=>$uid,'cid'=>$cid)) || $ignoreCache)
    {
        $sql = "SELECT profile_id AS profileId,
                       isCourseManager,
                       isPending,
                       tutor,
                       role
                FROM `" . $tbl_rel_course_user . "` `cours_user`
                WHERE `user_id`  = '" . (int) $uid . "'
                AND `code_cours` = '" . claro_sql_escape($cid) . "'";
        
        $cuData = claro_sql_query_get_single_row($sql);
        
        if ( !empty($cuData) )
        {
            $course_user_data['role'] = $cuData['role']; // not used
            
            $course_user_privilege['_profileId']        = $cuData['profileId'];
            $course_user_privilege['is_coursePending']  = (bool) ($cuData['isPending' ]);
            $course_user_privilege['is_courseMember']   = (bool) ($cuData['isPending' ] == 0 );
            $course_user_privilege['is_courseTutor']    = (bool) ($cuData['tutor'] == 1 );
            $course_user_privilege['is_courseAdmin']    = (bool) ($cuData['isCourseManager'] == 1 );
        }
        else // this user has no status related to this course
        {
            $course_user_privilege['_profileId']        = claro_get_profile_id('guest');
            $course_user_privilege['is_coursePending']  = false;
            $course_user_privilege['is_courseMember']   = false;
            $course_user_privilege['is_courseAdmin']    = false;
            $course_user_privilege['is_courseTutor']    = false;
            
            $course_user_data = null; // not used
        }
        
        $course_user_privilege['is_courseAdmin'] = (bool) ($course_user_privilege['is_courseAdmin'] || claro_is_platform_admin());
        $course_user_cache = array('uid'=>$uid,'cid'=>$cid);
    }
    
    return array('data' => $course_user_data, 'privilege' => $course_user_privilege);
}

/**
 * Get the profile id of the current user in a given course
 * @param   string $courseId id (sysCode) of the course
 * @return  int id of the current user's profile in the course
 */
function claro_get_current_user_profile_id_in_course( $courseId = null )
{
    $courseId = empty( $courseId ) ? claro_get_current_course_id() : $courseId;
    
    $userPrivilege = claro_get_course_user_privilege(
        $courseId,
        claro_get_current_user_id() );
    
    return $userPrivilege['_profileId'];
}

/**
 * Get the context from where the page is called
 * e.g.: if a tool is called from another tool
 * @return mixed tlabel or false if not called from a specific tool or whatever
 */
function claro_called_from()
{
    return get_init('calledFrom');
}

/**
 * Return if course status is enable
 */
function claro_is_course_enable()
{
    $tbl_mdb_names      = claro_sql_get_main_tbl();
    $tbl_course         = $tbl_mdb_names['course'];
    $courseId           = claro_get_current_course_id();
    $curdate            = claro_mktime();
    
    if (claro_is_course_manager())
    $sql=" SELECT c.`code`
             FROM `".$tbl_course."` c
            WHERE  (c.`status` != 'trash')
              AND c.`code` = '".$courseId."';";
    else
    $sql=" SELECT c.`code`
           FROM `".$tbl_course."` c
           WHERE (c.`status` = 'enable'
                   OR (c.`status` = 'date'
                       AND (UNIX_TIMESTAMP(`creationDate`) <= '". $curdate ."'
                            OR `creationDate` IS NULL OR UNIX_TIMESTAMP(`creationDate`) = 0
                           )
                       AND ('". $curdate ."' <= UNIX_TIMESTAMP(`expirationDate`)
                            OR `expirationDate` IS NULL
                           )
                      )
                 )
                 AND c.`code` = '".$courseId."';";
    
    $result = claro_sql_query_get_single_value($sql);
    
    if (isset($result) OR claro_is_platform_admin()) $return = true;
    else $return = false;
    
    return $return;
}
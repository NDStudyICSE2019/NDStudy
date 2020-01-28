<?php // $Id: claro_init_local.inc.php 14327 2012-11-16 09:43:24Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

//----------------------------------------------------------------------
// CLAROLINE
//----------------------------------------------------------------------
// Copyright (c) 2001-2012 Universite catholique de Louvain (UCL)
//----------------------------------------------------------------------
// This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
// as published by the FREE SOFTWARE FOUNDATION. The GPL is available
// through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
//----------------------------------------------------------------------
// Authors: see 'credits' file
//----------------------------------------------------------------------

/*******************************************************************************
 *
 *                             SCRIPT PURPOSE
 *
 * This script initializes and manages main Claroline session informations. It
 * keeps available session informations always up to date.
 *
 * You can request a course id. It will check if the course Id requested is the
 * same as the current one. If it isn't it will update session information from
 * the database. You can also force the course reset if you want ($cidReset).
 *
 * All the course informations are store in the $_course array.
 *
 * You can request a group id. It will check if the group Id requested is the
 * same as the current one. If it isn't it will update session information from
 * the database. You can also force the course reset if you want ($gidReset).
 *
 * All the current group information are stored in the $_group array
 *
 * The course id is stored in $_cid session variable.
 * The group  id is stored in $_gid session variable.
 *
 *
 *                    VARIABLES AFFECTING THE SCRIPT BEHAVIOR
 *
 * string  $login
 * string  $password
 * boolean $logout
 *
 * string  $cidReq   : course Id requested
 * boolean $cidReset : ask for a course Reset, if no $cidReq is provided in the
 *                     same time, all course informations is removed from the
 *                     current session
 *
 * int     $gidReq   : group Id requested
 * boolean $gidReset : ask for a group Reset, if no $gidReq is provided in the
 *                     same time, all group informations is removed from the
 *                     current session
 *
 * int     $tidReq   : tool Id requested
 * boolean $tidReset : ask for a tool reset, if no $tidReq or $tlabelReq is
 *                     provided  in the same time, all information concerning
 *                     the current tool is removed from the current sesssion
 *
 * $tlabelReq        : more generic call to a tool. Each tool are identified by
 *                     a unique id into the course. But tools which are part of
 *                     the claroline release have also an generic label.
 *                     Tool label and tool id are decoupled. It means that one
 *                     can have several token of the same tool with different
 *                     settings in the same course.
 *
 *                   VARIABLES SET AND RETURNED BY THE SCRIPT
 *
 * Here is resumed below all the variables set and returned by this script.
 *
 * USER VARIABLES
 *
 * int $_uid (the user id)
 *
 * string  $_user ['firstName']
 * string  $_user ['lastName' ]
 * string  $_user ['mail'     ]
 * string  $_user ['officialEmail'     ]
 * string  $_user ['lastLogin']
 *
 * boolean $is_platformAdmin
 * boolean $is_allowedCreateCourse
 *
 * COURSE VARIABLES
 *
 * string  $_cid (the course id)
 *
 * string  $_course['name'        ]
 * string  $_course['officialCode']
 * string  $_course['sysCode'     ]
 * string  $_course['path'        ]
 * string  $_course['dbName'      ]
 * string  $_course['dbNameGlu'   ]
 * string  $_course['titular'     ]
 * string  $_course['language'    ]
 * string  $_course['extLinkUrl'  ]
 * string  $_course['extLinkName' ]
 * string  $_course['categoryCode']
 * string  $_course['categoryName']
 *
 * PROPERTIES IN ALL GROUPS OF THE COURSE
 *
 * boolean $_groupProperties ['registrationAllowed']
 * boolean $_groupProperties ['private'            ]
 * int     $_groupProperties ['nbGroupPerUser'     ]
 * boolean $_groupProperties ['tools'] ['CLFRM']
 * boolean $_groupProperties ['tools'] ['CLDOC']
 * boolean $_groupProperties ['tools'] ['CLWIKI']
 * boolean $_groupProperties ['tools'] ['CLCHT']
 *
 * REL COURSE USER VARIABLES
 * int     $_profileId
 * string  $_courseUser['role']
 * boolean $is_courseMember
 * boolean $is_courseTutor
 * boolean $is_courseAdmin
 *
 * REL COURSE GROUP VARIABLES
 *
 * int     $_gid (the group id)
 *
 * string  $_group ['name'       ]
 * string  $_group ['description']
 * int     $_group ['tutorId'    ]
 * int     $_group ['forumId'    ]
 * string  $_group ['directory'  ]
 * int     $_group ['maxMember'  ]
 *
 * boolean $is_groupMember
 * boolean $is_groupTutor
 * boolean $is_groupAllowed
 *
 * TOOL VARIABLES
 *
 * int $_tid
 *
 * string $_courseTool['label'         ]
 * string $_courseTool['name'          ]
 * string $_courseTool['visibility'    ]
 * string $_courseTool['url'           ]
 * string $_courseTool['icon'          ]
 * string $_courseTool['access_manager']
 *
 * REL USER TOOL COURSE VARIABLES
 * boolean $is_toolAllowed
 *
 * LIST OF THE TOOLS AVAILABLE FOR THE CURRENT USER
 *
 * int     $_courseToolList[]['id'            ]
 * string  $_courseToolList[]['label'         ]
 * string  $_courseToolList[]['name'          ]
 * string  $_courseToolList[]['visibility'    ]
 * string  $_courseToolList[]['icon'          ]
 * string  $_courseToolList[]['access_manager']
 * string  $_courseToolList[]['url'           ]
 *
 *
 *                       IMPORTANT ADVICE FOR DEVELOPERS
 *
 * We strongly encourage developers to use a connection layer at the top of
 * their scripts rather than use these variables, as they are, inside the core
 * of their scripts. It will make Claroline code maintenance much easier.
 *
 * For example, a common practice is to connect the user status with action
 * permission flag at the top of the script like this :
 *
 *     $is_allowedToEdit = $is_courseAdmin
 *
 *
 *                               SCRIPT STRUCTURE
 *
 * 1. The script determines if there is an authentication attempt. This part
 * only chek if the login name and password are valid. Afterwards, it set the
 * $_uid (user id) and the $uidReset flag. Other user informations are retrieved
 * later. It's also in this section that optional external authentication
 * devices step in.
 *
 * 2. The script determines what other session informations have to be set or
 * reset, setting correctly $cidReset (for course) and $gidReset (for group).
 *
 * 3. If needed, the script retrieves the other user informations (first name,
 * last name, ...) and stores them in session.
 *
 * 4. If needed, the script retrieves the course information and stores them
 * in session
 *
 * 5. The script initializes the user status and permission for current course
 *
 * 6. If needed, the script retrieves group informations an store them in
 * session.
 *
 * 7. The script initializes the user status and permission for the current group.
 *
 * 8. The script initializes the user status and permission for the current tool
 *
 * 9. The script get the list of all the tool available into the current course
 *    for the current user.
 ******************************************************************************/

require_once dirname(__FILE__) . '/lib/auth/authmanager.lib.php';
require_once dirname(__FILE__) . '/lib/kernel/user.lib.php';
require_once dirname(__FILE__) . '/lib/kernel/course.lib.php';
require_once dirname(__FILE__) . '/lib/kernel/groupteam.lib.php';
require_once dirname(__FILE__) . '/lib/user.lib.php';
require_once dirname(__FILE__) . '/lib/core/claroline.lib.php';

// Load authentication config files
require_once claro_get_conf_repository() .  'auth.sso.conf.php';
require_once claro_get_conf_repository() .  'auth.cas.conf.php';
require_once claro_get_conf_repository() .  'auth.extra.conf.php';

// INIT CAS
if ( get_conf('claro_extauth_sso_system','cas') != '' )
{
    $ext_auth_sso_file = realpath(claro_get_conf_repository() . 'auth.' . get_conf('claro_extauth_sso_system','cas') . '.conf.php');

    if ( file_exists($ext_auth_sso_file) )
    {
        require_once $ext_auth_sso_file;
    }
}

/*===========================================================================
  Set claro_init_local.inc.php variables coming from HTTP request into the
  global name space.
 ===========================================================================*/

$AllowedPhpRequestList = array('logout', 'uidReset',
                               'cidReset', 'cidReq',
                               'gidReset', 'gidReq',
                               'tidReset', 'tidReq', 'tlabelReq');

// Cleaning up $GLOBALS to avoid issues with register_globals
foreach($AllowedPhpRequestList as $thisPhpRequestName)
{
    // some claroline scripts set these variables before calling
    // the claro init process. Avoid variable setting if it is the case.

    if ( isset($GLOBALS[$thisPhpRequestName]) )
    {
        continue;
    }

    if ( isset($_REQUEST[$thisPhpRequestName] ) )
    {
        $GLOBALS[$thisPhpRequestName] = $_REQUEST[$thisPhpRequestName];
    }
    else
    {
        $GLOBALS[$thisPhpRequestName] = null;
    }
}

/*
if ( is_null( $cidReset ) )
{
    if ( isset( $cidReq )
        && isset( $_SESSION['_cid'] )
        && $cidReq != $_SESSION['_cid'] )
    {
        $cidReset = true;
    }
    elseif ( isset( $cidReq )
        && !isset( $_SESSION['_cid'] ) )
    {
        $cidReset = true;
    }
    elseif ( !isset( $cidReq )
        && isset( $_SESSION['_cid'] ) )
    {
        $cidReset = true;
    }
}

if ( is_null( $gidReset ) )
{
    if ( isset( $gidReq )
        && isset( $_SESSION['_gid'] )
        && $gidReq != $_SESSION['_gid'] )
    {
        $gidReset = true;
    }
    elseif ( isset( $gidReq )
        && ! isset( $_SESSION['_gid'] ) )
    {
        $gidReset = true;
    }
    elseif ( ! isset( $gidReq )
        && isset( $_SESSION['_gid'] ) )
    {
        $gidReset = true;
    }
}*/

$login    = isset($_REQUEST['login'   ]) ? trim( $_REQUEST['login'   ] ) : null;
$password = isset($_REQUEST['password']) ? trim( $_REQUEST['password'] ) : null;

/*---------------------------------------------------------------------------
  Check authentification
 ---------------------------------------------------------------------------*/

// default variables initialization
$claro_loginRequested = false;
$claro_loginSucceeded = false;
$currentUser = false;

if ( $logout && !empty($_SESSION['_uid']) )
{
    // logout from CAS server
    if ( get_conf('claro_CasEnabled', false) && get_conf('claro_CasGlobalLogout') )
    {
        require get_path('rootSys').'/claroline/auth/extauth/cas/casProcess.inc.php';
    }
    
    // needed to notify that a user has just loggued out
    $logout_uid = $_SESSION['_uid'];
}

if ( ! empty($_SESSION['_uid']) && ! ($login || $logout) )
{
    if (isset($_REQUEST['switchToUser']))
    {
        if (! empty($_SESSION['_user']['isPlatformAdmin']))
        {
            if ((bool) $_SESSION['_user']['isPlatformAdmin'] === true)
            {
                $targetId = $_REQUEST['switchToUser'];

                if (user_is_admin($targetId))
                {
                    exit('ERROR !! You cannot access another administrator account !');
                }
                
                try
                {
                    $currentUser = Claro_CurrentUser::getInstance($targetId, true);
                    $currentUser->saveToSession();
                    
                }
                catch (Exception $ex)
                {
                    exit('ERROR !! Undefined user id: the requested user doesn\'t exist'
                         . 'at line '.__LINE__);
                }
                
                $_SESSION['_uid']             = $targetId;
                $_SESSION['isVirtualUser']    = true;
                $_SESSION['is_platformAdmin'] = $_SESSION['_user']['isPlatformAdmin'];
                $_SESSION['is_allowedCreateCourse'] = $_SESSION['_user']['isCourseCreator'];
            }
        }
    }
    
    // uid is in session => login already done, continue with this value
    $_uid = $_SESSION['_uid'];
    
    $is_platformAdmin = !empty($_SESSION['is_platformAdmin'])
        ? $_SESSION['is_platformAdmin']
        : false
        ;

    $is_allowedCreateCourse = !empty($_SESSION['is_allowedCreateCourse'])
        ? $_SESSION['is_allowedCreateCourse']
        : false
        ;
}
else
{
    // $_uid     = null;   // uid not in session ? prevent any hacking
    $uidReset = false;
    
    // Unset current user authentication :
    if ( isset( $GLOBALS['_uid'] ) )
    {
        unset( $GLOBALS['_uid'] );
    }
    
    if ( isset( $_SESSION['_uid'] ) )
    {
        unset( $_SESSION['_uid'] );
    }
    
    if ( isset( $GLOBALS['_user'] ) )
    {
        unset( $GLOBALS['_user'] );
    }
    
    if ( isset( $_SESSION['_user'] ) )
    {
        unset( $_SESSION['_user'] );
    }
    
    // CAS

    if ( get_conf('claro_CasEnabled', false)
         && isset($_REQUEST['authModeReq'])
         && $_REQUEST['authModeReq'] == 'CAS'
         )
    {
        require get_path('rootSys').'/claroline/auth/extauth/cas/casProcess.inc.php';
    }
    
    // SHIBBOLETH ( PROBABLY BROKEN !!!! )
    
    if ( get_conf('claro_ShibbolethEnabled',false) )
    {
        require get_path('rootSys').'/claroline/auth/extauth/shibboleth/shibbolethProcess.inc.php';
    }

    if ( $login && $password ) // $login && $password are given to log in
    {
        // reinitalize all session variables
        session_unset();

        $claro_loginRequested = true;
        
        try
        {
            $currentUser = AuthManager::authenticate($login, $password);

            if ( $currentUser )
            {
                $_uid = (int)$currentUser->userId;
                $uidReset = true;
                $claro_loginSucceeded = true;
            }
            else
            {
                $_uid = null;
                $claro_loginSucceeded = false;
            }
        }
        catch (Exception $e)
        {
            Console::error("Cannot authenticate user : " . $e->__toString());
            $_uid = null;
            $claro_loginSucceeded = false;
        }
    } // end if $login & password
    else
    {
        $claro_loginRequested = false;
    }
}

/*---------------------------------------------------------------------------
  User initialisation
 ---------------------------------------------------------------------------*/

if ( !empty($_uid) ) // session data refresh requested && uid is given (log in succeeded)
{
    try
    {
        /*if (!$currentUser)
        {
            $currentUser = Claro_CurrentUser::getInstance($_uid);
        }*/
        
        // User login
        if ( $uidReset )
        {
            // Update the current session id with a newly generated one ( PHP >= 4.3.2 )
            // This function is vital in preventing session fixation attacks
            // function_exists('session_regenerate_id') && session_regenerate_id();
        
            $cidReset = true;
            $gidReset = true;
            
            $currentUser = Claro_CurrentUser::getInstance( $_uid, true );
            
            $_user = $currentUser->getRawData();
    
            // Extracting the user data
            $is_platformAdmin = $currentUser->isPlatformAdmin;
            $is_allowedCreateCourse  = ( get_conf('courseCreationAllowed', true) && $currentUser->isCourseCreator ) || $is_platformAdmin;
            
            $currentUser->saveToSession();
    
            if ( $currentUser->firstLogin() )
            {
                // first login for a not self registred (e.g. registered by a teacher)
                // do nothing (code may be added later)
                $currentUser->updateCreatorId();
                $_SESSION['firstLogin'] = true;
            }
            else
            {
                $_SESSION['firstLogin'] = false;
            }
    
            // RECORD SSO COOKIE
            // $ssoEnabled set in conf/auth.sso.conf.php
            if ( get_conf('ssoEnabled',false ))
            {
                FromKernel::uses ( 'sso/cookie.lib' );
                $boolCookie = SingleSignOnCookie::setForUser( $currentUser->userId );
            } // end if ssoEnabled
        }
        // User in session
        else
        {
            $currentUser = Claro_CurrentUser::getInstance($_uid);
            
            try
            {
                $currentUser->loadFromSession();
                $_user = $currentUser->getRawData();
            }
            catch ( Exception $e )
            {
                $_user = null;
            }
        }
    }
    catch ( Exception $e )
    {
        exit('WARNING !! Undefined user id: the requested user doesn\'t exist '
            . 'at line '.__LINE__);
    }
}
else
{
    // Anonymous, logout or login failed
    $_user = null;
    $_uid  = null;
    $is_platformAdmin        = false;
    $is_allowedCreateCourse  = false;
}

/*---------------------------------------------------------------------------
  Course initialisation
 ---------------------------------------------------------------------------*/

// if the requested course is different from the course in session

if ( $cidReq && ( !isset($_SESSION['_cid']) || $cidReq != $_SESSION['_cid'] ) )
{
    $cidReset = true;
    $gidReset = true;    // As groups depend from courses, group id is reset
}

if ( $cidReset ) // course session data refresh requested
{
    if ( $cidReq )
    {
        $_course = claro_get_course_data($cidReq, true);

        if ($_course == false)
        {
            die('WARNING !! The course\'s datas couldn\'t be loaded at line '
                .__LINE__.'.  Please contact your platform administrator.');
        }

        $_cid    = $_course['sysCode'];

        $_groupProperties = claro_get_main_group_properties($_cid);

        if ($_groupProperties == false)
        {
            die('WARNING !! The group\'s properties couldn\'t be loaded at line '
                .__LINE__.'.  Please contact your platform administrator.');
        }
    }
    else
    {
        $_cid    = null;
        $_course = null;

        $_groupProperties ['registrationAllowed'] = false;
        
        $groupToolList = get_group_tool_label_list();
        
        foreach ( $groupToolList as $thisGroupTool )
        {
            $thisGroupToolLabel = $thisGroupTool['label'];
            $propertyList['tools'][$thisGroupToolLabel] = false;
        }
        
        $_groupProperties ['private'] = true;
    }

}
else // else of if($cidReset) - continue with the previous values
{
    $_cid = !empty($_SESSION['_cid'])
        ? $_SESSION['_cid']
        : null
        ;
    
    $_course = !empty($_SESSION['_course'])
        ? $_SESSION['_course']
        : null
        ;
    
    $_groupProperties = !empty($_SESSION['_groupProperties'])
        ? $_SESSION['_groupProperties']
        : null
        ;
}

/*---------------------------------------------------------------------------
  Course / user relation initialisation
 ---------------------------------------------------------------------------*/

if ( $uidReset || $cidReset ) // session data refresh requested
{
    if ( $_uid && $_cid ) // have keys to search data
    {
          $_course_user_properties = claro_get_course_user_properties($_cid,$_uid,true);

          // would probably be less and less used because
          // claro_get_course_user_data($_cid,$_uid)
          // and claro_get_current_course_user_data() do the same job

          $_profileId      = $_course_user_properties['privilege']['_profileId'];
          $is_courseMember = $_course_user_properties['privilege']['is_courseMember'];
          $is_courseTutor  = $_course_user_properties['privilege']['is_courseTutor'];
          $is_courseAdmin  = $_course_user_properties['privilege']['is_courseAdmin'];

          $_courseUser = claro_get_course_user_data($_cid,$_uid);
    }
    else // keys missing => not anymore in the course - user relation
    {
        // course
        $_profileId      = claro_get_profile_id('anonymous');
        $is_courseMember = false;
        $is_courseAdmin  = false;
        $is_courseTutor  = false;

        $_courseUser = null; // not used
    }
    
    $is_courseAllowed = (bool)
    (
        ( $_course['visibility']
          && ( $_course['access'] == 'public'
               || ( $_course['access'] == 'platform'
                    && claro_is_user_authenticated()
                  )
             )
        )
        || $is_courseMember
        || $is_platformAdmin
    ); // here because it's a right and not a state
}
else // else of if ($uidReset || $cidReset) - continue with the previous values
{
    $_profileId = !empty($_SESSION['_profileId'])
        ? $_SESSION['_profileId']
        : false
        ;
    
    $is_courseMember = !empty($_SESSION['is_courseMember'])
        ? $_SESSION['is_courseMember']
        : false
        ;
    
    $is_courseAdmin = !empty($_SESSION['is_courseAdmin'])
        ? $_SESSION['is_courseAdmin']
        : false
        ;
    
    $is_courseAllowed = !empty($_SESSION['is_courseAllowed'])
        ? $_SESSION['is_courseAllowed' ]
        : false
        ;
    
    $is_courseTutor = !empty($_SESSION['is_courseTutor'])
        ? $_SESSION['is_courseTutor']
        : false
        ;
    
    // not used !?!
    $_courseUser = !empty($_SESSION['_courseUser'])
        ? $_SESSION['_courseUser']
        : null
        ;
}

// Installed module in course if available in platform and not in course
if ( $_cid
    && is_array( $_course )
    && isset($_course['dbNameGlu'])
    && !empty($_course['dbNameGlu'])
    && trim($_course['dbNameGlu']) )
{
    // 0. load course configuration to avoid creating uneeded examples
    
    require claro_get_conf_repository() . 'course_main.conf.php';
    
    
    // 1. get tool list from main db
    
    $mainCourseToolList = claro_get_main_course_tool_list();
    
    // 2. get list af already installed tools from course
    
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_tool            = $tbl_mdb_names['tool'           ];

    $sql = " SELECT pct.id                    AS toolId       ,
                  pct.claro_label           AS label

            FROM `".$_course['dbNameGlu']."tool_list` AS ctl
            INNER JOIN `".$tbl_tool."` AS pct
            ON `ctl`.`tool_id` = `pct`.`id`
            WHERE ctl.installed = 'true'";
    
    $courseToolList = claro_sql_query_fetch_all_rows($sql);
    
    $tmp = array();
    
    foreach ( $courseToolList as $thisCourseTool )
    {
        $tmp[$thisCourseTool['label']] = $thisCourseTool['toolId'];
    }
    
    // 3. compare the two lists and register and install/activate missing tool if necessary
    
    $listOfToolsToAdd = array();
    
    foreach ( $mainCourseToolList as $thisToolId => $thisMainCourseTool )
    {
        if ( ! array_key_exists( $thisMainCourseTool['label'], $tmp ) )
        {
            $listOfToolsToAdd[$thisMainCourseTool['label']] = $thisToolId;
        }
    }
    
    foreach ( $listOfToolsToAdd as $toolLabel => $toolId )
    {
        if ( ! is_module_registered_in_course( $toolId, $_cid ) )
        {
            register_module_in_single_course( $toolId, $_cid );
        }
        
        if ( !is_module_installed_in_course( $toolLabel, $_cid )
            && 'AUTOMATIC' == get_module_data( $toolLabel, 'add_in_course' ) )
        {
            install_module_in_course( $toolLabel, $_cid );
        }
        
        if ( 'AUTOMATIC' == get_module_data( $toolLabel, 'add_in_course' ) )
        {
            if ( 'activated' == get_module_data( $toolLabel, 'activation' ) )
            {
                update_course_tool_activation_in_course( $toolId,
                    $_cid,
                    true );
                
                set_module_visibility_in_course( $toolId, $_cid, true );
            }
        }
    }
}

/*---------------------------------------------------------------------------
  Course / tool relation initialisation
 ---------------------------------------------------------------------------*/

// if the requested tool is different from the current tool in session
// (special request can come from the tool id, or the tool label)

if (   ( $tidReq    && $tidReq    != $_SESSION['_tid']                 )
    || ( $tlabelReq && ( ! isset($_SESSION['_courseTool']['label'])
                         || $tlabelReq != $_SESSION['_courseTool']['label']) )
   )
{
    $tidReset = true;
}

if ( $tidReset || $cidReset ) // session data refresh requested
{
    if ( ( $tidReq || $tlabelReq) && $_cid ) // have keys to search data
    {
        $tbl_mdb_names = claro_sql_get_main_tbl();
        $tbl_tool            = $tbl_mdb_names['tool'           ];

        $sql = " SELECT ctl.id                  AS id            ,
                      pct.id                    AS toolId       ,
                      pct.claro_label           AS label         ,
                      ctl.script_name           AS name          ,
                      ctl.visibility            AS visibility    ,
                      pct.icon                  AS icon          ,
                      pct.access_manager        AS access_manager,
                      pct.script_url            AS url

                   FROM `".$_course['dbNameGlu']."tool_list` ctl,
                    `".$tbl_tool."`  pct

               WHERE `ctl`.`tool_id` = `pct`.`id`
                 AND (`ctl`.`id`      = '". (int) $tidReq."'
                       OR   (".(int) is_null($tidReq)." AND pct.claro_label = '". claro_sql_escape($tlabelReq) ."')
                     )";

        // Note : 'ctl' stands for  'course tool list' and  'pct' for 'platform course tool'
        $_courseTool = claro_sql_query_get_single_row($sql);

        if ( is_array($_courseTool) ) // this tool have a recorded state for this course
        {
            $_tid        = $_courseTool['id'];
            $_mainToolId = $_courseTool['toolId'];
        }
        else // this tool has no status related to this course
        {
            $activatedModules = get_module_label_list( true );
            
            if ( ! in_array( $tlabelReq, $activatedModules ) )
            {
                exit('WARNING !! Undefined Tlabel or Tid: your script declare '
                    . 'be a tool wich is not registred at line '.__LINE__.'.  '
                    . 'Please contact your platform administrator.');
            }
            else
            {
                $_tid        = null;
                $_mainToolId = null;
                $_courseTool = null;
            }
        }
    }
    else // keys missing => not anymore in the course - tool relation
    {
        // course
        $_tid        = null;
        $_mainToolId = null;
        $_courseTool = null;
    }

}
else // continue with the previous values
{
    $_tid = !empty($_SESSION['_tid'])
        ? $_SESSION['_tid']
        : null
        ;
    
    $_mainToolId = !empty($_SESSION['_mainToolId'])
        ? $_SESSION['_mainToolId']
        : null
        ;
    
    $_courseTool = !empty( $_SESSION['_courseTool'])
        ? $_SESSION['_courseTool']
        : null
        ;
}

/*---------------------------------------------------------------------------
  Group initialisation
 ---------------------------------------------------------------------------*/

// if the requested group is different from the group in session

if ( $gidReq && ( !isset($_SESSION['_gid']) || $gidReq != $_SESSION['_gid']) )
{
    $gidReset = true;
}

if ( $gidReset || $cidReset ) // session data refresh requested
{
    if ( $gidReq && $_cid ) // have keys to search data
    {
        $context = array(
            CLARO_CONTEXT_COURSE => $_cid,
            CLARO_CONTEXT_GROUP => $gidReq );
        
        $course_group_data = claro_get_group_data($context, true );

        $_group = $course_group_data;
        
        if ( $_group ) // This group has recorded status related to this course
        {
            $_gid = $course_group_data ['id'];
        }
        else
        {
            claro_die('WARNING !! Undefined groupd id: the requested group '
                . ' doesn\'t exist at line '.__LINE__.'.  '
                . 'Please contact your platform administrator.');
        }
    }
    else  // Keys missing => not anymore in the group - course relation
    {
        $_gid   = null;
        $_group = null;
    }
}
else // continue with the previous values
{
    $_gid = !empty($_SESSION ['_gid'])
        ? $_SESSION ['_gid']
        : null
        ;
    
    $_group = !empty($_SESSION ['_group'])
        ? $_SESSION ['_group']
        : null
        ;
}

/*---------------------------------------------------------------------------
  Group / User relation initialisation
 ---------------------------------------------------------------------------*/

if ($uidReset || $cidReset || $gidReset) // session data refresh requested
{
    if ($_uid && $_cid && $_gid) // have keys to search data
    {
        $sql = "SELECT status,
                       role
                FROM `" . $_course['dbNameGlu'] . "group_rel_team_user`
                WHERE `user` = '". (int) $_uid . "'
                AND `team`   = '". (int) $gidReq . "'";

        $result = claro_sql_query($sql)  or die ('WARNING !! Load user course_group status (DB QUERY) FAILED ! '.__LINE__);

        if (mysql_num_rows($result) > 0) // This user has a recorded status related to this course group
        {
            $gpuData = mysql_fetch_array($result);

            $_groupUser ['status'] = $gpuData ['status'];
            $_groupUser ['role'  ] = $gpuData ['role'  ];

            $is_groupMember = true;
        }
        else
        {
            $is_groupMember = false;
            $_groupUser     = null;
        }

        $is_groupTutor = ($_group['tutorId'] == $_uid);

    }
    else  // Keys missing => not anymore in the user - group (of this course) relation
    {
        $is_groupMember = false;
        $is_groupTutor  = false;

        $_groupUser = null;
    }

    // user group access is allowed or user is group member or user is admin
    $is_groupAllowed = (bool) (!$_groupProperties['private']
                               || $is_groupMember
                               || $is_courseAdmin
                               || claro_is_group_tutor()
                               || $is_platformAdmin);

}
else // continue with the previous values
{
    $_groupUser = !empty($_SESSION['_groupUser'])
        ? $_SESSION['_groupUser']
        : null
        ;
        
    $is_groupMember  = !empty($_SESSION['is_groupMember'])
        ? $_SESSION['is_groupMember']
        : null
        ;
    
    $is_groupTutor = !empty($_SESSION['is_groupTutor'])
        ? $_SESSION['is_groupTutor']
        : null
        ;
    
    $is_groupAllowed = !empty($_SESSION['is_groupAllowed'])
        ? $_SESSION['is_groupAllowed']
        : null
        ;
}

/*---------------------------------------------------------------------------
  COURSE TOOL / USER / GROUP REL. INIT
 ---------------------------------------------------------------------------*/

if ( $uidReset || $cidReset || $gidReset || $tidReset ) // session data refresh requested
{
    if ( $_tid && $_gid )
    {
        //echo 'passed here';

        $toolLabel = trim( $_courseTool['label'] , '_');

        $is_toolAllowed = array_key_exists($toolLabel, $_groupProperties ['tools'])
            && $_groupProperties ['tools'] [$toolLabel]
            // do not allow to access group tools when groups are not allowed for current profile
            && claro_is_allowed_tool_read(get_tool_id_from_module_label('CLGRP'),$_profileId,$_cid);

        if ( $_groupProperties ['private'] )
        {
            $is_toolAllowed = $is_toolAllowed && ( $is_groupMember || claro_is_group_tutor() );
        }

        $is_toolAllowed = $is_toolAllowed || ( $is_courseAdmin || $is_platformAdmin );
    }
    elseif ( $_tid )
    {
        if ( ( ! $_courseTool['visibility'] && ! claro_is_allowed_tool_edit($_mainToolId,$_profileId,$_cid) )
             || ! claro_is_allowed_tool_read($_mainToolId,$_profileId,$_cid) )
        {
            $is_toolAllowed = false;
        }
        else
        {
            $is_toolAllowed = true;
        }
    }
    else
    {
        $is_toolAllowed = false;
    }

}
else // continue with the previous values
{
    $is_toolAllowed = !empty( $_SESSION['is_toolAllowed'] )
        ? $_SESSION['is_toolAllowed']
        : null
        ;
}

/*---------------------------------------------------------------------------
  Course tool list initialisation for current user
 ---------------------------------------------------------------------------*/

if ($uidReset || $cidReset)
{
    if ($_cid) // have course keys to search data
    {
        $_courseToolList = claro_get_course_tool_list($_cid, $_profileId, true, true);
    }
    else
    {
        $_courseToolList = null;
    }
}
else // continue with the previous values
{
    $_courseToolList = !empty($_SESSION['_courseToolList'])
        ? $_SESSION['_courseToolList']
        : null
        ;
}

/*===========================================================================
  Save all variables in session
 ===========================================================================*/

/*---------------------------------------------------------------------------
  User info in the platform
 ---------------------------------------------------------------------------*/
$_SESSION['_uid'                  ] = $_uid;
$_SESSION['_user'                 ] = $_user;
$_SESSION['is_allowedCreateCourse'] = $is_allowedCreateCourse;
$_SESSION['is_platformAdmin'      ] = $is_platformAdmin;

/*---------------------------------------------------------------------------
  Course info of $_cid course
 ---------------------------------------------------------------------------*/

$_SESSION['_cid'            ] = $_cid;
$_SESSION['_course'         ] = $_course;
$_SESSION['_groupProperties'] = $_groupProperties;

/*---------------------------------------------------------------------------
  User rights of $_uid in $_cid course
 ---------------------------------------------------------------------------*/

$_SESSION['_profileId'      ] = $_profileId;
$_SESSION['is_courseAdmin'  ] = $is_courseAdmin;
$_SESSION['is_courseAllowed'] = $is_courseAllowed;
$_SESSION['is_courseMember' ] = $is_courseMember;
$_SESSION['is_courseTutor'  ] = $is_courseTutor;

if ( isset($_courseUser) ) $_SESSION['_courseUser'] = $_courseUser; // not used

/*---------------------------------------------------------------------------
  Tool info of $_tid in $_cid course
 ---------------------------------------------------------------------------*/

$_SESSION['_tid'       ] = $_tid;
$_SESSION['_mainToolId'] = $_mainToolId;
$_SESSION['_courseTool'] = $_courseTool;

/*---------------------------------------------------------------------------
  Group info of $_gid in $_cid course
 ---------------------------------------------------------------------------*/

$_SESSION['_gid'           ] = $_gid;
$_SESSION['_group'         ] = $_group;
$_SESSION['is_groupAllowed'] = $is_groupAllowed;
$_SESSION['is_groupMember' ] = $is_groupMember;
$_SESSION['is_groupTutor'  ] = $is_groupTutor;

/*---------------------------------------------------------------------------
 Tool in $_cid course allowed to $_uid user
 ---------------------------------------------------------------------------*/

if ( $_cid && $_tid )
{
    $is_toolAllowed = $is_toolAllowed && claro_is_course_tool_activated( $_cid, $_tid );
}

$_SESSION['is_toolAllowed'] = $is_toolAllowed;

/*---------------------------------------------------------------------------
  List of available tools in $_cid course
 ---------------------------------------------------------------------------*/

$_SESSION['_courseToolList'] = $_courseToolList;
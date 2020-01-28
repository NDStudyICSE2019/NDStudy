<?php // $Id: sql.lib.php 13302 2011-07-11 15:19:09Z abourguignon $

/**
 * CLAROLINE
 *
 * @version     $Revision: 13302 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC
 *              LICENSE version 2 or later
 * @author      see 'credits' file
 * @package     KERNEL
 */

//////////////////////////////////////////////////////////////////////////////
//                   CLAROLINE DB    QUERY WRAPPRER MODULE
//////////////////////////////////////////////////////////////////////////////


/**
 * Return the tablename for a tool, dependig on the execution context
 * WARNING DO NOT USE THIS FUNCTION UNTIL THE deprecated TAG IS REMOVE
 *
 * @param array $tableList
 * @param array $contextData id To discrim table. Do not add context Id
 *  of an context active but managed by tool.
 * @return array
 * @deprecated for modules since Claroline 1.9, use get_module_main_tbl and
 *  get_module_course_tbl instead
 * @todo rewrite to use new Claroline core/context.lib.php
 */
function claro_sql_get_tbl( $tableList, $contextData=null)
{
    /**
     * If it's in a course, $courseId is set or $courseId is null but not claro_get_current_course_id()
     * if both are null, it's a main table
     *
     * when
     */

    if( ! is_array($tableList))
    {
        $tableListArr[] = $tableList;
        $tableList = $tableListArr;
    }
    else $tableList = $tableList;

    /**
     * Tool Context capatibility
     *
     * There is many context in claroline,
     * a new tool can don't provide initially
     * all field to discrim each context in fields.
     * When a tool can't discrim a context,
     * the table would be duplicated for each instance
     * and the name of table (or db) contain the discriminator
     *
     * This extreme modularity provide an easy growing
     * and integration but
     * easy
     *
     * Easy can't mean slowly.
     * If  I prupose a blog tool wich can't discrim user
     * I need to duplicate all blog table (in same or separate db).
     */

    if (!is_array($contextData)) $contextData = array();

    if ( isset($GLOBALS['_courseTool']['label']) )
    {
        $toolId = rtrim($GLOBALS['_courseTool']['label'],'_');
    }
    else
    {
        $toolId = null;
    }

    $contextDependance = get_context_db_discriminator($toolId);
    // Now place discriminator in db & table name.
    // if a context is needed ($contextData) and $contextDependance is found,
    // add the discriminator in schema name or table prefix
    $schemaPrefix = array();

    if (is_array($contextDependance) )
    {
        if (array_key_exists('schema',$contextDependance))
        {
            if (array_key_exists(CLARO_CONTEXT_COURSE,$contextData)
            && !is_null($contextData[CLARO_CONTEXT_COURSE])
            && in_array(CLARO_CONTEXT_COURSE, $contextDependance['schema']))
            {
                $schemaPrefix[] = get_conf('courseTablePrefix') . claro_get_course_db_name($contextData[CLARO_CONTEXT_COURSE]);
            }
        }

        $tablePrefix = '';

        if (array_key_exists('table',$contextDependance))
        {
            if (array_key_exists(CLARO_CONTEXT_COURSE,$contextData)
            && !is_null($contextData[CLARO_CONTEXT_COURSE])
            && in_array(CLARO_CONTEXT_COURSE, $contextDependance['table']))
            {
                $tablePrefix .= 'C_' . $contextData[CLARO_CONTEXT_COURSE] . '_';
            }
        }
    }

    //$schemaPrefix = (0==count($schemaPrefix) ? get_conf('mainDbName') : implode(get_conf('dbGlu'),$schemaPrefix)); // ne pas utiliser dbGlu tant qu'il peut valoir .
    $schemaPrefix = (0 == count($schemaPrefix) ? get_conf('mainDbName') : implode('_',$schemaPrefix));
    $tablePrefix  = ('' == $tablePrefix) ? get_conf('mainTblPrefix') : $tablePrefix;

    foreach ($tableList as $tableId)
    {
        /**
         *  Read this  to understand chanche  since  previous version thant 1.8
         *
         * Until 1.8  there was 2 functions
         *
         * function claro_sql_get_main_tbl()
         * function claro_sql_get_course_tbl($dbNameGlued = null)
         *
         * both was using  conf values
         * claro_sql_get_main_tbl was using  conf values
         * * get_conf('mainDbName')
         * * get_conf('mainTblPrefix')
         *
         */
        $tableNameList[$tableId] = $schemaPrefix . '`.`' . $tablePrefix . $tableId;
    }

    return $tableNameList;
}

/**
 * Get list of table names for central table.
 *
 * @return array list of the central claroline database tables
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @deprecated for module development since Claroline 1.9, use
 *  get_module_main_tbl instead
 */
function claro_sql_get_main_tbl()
{
    static $mainTblList = array();
    
    if ( count($mainTblList) == 0 )
    {
        $mainTblList= array (
        'coursehomepage_portlet'    => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'coursehomepage_portlet',
        'config_property'           => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'config_property',
        'config_file'               => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'config_file',
        'course'                    => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'cours',
        'category'                  => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'category',
        'event_resource'            => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'event_resource',
        'user'                      => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'user',
        'tool'                      => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'course_tool',
        'user_category'             => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'class',
        'user_rel_profile_category' => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'rel_class_user',
        'class'                     => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'class',
        'rel_class_user'            => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'rel_class_user',
        'rel_course_category'       => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'rel_course_category',
        'rel_course_class'          => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'rel_course_class',
        'rel_course_portlet'        => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'rel_course_portlet',
        'rel_course_user'           => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'rel_course_user',
        'sso'                       => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'sso',
        'notify'                    => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'notify',
        'upgrade_status'            => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'upgrade_status',
        'module'                    => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'module',
        'module_info'               => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'module_info',
        'module_contexts'           => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'module_contexts',
        'dock'                      => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'dock',
        'right_profile'             => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'right_profile',
        'right_rel_profile_action'  => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'right_rel_profile_action',
        'right_action'              => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'right_action',
        'user_property'             => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'user_property',
        'property_definition'       => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'property_definition',
        'im_message'                => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'im_message',
        'im_message_status'         => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'im_message_status',
        'im_recipient'              => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'im_recipient',
        'desktop_portlet'           => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'desktop_portlet',
        'desktop_portlet_data'      => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'desktop_portlet_data',
        
        'tracking_event'            => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'tracking_event',
        'log'                       => get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'log'
        );
    }
    
    return $mainTblList;
}

/**
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param  string $dbNameGlued (optionnal) course database with its platform
 *         glue already append. If no db name are set, the current course db
 *         will be taken.
 * @return array list of the current course database tables
 * @deprecated for module development since Claroline 1.9, use
 *  get_module_course_tbl instead
 */
function claro_sql_get_course_tbl($dbNameGlued = null)
{
    global $_course;
    static $courseTblList = array();
    static $courseDbInCache = null;

    if ( is_null($dbNameGlued) )
    {

        $forceTableSet   = (bool) ( $courseDbInCache != $_course['dbNameGlu'] );
        $courseDbInCache = $_course['dbNameGlu'];
    }
    else
    {

        $forceTableSet   = (bool) ( $courseDbInCache != $dbNameGlued );
        $courseDbInCache = $dbNameGlued;
    }

    if ( count($courseTblList) == 0 || $forceTableSet )
    {
        // FIXME remove tables of up to date modules
        $courseTblList = array(

              'announcement'           => $courseDbInCache . 'announcement',
              'bb_categories'          => $courseDbInCache . 'bb_categories',
              'bb_forums'              => $courseDbInCache . 'bb_forums',
              'bb_posts'               => $courseDbInCache . 'bb_posts',
              'bb_posts_text'          => $courseDbInCache . 'bb_posts_text',
              'bb_priv_msgs'           => $courseDbInCache . 'bb_priv_msgs',
              'bb_rel_topic_userstonotify'
                            => $courseDbInCache . 'bb_rel_topic_userstonotify',
              'bb_rel_forum_userstonotify'
                            => $courseDbInCache . 'bb_rel_forum_userstonotify',
              'bb_topics'              => $courseDbInCache . 'bb_topics',
              'bb_users'               => $courseDbInCache . 'bb_users',
              'bb_whosonline'          => $courseDbInCache . 'bb_whosonline',

              'calendar_event'         => $courseDbInCache . 'calendar_event',
              'course_description'     => $courseDbInCache . 'course_description',
              'document'               => $courseDbInCache . 'document',
              'course_properties'      => $courseDbInCache . 'course_properties',
              'group_property'         => $courseDbInCache . 'group_property',
              'group_rel_team_user'    => $courseDbInCache . 'group_rel_team_user',
              'group_team'             => $courseDbInCache . 'group_team',
              'lp_learnPath'           => $courseDbInCache . 'lp_learnPath',
              'lp_rel_learnPath_module'=> $courseDbInCache . 'lp_rel_learnPath_module',
              'lp_user_module_progress'=> $courseDbInCache . 'lp_user_module_progress',
              'lp_module'              => $courseDbInCache . 'lp_module',
              'lp_asset'               => $courseDbInCache . 'lp_asset',
              'qwz_exercise'           => $courseDbInCache . 'qwz_exercise' ,
              'qwz_question'           => $courseDbInCache . 'qwz_question',
              'qwz_rel_exercise_question'     => $courseDbInCache . 'qwz_rel_exercise_question',
              'qwz_answer_truefalse'   => $courseDbInCache . 'qwz_answer_truefalse',
              'qwz_answer_multiple_choice'    => $courseDbInCache . 'qwz_answer_multiple_choice',
              'qwz_answer_fib'         => $courseDbInCache . 'qwz_answer_fib',
              'qwz_answer_matching'    => $courseDbInCache . 'qwz_answer_matching',
              'tool_intro'             => $courseDbInCache . 'tool_intro',
              'tool'                   => $courseDbInCache . 'tool_list',
              'tracking_event'         => $courseDbInCache . 'tracking_event',
              'userinfo_content'       => $courseDbInCache . 'userinfo_content',
              'userinfo_def'           => $courseDbInCache . 'userinfo_def',
              'wrk_assignment'         => $courseDbInCache . 'wrk_assignment',
              'wrk_submission'         => $courseDbInCache . 'wrk_submission',
              'links'                  => $courseDbInCache . 'lnk_links',
              'resources'              => $courseDbInCache . 'lnk_resources',
              'wiki_properties'        => $courseDbInCache . 'wiki_properties',
              'wiki_pages'             => $courseDbInCache . 'wiki_pages',
              'wiki_pages_content'     => $courseDbInCache . 'wiki_pages_content',
              'wiki_acls'              => $courseDbInCache . 'wiki_acls'
              ); // end array

    } // end if ( count($course_tbl) == 0 )

    return $courseTblList;
}

/**
 * CLAROLINE mySQL query wrapper. It also provides a debug display which works
 * when the CLARO_DEBUG_MODE constant flag is set to on (true)
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author Christophe Gesch√© <moosh@claroline.net>
 * @param  string  $sqlQuery   - the sql query
 * @param  handler $dbHandler  - optional
 * @return handler             - the result handler
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query($sqlQuery, $dbHandler = '#' )
{

    if ( claro_debug_mode()
      && get_conf('CLARO_PROFILE_SQL',false)
      )
      {
         $start = microtime();
      }
    if ( $dbHandler == '#')
    {
        $resultHandler =  @mysql_query($sqlQuery);
    }
    else
    {
        $resultHandler =  @mysql_query($sqlQuery, $dbHandler);
    }

    if ( claro_debug_mode()
      && get_conf('CLARO_PROFILE_SQL',false)
      )
    {
        static $queryCounter = 1;
        $duration = microtime()-$start;
        $info = 'execution time : ' . ($duration > 0.001 ? '<b>' . round($duration,4) . '</b>':'&lt;0.001')  . '&#181;s'  ;
        // $info = ( $dbHandler == '#') ? mysql_info() : mysql_info($dbHandler);
        // $info .= ': affected rows :' . (( $dbHandler == '#') ? mysql_affected_rows() : mysql_affected_rows($dbHandler));
        $info .= ': affected rows :' . claro_sql_affected_rows();

        pushClaroMessage( '<br />Query counter : <b>' . $queryCounter++ . '</b> : ' . $info . '<br />'
            . '<code><span class="sqlcode">' . nl2br($sqlQuery) . '</span></code>'
            , (claro_sql_errno()?'error':'sqlinfo'));

    }
    if ( claro_debug_mode() && claro_sql_errno() )
    {
        echo '<hr size="1" noshade>'
        .    claro_sql_errno() . ' : '. claro_sql_error() . '<br>'
        .    '<pre style="color:red">'
        .    $sqlQuery
        .    '</pre>'
        .    ( function_exists('claro_html_debug_backtrace')
             ? claro_html_debug_backtrace()
             : ''
             )
        .    '<hr size="1" noshade>'
        ;
    }

    return $resultHandler;
}

/**
 * CLAROLINE mySQL errno wrapper.
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_errno($dbHandler = '#')
{
    if ( $dbHandler == '#' )
    {
        return mysql_errno();
    }
    else
    {
        return mysql_errno($dbHandler);
    }
}

/**
 * CLAROLINE mySQL error wrapper.
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_error($dbHandler = '#')
{
    if ( $dbHandler == '#' )
    {
        return mysql_error();
    }
    else
    {
        return mysql_error($dbHandler);
    }
}

/**
 * CLAROLINE mySQL selectDb wrapper.
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_select_db($dbName, $dbHandler = '#')
{
    if ( $dbHandler == '#' )
    {
        return mysql_select_db($dbName);
    }
    else
    {
        return mysql_select_db($dbName, $dbHandler);
    }
}

/**
 * CLAROLINE mySQL affected rows wrapper.
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_affected_rows($dbHandler = '#')
{
    if ( $dbHandler == '#' )
    {
        return mysql_affected_rows();
    }
    else
    {
        return mysql_affected_rows($dbHandler);
    }
}

/**
 * CLAROLINE mySQL insert id wrapper.
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_insert_id($dbHandler = '#')
{
    if ( $dbHandler == '#' )
    {
        return mysql_insert_id();
    }
    else
    {
        return mysql_insert_id($dbHandler);
    }
}

/**
 * Get the name of the specified fields in a query result
 *
 * @param string $sq - SQL query
 * @param ressource (optional) - result pointer
 * @return  names of the specified field index
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_field_names( $sql, $resultPt = null )
{
    static $_colNameList = array();

    $sqlHash = md5($sql);

    if ( ! array_key_exists( $sqlHash, $_colNameList) )
    {
        if ( is_resource($resultPt) && get_resource_type($resultPt) == 'mysql result' )
        {
            // if ressource type is mysql result use it
            $releasablePt = false;
        }
        else
        {
            $resultPt     = claro_sql_query($sql);
            $releasablePt = true;
        }

        $resultFieldCount = mysql_num_fields($resultPt);

        for ( $i = 0; $i < $resultFieldCount ; ++$i )
        {
            $_colNameList[$sqlHash][] = mysql_field_name($resultPt, $i);
        }

        if ( $releasablePt ) mysql_free_result($resultPt);
    }

    return $_colNameList[$sqlHash];
}

/**
 * CLAROLINE SQL query and fetch array wrapper. It returns all the result rows
 * in an associative array.
 *
 * @param  string  $sqlQuery the sql query
 * @param  handler $dbHandler optional
 * @return array associative array containing all the result rows
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */

function claro_sql_query_fetch_all_rows($sqlQuery, $dbHandler = '#')
{
    $result = claro_sql_query($sqlQuery, $dbHandler);

    if ($result)
    {
        $rowList = array();

        while( $row = mysql_fetch_array($result, MYSQL_ASSOC) )
        {
            $rowList [] = $row;
        }

        if ( count($rowList) == 0 )
        {
            // If there is no result at all, anticipate that the user could ask
            // for field name at least. It is more efficient to call the
            // function now as we still hold the result pointer. The field names
            // will be statically cached into the claro_sql_field_names() funtion.

            claro_sql_field_names($sqlQuery, $result);
        }

        mysql_free_result($result);

        return $rowList;
    }
    else
    {
        return false;
    }
}

/**
 * Alias for claro_sql_query_fetch_all_rows
 * @see claro_sql_query_fetch_all_rows()
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_fetch_all($sqlQuery, $dbHandler = '#')
{
    return claro_sql_query_fetch_all_rows($sqlQuery, $dbHandler);
}

/**
 * CLAROLINE SQL query and fetch array wrapper. It returns all the result in
 * associative array ARRANGED BY COLUMNS.
 *
 * @param  string  $sqlQuery  the sql query
 * @param  handler $dbHandler optional
 * @return associative array containing all the result arranged by columns
 *
 * @see    claro_sql_query()
 * @author Hugues Peeters <hugues.peeters@claroline.net>,
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_fetch_all_cols($sqlQuery, $dbHandler = '#')
{
    $result = claro_sql_query($sqlQuery, $dbHandler);

    if ($result)
    {
        $colList = array();

        while( $row = mysql_fetch_array($result, MYSQL_ASSOC) )
        {
            foreach($row as $key => $value ) $colList[$key][] = $value;
        }

        if( count($colList) == 0 )
        {
            // WHEN NO RESULT, THE SCRIPT CREATES AT LEAST COLUMN HEADERS

            $FieldNamelist = claro_sql_field_names($sqlQuery, $result);

            foreach($FieldNamelist as $thisFieldName)
            {
                $colList[$thisFieldName] = array();
            }
        } // end if( count($colList) == 0)

        mysql_free_result($result);

        return $colList;

    }
    else
    {
        return false;
    }
}


/**
 * CLAROLINE SQL query wrapper returning only a single result value.
 * Useful in some cases because, it avoid nested arrays of results.
 *
 * @param  string  $sqlQuery  the sql query
 * @param  handler $dbHandler optional
 * @return associative array containing all the result rows
 * @since  1.9
 * @see    claro_sql_query()
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>,
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_fetch_single_value($sqlQuery, $dbHandler = '#')
{
    $result = claro_sql_query($sqlQuery, $dbHandler);

    if($result)
    {
        $row = mysql_fetch_row($result);

        if ( is_array( $row ) )
        {
            list($value) = $row;
        }
        else
        {
            $value = null;
        }

        mysql_free_result($result);
        return $value;
    }
    else
    {
        return false;
    }
}

/**
 * CLAROLINE SQL query wrapper returning only a single result value.
 * Useful in some cases because, it avoid nested arrays of results.
 *
 * @param  string  $sqlQuery  the sql query
 * @param  handler $dbHandler optional
 * @return associative array containing all the result column
 * @since  1.5.1
 * @see    claro_sql_query_fetch_single_value()
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>,
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_get_single_value($sqlQuery, $dbHandler = '#')
{
    return claro_sql_query_fetch_single_value($sqlQuery, $dbHandler);
}

/**
 * CLAROLINE SQL query wrapper returning only the first row of the result
 * Useful in some cases because, it avoid nested arrays of results.
 *
 * @param  string  $sqlQuery  the sql query
 * @param  handler $dbHandler optional
 * @return associative array containing all the result column
 * @since  1.9.*
 * @see    claro_sql_query_get_single_row()
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_fetch_single_row($sqlQuery, $dbHandler = '#')
{
    return claro_sql_query_get_single_row($sqlQuery, $dbHandler);
}

/**
 * Get a single row from a SQL query
 * @param string $sqlQuery
 * @param ressource $dbHandler
 * @return array or false
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_get_single_row($sqlQuery, $dbHandler = '#')
{
    $result = claro_sql_query($sqlQuery, $dbHandler);
    // TODO if $result is empty it can't return false but empty array.
    if($result)
    {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        mysql_free_result($result);
        return $row;
    }
    else
    {
        return false;
    }
}



/**
 * CLAROLINE SQL query wrapper returning the number of rows affected by the
 * query
 *
 * @param  string  $sqlQuery  the sql query
 * @param  handler $dbHandler optional
 * @return int                the number of rows affected by the query
 *
 * @see    claro_sql_query()
 * @author Hugues Peeters <hugues.peeters@claroline.net>,
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_affected_rows($sqlQuery, $dbHandler = '#')
{
    $result = claro_sql_query($sqlQuery, $dbHandler);

    if ($result)
    {
        if ($dbHandler == '#') return mysql_affected_rows();
        else                   return mysql_affected_rows($dbHandler);

        // NOTE. To make claro_sql_query_affected_rows() work properly,
        // database connection is required with CLIENT_FOUND_ROWS flag.
        //
        // When using UPDATE, MySQL will not update columns where the new
        // value is the same as the old value. This creates the possiblity
        // that mysql_affected_rows() may not actually equal the number of
        // rows matched, only the number of rows that were literally affected
        // by the query. But this behavior can be changed by setting the
        // CLIENT_FOUND_ROWS flag in mysql_connect(). mysql_affected_rows()
        // will return then the number of rows matched, even if none are
        // updated.
    }
    else
    {
        return false;
    }
}

/**
 * CLAROLINE mySQL query wrapper returning the last id generated by the last
 * inserted row
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>,
 * @param  string  $sqlQuery  the sql query
 * @param  handler $dbHandler optional
 * @return integer the id generated by the previous insert query
 *
 * @see    claro_sql_query()
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_query_insert_id($sqlQuery, $dbHandler = '#')
{
    $result = claro_sql_query($sqlQuery, $dbHandler);

    if ($result)
    {
        if ($dbHandler == '#') return mysql_insert_id();
        else                   return mysql_insert_id($dbHandler);
    }
    else
    {
        return false;
    }
}

/**
 * Protect Sql statment
 *
 * @param unknown_type $statement
 * @param unknown_type $db
 * @return unknown
 * @deprecated since Claroline 1.9, use Claroline::getDatabase() and new classes
 *  in database/database.lib.php instead
 */
function claro_sql_escape($statement,$db=null)
{
    if (is_null($db)) return mysql_real_escape_string($statement);
    else              return mysql_real_escape_string($statement, $db);

}



/**
 * Return an array of 2 array containing context wich can't be manage by tool
 * and where to store the discriminator.
 *
 * By default it's in table name except of course context wich follow singleDbMode value.
 *
 * DO NOT USE THIS FUNCTION !
 *
 * @param string $toolId claro_label
 * @return array of array
 *
 * @since 1.8
 * @deprecated since Claroline 1.9, see claro_sql_get_tbl for details
 */

require_once(dirname(__FILE__) . '/module.lib.php');
function get_context_db_discriminator($toolId)
{

    // array ( CLARO_CONTEXT_USER, CLARO_CONTEXT_COURSE, CLARO_CONTEXT_GROUP, 'toolInstance', 'session')

    // This fixed result would became result of config
    // Admin can select for each context for each tool,
    // if the descriminator needed (because not managed by tool )
    // would be placed in table name or schema name.

    // switch n'as plus trop de sens ici.
    // le default  devrait probablement sortir
    // et le switch des debrayages dans if (!get_conf('singleDbEnabled'))
    // parce que si singleDbEnabled =true $genericConfig['schema'] DOIT tre vide

    switch ($toolId)
    {
// ie        case 'CLANN' : return array('schema' => array (CLARO_CONTEXT_COURSE), 'table' => array(CLARO_CONTEXT_GROUP));
// ie        case 'CLWIKI' : return array('schema' => array (CLARO_CONTEXT_COURSE, CLARO_CONTEXT_GROUP));
        default:
            $dependance = get_module_db_dependance($toolId);

            // By default all is in tableName except for course wich follow singleDbEnabled;
            $genericConfig['table'] = $dependance ;
            if(is_array($dependance) && in_array(CLARO_CONTEXT_COURSE,$dependance))
            {
                if (!get_conf('singleDbEnabled'))
                {
                    $genericConfig['schema'] = array(CLARO_CONTEXT_COURSE);
                    $genericConfig['table'] = array_diff ($genericConfig['table'], $genericConfig['schema'] );
                }
            }
            return $genericConfig;
    }

}

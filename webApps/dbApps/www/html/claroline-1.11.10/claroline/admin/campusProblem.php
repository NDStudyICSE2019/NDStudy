<?php // $Id: campusProblem.php 14552 2013-10-03 08:26:03Z ldumorti $

/**
 * CLAROLINE
 *
 * This tool run some check to detect abnormal situation.
 * This script is a set of independant tests on the data.
 * Theses tests check if data are logical.
 * This script use Cache_lite.
 *
 * @version     $Revision: 14552 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/ADMIN
 * @author      S�bastien Piraux <pir@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 *
 * @todo separate checking and output
 * @todo protect "showall" when there is nothing in cache
 */

define('DISP_RESULT',__LINE__);
define('DISP_NOT_ALLOWED',__LINE__);


require '../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

include_once get_path('incRepositorySys') . '/lib/statsUtils.lib.inc.php';
include_once get_path('incRepositorySys') . '/lib/thirdparty/pear/Lite.php';
include_once claro_get_conf_repository() . 'CLKCACHE.conf.php';

// right
$is_allowedToCheckProblems = claro_is_platform_admin();


// Cache_lite setting & init
$cache_options = array( 'cacheDir' => get_path('rootSys') . 'tmp/cache/campusProblem/',
                        'lifeTime' => get_conf('cache_lifeTime', 10),
                        'automaticCleaningFactor' =>get_conf('cache_automaticCleaningFactor', 50),
);

if ( claro_debug_mode() ) $cache_options['pearErrorMode'] = CACHE_LITE_ERROR_DIE;
if ( claro_debug_mode() ) $cache_options['lifeTime'] = 3;

if (! file_exists($cache_options['cacheDir']) )
{
    include_once get_path('incRepositorySys') . '/lib/fileManage.lib.php';
    claro_mkdir($cache_options['cacheDir'],CLARO_FILE_PERMISSIONS,true);
}
$Cache_Lite = new Cache_Lite($cache_options);

/**
 * DB tables definition
 */

$tbl_mdb_names       = claro_sql_get_main_tbl();
$tbl_cdb_names       = claro_sql_get_course_tbl();
$tbl_course          = $tbl_mdb_names['course'];
$tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];
$tbl_user            = $tbl_mdb_names['user'];
$tbl_tracking_event  = $tbl_mdb_names['tracking_event'];
$tbl_document        = $tbl_cdb_names['document'];
$toolNameList = claro_get_tool_name_list();

// used in strange cases, a course is unused if not used since $limitBeforeUnused
// INTERVAL SQL expr. see http://www.mysql.com/doc/en/Date_and_time_functions.html
$limitBeforeUnused = "INTERVAL 6 MONTH";

// Prepare output
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$nameTools = get_lang('Scan technical fault');

$htmlHeadXtra[] = "
<style media='print' type='text/css'>
<!--
TD {border-bottom: thin dashed Gray;}
-->
</style>";

$display = ( $is_allowedToCheckProblems) ? DISP_RESULT : DISP_NOT_ALLOWED;

////////////// OUTPUT ///////////////
$out = '';

$out .= claro_html_tool_title( $nameTools );

switch ($display)
{
    case DISP_NOT_ALLOWED :
        {
            $dialogBox = new DialogBox();
            $dialogBox->error( get_lang('Not allowed') );
            $out .= $dialogBox->render();
        } break;

    case DISP_RESULT :
        {
            $dg = new claro_datagrid();
            $dg->set_idLineType('numeric');
            $dg->set_colAttributeList( array( 'qty' =>array('width'=>'15%' , 'align' => 'center')));
            // in $view, a 1 in X posof the $view string means that the 'category' number X
            // will be show, 0 means don't show
            $out .= '<small>'
            .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=111111111">' . get_lang('Show all') . '</a>]'
            .    '&nbsp;'
            .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=000000000">' . get_lang('Show none') . '</a>]'
            .    '</small>' . "\n\n"
            ;

            if( isset($_REQUEST['view'])) $view = strip_tags($_REQUEST['view']);
            else                          $view = "000000000";

            $levelView=-1;

            /***************************************************************************
            *        Main
            ***************************************************************************/
            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "SELECT DISTINCT username AS username
                             , count(*)          AS qty
                        FROM `" . $tbl_user . "`
                        GROUP BY username
                        HAVING qty > 1
                        ORDER BY qty DESC
                        LIMIT 100";
                    $data = claro_sql_query_fetch_all($sql);
                    if (!is_array($data) || 0 == sizeof($data)) $data[] = array( '-','qty'=>'-');
                    $dg->set_colTitleList(array(get_lang('Username'),get_lang('count')));
                    $dg->set_grid($data);
                    $datagrid[$levelView] .= $dg->render();
                    $Cache_Lite->save($datagrid[$levelView],$levelView);
                }
                $out .= '-'
                .    ' &nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('Accounts with same <i>Username</i>')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                .    $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />' . "\n"
                ;
            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Accounts with same <i>Username</i>')
                .    '</a>' . "\n"
                ;
            }
            $out .= '</p>' . "\n\n";

            /***************************************************************************
            *        Platform access and logins
            ***************************************************************************/
            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                $out .= '- '
                .    '&nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('Accounts with same <i>Email</i>')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .     get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                ;
                //--  multiple account with same email

                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "SELECT DISTINCT             email ,
                                        count(*) AS qty
                        FROM `" . $tbl_user . "`
                        GROUP BY email
                        HAVING qty > 1
                        ORDER BY qty DESC
                        LIMIT 100";
                    $data = claro_sql_query_fetch_all($sql);
                    if (!is_array($data) || 0 == sizeof($data)) $data[] = array( '-', '-');
                    $dg->set_colTitleList(array(get_lang('email'), get_lang('count')));
                    $dg->set_grid($data);
                    $datagrid[$levelView] = $dg->render();
                    $Cache_Lite->save($datagrid[$levelView], $levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;
            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Accounts with same <i>Email</i>')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n";


            $tempView = $view;
            $levelView++;
            $out .= "<p>\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                //--  courses without professor
                $out .= '- '
                .    '&nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('Courses without a lecturer')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                ;

                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "SELECT CONCAT(c.code,' (<a href=\"admincourseusers.php?cidToEdit=',c.code,'\">',c.administrativeNumber,'</a>)')
                                                   AS course,
                               count( cu.user_id ) AS qty
                    FROM `" . $tbl_course . "` c
                    LEFT JOIN `" . $tbl_rel_course_user . "` cu
                        ON c.code = cu.code_cours
                        AND cu.isCourseManager = 1
                    GROUP BY c.code, isCourseManager
                    HAVING qty = 0
                    ORDER BY code_cours
                        LIMIT 100";

                    $data = claro_sql_query_fetch_all($sql);
                    if (!is_array($data) || 0 == sizeof($data))
                    $data[] = array( '-','qty'=>'-');
                    $dg->set_colTitleList(array(get_lang('Code'), get_lang('Total')));
                    $dg->set_grid($data);
                    $datagrid[$levelView] = $dg->render();
                    $Cache_Lite->save($datagrid[$levelView],$levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;
            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Courses without a lecturer')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n\n";

            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                //-- courses without students
                $out .= '- '
                .    '&nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('Courses without student')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                ;

                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "SELECT CONCAT(c.code,' (<a href=\"admincourseusers.php?cidToEdit=',c.code,'\">',c.administrativeNumber,'</a>)')
                                                   AS course,
                               count( cu.user_id ) AS qty
                    FROM `" . $tbl_course . "`               AS c
                    LEFT JOIN `" . $tbl_rel_course_user . "` AS cu
                        ON c.code = cu.code_cours
                        AND cu.isCourseManager = 0
                    GROUP BY c.code, isCourseManager
                    HAVING qty = 0
                    ORDER BY code_cours
                        LIMIT 100";
                    $option['colTitleList'] = array('code','count');
                    $data = claro_sql_query_fetch_all($sql);
                    if (!is_array($data) || 0 == sizeof($data))
                    $dg->set_colTitleList(array(get_lang('Code'), get_lang('Total')));
                    $dg->set_grid($data);
                    $datagrid[$levelView] = $dg->render();
                    $Cache_Lite->save($datagrid[$levelView],$levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;
            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Courses without student')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n\n";


            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                //-- logins not used for $limitBeforeUnused
                $out .= '- '
                .    '&nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('Logins not used')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                ;

                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "SELECT `us`.`username`, `nom`, `prenom`, `email`, 
                               MAX(`tr`.`date`) AS qty
                    FROM `" . $tbl_user . "`               AS us
                    LEFT JOIN `" . $tbl_tracking_event . "` AS tr
                    ON`tr`.`user_id` = `us`.`user_id`
                    GROUP BY `us`.`username`
                    HAVING ( MAX(`tr`.`date`) < (NOW() - " . $limitBeforeUnused . " ) ) OR MAX(`tr`.`date`) IS NULL
                        LIMIT 100";


                    $loginWithoutAccessResults = claro_sql_query_fetch_all($sql);
                    for($i = 0; $i < sizeof($loginWithoutAccessResults); $i++)
                    {
                        if ( !isset($loginWithoutAccessResults[$i][1]) )
                        {
                            $loginWithoutAccessResults[$i][1] = get_lang('Never used');
                        }
                    }

                    $loginWithoutAccessResults = claro_sql_query_fetch_all($sql);
                    if (!is_array($loginWithoutAccessResults) || 0 == sizeof($loginWithoutAccessResults))
                    $loginWithoutAccessResults[] = array( '-','qty'=>'-');
                    $dg->set_colTitleList(array(get_lang('Username'), get_lang('Last name'), get_lang('First name'), get_lang('Email'), get_lang('Login date')));                    
                    $dg->set_grid($loginWithoutAccessResults);
                    $datagrid[$levelView] = $dg->render();
                    $Cache_Lite->save($datagrid[$levelView], $levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;

            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Logins not used')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n\n";

            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                //--  multiple account with same username AND same password (for compatibility with previous versions)
                $out .= '- &nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('Accounts with same <i>Username</i> AND same <i>Password</i>')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                ;

                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "SELECT DISTINCT CONCAT(username, \" -- \", password)
                                        AS paire
                             , count(*) AS qty
                        FROM `" . $tbl_user . "`
                        GROUP BY paire
                        HAVING qty > 1
                        ORDER BY qty DESC
                        LIMIT 100";
                    $data = claro_sql_query_fetch_all($sql);
                    if (!is_array($data) || 0 == sizeof($data))
                    $data[] = array( '-','qty'=>'-');
                    $dg->set_colTitleList(array(get_lang('Pairs'), get_lang('Total')));
                    $dg->set_grid($data);
                    $datagrid[$levelView] = $dg->render();
                    $Cache_Lite->save($datagrid[$levelView],$levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;

            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Accounts with same <i>Username</i> AND same <i>Password</i>')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n\n";

            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                //-- courses without access, not used for $limitBeforeUnused
                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql ="SELECT code, dbName
                       FROM `" . $tbl_course . "`
                       ORDER BY code ASC";
                    $resCourseList = claro_sql_query($sql);
                    $i = 0;
                    $courseWithoutAccess = array();
                    while ( ($course = mysql_fetch_array($resCourseList) ) )
                    {
                        $tbl_course_tracking_event = get_conf('courseTablePrefix') . $course['dbName'] . get_conf('dbGlu') . "tracking_event";
                        $sql = "SELECT IF( MAX(`date`)  < (NOW() - " . $limitBeforeUnused . " ), MAX(`date`) , 'recentlyUsedOrNull' )
                                                         AS lastDate
                                  , count(`date`) AS qty
                            FROM `" . $tbl_course_tracking_event . "`";
                        $coursesNotUsedResult = claro_sql_query($sql);

                       
                        if ( ( $courseAccess = mysql_fetch_array($coursesNotUsedResult) ) )
                        {
                            if ( 'recentlyUsedOrNull' == $courseAccess['lastDate'] && 0 != $courseAccess['qty'] ) continue;
                            $courseWithoutAccess[$i][0] = $course['code'];
                            if ( 'recentlyUsedOrNull' == $courseAccess['lastDate'] ) // if no records found ,course was never accessed
                            $courseWithoutAccess[$i][1] = get_lang('Never used');
                            else    $courseWithoutAccess[$i][1] = $courseAccess['lastDate'];
                        }

                        $i++;
                    }

                    if (!is_array($courseWithoutAccess) || 0 == sizeof($courseWithoutAccess))
                    $courseWithoutAccess[] = array( '-','qty'=>'-');
                    $dg->set_colTitleList(array(get_lang('Code'), get_lang('Last access')));
                    $dg->set_grid($courseWithoutAccess);
                    $datagrid[$levelView] = '- '
                    .    '&nbsp;&nbsp;'
                    .    '<b>'
                    .    get_lang('Courses not used')
                    .    '</b>'
                    .    '&nbsp;&nbsp;&nbsp;'
                    .    '<small>'
                    .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                    .    get_lang('Close')
                    .    '</a>]'
                    .    '</small>'
                    .    '<br />' . "\n"
                    .    $dg->render();

                    ;
                    $Cache_Lite->save($datagrid[$levelView],$levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;



            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Courses not used')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n\n"
            .    claro_html_tool_title(get_lang('Integrity problems'));




            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                //-- Courses with unexisting users registered : courses that have users not registered on the platform
                $out .= '- '
                .    '&nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('User registered in a course having an unexisting (deprecated) status')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                ;

                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "
                SELECT concat('(',cu.code_cours,') <br />', c.administrativeNumber,' : ',c.intitule) course,
                       cu.user_id AS user_id
                FROM `" . $tbl_rel_course_user . "` AS cu
                    INNER JOIN `" . $tbl_course . "` AS c
                        ON c.code = cu.code_cours
                    LEFT JOIN `" . $tbl_user . "` AS u
                        ON u.user_id = cu.user_id
                    WHERE cu.isCourseManager not in ('0','1')
                ORDER BY user_id
                        LIMIT 100";

                    $option['colTitleList'] = array('code','count');
                    $data = claro_sql_query_fetch_all($sql);
                    if (!is_array($data) || 0 == sizeof($data))
                    $dg->set_colTitleList(array(get_lang('Code'), get_lang('Total')));
                    $dg->set_grid($data);
                    $datagrid[$levelView] = $dg->render();
                    $Cache_Lite->save($datagrid[$levelView],$levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;
            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('User registered in a course having an unexisting (deprecated) status')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n\n";

            $tempView = $view;
            $levelView++;
            $out .= '<p>' . "\n";
            if('1' == $view[$levelView])
            {
                $tempView[$levelView] = '0';
                //-- Courses with unexisting users registered : courses that have users not registered on the platform
                $out .= '- '
                .    '&nbsp;&nbsp;'
                .    '<b>'
                .    get_lang('Courses with unexisting users registered')
                .    '</b>'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<small>'
                .    '[<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Close')
                .    '</a>]'
                .    '</small>'
                .    '<br />' . "\n"
                ;

                if (false === $datagrid[$levelView] = $Cache_Lite->get($levelView))
                {
                    $sql = "
                SELECT concat('(',cu.code_cours,') <br />', c.administrativeNumber,' : ',c.intitule) course,
                       cu.user_id AS user_id
                FROM `" . $tbl_rel_course_user . "` AS cu
                    INNER JOIN `" . $tbl_course . "` AS c
                        ON c.code = cu.code_cours
                    LEFT JOIN `" . $tbl_user . "` AS u
                        ON u.user_id = cu.user_id
                    WHERE u.user_id is null
                ORDER BY user_id
                        LIMIT 100";

                    $option['colTitleList'] = array('code','count');
                    $data = claro_sql_query_fetch_all($sql);
                    if (!is_array($data) || 0 == sizeof($data))
                    $dg->set_colTitleList(array(get_lang('Code'), get_lang('Total')));
                    $dg->set_grid($data);
                    $datagrid[$levelView] = $dg->render();
                    $Cache_Lite->save($datagrid[$levelView],$levelView);
                }

                $out .= $datagrid[$levelView]
                .    '<small>'
                .    get_lang('Last computing')
                .    ' '
                .    claro_html_localised_date(get_locale('dateTimeFormatLong').':%S', $Cache_Lite->lastModified())
                .    ', '
                .    get_lang('%delay ago', array('%delay' => claro_html_duration(time()-$Cache_Lite->lastModified())))
                .    '</small>'
                .    '<br />'
                ;
            }
            else
            {
                $tempView[$levelView] = '1';
                $out .= '+'
                .    '&nbsp;&nbsp;&nbsp;'
                .    '<a href="' . $_SERVER['PHP_SELF'] . '?view=' . $tempView . '">'
                .    get_lang('Courses with unexisting users registered')
                .    '</a>'
                ;
            }
            $out .= '</p>' . "\n\n";
        }
        break;
    default:trigger_error('display (' . $display . ') unknown', E_USER_NOTICE);
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

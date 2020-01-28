<?php

// $Id: agenda.lib.php 14461 2013-05-29 09:34:33Z jrm_ $
if ( count ( get_included_files () ) == 1 )
    die ( '---' );
/**
 * CLAROLINE
 *
 * - For a Student -> View angeda Content
 * - For a Prof    -> - View agenda Content
 *         - Update/delete existing entries
 *         - Add entries
 *         - generate an "announce" entries about an entries
 *
 * @version     1.8 $Revision: 14461 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLCAL
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 */

/**
 * get list of all agenda item in the given or current course
 *
 * @param string $order  'ASC' || 'DESC' : ordering of the list.
 * @param string $courseCode current :sysCode of the course (leaveblank for current course)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return array of array(`id`, `titre`, `contenu`, `day`, `hour`, `lasting`, `visibility`)
 * @since  1.7
 */
function agenda_get_item_list ( $context, $order = 'DESC' )
{
    $tbl = claro_sql_get_course_tbl ( claro_get_course_db_name_glued ( $context[ CLARO_CONTEXT_COURSE ] ) );

    $sql = "SELECT           `id`,
                `titre`   AS `title`,
                `contenu` AS `content`,
                             `day`,
                             `hour`,
                             `lasting`,
                             `speakers`,
                             `visibility`,
                             `location`
        FROM `" . $tbl[ 'calendar_event' ] . "`
        WHERE group_id = " . (int) claro_get_current_group_id () . "
        ORDER BY `day` " . ('DESC' == $order ? 'DESC' : 'ASC') . "
        , `hour` " . ('DESC' == $order ? 'DESC' : 'ASC');

    return claro_sql_query_fetch_all ( $sql );
}

/**
 * Delete an event in the given or current course
 *
 * @param integer $event_id id the requested event
 * @param string $courseCode current :sysCode of the course (leaveblank for current course)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return result of deletion query
 * @since  1.7
 */
function agenda_delete_item ( $event_id, $courseCode = null )
{
    $tbl_c_names = claro_sql_get_course_tbl ( claro_get_course_db_name_glued ( $courseCode ) );
    $tbl_calendar_event = $tbl_c_names[ 'calendar_event' ];

    $sql = "DELETE FROM  `" . $tbl_calendar_event . "`
            WHERE id= " . (int) $event_id;
    return claro_sql_query ( $sql );
}

/**
 * Delete an event in the given or current course
 *
 * @param integer $event_id id the requested event
 * @param string $courseCode current :sysCode of the course (leaveblank for current course)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return result of deletion query
 * @since  1.7
 */
function agenda_delete_all_items ( $courseCode = null )
{
    $tbl_c_names = claro_sql_get_course_tbl ( claro_get_course_db_name_glued ( $courseCode ) );
    $tbl_calendar_event = $tbl_c_names[ 'calendar_event' ];

    $sql = "DELETE FROM  `" . $tbl_calendar_event . "` WHERE group_id=" . (int) claro_get_current_group_id ();
    return claro_sql_query ( $sql );
}

/**
 * add an new event in the given or current course
 *
 * @param string   $title   title of the new item
 * @param string   $content content of the new item
 * @param date     $time    publication dat of the item def:now
 * @param string   $courseCode sysCode of the course (leaveblank for current course)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return id of the new item
 * @since  1.7
 */
function agenda_add_item ( $title = '', $content = '', $day = null, $hour = null, $lasting = '', $speakers = '', $location = '', $visibility = 'SHOW', $courseCode = null )
{
    $tbl_c_names = claro_sql_get_course_tbl ( claro_get_course_db_name_glued ( $courseCode ) );
    $tbl_calendar_event = $tbl_c_names[ 'calendar_event' ];

    if ( is_null ( $day ) )
        $day = date ( 'Y-m-d' );
    if ( is_null ( $hour ) )
        $hour = date ( 'H:i:s' );
    $speakers = (!empty ( $speakers )) ? ("'" . claro_sql_escape ( $speakers ) . "'") : ("null");

    $sql = "INSERT INTO `" . $tbl_calendar_event . "`
            SET
            titre       = '" . claro_sql_escape ( trim ( $title ) ) . "',
            contenu     = '" . claro_sql_escape ( trim ( $content ) ) . "',
            day         = '" . $day . "',
            hour        = '" . $hour . "',
            visibility  = '" . ($visibility == 'HIDE' ? 'HIDE' : 'SHOW') . "',
            lasting     = '" . claro_sql_escape ( trim ( $lasting ) ) . "',
            speakers    = " . $speakers . ",
            location    = '" . claro_sql_escape ( trim ( $location ) ) . "',
            group_id    = " . (int) claro_get_current_group_id ();

    return claro_sql_query_insert_id ( $sql );
}

/**
 * Update an announcement in the given or current course
 *
 * @param string     $title         title of the new item
 * @param string     $content       content of the new item
 * @param date       $time          publication dat of the item def:now
 * @param string     $courseCode    sysCode of the course (leaveblank for current course)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return handler of query
 * @since  1.7
 */
function agenda_update_item ( $event_id, $title = null, $content = null, $day = null, $hour = null, $lasting = null, $speakers = '', $location = null, $visibility = null, $courseCode = null )
{
    $tbl_c_names = claro_sql_get_course_tbl ( claro_get_course_db_name_glued ( $courseCode ) );
    $tbl_calendar_event = $tbl_c_names[ 'calendar_event' ];

    $speakers = (!empty ( $speakers )) ? ("'" . claro_sql_escape ( $speakers ) . "'") : ("null");

    $sqlSet = array ( );
    if ( !is_null ( $title ) )
        $sqlSet[ ] = " `titre` = '" . claro_sql_escape ( trim ( $title ) ) . "' ";
    if ( !is_null ( $content ) )
        $sqlSet[ ] = " `contenu` = '" . claro_sql_escape ( trim ( $content ) ) . "' ";
    if ( !is_null ( $day ) )
        $sqlSet[ ] = " `day` = '" . claro_sql_escape ( trim ( $day ) ) . "' ";
    if ( !is_null ( $hour ) )
        $sqlSet[ ] = " `hour` = '" . claro_sql_escape ( trim ( $hour ) ) . "' ";
    if ( !is_null ( $lasting ) )
        $sqlSet[ ] = " `lasting` = '" . claro_sql_escape ( trim ( $lasting ) ) . "' ";
    if ( !is_null ( $lasting ) )
        $sqlSet[ ] = " `speakers` = " . $speakers;
    if ( !is_null ( $visibility ) )
        $sqlSet[ ] = " `visibility` = '" . ($visibility == 'HIDE' ? 'HIDE' : 'SHOW') . "' ";
    if ( !is_null ( $location ) )
        $sqlSet[ ] = " `location` = '" . claro_sql_escape ( trim ( $location ) ) . "' ";

    if ( count ( $sqlSet ) > 0 )
    {
        $sql = "UPDATE `" . $tbl_calendar_event . "`
                SET " . implode ( ', ', $sqlSet ) . "
                WHERE `id` = " . (int) $event_id;

        return claro_sql_query ( $sql );
    }
    else
        return null;
}

/**
 * return data for the event  of the given id of the given or current course
 *
 * @param integer $event_id id the requested event
 * @param string  $courseCode sysCode of the course (leaveblank for current course)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return array(`id`, `title`, `content`, `dayAncient`, `hourAncient`, `lastingAncient`) of the event
 * @since  1.7
 */
function agenda_get_item ( $event_id, $courseCode = null )
{
    $tbl_c_names = claro_sql_get_course_tbl ( claro_get_course_db_name_glued ( $courseCode ) );
    $tbl_calendar_event = $tbl_c_names[ 'calendar_event' ];
    $sql = "SELECT `id`,
                   `titre`      AS `title`,
                   `contenu`    AS `content`,
                   `day`        AS `dayAncient`,
                   `hour`       AS `hourAncient`,
                   `lasting`    AS `lastingAncient`,
                   `speakers`     AS `speakers`,
                   `location`   AS `location`
            FROM `" . $tbl_calendar_event . "`
            WHERE `id` = " . (int) $event_id;

    $event = claro_sql_query_get_single_row ( $sql );

    if ( $event )
        return $event;
    else
        return claro_failure::set_failure ( 'EVENT_ENTRY_UNKNOW' );
}

/**
 * return data for the event  of the given id of the given or current course
 *
 * @param integer $event_id id the requested event
 * @param string  $visibility 'SHOW' || 'HIDE'  ordering of the list.
 * @param string  $courseCode  sysCode of the course (leaveblank for current course)
 * @author Christophe Gesche <moosh@claroline.net>
 * @return result handler
 * @since  1.7
 */
function agenda_set_item_visibility ( $event_id, $visibility, $courseCode = null )
{
    $tbl_c_names = claro_sql_get_course_tbl ( claro_get_course_db_name_glued ( $courseCode ) );
    $tbl_calendar_event = $tbl_c_names[ 'calendar_event' ];

    $sql = "UPDATE `" . $tbl_calendar_event . "`
            SET   visibility = '" . ($visibility == 'HIDE' ? "HIDE" : "SHOW") . "'
                  WHERE id =  " . (int) $event_id;
    return claro_sql_query ( $sql );
}

//////////////////////////////////////////////////////////////////////////////

/**
 * fetch all agenda item of a course for a given month
 *
 * @param array $thisCourse
 * @param integer $month
 * @param integer $year
 * @return array list of items
 */
function get_agenda_items_list ( $thisCourse, $month, $year )
{
    //FIXME
    $tbl = claro_sql_get_course_tbl ( get_conf ( 'courseTablePrefix' ) . $thisCourse[ 'db' ] . get_conf ( 'dbGlu' ) );

    $sql = "SELECT `id`,
                   `titre`   AS `title`,
                   `contenu` AS `content`,
                                `day`,
                                `hour`,
                                `lasting`,
                                `location`
            FROM `" . $tbl[ 'calendar_event' ] . "`
            WHERE MONTH(`day`) = " . (int) $month . "
              AND YEAR(`day`)  = " . (int) $year . "
              AND visibility   = 'SHOW'
            ORDER BY `day` ASC, `hour` ASC";

    return claro_sql_query_fetch_all_rows ( $sql );
}

/**
 * fetch the N next agenda item of a course
 *
 * @param array $thisCourse
 * @param integer $nbEvents number of events
 * @param integer $month
 * @param integer $year
 * @return array list of items
 */
function get_agenda_next_items_list ( $thisCourse, $nbEvents, $month, $year )
{
    $tbl = claro_sql_get_course_tbl ( get_conf ( 'courseTablePrefix' ) . $thisCourse[ 'db' ] . get_conf ( 'dbGlu' ) );

    $sql = "SELECT `id`,
                   `titre`   AS `title`,
                   `contenu` AS `content`,
                                `day`,
                                `hour`,
                                `lasting`,
                                `location`
            FROM `" . $tbl[ 'calendar_event' ] . "`
            WHERE YEAR(`day`)  = " . (int) $year . "
              AND `visibility` = 'SHOW'
            ORDER BY `day` ASC, `hour` ASC
            LIMIT 0, " . (int) $nbEvents;

    return claro_sql_query_fetch_all_rows ( $sql );
}

function get_agenda_items_compact_mode ( $userCourseList, $month, $year )
{
    $courseDigestList = array ( );

    $toolId = get_tool_id_from_module_label ( 'CLCAL' );

    // get agenda-items for every course
    foreach ( $userCourseList as $thisCourse )
    {
        if ( is_module_installed_in_course_lightversion ( 'CLCAL', $thisCourse )
            && is_tool_activated_in_course_lightversion ( $toolId, $thisCourse )
            && is_tool_visible_for_portlet( $toolId, $thisCourse['sysCode'] ) )
        {
            $courseEventList = get_agenda_items_list ( $thisCourse, $month, $year );

            if ( is_array ( $courseEventList ) )
            {
                foreach ( $courseEventList as $thisEvent )
                {
                    $eventLine = trim ( strip_tags ( $thisEvent[ 'title' ] ) );

                    if ( $eventLine == '' )
                    {
                        $eventContent = trim ( strip_tags ( $thisEvent[ 'content' ] ) );
                        $eventLine = substr ( $eventContent, 0, 60 ) . (strlen ( $eventContent ) > 60 ? ' (...)' : '');
                    }

                    $eventDate = explode ( '-', $thisEvent[ 'day' ] );
                    $day = intval ( $eventDate[ 2 ] );

                    if ( !array_key_exists ( $day, $courseDigestList ) )
                    {
                        $courseDigestList[ $day ] = array ( );
                        $courseDigestList[ $day ][ 'eventList' ] = array ( );
                        $courseDigestList[ $day ][ 'date' ] = $thisEvent[ 'day' ];
                    }

                    $courseDigestList[ $day ][ 'eventList' ][ ] =
                        array (
                            'hour' => $thisEvent[ 'hour' ],
                            'courseOfficialCode' => $thisCourse[ 'officialCode' ],
                            'courseSysCode' => $thisCourse[ 'sysCode' ],
                            'content' => $eventLine,
                            'url' => get_path ( 'url' )
                            . '/claroline/calendar/agenda.php?cidReq='
                            . $thisCourse[ 'sysCode' ]
                            . '#item' . $thisEvent[ 'id' ]
                    );
                }
            }
        }
        
        ksort ( $courseDigestList );
    }
    
    return $courseDigestList;
}

function get_agenda_items ( $userCourseList, $month, $year )
{
    $items = array ( );

    // get agenda-items for every course

    foreach ( $userCourseList as $thisCourse )
    {

        $courseEventList = get_agenda_items_list ( $thisCourse, $month, $year );

        if ( is_array ( $courseEventList ) )
            foreach ( $courseEventList as $thisEvent )
            {
                $eventLine = trim ( strip_tags ( $thisEvent[ 'title' ] ) );

                if ( $eventLine == '' )
                {
                    $eventContent = trim ( strip_tags ( $thisEvent[ 'content' ] ) );
                    $eventLine = substr ( $eventContent, 0, 60 ) . (strlen ( $eventContent ) > 60 ? ' (...)' : '');
                }

                $eventDate = explode ( '-', $thisEvent[ 'day' ] );
                $day = intval ( $eventDate[ 2 ] );
                $eventTime = explode ( ':', $thisEvent[ 'hour' ] );
                $time = $eventTime[ 0 ] . ':' . $eventTime[ 1 ];
                $url = get_path ( 'url' ) . '/claroline/calendar/agenda.php?cidReq=' . $thisCourse[ 'sysCode' ];

                if ( !isset ( $items[ $day ][ $thisEvent[ 'hour' ] ] ) )
                {
                    $items[ $day ][ $thisEvent[ 'hour' ] ] = '';
                }

                $items [ $day ] [ $thisEvent[ 'hour' ] ] .= '<br />'
                    . '<i>'
                    . '<small>' . $time . ' : </small>'
                    . '</i>'
                    . '<br />'
                    . $eventLine
                    . ' - '
                    . '<small>'
                    . '<a href="' . $url . '">'
                    . $thisCourse[ 'officialCode' ]
                    . '</a>'
                    . '</small>' . "\n"
                ;
            } // end foreach courseEventList
    }

    // sorting by hour for every day
    $agendaItemList = array ( );

    while ( list($agendaday, $tmpitems) = each ( $items ) )
    {
        sort ( $tmpitems );

        while ( list(, $val) = each ( $tmpitems ) )
        {
            if ( !isset ( $agendaItemList[ $agendaday ] ) )
                $agendaItemList[ $agendaday ] = '';
            $agendaItemList[ $agendaday ] .= $val;
        }
    }

    return $agendaItemList;
}

function claro_disp_monthly_calendar ( $agendaItemList, $month, $year, $weekdaynames, $monthName )
{

    pushClaroMessage ( (function_exists ( 'claro_html_debug_backtrace' ) ? claro_html_debug_backtrace () : 'claro_html_debug_backtrace() not defined'
        )
        . 'claro_disp_monthly_calendar is deprecated , use claro_html_monthly_calendar', 'error' );

    return claro_html_monthly_calendar ( $agendaItemList, $month, $year, $weekdaynames, $monthName );
}

/**
 * build a view of items place a monthly view
 *
 * @param array $agendaItemList list of item to include in the view
 * @param integer $month number of the month in the year (0=january)
 * @param integer $year number of the year (4 digit)
 * @param array $weekdaynames array from langfiles with names of the week days
 * @param string $monthName name of the current month
 * @return mixed : whether success html stream or false and error throw claro_failure
 */
function claro_html_monthly_calendar ( $agendaItemList, $month, $year, $weekdaynames, $monthName, $compactMode = false )
{
    $htmlStream = '';
    //Handle leap year
    $numberofdays = array ( 0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

    if ( ($year % 400 == 0) || ( $year % 4 == 0 && $year % 100 != 0 ) )
    {
        $numberofdays[ 2 ] = 29;
    }

    //Get the first day of the month
    $dayone = getdate ( mktime ( 0, 0, 0, $month, 1, $year ) );

    //Start the week on monday
    $startdayofweek = $dayone[ 'wday' ] <> 0 ? ($dayone[ 'wday' ] - 1) : 6;

    $backwardsURL = $_SERVER[ 'PHP_SELF' ]
        . '?month=' . ($month == 1 ? 12 : $month - 1)
        . '&amp;year=' . ($month == 1 ? $year - 1 : $year);

    $forewardsURL = $_SERVER[ 'PHP_SELF' ]
        . '?month=' . ($month == 12 ? 1 : $month + 1)
        . '&amp;year=' . ($month == 12 ? $year + 1 : $year);

    $htmlStream .= '<table class="claroTable" width="100%">' . "\n"
        . '<tr class="superHeader">' . "\n"
        . '<th width="13%">'
    ;

    if ( $compactMode == false )
    {
        $htmlStream .= '<center>' . "\n"
            . '<a href="' . $backwardsURL . '">&lt;&lt;</a>'
            . '</center>' . "\n"
        ;
    }

    $htmlStream .= '</th>' . "\n"
        . '<th width="65%" colspan="5">'
        . '<center>'
        . $monthName . ' ' . $year
        . '</center>'
        . '</th>' . "\n"
        . '<th width="13%">'
    ;

    if ( $compactMode == false )
    {
        $htmlStream .= '<center>' . "\n"
            . '<a href="' . $forewardsURL . '">&gt;&gt;</a>
        .    </center>'
        ;
    }

    $htmlStream .= '</th>' . "\n"
        . '</tr>' . "\n"
        . '<tr>' . "\n"
    ;

    for ( $iterator = 1; $iterator < 8; $iterator++ )
    {
        $htmlStream .= '<th width="13%">' . $weekdaynames[ $iterator % 7 ] . '</th>' . "\n";
    }

    $htmlStream .= '</tr>' . "\n\n";

    $curday = -1;

    $today = getdate ();

    while ( $curday <= $numberofdays[ $month ] )
    {
        $htmlStream .= '<tr>' . "\n";

        for ( $iterator = 0; $iterator < 7; $iterator++ )
        {
            if ( ($curday == -1) && ($iterator == $startdayofweek) )
            {
                $curday = 1;
            }

            if ( ($curday > 0) && ($curday <= $numberofdays[ $month ]) )
            {
                if ( ($curday == $today[ 'mday' ])
                    && ($year == $today[ 'year' ])
                    && ($month == $today[ 'mon' ]) )
                {
                    $weekdayType = 'highlight'; // today
                }
                elseif ( $iterator < 5 )
                {
                    $weekdayType = 'workingWeek';
                }
                else
                {
                    $weekdayType = 'weekEnd';
                }

                $dayheader = $curday;

                $htmlStream .= '<td height="40" width="12%" valign="top" '
                    . 'class="' . $weekdayType
                    . ($compactMode && isset ( $agendaItemList[ $curday ] ) ? ' dayWithEvent' : '')
                    . '">'
                ;

                if ( $compactMode && isset ( $agendaItemList[ $curday ] ) )
                {
                    $htmlStream .= '<a href="' . $agendaItemList[ $curday ][ 'eventList' ][ 0 ][ 'url' ] . '">' . $dayheader . '</a>';
                }
                else
                {
                    $htmlStream .= $dayheader;
                }

                if ( !$compactMode && isset ( $agendaItemList[ $curday ] ) )
                {
                    $htmlStream .= $agendaItemList[ $curday ];
                }

                $htmlStream .= '</td>' . "\n";

                $curday++;
            }
            else
            {
                $htmlStream .= '<td width="12%">&nbsp;</td>' . "\n";
            }
        }
        $htmlStream .= '</tr>' . "\n\n";
    }
    $htmlStream .= '</table>';

    return $htmlStream;
}
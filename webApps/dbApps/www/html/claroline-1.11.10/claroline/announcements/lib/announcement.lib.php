<?php // $Id: announcement.lib.php 14461 2013-05-29 09:34:33Z jrm_ $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * The script works with the 'annoucement' tables in the main claroline table
 *
 * DB Table structure:
 * ---
 *
 * id         : announcement id
 * contenu    : announcement content
 * visibleFrom  : date of the publication of the announcement
 * visibleUntil  : date of expiration of the announcement
 * temps      : date of the announcement introduction / modification
 * title      : optionnal title for an announcement
 * ordre      : order of the announcement display
 *              (the announcements are display in desc order)
 *
 * @version     1.8 $Revision: 14461 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLANN
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 */


/**
 * Get list of all announcements in the given or current course.
 *
 * @param array     $thisCourse
 * @param int       $limit number of records to return
 * @param int       $startTime
 * @param bool      $visibleOnly
 * @return array of array(id, title, content, time, visibility, rank)
 * @since 1.7
 */
function announcement_get_course_item_list($thisCourse, $limit = null, $startTime = null, $visibleOnly = true )
{
    // **** Caution: has to get fixed !
    $tableAnn = get_conf('courseTablePrefix') . $thisCourse['db'] . get_conf('dbGlu') . 'announcement';
    // ****
    
    $sql = "SELECT '" . claro_sql_escape($thisCourse['sysCode']     ) ."' AS `courseSysCode`, \n"
            . "'" . claro_sql_escape($thisCourse['officialCode']) ."'     AS `courseOfficialCode`, \n"
            . "'CLANN'                                              AS `toolLabel`,\n"
            . "CONCAT(`temps`, ' ', '00:00:00')                     AS `date`, \n"
            . "CONCAT(`title`,' - ',`contenu`)                      AS `content`, \n"
            . "`visibility`, \n"
            . "`visibleFrom`, \n"
            . "`visibleUntil` \n"
            . "FROM `" . $tableAnn . "` \n"
            . "WHERE CONCAT(`title`, `contenu`) != '' \n"
            . ( $startTime ? '' : "AND DATE_FORMAT( `temps`, '%Y %m %d') >= '".date('Y m d', (double)$startTime)."' \n" )
            . ( $visibleOnly ? "  AND visibility = 'SHOW' \n" : '' )
            . "ORDER BY `date` DESC \n"
            . ( $limit ? "LIMIT " . (int) $limit : '' )
            ;
    
    return claro_sql_query_fetch_all_cols($sql);
}


/**
 * Get list of all announcements in the given or current course.
 *
 * @param array     $thisCourse
 * @param int       $limit number of records to return
 * @param int       $startTime
 * @param bool      $visibleOnly
 * @return array of array(id, title, content, time, visibility, rank)
 * @since 1.7
 */
function announcement_get_course_item_list_portlet($thisCourse, $limit = null, $startTime = null, $visibleOnly = true )
{
    // **** Caution: has to get fixed !
    $tableAnn = get_conf('courseTablePrefix') . $thisCourse['db'] . get_conf('dbGlu') . 'announcement';
    // ****
    
    $curdate = claro_mktime();
    
    $sql = "SELECT '" . claro_sql_escape($thisCourse['sysCode']     ) ."' AS `courseSysCode`, \n"
         . "'" . claro_sql_escape($thisCourse['officialCode']) ."'  AS `courseOfficialCode`, \n"
         . "'CLANN'                                                 AS `toolLabel`, \n"
         . "CONCAT(`temps`, ' ', '00:00:00')                        AS `date`, \n"
         . "`id`                                                    AS `id`, \n"
         . "`title`                                                 AS `title`, \n"
         . "`contenu`                                               AS `content`, \n"
         . "`visibility`, \n"
         . "`visibleFrom`, \n"
         . "`visibleUntil` \n"
         . "FROM `" . $tableAnn . "` \n"
         . "WHERE CONCAT(`title`, `contenu`) != '' \n"
         . ( $startTime ? '' : "AND DATE_FORMAT( `temps`, '%Y %m %d') >= '".date('Y m d', (double)$startTime)."' \n" )
         . ( $visibleOnly ? "  AND visibility = 'SHOW' \n" : '' )
         . "            AND (UNIX_TIMESTAMP(`visibleFrom`) < '". $curdate ."'
                              OR `visibleFrom` IS NULL OR UNIX_TIMESTAMP(`visibleFrom`)=0
                            )
                        AND ('". $curdate ."' < UNIX_TIMESTAMP(`visibleUntil`) OR `visibleUntil` IS NULL)"
         . "ORDER BY `date` DESC \n"
         . ( $limit ? "LIMIT " . (int) $limit : '' );
    
    return claro_sql_query_fetch_all_rows($sql);
}

function announcement_get_items_portlet($personnalCourseList)
{
    $courseDigestList = array();
    
    $clannToolId = get_tool_id_from_module_label('CLANN');
    
    foreach($personnalCourseList as $thisCourse)
    {
        if ( is_module_installed_in_course_lightversion ( 'CLANN', $thisCourse ) 
            && is_tool_activated_in_course_lightversion( $clannToolId, $thisCourse )
            && is_tool_visible_for_portlet( $clannToolId, $thisCourse['sysCode'] ) )
        {
            $courseEventList = announcement_get_course_item_list_portlet($thisCourse, get_conf('announcementPortletMaxItems', 3));

            if ( is_array($courseEventList) )
            {
                foreach($courseEventList as $thisEvent)
                {
                    $courseTitle = trim(strip_tags($thisCourse['title']));
                    if ( $courseTitle == '' )
                    {
                        $courseTitle = substr($courseTitle, 0, 60) . (strlen($courseTitle) > 60 ? ' (...)' : '');
                    }

                    $eventContent = trim(strip_tags($thisEvent['content']));
                    if ( $eventContent == '' )
                    {
                        $eventContent = substr($eventContent, 0, 60) . (strlen($eventContent) > 60 ? ' (...)' : '');
                    }

                    $courseOfficialCode = $thisEvent['courseOfficialCode'];

                    if(!array_key_exists($courseOfficialCode, $courseDigestList))
                    {
                        $courseDigestList[$courseOfficialCode] = array();
                        $courseDigestList[$courseOfficialCode]['eventList'] = array();
                        $courseDigestList[$courseOfficialCode]['id'] = $thisEvent['id'];
                        $courseDigestList[$courseOfficialCode]['courseOfficialCode'] = $courseOfficialCode;
                        $courseDigestList[$courseOfficialCode]['title'] = $courseTitle;
                        $courseDigestList[$courseOfficialCode]['visibility'] = $thisEvent['visibility'];
                        $courseDigestList[$courseOfficialCode]['visibleFrom'] = $thisEvent['visibleFrom'];
                        $courseDigestList[$courseOfficialCode]['visibleUntil'] = $thisEvent['visibleUntil'];
                        $courseDigestList[$courseOfficialCode]['url'] = get_path('url')
                            . '/claroline/announcements/announcements.php?cidReq='
                            . $thisEvent['courseSysCode'];
                    }

                    $courseDigestList[$courseOfficialCode]['eventList'][] =
                        array(
                            'id' => $thisEvent['id'],
                            'courseSysCode' => $thisEvent['courseSysCode'],
                            'toolLabel' => $thisEvent['toolLabel'],
                            'title' => $thisEvent['title'],
                            'content' => $eventContent,
                            'date' => $thisEvent['date'],
                            'url' => get_path('url')
                                . '/claroline/announcements/announcements.php?cidReq='
                                . $thisEvent['courseSysCode']
                                . '#item'.$thisEvent['id']
                        );
                }
            }
        }
    }
    
    return $courseDigestList;
}

function announcement_get_item_list($context, $order='DESC')
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($context[CLARO_CONTEXT_COURSE]));

    $sql = "SELECT id,
                   title,
                   contenu          AS content,
                   visibleFrom,
                   visibleUntil,
                   temps            AS `time`,
                   visibility,
                   ordre            AS rank
            FROM `" . $tbl['announcement'] . "`
            ORDER BY ordre " . ($order == 'DESC' ? 'DESC' : 'ASC');
    return claro_sql_query_fetch_all($sql);
}

/**
 * Delete an announcement in the given or current course
 *
 * @param integer $announcement_id id the requested announcement
 * @param string $course_id  sysCode of the course (leaveblank for current course)
 * @return result of deletion query
 * @since 1.7
 */
function announcement_delete_item($id, $course_id=null)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    
    $sql = "DELETE FROM  `" . $tbl['announcement'] . "`
            WHERE id = '" . (int) $id . "'";
    return claro_sql_query($sql);
}

/**
 * Delete an announcement in the given or current course
 *
 * @param integer $announcement_id id the requested announcement
 * @param string $course_id        sysCode of the course (leaveblank for current course)
 * @return result of deletion query
 * @since 1.7
 */
function announcement_delete_all_items($course_id=null)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    
    $sql = "DELETE FROM  `" . $tbl['announcement'] . "`";
    return claro_sql_query($sql);
}

/**
 * Add a new announcement in the given or current course.
 *
 * @param string    $title title of the new item
 * @param string    $content   content of the new item
 * @param date      $visibleFrom
 * @param date      $visibleUntil
 * @param bool      visibility
 * @param date      $time  publication date of the item def:now
 * @param string    $course_id sysCode of the course (leaveblank for current course)
 * @return id of the new item
 * @since 1.7
 * @todo convert to param date timestamp
 */
function announcement_add_item($title='',$content='', $visible_from=null, $visible_until=null, $visibility=null, $time=null, $course_id=null)
{
    $tbl= claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    
    $sqlTime = (is_null($time)) ? ($sqlTime = "NOW()") : ("FROM_UNIXTIME('". (int) $time ."')");
    
    // Determine the position of the new announcement
    $sql = "SELECT (MAX(ordre) + 1) AS nextRank
            FROM  `" . $tbl['announcement'] . "`";
    
    $nextRank = claro_sql_query_get_single_value($sql);
    
    $visibility = (($visibility == 1) ? ("SHOW") : ("HIDE"));
    $visible_from = (!is_null($visible_from) ? ("'".claro_sql_escape($visible_from)."'") : ("NULL"));
    $visible_until = (!is_null($visible_until) ? ("'".claro_sql_escape($visible_until)."'") : ("NULL"));
    
    // Insert announcement
    $sql = "INSERT INTO `" . $tbl['announcement'] . "`
            SET title           = '" . claro_sql_escape(trim($title)) . "',
                contenu         = '" . claro_sql_escape(trim($content)) . "',
                temps           = " . $sqlTime .",
                visibleFrom     = " . $visible_from . ",
                visibleUntil    = " . $visible_until . ",
                ordre           = '" . (int) $nextRank . "',
                visibility      = '" . $visibility . "'";
    
    return claro_sql_query_insert_id($sql);
}

/**
 * Update an announcement in the given or current course.
 *
 * @param string    $title     title of the new item
 * @param string    $content   content of the new item
 * @param date      $visible_from
 * @param date      $visible_until
 * @param bool      visibility
 * @param date      $time      publication date of the item def:now
 * @param string    $course_id sysCode of the course (leaveblank for current course)
 * @return handler of query
 * @since 1.7
 * @todo convert to param date timestamp
 */
function announcement_update_item($announcement_id, $title=null, $content=null, $visible_from=null, $visible_until=null, $visibility=null, $time=null, $course_id=null)
{
    $tbl= claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    
    $visibility = (($visibility == 1) ? ("SHOW") : ("HIDE"));
    $visible_from = (!is_null($visible_from) ? ("'".claro_sql_escape($visible_from)."'") : ("NULL"));
    $visible_until = (!is_null($visible_until) ? ("'".claro_sql_escape($visible_until)."'") : ("NULL"));
    
    $sqlSet = array();
    if(!is_null($title))      $sqlSet[] = " title = '" . claro_sql_escape(trim($title)) . "' ";
    if(!is_null($content))    $sqlSet[] = " contenu = '" . claro_sql_escape(trim($content)) . "' ";
    if(!is_null($content))    $sqlSet[] = " visibleFrom = " . $visible_from . " ";
    if(!is_null($content))    $sqlSet[] = " visibleUntil = " . $visible_until . " ";
    if(!is_null($visibility)) $sqlSet[] = " visibility = '" . $visibility . "' ";
    if(!is_null($time))       $sqlSet[] = " temps = from_unixtime('".(int)$time."') ";
    
    if (count($sqlSet) > 0)
    {
        $sql = "UPDATE  `" . $tbl['announcement'] . "`
                SET " . implode(', ', $sqlSet) . "
                WHERE id='" . (int) $announcement_id . "'";
        
        return claro_sql_query($sql);
    }
    else
        return null;
}

/**
 * Returns data for the announcement  of the given id of the given or current course.
 *
 * @param integer   $announcement_id id the requested announcement
 * @param string    $course_id       sysCode of the course (leaveblank for current course)
 * @return array(id, title, content, visibility, rank) of the announcement
 * @since 1.7
 */
function announcement_get_item($announcement_id, $course_id=null)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    $sql = "SELECT id,
                   title,
                   contenu      AS content,
                   visibleFrom,
                   visibleUntil,
                   visibility,
                   ordre        AS rank
            FROM  `" . $tbl['announcement'] . "`
            WHERE id = " . (int) $announcement_id ;
    
    $announcement = claro_sql_query_get_single_row($sql);
    
    if ($announcement) return $announcement;
    else               return claro_failure::set_failure('ANNOUNCEMENT_UNKNOW');
}

function announcement_set_item_visibility($announcement_id, $visibility, $course_id=null)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    if (!in_array($visibility, array ('HIDE','SHOW')))
     trigger_error('ANNOUNCEMENT_VISIBILITY_UNKNOW', E_USER_NOTICE);
    $sql = "UPDATE `" . $tbl['announcement'] . "`
            SET   visibility = '" . ($visibility=='HIDE'?'HIDE':'SHOW') . "'
                  WHERE id = '" . (int) $announcement_id . "'";
    return  claro_sql_query($sql);
}

/**
 * Displaces an entry (up or down).
 *
 * @param  integer $entryId  an valid id of announcement.
 * @param  string $cmd       'UP' or 'DOWN'
 * @return true;
 *
 * @author Christophe Gesche <moosh@claroline.net>
 */
function move_entry($item_id, $cmd, $course_id=null)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    
    if ( $cmd == 'DOWN' )
    {
        $thisAnnouncementId = $item_id;
        $sortDirection      = 'DESC';
    }
    elseif ( $cmd == 'UP' )
    {
        $thisAnnouncementId = $item_id;
        $sortDirection      = 'ASC';
    }
    else
        return false;
    
    if ( $sortDirection )
    {
        $sql = "SELECT id,
                       ordre AS rank
                FROM `" . $tbl['announcement'] . "`
                ORDER BY `ordre` " . $sortDirection;
        
        $result = claro_sql_query($sql);
        $thisAnnouncementRankFound = false;
        $thisAnnouncementRank = '';
        while ( (list ($announcementId, $announcementRank) = mysql_fetch_row($result)) )
        {
            // STEP 2 : FOUND THE NEXT ANNOUNCEMENT ID AND ORDER.
            //          COMMIT ORDER SWAP ON THE DB

            if ($thisAnnouncementRankFound == true)
            {
                $nextAnnouncementId    = $announcementId;
                $nextAnnouncementRank  = $announcementRank;

                $sql = "UPDATE `" . $tbl['announcement'] . "`
                    SET ordre = '" . (int) $nextAnnouncementRank . "'
                    WHERE id =  '" . (int) $thisAnnouncementId . "'";

                claro_sql_query($sql);

                $sql = "UPDATE `" . $tbl['announcement'] . "`
                    SET ordre = '" . $thisAnnouncementRank . "'
                    WHERE id =  '" . $nextAnnouncementId . "'";
                claro_sql_query($sql);

                return true;
            }

            // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT

            if ( $announcementId == $thisAnnouncementId )
            {
                $thisAnnouncementRank      = $announcementRank;
                $thisAnnouncementRankFound = true;
            }
        }
        
        if (!$thisAnnouncementRankFound)
        {
            return false;
        }
    }
}

function clann_get_max_and_min_rank( $course_id = null )
{
    $course_id = is_null($course_id) ? claro_get_current_course_id() : $course_id;
    
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    
    return Claroline::getDatabase()->query( "
      SELECT 
        MAX(ordre) AS maxRank,
        MIN(ordre) AS minRank
     FROM `" . $tbl['announcement'] . "`" )->fetch();
}

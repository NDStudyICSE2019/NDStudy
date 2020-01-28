<?php // $Id: ical.write.cnr.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 13708 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLWRK
 *
 * @author Claro Team <cvs@claroline.net>
 */

function CLWRK_write_ical( $iCal, $context)
{
    if (is_array($context) && count($context) > 0)
    {
        $courseCode = (array_key_exists(CLARO_CONTEXT_COURSE,$context)) ? $context[CLARO_CONTEXT_COURSE] : $courseCode = claro_get_current_course_id();

        $courseData = claro_get_course_data($courseCode);
        $toolNameList = claro_get_tool_name_list();
        $assignmentList = assignmentList($courseCode);
        
        $organizer = (array) array($courseData['titular'], $courseData['email']);
        $attendees = array();
        $categories = array(
            get_conf('siteName'),
            $courseData['officialCode'],
            trim($toolNameList['CLWRK'])
        );

        foreach ($assignmentList as $thisAssignment)
        {
            if( 'VISIBLE' == $thisAssignment['visibility'])
            {

                $categories[] = $thisAssignment['assignment_type'];


                $assignmentContent = trim(strip_tags($thisAssignment['description']));
                $iCal->addToDo(
                trim($thisAssignment['title']), // Title
                $assignmentContent, // Description
                '', // Location
                (int) $thisAssignment['start_date_unix'], // Start time
                3600, //(($thisAssignment['end_date_unix']-$thisAssignment['start_date_unix'])/60), // Duration in minutes
                (int) $thisAssignment['end_date_unix'], // End time
                0, // Percentage complete
                5, // Priority = 0-9
                1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
                1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
                $organizer, // Organizer
                $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
                $categories, // Array with Strings
                time(), // Last Modification
                0, // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
                0, // frequency: 0 = once, secoundly - yearly = 1-7
                0, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
                0, // Interval for frequency (every 2,3,4 weeks...)
                array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
                1, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
                '', // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
                get_path('rootWeb') .'work/work_list.php?cidReq=' . $courseCode.'&amp;assigId=' . $thisAssignment['id'], // optional URL for that event
                get_locale('iso639_1_code'), // Language of the Strings
                '' // Optional UID for this ToDo
                );
            }
        }
    }
    return $iCal;

}

/**
 * Return the list of assigment of the current course
 *
 * @param string coursecode or null (to take default)
 *
 * @return array of array(id,title,description,def_submission_visibility,visibility,assignment_type,start_date_unix,end_date_unix)
 */
function assignmentList($courseCode = null)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($courseCode));
    $tbl_wrk_assignment = $tbl_cdb_names['wrk_assignment'];

    $sql = "SELECT    `id`,
                      `title`,
                      `description`,
                      `def_submission_visibility`,
                      `visibility`,
                      `assignment_type`,
               unix_timestamp(`start_date`)
                   AS `start_date_unix`,
               unix_timestamp(`end_date`)
                   AS `end_date_unix`
               FROM `" . $tbl_wrk_assignment . "`";

    return claro_sql_query_fetch_all_rows($sql);
}

<?php // $Id: ical.write.cnr.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLCAL
 * @subpackage  CLRSS
 * @author      Claro Team <cvs@claroline.net>
 */

function CLCAL_write_ical( $iCal, $context)
{

    if (is_array($context) && count($context)>0)
    {
        $courseId = (array_key_exists(CLARO_CONTEXT_COURSE,$context)) ? $context[CLARO_CONTEXT_COURSE] : claro_get_current_course_id();
    }

    if (false !== $courseData = claro_get_course_data($courseId))
    {

        $toolNameList = claro_get_tool_name_list();
        require_once dirname(__FILE__) . '/../lib/agenda.lib.php';
        $eventList    = agenda_get_item_list($context,'ASC');
        
        $organizer = (array) array($courseData['titular'], $courseData['email']);
        $attendees = array();
        $categories = array(
            get_conf('siteName'),
            $courseData['officialCode'],
            trim($toolNameList['CLCAL'])
        );

        foreach ($eventList as $thisEvent)
        {
            if( 'SHOW' == $thisEvent['visibility'] )
            {
                $eventDuration = (isset($thisEvent['duration'])?$thisEvent['duration']:get_conf('defaultEventDuration','60'));
                $startDate = strtotime($thisEvent['day'] . ' ' . $thisEvent['hour'] ); // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
                $endDate = $startDate + $eventDuration;

                $iCal->addEvent($organizer, // Organizer
                $startDate, //timestamp
                $endDate, //timestamp
                '', // Location
                0, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
                $categories, // Array with Strings
                trim(str_replace('<!-- content: html -->','',$thisEvent['content'])), // Description
                trim($thisEvent['title']), // Title
                1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
                $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
                5, // Priority = 0-9
                0, // frequency: 0 = once, secoundly - yearly = 1-7
                0, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
                0, // Interval for frequency (every 2,3,4 weeks...)
                array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
                1, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
                '', // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
                0,  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
                1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
                get_path('rootWeb') . get_module_url('CLCAL') . '/agenda.php?cidReq=' . $courseId . '&amp;l#item' . $thisEvent['id'], // optional URL for that event
                get_locale('iso639_1_code'), // Language of the Strings
                '' // Optional UID for this event
                );
            }
        }
    }
    return $iCal;
}

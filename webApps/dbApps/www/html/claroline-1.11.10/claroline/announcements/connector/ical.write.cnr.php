<?php // $Id: ical.write.cnr.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLANN
 * @subpackage  CLICAL
 * @author      Claro Team <cvs@claroline.net>
 */

function CLANN_write_ical( $iCal, $context)
{
    if (is_array($context) && count($context)>0)
    {
        $courseId = (array_key_exists(CLARO_CONTEXT_COURSE,$context)) ? $context[CLARO_CONTEXT_COURSE] : claro_get_current_course_id();
    }

    require_once dirname(__FILE__) . '/../lib/announcement.lib.php';
    $courseData = claro_get_course_data($courseId);

    $toolNameList = claro_get_tool_name_list();
    $announcementList = announcement_get_item_list($context, 'DESC');

    $organizer = (array) array($courseData['titular'], $courseData['email']);
    $attendees = array();
    $categories = array(
        get_conf('siteName'),
        $courseData['officialCode'],
        trim($toolNameList['CLANN'])
    );

    foreach ($announcementList as $announcementItem)
    {
        if('SHOW' == $announcementItem['visibility'])
        {
            /*
            $rssList[] = array( 'title'       => trim($announcementItem['title'])
            ,                   'category'    => trim($toolNameList['CLANN'])
            ,                   'guid'        => get_module_url('CLANN') . '/announcements.php?cidReq='.claro_get_current_course_id().'&l#ann'.$announcementItem['id']
            ,                   'link'        => get_module_url('CLANN') . '/announcements.php?cidReq='.claro_get_current_course_id().'&l#ann'.$announcementItem['id']
            ,                   'description' => trim(str_replace('<!-- content: html -->','',$announcementItem['content']))
            ,                   'pubDate'     => date('r', stripslashes(strtotime($announcementItem['time'])))
            //,                   'author'      => $_course['email']
            );
            */
            $iCal->addJournal(
            trim($announcementItem['title']), // Title
            trim(str_replace('<!-- content: html -->','',$announcementItem['content'])), // Description
            strtotime($announcementItem['time']), // Start time
            strtotime($announcementItem['time']), // Created
            time(), // Last modification
            1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
            1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
            $organizer, // Organizer
            $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
            $categories, // Array with Strings
            5, // frequency: 0 = once, secoundly - yearly = 1-7
            10, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
            1, // Interval for frequency (every 2,3,4 weeks...)
            array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
            0, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
            '', // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
            get_path('rootWeb') . get_module_url('CLANN') . '/announcements.php?cidReq=' . $courseId . '&amp;l#ann' . $announcementItem['id'], // optional URL for that event
            get_locale('iso639_1_code'), // Language of the Strings
            '' // Optional UID for this Journal
            );
        }
    }
    return $iCal;
}

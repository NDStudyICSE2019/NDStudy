<?php // $Id: coursehomepagecalendar.lib.php 13600 2011-09-21 11:14:20Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Course home page : MyCalendar portlet calendar class
 *
 * @version     $Revision: 13600 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     DESKTOP
 * @author      Claroline team <info@claroline.net>
 * @since       1.10
 */

FromKernel::uses('user.lib','courselist.lib');
From::Module('CLCAL')->uses('agenda.lib');

include claro_get_conf_repository() . 'CLHOME.conf.php'; // conf file

class CourseHomePageCalendar
{
    protected $courseCode;
    protected $year;
    protected $month;
    
    public function __construct($courseCode)
    {
        if (file_exists(claro_get_conf_repository() . 'CLCAL.conf.php'))
        {
            include claro_get_conf_repository() . 'CLCAL.conf.php';
        }
        
        $today              = getdate();
        $this->courseCode   = $courseCode;
        $this->month        = $today['mon'];
        $this->year         = $today['year'];
    }
    
    public function setYear( $year )
    {
        $this->year = $year;
    }
    
    public function setMonth( $month )
    {
        $this->month = $month;
    }
    
    public function getMonthName()
    {
        $langMonthNames = get_locale('langMonthNames');
        return $langMonthNames['long'][$this->month -1];
    }
    
    public function getMonth()
    {
        return $this->month;
    }
    
    public function getYear()
    {
        return $this->year;
    }
    
    public function render()
    {
        // Select current course's datas
        $tbl_mdb_names      = claro_sql_get_main_tbl();
        $tbl_courses        = $tbl_mdb_names['course'];
        
        $curdate = claro_mktime();
        
        $sql = "SELECT course.cours_id,
                       course.code                  AS `sysCode`,
                       course.directory             AS `directory`,
                       course.administrativeNumber  AS `officialCode`,
                       course.dbName                AS `db`,
                       course.intitule              AS `title`,
                       course.titulaires            AS `titular`,
                       course.language              AS `language`,
                       course.access                AS `access`,
                       course.status,
                       course.sourceCourseId,
                       UNIX_TIMESTAMP(course.expirationDate) AS expirationDate,
                       UNIX_TIMESTAMP(course.creationDate)   AS creationDate
                FROM `" . $tbl_courses . "` AS course
                WHERE course.code = " . Claroline::getDatabase()->quote($this->courseCode);
        
        $result = Claroline::getDatabase()->query($sql);
        $courseData = $result->fetch(Database_ResultSet::FETCH_ASSOC);
        
        $courseEventList = get_agenda_next_items_list($courseData, 10, $this->month, $this->year);
        
        if ( is_array($courseEventList) )
        {
            $courseDigestList = array();
            
            foreach($courseEventList as $thisEvent )
            {
                $eventLine = trim(strip_tags($thisEvent['title']));
                
                if ( $eventLine == '' )
                {
                    $eventContent = trim(strip_tags($thisEvent['content']));
                    $eventLine    = substr($eventContent, 0, 60) . (strlen($eventContent) > 60 ? ' (...)' : '');
                }
                
                $eventDate = $thisEvent['day'];
                
                if(!array_key_exists($eventDate, $courseDigestList))
                {
                    $courseDigestList[$eventDate] = array();
                    $courseDigestList[$eventDate]['eventList'] = array();
                    $courseDigestList[$eventDate]['date'] = $eventDate;
                }
                
                $courseDigestList[$eventDate]['eventList'][] =
                    array(
                        'id' => $thisEvent['id'],
                        'hour' => $thisEvent['hour'],
                        'location' => $thisEvent['location'],
                        'courseOfficialCode' => $courseData['officialCode'],
                        'courseSysCode' => $courseData['sysCode'],
                        'content' => $eventLine,
                        'url' => get_path('url').'/claroline/calendar/agenda.php?cidReq=' . $courseData['sysCode']
                    );
            
            }
        }
        
        $output = '';
        
        //$output .= '<div class="calendar">'.$this->ajaxMiniCalendar($agendaItemList).'</div>';
        
        $output .= '<div class="details">' . "\n"
                 . '<dl>' . "\n";
        
        if($courseDigestList)
        {
            foreach($courseDigestList as $agendaItem)
            {
                $output .= '<dt>' . "\n"
                         . '<h2>'
                         . claro_html_localised_date( get_locale('dateFormatLong'),
                                strtotime($agendaItem['date']) )
                         . '</h2>' . "\n"
                         . '</dt>' . "\n";
                
                foreach($agendaItem['eventList'] as $agendaEvent)
                {
                    $output .= '<dd>'
                             . '<b>' . $agendaEvent['content'] . '</b>' . "\n"
                             . (!empty($agendaEvent['hour']) ?
                                ' &ndash; ' . ucfirst( strftime( get_locale('timeNoSecFormat'), strtotime($agendaEvent['hour']))) :
                                '')
                             . (!empty($agendaEvent['location']) ?
                                ' | ' . $agendaEvent['location'] :
                                '')
                             . ' (<a href="' . $agendaEvent['url'] . '#item' . $agendaEvent['id'] . '">'
                             . get_lang('more details')
                             . '</a>)' . "\n"
                             . '</dd>' . "\n";
                }
            }
        }
        else
        {
            $output .= '<dt>' . "\n"
                     . get_lang('No event to display') . "\n"
                     . '</dt>' . "\n";
        }
        
        $output .= ''
                 . '</dl>' . "\n"
                 . '</div>' . "\n";
        
        return $output;
    }
}
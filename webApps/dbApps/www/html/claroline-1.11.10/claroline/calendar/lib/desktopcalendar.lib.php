<?php // $Id: desktopcalendar.lib.php 14162 2012-05-24 13:25:20Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * User desktop : MyCalendar portlet calendar class
 *
 * @version     $Revision: 14162 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     DESKTOP
 * @author      Claroline team <info@claroline.net>
 * @since       1.9
 */

FromKernel::uses('user.lib','courselist.lib');
From::Module('CLCAL')->uses('agenda.lib');

include claro_get_conf_repository() . 'CLHOME.conf.php'; // conf file

class UserDesktopCalendar
{
    protected $year;
    protected $month;
    
    public function __construct()
    {
        if (file_exists(claro_get_conf_repository() . 'CLCAL.conf.php'))
        {
            include claro_get_conf_repository() . 'CLCAL.conf.php';
        }
        
        $today = getdate();
        $this->month = $today['mon'];
        $this->year = $today['year'];
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
    
    protected function ajaxMiniCalendar( $agendaItemList )
    {
        $weekdaynames = get_locale('langDay_of_weekNames');
        $weekdaynames = $weekdaynames['init'];
        
        $htmlStream = '';
        //Handle leap year
        $numberofdays = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
    
        if ( ($this->getYear()%400 == 0) || ( $this->getYear()%4 == 0 && $this->getYear()%100 != 0 ) )
        {
            $numberofdays[2] = 29;
        }
    
        //Get the first day of the month
        $dayone = getdate(mktime(0,0,0,$this->getMonth(),1,$this->getYear()));
    
        //Start the week on monday
        $startdayofweek = $dayone['wday']<> 0 ? ($dayone['wday']-1) : 6;
        
        $previousMonth = $this->getMonth() == 1
            ? 12
            : $this->getMonth() -1
            ;
        
        $previousYear = $this->getMonth() == 1
            ? $this->getYear() - 1
            : $this->getYear()
            ;
        
        $nextMonth = $this->getMonth() == 12
            ? 1
            : $this->getMonth() + 1
            ;
        
        $nextYear = $this->getMonth() == 12
            ? $this->getYear() + 1
            : $this->getYear()
            ;
            
        $htmlStream = "<script type=\"text/javascript\">
var UserDesktopCalendar = {
    previous: function(){
        $.ajax({
            url: '".get_module_url('CLCAL')."/ajaxHandler.php',
            data: 'year=".(int)$previousYear."&month=".(int)$previousMonth."&location=userdesktop',
            success: function(response){
                $('#portletMycalendar').empty().append(response);
            }
        });
    },
    next: function(){
        $.ajax({
            url: '".get_module_url('CLCAL')."/ajaxHandler.php',
            data: 'year=".(int)$nextYear."&month=".(int)$nextMonth."&location=userdesktop',
            success: function(response){
                $('#portletMycalendar').empty().append(response);
            }
        });
    }
};
</script>";
        
        $htmlStream .=  '<table class="claroTable" width="100%">' . "\n"
        .    '<tr class="superHeader">' . "\n"
        .    '<th width="13%">'
        ;
        
        $htmlStream .= '<center>' . "\n"
        .    '<a href="" onclick="UserDesktopCalendar.previous();return false;">&lt;&lt;</a>'
        .    '</center>' . "\n"
        ;
        
        $htmlStream .= '</th>' . "\n"
        .    '<th width="65%" colspan="5">'
        .    '<center>'
        .    $this->getMonthName() . ' ' . $this->getYear()
        .    '</center>'
        .    '</th>' . "\n"
        .    '<th width="13%">'
        ;
        
        $htmlStream .= '<center>' . "\n"
        .    '<a href="" onclick="UserDesktopCalendar.next();return false;">&gt;&gt;</a>'
        .    '</center>'
        ;
    
        $htmlStream .= '</th>' . "\n"
        .    '</tr>' . "\n"
        .    '<tr>' ."\n"
        ;
    
        for ( $iterator = 1; $iterator < 8; $iterator++)
        {
            $htmlStream .=   '<th width="13%">' . $weekdaynames[$iterator%7] . '</th>' . "\n";
        }
    
        $htmlStream .=  '</tr>' . "\n\n";
    
        $curday = -1;
    
        $today = getdate();
    
        while ($curday <= $numberofdays[$this->getMonth()])
        {
            $htmlStream .=  '<tr>' . "\n";
    
            for ($iterator = 0; $iterator < 7 ; $iterator++)
            {
                if ( ($curday == -1) && ($iterator == $startdayofweek) )
                {
                    $curday = 1;
                }
    
                if ( ($curday > 0) && ($curday <= $numberofdays[$this->getMonth()]) )
                {
                    if (   ($curday == $today['mday'])
                    && ($this->getYear()   == $today['year'])
                    && ($this->getMonth()  == $today['mon' ]) )
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
    
                    $dayheader = $curday ;
    
                    $htmlStream .= '<td height="40" width="12%" valign="top" '
                    .    'class="' . $weekdayType
                    .    (isset($agendaItemList[$curday]) ? ' dayWithEvent': '')
                    .    '">'
                    ;
    
                    if ( isset($agendaItemList[$curday]) )
                    {
                        $htmlStream .= '<a href="'.$agendaItemList[$curday]['eventList'][0]['url'].'">' . $dayheader .'</a>';
                    }
                    else
                    {
                        $htmlStream .= $dayheader;
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
    
    public function render()
    {
        $userCourseList = get_user_course_list( claro_get_current_user_id() );
        $agendaItemList = get_agenda_items_compact_mode($userCourseList, $this->month, $this->year);

        $output = '';

        
        
        $output .= '<div class="details">' . "\n"
                 . '<dl class="calendarDetails">' . "\n";
        
        if($agendaItemList)
        {
            foreach($agendaItemList as $agendaItem)
            {
                $output .= '<dt>' . "\n"
                         . '<img class="iconDefinitionList" src="' . get_icon_url('agenda', 'CLCAL') . '" alt="Calendar" />&nbsp;'
                         . claro_html_localised_date( get_locale('dateFormatLong'),
                                strtotime($agendaItem['date']) )
                         . '</dt>' . "\n";
                
                foreach($agendaItem['eventList'] as $agendaEvent)
                {
                    $output .= '<dd>'
                             . '<a href="' . $agendaEvent['url'] . '">'
                             . $agendaEvent['courseOfficialCode']
                             . '</a> : ' . "\n"
                             . $agendaEvent['content'] . "\n"
                             . '</dd>' . "\n";
                }
            }
        }
        else
        {
            $output .= '<dt>' . "\n"
                     . '<img class="iconDefinitionList" src="' . get_icon_url('agenda', 'CLCAL') . '" alt="" />&nbsp;'
                     . get_lang('No event to display') . "\n"
                     . '</dt>' . "\n";
        }
        
        $output .= ''
                 . '</dl>' . "\n"
                 . '</div>' . "\n";
        
        // $output .= '<div class="calendar">'.$this->ajaxMiniCalendar($agendaItemList).'</div>';
        
        return $output;
    }
}

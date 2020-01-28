<?php // $Id: mycourselist.class.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * User desktop : course list portlet.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     DESKTOP
 * @author      Claroline Team <info@claroline.net>
 * @fixme       should not be a portlet anymore
 */

FromKernel::uses('courselist.lib');

// we need CLHOME conf file for render_user_course_list function
include claro_get_conf_repository() . 'CLHOME.conf.php'; // conf file

class MyCourseList extends UserDesktopPortlet
{
    public function __construct()
    {
        $this->name = 'My course list';
        $this->label = 'mycourselist';
    }
    
    public function renderContent()
    {
        global $platformLanguage;
        
        $out = '';
        
        // Last user action
        $lastUserAction = (isset($_SESSION['last_action']) &&
            $_SESSION['last_action'] != '1970-01-01 00:00:00') ?
            $_SESSION['last_action'] :
            date('Y-m-d H:i:s');
        
        $userCommands = array();
        
        // User commands
        // 'Create Course Site' command. Only available for teacher.
        if (claro_is_allowed_to_create_course())
        {
            $userCommands[] = '<a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'course/create.php')).'" class="userCommandsItem">'
                            . '<img src="' . get_icon_url('courseadd') . '" alt="" /> '
                            . get_lang('Create a course site')
                            . '</a>' . "\n";
        }
        elseif ( $GLOBALS['currentUser']->isCourseCreator )
        {
            $userCommands[] = '<span class="userCommandsItemDisabled">'
                            . '<img src="' . get_icon_url('courseadd') . '" alt="" /> '
                            . get_lang('Create a course site')
                            . '</span>' . "\n";
        }
        
        if (get_conf('allowToSelfEnroll',true))
        {
            $userCommands[] = '<a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'auth/courses.php?cmd=rqReg&amp;categoryId=0')).'" class="userCommandsItem">'
                            . '<img src="' . get_icon_url('enroll') . '" alt="" /> '
                            . get_lang('Enrol on a new course')
                            . '</a>' . "\n";
            
            $userCommands[] = '<a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'auth/courses.php?cmd=rqUnreg')).'" class="userCommandsItem">'
                            . '<img src="' . get_icon_url('unenroll') . '" alt="" /> '
                            . get_lang('Remove course enrolment')
                            . '</a>' . "\n";
        }
        
        $userCommands[] = '<a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'course/platform_courses.php')).'" class="userCommandsItem">'
                        . '<img src="' . get_icon_url('course') . '" alt="" /> '
                        . get_lang('All platform courses')
                        . '</a>' . "\n";
        
        $userCommands[] = '<a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'notification_date.php')).'" class="userCommandsItem">'
                        . '<img class="iconDefinitionList" src="'.get_icon_url('hot').'" alt="'.get_lang('New items').'" />'
                        . ' '.get_lang('New items').' '
                        . get_lang('to another date')
                        . ((substr($lastUserAction, strlen($lastUserAction) - 8) == '00:00:00' ) ?
                            (' ['.claro_html_localised_date(
                                get_locale('dateFormatNumeric'),
                                strtotime($lastUserAction)).']') :
                            (''))
                        . '</a>' . "\n";
        
        $userCourseList = render_user_course_list();
        
        $userCourseListDesactivated = render_user_course_list_desactivated();
        
        $out .= /*'<table>'
              . '<tbody>'
              . '<tr>'
              . '<td class="userCommands">'*/
              '<div class="userCommands">'
              . '<h2>'.get_lang('Manage my courses').'</h2>'
              . claro_html_list($userCommands)
              . '</div>'
              /*. '</td>'
              . '<td class="userCourseList">'*/
              . '<div class="userCourseList">'
              . '<h2>'.get_lang('My course list').'</h2>'
              . $userCourseList;
              
        if (!empty($userCourseListDesactivated))
        {
            $out .= '<h4>'.get_lang('Deactivated course list').'</h4>'
                  . $userCourseListDesactivated;
        }
        
        $out .= '</div>';/*'</td>'
              . '</tr>'
              . '</tbody>'
              . '</table>'*/;
        
        $this->content = $out;
        
        return $this->content;
    }
    
    
    public function renderTitle()
    {
        $output = get_lang('My course list');
        
        return $output;
    }
    
    public function render()
    {
        return '<div class="portlet'.(!empty($this->label)?' '.$this->label:'').'">' . "\n"
             . '<h1>' . "\n"
             . $this->renderTitle() . "\n"
             . '</h1>' . "\n"
             . '<div class="content">' . "\n"
             . $this->renderContent()
             . '</div>' . "\n"
             . '</div>' . "\n\n";
    }
}

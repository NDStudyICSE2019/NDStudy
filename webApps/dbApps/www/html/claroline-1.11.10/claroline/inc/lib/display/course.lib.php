<?php // $Id: course.lib.php 14481 2013-06-21 07:50:13Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Course tool list display class
 *
 * @version     Claroline 1.11 $Revision: 14481 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     display
 */

class CurrentCourseToolListBlock implements Display
{
    protected
        $courseCode,
        $courseId,
        $profileId,
        $template,
        $viewMode,
        $courseObject;
    
    public function __construct()
    {
        $this->courseCode = claro_get_current_course_id();
        $this->courseId = ClaroCourse::getIdFromCode($this->courseCode);
        $this->userId = claro_get_current_user_id();
        $this->profileId = claro_get_current_user_profile_id_in_course();
        $this->viewMode = claro_get_tool_view_mode();
        $this->courseObject = new ClaroCourse();
        $this->courseObject->load($this->courseCode);
        $this->currentCourseContext = Claro_Context::getUrlContext(array( CLARO_CONTEXT_COURSE => $this->courseCode ));
        
        $this->template = new CoreTemplate('coursetoollist.tpl.php');
    }
    
    public function setViewMode( $viewMode )
    {
        $this->viewMode = $viewMode;
    }
    
    protected function getCurrentToolLabel()
    {
        return $GLOBALS['tlabelReq'];
    }
    
    protected function getUserLastAction()
    {
        return (
            (isset($_SESSION['last_action']) && $_SESSION['last_action'] != '1970-01-01 00:00:00')
                ? $_SESSION['last_action']
                : date('Y-m-d H:i:s')
        );
    }
    
    protected function getModuleLinkList()
    {
        //$toolNameList = claro_get_tool_name_list();
        
        $claro_notifier = Claroline::getInstance()->notification;

        // Get tool id where new events have been recorded since last login
        if ( $this->userId )
        {
            $date = $claro_notifier->get_notification_date( $this->userId );
            $modified_tools = $claro_notifier->get_notified_tools( $this->courseCode, $date, $this->userId );
        }
        else
        {
            $modified_tools = array();
        }
        
        $toolLinkList = array();
        
        $toolName = get_lang('Course homepage');

        // Trick to find how to build URL, must be IMPROVED
        $url = claro_htmlspecialchars( Url::Contextualize( get_path('url') . '/claroline/course/index.php?cid='.$this->courseCode ) );
        $icon = get_icon_url('coursehome');
        $htmlId = 'id="courseHomePage"';
        $removableTool = false;
        
        $classCurrent = ( empty($GLOBALS['tlabelReq']) ) ? ' currentTool' : '';
        
        $toolLinkList[] = '<a '.$htmlId.'class="item' . $classCurrent . '" href="' . $url . '">'
            . '<img class="clItemTool"  src="' . $icon . '" alt="" />&nbsp;'
            . $toolName
            . '</a>' . "\n";

        // Generate tool lists
        $toolListSource = claro_get_course_tool_list( $this->courseCode, $this->profileId, true);

        foreach ( $toolListSource as $thisTool )
        {
            // Special case when display mode is student and tool invisible doesn't display it
            if ( ( $this->viewMode == 'STUDENT' ) && ! $thisTool['visibility']  )
            {
                continue;
            }

            if (isset($thisTool['label'])) // standart claroline tool or module of type tool
            {
                $thisToolName = $thisTool['name'];
                $toolName = get_lang($thisToolName);

                // Trick to find how to build URL, must be IMPROVED
                $url = claro_htmlspecialchars( Url::Contextualize( get_module_url($thisTool['label']) . '/' . $thisTool['url'], $this->currentCourseContext ) );
                $icon = get_module_url($thisTool['label']) .'/'. $thisTool['icon'];
                $htmlId = 'id="' . $thisTool['label'] . '"';
                $removableTool = false;
            }
            else   // External tool added by course manager
            {
                if ( ! empty($thisTool['external_name'])) $toolName = $thisTool['external_name'];
                else $toolName = '<i>no name</i>';
                $url = claro_htmlspecialchars( trim($thisTool['url']) );
                $icon = get_icon_url('link');
                $htmlId = '';
                $removableTool = true;
            }

            $style = !$thisTool['visibility']? 'invisible ' : '';
            $classItem = (in_array($thisTool['id'], $modified_tools)) ? ' hot' : '';
            $classCurrent = ( isset($thisTool['label']) && $thisTool['label'] == $GLOBALS['tlabelReq'] ) ? ' currentTool' : '';

            if ( !empty( $url ) )
            {
                $toolLinkList[] = '<a '.$htmlId.'class="' . $style . 'item' . $classItem . $classCurrent . '" href="' . $url . '">'
                                      . '<img class="clItemTool"  src="' . $icon . '" alt="" />&nbsp;'
                                      . $toolName
                                      . '</a>' . "\n";
            }
            else
            {
                $toolLinkList[] = '<span ' . $style . '>'
                                      . '<img class="clItemTool" src="' . $icon . '" alt="" />&nbsp;'
                                      . $toolName
                                      . '</span>' . "\n";
            }
        }
        
        return $toolLinkList;
    }
    
    protected function getExtraToolLinkList()
    {
        $otherToolsList = array();
        
        $otherToolsList[] = '<img class="iconDefinitionList" src="'.get_icon_url('hot').'" alt="'.get_lang('New items').'" />'
                  . ' '.get_lang('New items').' '
                  . '(<a href="'.claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'notification_date.php', $this->currentCourseContext ) ).'">'
                  . get_lang('to another date')
                   . '</a>)'
                  . ((substr($this->getUserLastAction(), strlen($this->getUserLastAction()) - 8) == '00:00:00' ) ?
                      (' <br />['.claro_html_localised_date(
                          get_locale('dateFormatNumeric'),
                          strtotime($this->getUserLastAction())).']') :
                      (''))
                  . "\n";
        
        return $otherToolsList;
    }
    
    protected function getManagerLinkList()
    {
        $courseManageToolLinkList = array();
        
        if ( $this->viewMode != 'STUDENT' )
        { 
            $courseManageToolLinkList[] = '<a class="claroCmd" href="' . claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb')  . 'course/tools.php', $this->currentCourseContext ) ) . '">'
                                . '<img src="' . get_icon_url('edit') . '" alt="" /> '
                                . get_lang('Edit Tool list')
                                . '</a>';

            $courseManageToolLinkList[] = '<a class="claroCmd" href="' . claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'course/settings.php', $this->currentCourseContext ) ) . '">'
                                        . '<img src="' . get_icon_url('settings') . '" alt="" /> '
                                        . get_lang('Course settings')
                                        . '</a>';

            if ( !ClaroCourse::isSessionCourse($this->courseId)
                && claro_is_allowed_to_create_course()
                && ( get_conf( 'courseSessionAllowed' , true ) || claro_is_platform_admin() ) )
            {
                $courseManageToolLinkList[] = '<a class="claroCmd" href="' . claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb')
                                            . 'course/create.php', array('course_sourceCourseId'=>$this->courseId) )) . '">'
                                            . '<img src="' . get_icon_url('duplicate') . '" alt="" /> '
                                            . get_lang("Create a session course")
                                            . '</a>' ;
            }

            if( get_conf('is_trackingEnabled') )
            {
                $courseManageToolLinkList[] =  '<a class="claroCmd" href="' . claro_htmlspecialchars(Url::Contextualize( get_path('clarolineRepositoryWeb') . 'tracking/courseReport.php', $this->currentCourseContext )) . '">'
                                            . '<img src="' . get_icon_url('statistics') . '" alt="" /> '
                                            . get_lang('Statistics')
                                            . '</a>';
            }

            $extraManageToolList = get_course_manage_module_list(true);

            if ( $extraManageToolList )
            {
                foreach ( $extraManageToolList as $extraManageTool )
                {
                    $courseManageToolLinkList[] =  '<a class="claroCmd" href="' . claro_htmlspecialchars(Url::Contextualize( get_module_entry_url($extraManageTool['label'] ) ) ) . '">'
                    .                             '<img src="' . get_module_icon_url($extraManageTool['label'], $extraManageTool['icon'], 'settings') . '" alt="" /> '
                    .                             get_lang($extraManageTool['name'])
                    .                             '</a>'
                    ;
                }
            }
        }
        
        return $courseManageToolLinkList;

    }
    
    public function render()
    {
        $this->template->assign( 'toolLinkList', $this->getModuleLinkList() );
        $this->template->assign( 'otherToolsList', $this->getExtraToolLinkList() );
        $this->template->assign( 'courseManageToolLinkList', $this->getManagerLinkList() );
        
        return $this->template->render();
    }
}

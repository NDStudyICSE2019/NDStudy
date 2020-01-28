<?php // $Id: agenda.php 14381 2013-02-05 07:49:22Z zefredz $

/**
 * CLAROLINE
 *
 * - For a Student -> View agenda content
 * - For a Prof    ->
 *         - View agenda content
 *         - Update/delete existing entries
 *         - Add entries
 *         - generate an "announce" entries about an entries
 *
 * @version     $Revision: 14381 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @package     CLCAL
 */

$tlabelReq  = 'CLCAL';
$gidReset   = true;

require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';

$_user      = claro_get_current_user_data();
$_course    = claro_get_current_course_data();

//**//

if (claro_is_in_a_group()) $currentContext = claro_get_current_context(array('course','group'));
else                       $currentContext = claro_get_current_context('course');

//**/

FromKernel::uses('core/linker.lib');
ResourceLinker::init();

require_once './lib/agenda.lib.php';
require_once get_path('incRepositorySys') . '/lib/form.lib.php';

require claro_get_conf_repository() . 'ical.conf.php';
require claro_get_conf_repository() . 'rss.conf.php';

$context = claro_get_current_context(CLARO_CONTEXT_COURSE);
define('CONFVAL_LOG_CALENDAR_INSERT', false);
define('CONFVAL_LOG_CALENDAR_DELETE', false);
define('CONFVAL_LOG_CALENDAR_UPDATE', false);

if ( !claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

$nameTools = get_lang('Agenda');

claro_set_display_mode_available(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

if ( $is_allowedToEdit )
{
// 'rqAdd' ,'rqEdit', 'exAdd','exEdit', 'exDelete', 'exDeleteAll', 'mkShow', 'mkHide'

    if ( isset($_REQUEST['cmd'])
        && ( 'rqAdd' == $_REQUEST['cmd'] || 'rqEdit' == $_REQUEST['cmd'] )
    )
    {
        if ( 'rqEdit' == $_REQUEST['cmd'] )
        {
            $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                    array( 'id' => (int) $_REQUEST['id'] ) );
            
            ResourceLinker::setCurrentLocator( $currentLocator );
        }
    }
}

$tbl_c_names = claro_sql_get_course_tbl();
$tbl_calendar_event = $tbl_c_names['calendar_event'];

$cmd = ( isset($_REQUEST['cmd']) ) ?$_REQUEST['cmd']: null;

$dialogBox = new DialogBox();

// Order direction
$acceptedValues = array('DESC','ASC');

if (!empty($_REQUEST['order'])
    && in_array(strtoupper($_REQUEST['order']), $acceptedValues))
{
    $orderDirection = strtoupper($_REQUEST['order']);
}
else
{
    $orderDirection = 'ASC';
}

$_SESSION['orderDirection'] = $orderDirection;


/**
 * COMMANDS SECTION
 */

$display_form = false;

if ( $is_allowedToEdit )
{
    $id         = ( isset($_REQUEST['id']) ) ? ((int) $_REQUEST['id']) : (0);
    $title      = ( isset($_REQUEST['title']) ) ? (trim($_REQUEST['title'])) : ('');
    $content    = ( isset($_REQUEST['content']) ) ? (trim($_REQUEST['content'])) : ('');
    $lasting    = ( isset($_REQUEST['lasting']) ) ? (trim($_REQUEST['lasting'])) : ('');
    $speakers   = ( isset($_REQUEST['speakers']) ) ? (trim($_REQUEST['speakers'])) : ('');
    $location   = ( isset($_REQUEST['location']) ) ? (trim($_REQUEST['location'])) : ('');
    
    $autoExportRefresh = false;
    
    if ( 'exAdd' == $cmd )
    {
        $date_selection = $_REQUEST['fyear'] . '-' . $_REQUEST['fmonth'] . '-' . $_REQUEST['fday'];
        $hour           = $_REQUEST['fhour'] . ':' . $_REQUEST['fminute'] . ':00';
        
        $entryId = agenda_add_item($title, $content, $date_selection, $hour, $lasting, $speakers, $location) ;
        
        if ( $entryId != false )
        {
            $dialogBox->success( get_lang('Event added to the agenda') );
            
            $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                array( 'id' => (int) $entryId ) );
            
            $resourceList =  isset($_REQUEST['resourceList'])
                ? $_REQUEST['resourceList']
                : array()
                ;
                
            ResourceLinker::updateLinkList( $currentLocator, $resourceList );

            if ( CONFVAL_LOG_CALENDAR_INSERT )
            {
                $claroline->log('CALENDAR', array ('ADD_ENTRY' => $entryId));
            }

            // notify that a new agenda event has been posted
            $eventNotifier->notifyCourseEvent('agenda_event_added', claro_get_current_course_id(), claro_get_current_tool_id(), $entryId, claro_get_current_group_id(), '0');
            $autoExportRefresh = true;
        }
        else
        {
            $dialogBox->error( get_lang('Unable to add the event to the agenda') );
        }
    }
    
    
    /*------------------------------------------------------------------------
    EDIT EVENT COMMAND
    --------------------------------------------------------------------------*/
    
    if ( 'exEdit' == $cmd )
    {
        $date_selection = $_REQUEST['fyear'] . '-' . $_REQUEST['fmonth'] . '-' . $_REQUEST['fday'];
        $hour           = $_REQUEST['fhour'] . ':' . $_REQUEST['fminute'] . ':00';

        if ( !empty($id) )
        {
            if ( agenda_update_item($id,$title,$content,$date_selection,$hour,$lasting,$speakers,$location) )
            {
                $dialogBox->success( get_lang('Event updated into the agenda') );
                
                $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                    array( 'id' => (int) $id ) );
                
                $resourceList =  isset($_REQUEST['resourceList'])
                    ? $_REQUEST['resourceList']
                    : array()
                    ;
                    
                ResourceLinker::updateLinkList( $currentLocator, $resourceList );

                $eventNotifier->notifyCourseEvent('agenda_event_modified', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0'); // notify changes to event manager
                $autoExportRefresh = true;
            }
            else
            {
                $dialogBox->error( get_lang('Unable to update the event into the agenda') );
            }
        }
    }
    
    
    /*------------------------------------------------------------------------
    DELETE EVENT COMMAND
    --------------------------------------------------------------------------*/
    
    if ( 'exDelete' == $cmd && !empty($id) )
    {
        if ( agenda_delete_item($id) )
        {
            $dialogBox->success( get_lang('Event deleted from the agenda') );
            
            $eventNotifier->notifyCourseEvent('agenda_event_deleted', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0'); // notify changes to event manager
            $autoExportRefresh = true;
            if ( CONFVAL_LOG_CALENDAR_DELETE )
            {
                $claroline->log('CALENDAR',array ('DELETE_ENTRY' => $id));
            }
        }
        else
        {
            $dialogBox->error( get_lang('Unable to delete event from the agenda') );
        }
        
        // linker_delete_resource();
    }
    
    
    /*----------------------------------------------------------------------------
    DELETE ALL EVENTS COMMAND
    ----------------------------------------------------------------------------*/
    
    if ( 'exDeleteAll' == $cmd )
    {
        if ( agenda_delete_all_items())
        {
            $eventNotifier->notifyCourseEvent('agenda_event_list_deleted', claro_get_current_course_id(), claro_get_current_tool_id(), null, claro_get_current_group_id(), '0');

            $dialogBox->success( get_lang('All events deleted from the agenda') );

            if ( CONFVAL_LOG_CALENDAR_DELETE )
            {
                $claroline->log('CALENDAR', array ('DELETE_ENTRY' => 'ALL') );
            }
        }
        else
        {
            $dialogBox->error( get_lang('Unable to delete all events from the agenda') );
        }

        // linker_delete_all_tool_resources();
    }
    
    
    /*-------------------------------------------------------------------------
    EDIT EVENT VISIBILITY
    ---------------------------------------------------------------------------*/
    
    if ( 'mkShow' == $cmd  || 'mkHide' == $cmd )
    {
        if ($cmd == 'mkShow')
        {
            $visibility = 'SHOW';
            $eventNotifier->notifyCourseEvent('agenda_event_visible', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0'); // notify changes to event manager
            $autoExportRefresh = true;
        }

        if ($cmd == 'mkHide')
        {
            $visibility = 'HIDE';
            $eventNotifier->notifyCourseEvent('agenda_event_invisible', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0'); // notify changes to event manager
            $autoExportRefresh = true;
        }

        agenda_set_item_visibility($id, $visibility);
    }
    
    
    /*------------------------------------------------------------------------
    EVENT EDIT
    --------------------------------------------------------------------------*/
    
    if ( 'rqEdit' == $cmd  || 'rqAdd' == $cmd  )
    {
        claro_set_display_mode_available(false);

        if ( 'rqEdit' == $cmd  && !empty($id) )
        {
            $editedEvent = agenda_get_item($id) ;
            // get date as unixtimestamp for claro_dis_date_form and claro_html_time_form
            $editedEvent['date'] = strtotime($editedEvent['dayAncient'].' '.$editedEvent['hourAncient']);
            $nextCommand = 'exEdit';
        }
        else
        {
            $editedEvent['id']              = '';
            $editedEvent['title']           = '';
            $editedEvent['content']         = '';
            $editedEvent['date']            = time();
            $editedEvent['lastingAncient']  = false;
            $editedEvent['location']        = '';

            $nextCommand = 'exAdd';
        }
        $display_form = true;
    } // end if cmd == 'rqEdit' && cmd == 'rqAdd'

    if ( $autoExportRefresh)
    {
        // ical update
        if (get_conf('enableICalInCourse',1) )
        {
            require_once get_path('incRepositorySys') . '/lib/ical.write.lib.php';
            buildICal( array(CLARO_CONTEXT_COURSE => claro_get_current_course_id()));
        }
    }

} // end id is_allowed to edit



// Display
$noQUERY_STRING = true;

$eventList = agenda_get_item_list($currentContext,$orderDirection);

// Command list
$cmdList = array();

$cmdList[] = array(
    'name' => get_lang('Today'),
    'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '#today'))
);


if ( count($eventList) > 0 )
{
    if ( get_conf('enableICalInCourse') )
    {
        $cmdList[] = array(
            'img' => 'calendar',
            'name' => get_lang('Download'),
            'url' => claro_htmlspecialchars(Url::Contextualize( get_path('url').'/claroline/backends/ical.php' ))
        );
    }
    
    if ( $orderDirection == 'DESC' )
    {
        $cmdList[] = array(
            'img' => 'reverse',
            'name' => get_lang('Oldest first'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?order=asc'))
        );
    }
    else
    {
        $cmdList[] = array(
            'img' => 'reverse',
            'name' => get_lang('Newest first'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?order=desc'))
        );
    }
}

if (claro_is_allowed_to_edit())
{
    $cmdList[] = array(
        'img' => 'agenda_new',
        'name' => get_lang('Add an event'),
        'url' => claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] . '?cmd=rqAdd' ))
    );
    
    if ( count($eventList) > 0 )
    {
        $cmdList[] = array(
            'img' => 'delete',
            'name' => get_lang('Clear up event list'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exDeleteAll')) . '" '
                   . ' onclick="javascript:if(!confirm(\'' . clean_str_for_javascript(get_lang('Clear up event list ?')) . '\')) return false;'
        );
    }
}

// Title parts
if     ( 'rqAdd' == $cmd )  $subTitle = get_lang('Add an event');
elseif ( 'rqEdit' == $cmd ) $subTitle = get_lang('Edit Event');
elseif ($orderDirection == 'ASC') $subTitle = get_lang('Sorted in ascending order (from January to December)');
elseif ($orderDirection == 'DESC') $subTitle = get_lang('Sorted in descending order (from December to January)');
else                        $subTitle = '';

$titleParts = array('mainTitle' => $nameTools, 'subTitle' => $subTitle);


// Display
//TODO this tool could use a template

Claroline::getDisplay()->body->appendContent(claro_html_tool_title($titleParts, null, $cmdList));
Claroline::getDisplay()->body->appendContent($dialogBox->render());

if ($display_form)
{
    // Ressource linker
    if ( 'rqEdit' == $_REQUEST['cmd'] )
    {
        ResourceLinker::setCurrentLocator(
            ResourceLinker::$Navigator->getCurrentLocator(
                array( 'id' => (int) $_REQUEST['id'] ) ) );
    }
    
    $template = new ModuleTemplate($tlabelReq, 'form.tpl.php');
    $template->assign('formAction', Url::Contextualize($_SERVER['PHP_SELF']));
    $template->assign('relayContext', claro_form_relay_context());
    $template->assign('cmd', $nextCommand);
    $template->assign('event', $editedEvent);
    
    Claroline::getDisplay()->body->appendContent($template->render());
}

if (claro_is_user_authenticated())
{
    $date = $claro_notifier->get_notification_date(claro_get_current_user_id());
}

$preparedEventList = array();
foreach ( $eventList as $thisEvent )
{
    if (('HIDE' == $thisEvent['visibility'] && $is_allowedToEdit)
        || 'SHOW' == $thisEvent['visibility'])
    {
        // Hot item ?
        if (claro_is_user_authenticated()
            && $claro_notifier->is_a_notified_ressource(claro_get_current_course_id(), $date, claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $thisEvent['id']))
        {
            $thisEvent['hot'] = true;
        }
        else
        {
            $thisEvent['hot'] = false;
        }
        
        // Visible item ?
        if ($thisEvent['visibility'] == 'SHOW')
        {
            $thisEvent['visible'] = true;
        }
        else
        {
            $thisEvent['visible'] = false;
        }
        
        // Linked resources ?
        $currentLocator = ResourceLinker::$Navigator->getCurrentLocator( array('id' => $thisEvent['id'] ) );
        
        $thisEvent['currentLocator'] = $currentLocator;
        
        if (($is_allowedToEdit || $thisEvent['visible']))
        {
            $preparedEventList[] = $thisEvent;
        }
    }
}


$template = new ModuleTemplate($tlabelReq, 'list.tpl.php');
$template->assign('eventList', $preparedEventList);
$template->assign('orderDirection', $orderDirection);

Claroline::getDisplay()->body->appendContent($template->render());

echo Claroline::getDisplay()->render();

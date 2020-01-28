<?php // $Id: announcements.php 14716 2014-02-17 12:46:15Z zefredz $

/**
 * CLAROLINE
 *
 * The script works with the 'annoucement' tables in the main claroline table.
 *
 * DB Table structure:
 * ---
 *
 * id           : announcement id
 * contenu      : announcement content
 * visibleFrom  : date of the publication of the announcement
 * visibleUntil : date of expiration of the announcement
 * temps        : date of the announcement introduction / modification
 * title        : optionnal title for an announcement
 * ordre        : order of the announcement display
 *              (the announcements are display in desc order)
 *
 * Script Structure:
 * ---
 *
 *        commands
 *            move up and down announcement
 *            delete announcement
 *            delete all announcements
 *            modify announcement
 *            submit announcement (new or modified)
 *
 *        display
 *            title
 *          button line
 *          form
 *            announcement list
 *            form to fill new or modified announcement
 *
 * @version     $Revision: 14716 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLANN
 * @author      Claro Team <cvs@claroline.net>
 */

define('CONFVAL_LOG_ANNOUNCEMENT_INSERT', false);
define('CONFVAL_LOG_ANNOUNCEMENT_DELETE', false);
define('CONFVAL_LOG_ANNOUNCEMENT_UPDATE', false);


/**
 *  CLAROLINE MAIN SETTINGS
 */

$tlabelReq = 'CLANN';
$gidReset = true;

require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);
$context = claro_get_current_context(CLARO_CONTEXT_COURSE);

// Local lib
require_once './lib/announcement.lib.php';

// get some shared lib
require_once get_path('incRepositorySys') . '/lib/sendmail.lib.php';
// require_once get_path('clarolineRepositorySys') . '/linker/linker.inc.php';

FromKernel::uses('core/linker.lib');
ResourceLinker::init();

// Get specific conf file
require claro_get_conf_repository() . 'ical.conf.php';
require claro_get_conf_repository() . 'rss.conf.php';

claro_set_display_mode_available(true);

// Set flag following depending on settings
$is_allowedToEdit = claro_is_allowed_to_edit();
$courseId         = claro_get_current_course_id();
$userLastLogin    = claro_get_current_user_data('lastLogin');

// DB tables definition
$tbl_cdb_names   = claro_sql_get_main_tbl();
$tbl_course_user = $tbl_cdb_names['rel_course_user'];
$tbl_user        = $tbl_cdb_names['user'];

// Default display
$displayForm = false;
$displayList = true;

$subTitle = '';

$dialogBox = new DialogBox();

// avoid executing commands twice when switching view mode
if  ( isset($_REQUEST['viewMode']) )
{
    unset ( $_REQUEST['cmd'] );
    unset ( $_REQUEST['id'] );
}


/**
 * COMMANDS SECTION (COURSE MANAGER ONLY)
 */

$id     = isset($_REQUEST['id'])  ? (int) $_REQUEST['id']   : 0;
$cmd    = isset($_REQUEST['cmd']) ? $cmd = $_REQUEST['cmd'] : '';

if($is_allowedToEdit) // check teacher status
{
    if( isset($_REQUEST['cmd'])
          && ($_REQUEST['cmd'] == 'rqCreate' || $_REQUEST['cmd'] == 'rqEdit')  )
    {
        if ( 'rqEdit' == $_REQUEST['cmd'] )
        {
            $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                    array( 'id' => (int) $_REQUEST['id'] ) );
            
            ResourceLinker::setCurrentLocator( $currentLocator );
        }
    }
    
    $autoExportRefresh = false;
    if ( !empty($cmd) )
    {
        // Move announcements up or down
        if ( 'exMvDown' == $cmd  )
        {
            if (move_entry($id, 'DOWN'))
            {
                $dialogBox->success(get_lang('Item has been moved down'));
            }
            else
            {
                $dialogBox->error(get_lang('Item can\'t be moved down'));
            }
        }
        if ( 'exMvUp' == $cmd )
        {
            if (move_entry($id, 'UP'))
            {
                $dialogBox->success(get_lang('Item has been moved up'));
            }
            else
            {
                $dialogBox->error(get_lang('Item can\'t be moved up'));
            }
        }
        
        // Delete announcement
        if ( 'exDelete' == $cmd )
        {
            if ( announcement_delete_item($id) )
            {
                $dialogBox->success( get_lang('Announcement has been deleted') );
                
                if ( CONFVAL_LOG_ANNOUNCEMENT_DELETE ) $claroline->log('ANNOUNCEMENT',array('DELETE_ENTRY'=>$id));
                $eventNotifier->notifyCourseEvent('anouncement_deleted', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0');
                $autoExportRefresh = true;
                
                #linker_delete_resource();
            }
            else
            {
                $dialogBox->error( get_lang('Cannot delete announcement') );
            }
        }
        
        // Delete all announcements
        if ( 'exDeleteAll' == $cmd )
        {
            $announcementList = announcement_get_item_list($context);
            if ( announcement_delete_all_items() )
            {
                $dialogBox->success( get_lang('Announcements list has been cleared up') );
                
                if ( CONFVAL_LOG_ANNOUNCEMENT_DELETE ) $claroline->log('ANNOUNCEMENT',array ('DELETE_ENTRY' => 'ALL'));
                $eventNotifier->notifyCourseEvent('all_anouncement_deleted', claro_get_current_course_id(), claro_get_current_tool_id(), $announcementList , claro_get_current_group_id(), '0');
                $autoExportRefresh = true;
                
                #linker_delete_all_tool_resources();
            }
            else
            {
                $dialogBox->error( get_lang('Cannot delete announcement list') );
            }
        }
        
        // Require announcement's edition
        if ( 'rqEdit' == $cmd  )
        {
            $subTitle = get_lang('Modifies this announcement');
            claro_set_display_mode_available(false);
            
            // Get the announcement to modify
            $announcement = announcement_get_item($id);
            $displayForm = true;
            $formCmd = 'exEdit';
        
        }
        
        // Switch announcement's visibility
        if ( 'mkShow' == $cmd || 'mkHide' == $cmd )
        {
            if ( 'mkShow' == $cmd )
            {
                $eventNotifier->notifyCourseEvent('anouncement_visible', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0');
                $visibility = 'SHOW';
            }
            if ( 'mkHide' == $cmd )
            {
                $eventNotifier->notifyCourseEvent('anouncement_invisible', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0');
                $visibility = 'HIDE';
            }
            if (announcement_set_item_visibility($id, $visibility))
            {
                $dialogBox->success( get_lang('Visibility modified') );
            }
            $autoExportRefresh = true;
        }
        
        // Require new announcement's creation
        if ( 'rqCreate' == $cmd )
        {
            $subTitle = get_lang('Add announcement');
            claro_set_display_mode_available(false);
            $displayForm = true;
            $formCmd = 'exCreate';
            $announcement=array();
        }
        
        // Submit announcement
        if ( 'exCreate' == $cmd  || 'exEdit' == $cmd )
        {
            $title       = isset($_REQUEST['title'])      ? trim($_REQUEST['title']) : '';
            $content     = isset($_REQUEST['newContent']) ? trim($_REQUEST['newContent']) : '';
            $emailOption = isset($_REQUEST['emailOption'])? (int) $_REQUEST['emailOption'] : 0;
            $visibility  = (int) $_REQUEST['visibility'];
            
            // Manage the visibility options
            if (isset($_REQUEST['visibility']) && $_REQUEST['visibility'] == 1)
            {
                if (isset($_REQUEST['enable_visible_from']) && (isset($_REQUEST['visible_from_year']) && isset($_REQUEST['visible_from_month']) && isset($_REQUEST['visible_from_day'])))
                {
                    $visible_from = $_REQUEST['visible_from_year'].'-'.$_REQUEST['visible_from_month'].'-'.$_REQUEST['visible_from_day'];
                }
                else
                {
                    $visible_from = null;
                }
                
                if (isset($_REQUEST['enable_visible_until']) && (isset($_REQUEST['visible_until_year']) && isset($_REQUEST['visible_until_month']) && isset($_REQUEST['visible_until_day'])))
                {
                    $visible_until = $_REQUEST['visible_until_year'].'-'.$_REQUEST['visible_until_month'].'-'.$_REQUEST['visible_until_day'];
                }
                else
                {
                    $visible_until = null;
                }
            }
            else
            {
                $visible_from = null;
                $visible_until = null;
            }
            
            // Modification of an announcement
            if ( 'exEdit' == $cmd )
            {
                // One of the two visible date fields is null OR the "from" field is <= the "until" field
                if ((is_null($visible_from) || is_null($visible_until)) || ($visible_from <= $visible_until))
                {
                    if ( announcement_update_item((int) $_REQUEST['id'], $title, $content, $visible_from, $visible_until, $visibility) )
                    {
                        $dialogBox->success( get_lang('Announcement has been modified') );
                        
                        $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                            array( 'id' => (int) $_REQUEST['id'] ) );
                        
                        $resourceList =  isset($_REQUEST['resourceList'])
                            ? $_REQUEST['resourceList']
                            : array()
                            ;
                            
                        ResourceLinker::updateLinkList( $currentLocator, $resourceList );
                        
                        $eventNotifier->notifyCourseEvent('anouncement_modified', claro_get_current_course_id(), claro_get_current_tool_id(), $id, claro_get_current_group_id(), '0');
                        if (CONFVAL_LOG_ANNOUNCEMENT_UPDATE) $claroling->log('ANNOUNCEMENT', array ('UPDATE_ENTRY'=>$_REQUEST['id']));
                        $autoExportRefresh = true;
                    }
                    else
                    {
                        if ( $failure = claro_failure::get_last_failure() )
                        {
                            $dialogBox->error( $failure );
                        }
                        else
                        {
                            $dialogBox->error( get_lang('Impossible to modify the announcement') );
                        }
                        
                        $emailOption = 0;
                    }
                }
                else
                {
                    $dialogBox->error( get_lang('The "visible from" date can\'t exceed the "visible until" date') );
                    $emailOption = 0;
                }
            }
            
            // Create a new announcement
            elseif ( 'exCreate' == $cmd )
            {
                // One of the two visible date fields is null OR the "from" field is <= the "until" field
                if ((is_null($visible_from) || is_null($visible_until)) || ($visible_from <= $visible_until))
                {
                    // Determine the rank of the new announcement
                    $insert_id = announcement_add_item($title, $content, $visible_from, $visible_until, $visibility) ;
                    
                    if ( $insert_id )
                    {
                        $dialogBox->success( get_lang('Announcement has been added') );
                        
                        $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                            array( 'id' => (int) $insert_id ) );
                        
                        $resourceList =  isset($_REQUEST['resourceList'])
                            ? $_REQUEST['resourceList']
                            : array()
                            ;
                            
                        ResourceLinker::updateLinkList( $currentLocator, $resourceList );
                        
                        $eventNotifier->notifyCourseEvent('anouncement_added',claro_get_current_course_id(), claro_get_current_tool_id(), $insert_id, claro_get_current_group_id(), '0');
                        if (CONFVAL_LOG_ANNOUNCEMENT_INSERT) $claroline->log('ANNOUNCEMENT',array ('INSERT_ENTRY'=>$insert_id));
                        $autoExportRefresh = true;
                    }
                    else
                    {
                        if ( $failure = claro_failure::get_last_failure() )
                        {
                            $dialogBox->error( $failure );
                        }
                        else
                        {
                            $dialogBox->error( get_lang('Impossible to add the announcement') );
                        }
                        
                        $emailOption = 0;
                    }
                }
                else
                {
                    
                    $dialogBox->error( get_lang('The "visible from" date can\'t exceed the "visible until" date') );
                    $emailOption = 0;
                }
            } // end elseif cmd == exCreate
            
            // Email sending (optionnal)
            if ( 1 == $emailOption )
            {
                $courseSender = claro_get_current_user_data('firstName') . ' ' . claro_get_current_user_data('lastName');
                
                $courseOfficialCode = claro_get_current_course_data('officialCode');
                
                $subject = '';
                if ( !empty($title) ) $subject .= $title ;
                else                  $subject .= get_lang('Message from your lecturer');
                
                $msgContent = $content;
                
                // Enclosed resource
                $body = $msgContent . "\n" .
                    "\n" .
                    ResourceLinker::renderLinkList( $currentLocator, true );
                
                require_once dirname(__FILE__) . '/../messaging/lib/message/messagetosend.lib.php';
                require_once dirname(__FILE__) . '/../messaging/lib/recipient/courserecipient.lib.php';
                
                $courseRecipient = new CourseRecipient(claro_get_current_course_id());
                
                $message = new MessageToSend(claro_get_current_user_id(),$subject,$body);
                $message->setCourse(claro_get_current_course_id());
                $message->setTools('CLANN');
                
                $messageId = $courseRecipient->sendMessage($message);
                
                if ( $failure = claro_failure::get_last_failure() )
                {
                    $dialogBox->warning( $failure );
                }
                
            }   // end if $emailOption==1
        }   // end if $submit Announcement
        
        if ($autoExportRefresh)
        {
            /**
             * in future, the 2 following calls would be pas by event manager.
             */
            // rss update
            /*if ( get_conf('enableRssInCourse',1))
            {
                require_once get_path('incRepositorySys') . '/lib/rss.write.lib.php';
                build_rss( array('course' => claro_get_current_course_id()));
            }*/
            
            // iCal update
            if (get_conf('enableICalInCourse', 1)  )
            {
                require_once get_path('incRepositorySys') . '/lib/ical.write.lib.php';
                buildICal( array('course' => claro_get_current_course_id()));
            }
        }
        
    } // end if isset $_REQUEST['cmd']
    
} // end if is_allowedToEdit


// Prepare displays
if ($displayList)
{
    // list
    $announcementList = announcement_get_item_list($context);
    $bottomAnnouncement = $announcementQty = count($announcementList);
}



$displayButtonLine = (bool) $is_allowedToEdit && ( empty($cmd) || $cmd != 'rqEdit' || $cmd != 'rqCreate' ) ;

// Command list
$cmdList = array();

if ( $displayButtonLine )
{
    if ( $cmd != 'rqEdit' && $cmd != 'rqCreate'  )
    {
        $cmdList[] = array(
            'img' => 'announcement_new',
            'name' => get_lang('Add announcement'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=rqCreate'))
        );
    }
    
    if ( claro_is_course_manager() )
    {
        $cmdList[] = array(
            'img' => 'mail_close',
            'name' => get_lang('Messages to selected users'),
            'url' => claro_htmlspecialchars(Url::Contextualize(get_path('clarolineRepositoryWeb') . 'messaging/messagescourse.php?from=clann'))
        );
    }
    
    if (($announcementQty > 0 ))
    {
        $cmdList[] = array(
            'img' => 'delete',
            'name' => get_lang('Clear up list of announcements'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exDeleteAll')),
            'params' => array('onclick' => 'return CLANN.confirmationDelAll()')
        );
    }
}


/**
 *  DISPLAY SECTION
 */

$nameTools = get_lang('Announcements');
$noQUERY_STRING = true;

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance()->addLangVar('Are you sure you want to delete all the announcements ?');
JavascriptLanguage::getInstance()->addLangVar('Are you sure to delete %name ?');

JavascriptLoader::getInstance()->load('announcements');

$output = '';

if ( !empty( $subTitle ) )
{
    $titleParts = array('mainTitle' => $nameTools, 'subTitle' => $subTitle);
}
else
{
    $titleParts = $nameTools;
}

Claroline::getDisplay()->body->appendContent(claro_html_tool_title($titleParts, null, $cmdList));
Claroline::getDisplay()->body->appendContent($dialogBox->render());


/**
 * FORM TO FILL OR MODIFY AN ANNOUNCEMENT
 */

if ( $displayForm )
{
    // DISPLAY ADD ANNOUNCEMENT COMMAND
    
    // Ressource linker
    if ( $_REQUEST['cmd'] == 'rqEdit' )
    {
        ResourceLinker::setCurrentLocator(
            ResourceLinker::$Navigator->getCurrentLocator(
                array( 'id' => (int) $_REQUEST['id'] ) ) );
    }
    
    $template = new ModuleTemplate($tlabelReq, 'form.tpl.php');
    $template->assign('formAction', Url::Contextualize($_SERVER['PHP_SELF']));
    $template->assign('relayContext', claro_form_relay_context());
    $template->assign('cmd', $formCmd);
    $template->assign('announcement', $announcement);
    
    Claroline::getDisplay()->body->appendContent($template->render());
}


/**
 * ANNOUNCEMENTS LIST
 */
if ($displayList)
{
    // Get notification date
    if (claro_is_user_authenticated()) $date = $claro_notifier->get_notification_date(claro_get_current_user_id());
    
    $preparedAnnList = array();
    $lastPostDate = '';
    foreach ( $announcementList as $thisAnn )
    {
        // Hide hidden and out of deadline elements
        $isVisible = (bool) ($thisAnn['visibility'] == 'SHOW') ? (1) : (0);
        $isOffDeadline = (bool)
            (
                (isset($thisAnn['visibleFrom'])
                    && strtotime($thisAnn['visibleFrom']) > time()
                )
                ||
                (isset($thisAnn['visibleUntil'])
                    && time() > strtotime($thisAnn['visibleUntil'])+86400
                )
            ) ? (1) : (0);

        if ( !$isVisible || $isOffDeadline )
        {
            $thisAnn['visible'] = false;
        }
        else
        {
            $thisAnn['visible'] = true;
        }
        
        // Flag hot items
        if (claro_is_user_authenticated()
            && $claro_notifier->is_a_notified_ressource(
                claro_get_current_course_id(),
                $date,
                claro_get_current_user_id(),
                claro_get_current_group_id(),
                claro_get_current_tool_id(),
                $thisAnn['id']
            )
        )
        {
            $thisAnn['hot'] = true;
        }
        else
        {
            $thisAnn['hot'] = false;
        }
        
        $thisAnn['content'] = make_clickable($thisAnn['content']);
        
        // Post time format in MySQL date format
        $lastPostDate = ((isset($thisAnn['visibleFrom'])) ?
            ($thisAnn['visibleFrom']) :
            ($thisAnn['time']));
        
        // Set the current locator
        $currentLocator = ResourceLinker::$Navigator->getCurrentLocator( array('id' => $thisAnn['id'] ) );
        $thisAnn['currentLocator'] = $currentLocator;
        
        
        if (($is_allowedToEdit || ($isVisible && !$isOffDeadline)))
        {
            $preparedAnnList[] = $thisAnn;
        }
    }
    
    $maxMinRanks = clann_get_max_and_min_rank();
    
    $template = new ModuleTemplate($tlabelReq, 'list.tpl.php');
    $template->assign('announcementList', $preparedAnnList);
    $template->assign('lastPostDate', $lastPostDate);
    $template->assign('maxRank', $maxMinRanks['maxRank']);
    $template->assign('minRank', $maxMinRanks['minRank']);
    
    Claroline::getDisplay()->body->appendContent($template->render());
} // end if displayList

Claroline::getDisplay()->body->appendContent( $output );

echo Claroline::getDisplay()->render();

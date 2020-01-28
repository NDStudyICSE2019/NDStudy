<?php // $Id: index.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * This page displays the course's description to the user.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/CLDSC/
 * @author      Claro Team <cvs@claroline.net>
 * @package     CLDSC
 * @since       1.9
 */

// TODO add config var to allow multiple post of same type
$tlabelReq = 'CLDSC';

require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

claro_set_display_mode_available(true);

$is_allowedToEdit = claro_is_allowed_to_edit();

//-- Tool libraries
include_once get_module_path($tlabelReq) . '/lib/courseDescription.class.php';
include_once get_module_path($tlabelReq) . '/lib/courseDescription.lib.php';

//-- Get $tipList
$tipList = get_tiplistinit();

/*
 * init request vars
 */
$acceptedCmdList = array('rqEdit', 'exEdit', 'exDelete', 'mkVis','mkInvis');

if ( isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList) )
{
    $cmd = $_REQUEST['cmd'];
}
else
{
    $cmd = null;
}

if ( isset($_REQUEST['descId']) && is_numeric($_REQUEST['descId']) )
{
    $descId = (int) $_REQUEST['descId'];
}
else
{
    $descId = null;
}

if ( isset($_REQUEST['category']) && $_REQUEST['category'] >= 0 )
{
    $category = $_REQUEST['category'];
}
else
{
    $category = -1;
}

/*
 * init other vars
 */
$dialogBox = new DialogBox();

if ($is_allowedToEdit && !empty($cmd))
{
    $description = new CourseDescription();
    
    if (!empty($descId))
    {
        $description->load($descId);
    }
    
    if ( $cmd == 'exEdit' )
    {
        if ( isset($_REQUEST['descTitle']) )
        {
            $description->setTitle($_REQUEST['descTitle']);
        }
        
        if ( isset($_REQUEST['descContent']) )
        {
            $description->setContent($_REQUEST['descContent']);
        }
        
        if ( isset($_REQUEST['descCategory']) )
        {
            $description->setCategory($_REQUEST['descCategory']);
        }
        
        if ( $description->validate() )
        {
            // Update description
            if ( $description->save() )
            {
                if ( $descId )
                {
                    $eventNotifier->notifyCourseEvent('course_description_modified', claro_get_current_course_id(), claro_get_current_tool_id(), $descId, claro_get_current_group_id(), '0');
                    $dialogBox->success( get_lang('Description updated') );
                }
                else
                {
                    $eventNotifier->notifyCourseEvent('course_description_added', claro_get_current_course_id(), claro_get_current_tool_id(), $descId, claro_get_current_group_id(), '0');
                    $dialogBox->success( get_lang('Description added') );
                }
            }
            else
            {
                $dialogBox->error( get_lang('Unable to update') );
            }
        }
        else
        {
            $cmd = 'rqEdit';
        }
    }
    
    
    /*-------------------------------------------------------------------------
        REQUEST DESCRIPTION ITEM EDITION
    -------------------------------------------------------------------------*/
    
    if ( $cmd == 'rqEdit' )
    {
        claro_set_display_mode_available(false);
        
        // Manage the tips
        $tips['isTitleEditable']    = isset($tipList[$category]['isEditable']) ? $tipList[$category]['isEditable'] : true;
        $tips['presetTitle']        = !empty($tipList[$category]['title']) ? claro_htmlspecialchars($tipList[$category]['title']) : '';
        $tips['question']           = !empty($tipList[$category]['question']) ? $tipList[$category]['question'] : '';
        $tips['information']        = !empty($tipList[$category]['information']) ? $tipList[$category]['information'] : '';
        
        $displayForm = true;
    }
    
    
    /*-------------------------------------------------------------------------
        DELETE DESCRIPTION ITEM
    -------------------------------------------------------------------------*/
    
    if ( $cmd == 'exDelete' )
    {
        if ( $description->delete() )
        {
            $eventNotifier->notifyCourseEvent('course_description_deleted',claro_get_current_course_id(), claro_get_current_tool_id(), $descId, claro_get_current_group_id(), '0');
            $dialogBox->success( get_lang("Description deleted.") );
        }
        else
        {
            $dialogBox->error( get_lang("Unable to delete") );
        }
    }
    
    
    /*-------------------------------------------------------------------------
        EDIT  VISIBILITY DESCRIPTION ITEM
    -------------------------------------------------------------------------*/
    
    if ( $cmd == 'mkVis' )
    {
        $description->setVisibility('VISIBLE');
        
        if ( $description->save() )
        {
            $eventNotifier->notifyCourseEvent('course_description_visible',claro_get_current_course_id(), claro_get_current_tool_id(), $descId, claro_get_current_group_id(), '0');
        }
    }
    
    if ( $cmd == 'mkInvis' )
    {
        $description->setVisibility('INVISIBLE');
        $description->save();
    }
}




// Load the description elements
$descList = course_description_get_item_list();

//Display
$nameTools = get_lang('Course description');

$noQUERY_STRING = true; // to remove parameters in the last breadcrumb link

Claroline::getDisplay()->body->appendContent(claro_html_tool_title($nameTools));
Claroline::getDisplay()->body->appendContent($dialogBox->render());

if ( $is_allowedToEdit )
{
    /**************************************************************************
    EDIT FORM DISPLAY
    **************************************************************************/
    
    if ( isset($displayForm) && $displayForm )
    {
        $template = new ModuleTemplate($tlabelReq, 'form.tpl.php');
        $template->assign('formAction', claro_htmlspecialchars($_SERVER['PHP_SELF']));
        $template->assign('relayContext', claro_form_relay_context());
        $template->assign('descId', (int) $descId);
        $template->assign('category', $category);
        $template->assign('tips', $tips);
        $template->assign('description', $description);
        
        Claroline::getDisplay()->body->appendContent($template->render());
    } // end if display form
    else
    {
        /**************************************************************************
        ADD FORM DISPLAY
        **************************************************************************/
        
        $htmlOptionsList = '';
        if ( is_array($tipList) && !empty($tipList) )
        {
            foreach ( $tipList as $key => $tip )
            {
                $alreadyUsed = false;
                foreach ( $descList as $description )
                {
                    if ( $description['category'] == $key )
                    {
                        $alreadyUsed = true;
                        break;
                    }
                }
                
                if ( ($alreadyUsed) == false)
                {
                    $htmlOptionsList .= '<option value="' . $key . '">' . claro_htmlspecialchars($tip['title']) . '</option>' . "\n";
                }
            }
        }
        
        $template = new ModuleTemplate($tlabelReq, 'select.tpl.php');
        $template->assign('formAction', claro_htmlspecialchars($_SERVER['PHP_SELF']));
        $template->assign('relayContext', claro_form_relay_context());
        $template->assign('optionsList', $htmlOptionsList);
        
        Claroline::getDisplay()->body->appendContent($template->render());
    }
} // end if is_allowedToEdit


/******************************************************************************
DESCRIPTION LIST DISPLAY
******************************************************************************/

if (claro_is_user_authenticated())
{
    $date = $claro_notifier->get_notification_date(claro_get_current_user_id());
}

$preparedDescList = array();
foreach ($descList as $description)
{
    if (claro_is_user_authenticated() && $claro_notifier->is_a_notified_ressource(claro_get_current_course_id(), $date, claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $description['id']))
    {
        $description['hot'] = true;
    }
    else
    {
        $description['hot'] = false;
    }
    
    // Remove invisible items
    if (($description['visibility'] == 'VISIBLE'
        || ($description['visibility'] == 'INVISIBLE' && $is_allowedToEdit)))
    {
        if ($description['visibility'] == 'VISIBLE')
        {
            $description['visible'] = 1;
        }
        else
        {
            $description['visible'] = 0;
        }
        
        $preparedDescList[] = $description;
    }
}

$template = new ModuleTemplate($tlabelReq, 'list.tpl.php');
$template->assign('descriptionList', $preparedDescList);

Claroline::getDisplay()->body->appendContent($template->render());

echo Claroline::getInstance()->display->render();
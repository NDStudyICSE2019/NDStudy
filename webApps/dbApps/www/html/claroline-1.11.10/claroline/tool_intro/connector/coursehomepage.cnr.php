<?php // $Id: coursehomepage.cnr.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Course home page: Announcements portlet
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLCHP
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @author      Claroline team <info@claroline.net>
 * @since       1.10
 */

require_once get_module_path( 'CLTI' ) . '/lib/toolintroductioniterator.class.php';

class CLTI_Portlet extends CourseHomePagePortlet
{
    public function renderContent()
    {
        // Init linker
        FromKernel::uses('core/linker.lib');
        ResourceLinker::init();
        
        $output = '';
        $output .= '<dl id="portletAbout">' . "\n";
        
        $toolIntroIterator = new ToolIntroductionIterator($this->courseCode);
        
        if ( count( $toolIntroIterator ) > 0)
        {
            $introList = '';
            
            foreach ($toolIntroIterator as $introItem)
            {
                if ($introItem->getVisibility() == 'SHOW')
                {
                    // Display attached resources (if any)
                    $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(array('id' => $introItem->getId()));
                    $currentLocator->setModuleLabel('CLINTRO');
                    $currentLocator->setResourceId($introItem->getId());
                    
                    $resources = ResourceLinker::renderLinkList($currentLocator);
                    
                    // Prepare the render
                    $introList .= '<dt>' . "\n"
                             . '</dt>' . "\n"
                             . '<dd'.(!$toolIntroIterator->hasNext()?' class="last"':'').'>' . "\n"
                             . claro_parse_user_text($introItem->getContent()) . "\n"
                             . $resources
                             . (claro_is_allowed_to_edit() ?
                               '<div class="manageTools"><a
                                    href="'.claro_htmlspecialchars(Url::Contextualize(get_module_url('CLTI').'/index.php?cmd=rqEd&amp;id='.$introItem->getId())).'"
                                    title="'.get_lang('Edit this item').'">
                                    <img src="'.get_icon_url('edit').'" alt="'.get_lang('Edit').'" />
                                </a>
                                
                                <a
                                    href="'.claro_htmlspecialchars(Url::Contextualize(get_module_url('CLTI').'/index.php?cmd=exDel&amp;id='.$introItem->getId())).'"
                                    title="'.get_lang('Delete this item').'">
                                    <img src="'.get_icon_url('delete').'" alt="'.get_lang('Delete').'" />
                                </a></div>' :
                               '')
                             . '</dd>' . "\n";
                }
            }
        }
        
        if ( count( $toolIntroIterator ) == 0 || empty($introList))
        {
            $output .= '<dt></dt>'
                     . '<dd>' . "\n"
                     . ' ' . get_lang('No headline') . '. '
                     . (claro_is_allowed_to_edit() ? '<a href="' . claro_htmlspecialchars(Url::Contextualize(get_module_url('CLTI').'/index.php?cmd=rqAdd')) . '">'
                     . get_lang('Would you like to add one ?') . '</a>' . "\n" : '')
                     . '</dd>' . "\n";
        }
        else
        {
            $output .= $introList;
        }
        
        $output .= '</dl>';
        
        return $output;
    }
    
    public function renderTitle()
    {
        $output = '<img '
                . 'src="' . get_icon_url('headline', 'CLTI')  . '"'
                . 'alt="' . get_lang('Headline') . '" /> '
                . get_lang('Headlines');
        
        if (claro_is_allowed_to_edit())
        {
            $output .= ' <span class="separator">|</span> <a href="'
                     . claro_htmlspecialchars(Url::Contextualize(get_module_url( 'CLTI' ) . '/index.php'))
                     . '">'
                     . '<img src="' . get_icon_url('settings') . '" alt="'.get_lang('Settings').'" /> '
                     . get_lang('Manage').'</a>';
        }
        
        return $output;
    }
}
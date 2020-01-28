<?php // $Id: desktop.cnr.php 14233 2012-08-07 12:19:51Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * User desktop : MyCalendar portlet.
 *
 * @version     $Revision: 14233 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     DESKTOP
 * @author      Claroline Team <info@claroline.net>
 *
 * @todo move to calendar module
 */

require_once get_module_path( 'CLCAL' ) . '/lib/agenda.lib.php';

class CLCAL_Portlet extends UserDesktopPortlet
{
    public function __construct()
    {
        $this->name = 'My calendar';
        $this->label = 'CLCAL_Portlet';
    }
    
    public function renderContent()
    {
        
            $output = '<div id="portletMycalendar">' . "\n"
                . '<img src="'.get_icon_url('loading').'" alt="" />' . "\n"
                . '</div>' . "\n"
                ;

            $output .= "<script type=\"text/javascript\">
    $(document).ready( function(){
        $('#portletMycalendar').load('"
            .get_module_url('CLCAL')."/ajaxHandler.php', { location : 'userdesktop' });
    });
    </script>";

        return $output;
    }
    
    public function renderTitle()
    {
        return get_lang('My calendar');
    }
}
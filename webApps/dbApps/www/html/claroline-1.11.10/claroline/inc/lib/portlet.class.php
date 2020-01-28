<?php //$Id: portlet.class.php 13117 2011-05-02 13:04:44Z abourguignon $

/**
 * CLAROLINE
 *
 * Use portlets to display informations (course list, calendar,
 * announces, ...) via connectors in user's desktop
 * or course home page.
 *
 * @version     $Revision: 13117 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since       1.10
 */

abstract class Portlet implements Display
{
    private $label;
    
    public function __construct($label)
    {
        $this->label = $label;
    }
    
    // Render title
    abstract public function renderTitle();
    
    // Render content
    abstract public function renderContent();
    
    // Render all
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
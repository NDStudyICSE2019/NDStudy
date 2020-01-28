<?php // $Id: profileToolRight.class.php 14198 2012-07-06 13:24:06Z zefredz $

/**
 * CLAROLINE
 *
 * Class to manage profile and tool right (none, user, manager)
 *
 * @version     1.11 $Revision: 14198 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLMAIN
 * @author      Claro Team <cvs@claroline.net>
 */
require_once dirname ( __FILE__ ) . '/constants.inc.php';
require_once dirname ( __FILE__ ) . '/profileToolAction.class.php';

class RightProfileToolRight extends RightProfileToolAction
{

    /**
     * Set the tool right (none, user, manager)
     *
     * @param integer $toolId tool identifier
     * @param string $right the right value
     */
    public function setToolRight ( $toolId, $right )
    {
        if ( $right == 'none' )
        {
            $this->setAction ( $toolId, 'read', false );
            $this->setAction ( $toolId, 'edit', false );
        }
        elseif ( $right == 'user' )
        {
            $this->setAction ( $toolId, 'read', true );
            $this->setAction ( $toolId, 'edit', false );
        }
        elseif ( $right == 'manager' )
        {
            $this->setAction ( $toolId, 'read', true );
            $this->setAction ( $toolId, 'edit', true );
        }
    }

    /**
     * Get the tool right (none, user, manager)
     *
     * @param integer $toolId tool identifier
     */
    public function getToolRight ( $toolId )
    {
        $readAction = (bool) $this->getAction ( $toolId, 'read' );
        $manageAction = (bool) $this->getAction ( $toolId, 'edit' );

        if ( $readAction == false && $manageAction == false )
        {
            return 'none';
        }
        elseif ( $readAction == true && $manageAction == false )
        {
            return 'user';
        }
        else
        {
            return 'manager';
        }
    }

    /**
     * Set right of the tool list
     */
    public function setToolListRight ( $toolList, $right )
    {
        foreach ( $toolList as $toolId )
        {
            $this->setToolRight ( $toolId, $right );
        }
    }

}

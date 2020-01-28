<?php

// $Id: profileToolAction.class.php 14198 2012-07-06 13:24:06Z zefredz $

if ( count ( get_included_files () ) == 1 )
{
    die ( 'The file ' . basename ( __FILE__ ) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Class to manage relation between profile and tool action
 *
 * @version     1.9 $Revision: 14198 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     RIGHT
 * @author      Claro Team <cvs@claroline.net>
 */
require_once dirname ( __FILE__ ) . '/constants.inc.php';
require_once dirname ( __FILE__ ) . '/profile.class.php';
require_once dirname ( __FILE__ ) . '/toolAction.class.php';

class RightProfileToolAction
{

    /**
     * @var $profile profile object
     */
    var $profile;

    /**
     * @array $toolActionList list action of the profile and their values
     */
    var $toolActionList = array ( );

    /**
     * @array $tbl list of table (DB)
     */
    var $tbl;

    /**
     * Constructor
     */
    public function __construct ()
    {
        $tbl_mdb_names = claro_sql_get_main_tbl ();

        $this->tbl[ 'profile' ] = $tbl_mdb_names[ 'right_profile' ];
        $this->tbl[ 'rel_profile_action' ] = $tbl_mdb_names[ 'right_rel_profile_action' ];
        $this->tbl[ 'action' ] = $tbl_mdb_names[ 'right_action' ];
        $this->tbl[ 'course_tool' ] = $tbl_mdb_names[ 'tool' ];
    }

    /**
     * Load rights of a profile
     */
    public function load ( $profile )
    {
        // load profile
        $this->profile = $profile;

        // load all tool_action
        $this->loadToolActionList ();
    }

    /**
     * Load tool action list value of a profile
     */
    public function loadToolActionList ()
    {
        // load all action for this profile type
        $sql = " SELECT A.id, A.name, A.tool_id, CT.claro_label
                 FROM `" . $this->tbl[ 'action' ] . "` `A`,
                      `" . $this->tbl[ 'course_tool' ] . "` `CT`
                 WHERE type = '" . claro_sql_escape ( $this->profile->type ) . "'
                    AND A.tool_id = CT.id
                 ORDER BY CT.def_rank";

        $actionResult = claro_sql_query_fetch_all ( $sql );

        // initialise all tool action
        foreach ( $actionResult as $action )
        {
            $toolId = $action[ 'tool_id' ];
            $actionName = $action[ 'name' ];
            $this->toolActionList[ $toolId ][ $actionName ] = false;
        }

        // load value of action
        $sql = " SELECT PA.action_id, PA.value, A.tool_id, A.name
                 FROM `" . $this->tbl[ 'rel_profile_action' ] . "` `PA`,
                      `" . $this->tbl[ 'action' ] . "` `A`
                 WHERE PA.profile_id = " . $this->profile->id . "
                 AND PA.action_id = A.id
                 AND PA.courseId = ''";

        $action_list = claro_sql_query_fetch_all ( $sql );

        // load all actions value for the profile
        foreach ( $action_list as $this_action )
        {
            $actionName = $this_action[ 'name' ];
            $actionValue = (bool) $this_action[ 'value' ];
            $toolId = $this_action[ 'tool_id' ];

            if ( isset ( $this->toolActionList[ $toolId ][ $actionName ] ) )
            {
                $this->toolActionList[ $toolId ][ $actionName ] = $actionValue;
            }
        }
    }

    /**
     * Save profile tool list action value
     */
    public function save ()
    {
        $this->toolActionList;

        // delete all relation
        $sql = "DELETE FROM `" . $this->tbl[ 'rel_profile_action' ] . "`
                WHERE profile_id=" . $this->profile->id . "
                AND courseId = '' ";

        claro_sql_query ( $sql );

        // insert new relation

        foreach ( $this->toolActionList as $toolId => $actionList )
        {
            foreach ( $actionList as $actionName => $actionValue )
            {
                if ( $actionValue == true )
                    $actionValue = 1;
                else
                    $actionValue = 0;

                $action = new RightToolAction();

                $action->load ( $actionName, $toolId );

                $actionId = $action->getId ();

                $sql = "INSERT INTO `" . $this->tbl[ 'rel_profile_action' ] . "`
                        SET profile_id = " . $this->profile->id . ",
                         action_id = " . $actionId . ",
                         value = " . $actionValue . ",
                         courseId = '' ";
                claro_sql_query ( $sql );
            }
        }
    }

    /**
     * Set action value of the profile
     *
     * @param integer $tool_id tool identifier
     * @param string $action_name action name
     * @param boolean $value action value
     */
    public function setAction ( $toolId, $actionName, $value )
    {
        $value = (bool) $value;

        if ( isset ( $this->toolActionList[ $toolId ][ $actionName ] ) )
        {
            $this->toolActionList[ $toolId ][ $actionName ] = $value;
        }
    }

    /**
     * Get action value of the profile
     *
     * @param integer $toolId tool identifier
     * @param string $actionName action name
     * @return boolean
     */
    public function getAction ( $toolId, $actionName )
    {
        if ( isset ( $this->toolActionList[ $toolId ][ $actionName ] ) )
        {
            return $this->toolActionList[ $toolId ][ $actionName ];
        }
        else
        {
            return null;
        }
    }

    /**
     * Get action list of the profile
     */
    public function getToolActionList ()
    {
        return $this->toolActionList;
    }

}

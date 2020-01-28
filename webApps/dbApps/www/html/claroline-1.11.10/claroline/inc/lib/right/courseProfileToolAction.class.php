<?php // $Id: courseProfileToolAction.class.php 14198 2012-07-06 13:24:06Z zefredz $

/**
 * CLAROLINE
 *
 * Class to manage relation between profile and tool action in a course
 *
 * @version     1.11 $Revision: 14198 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     RIGHT
 * @author      Claro Team <cvs@claroline.net>
 */
require_once dirname ( __FILE__ ) . '/constants.inc.php';
require_once dirname ( __FILE__ ) . '/profileToolRight.class.php';

class RightCourseProfileToolRight extends RightProfileToolRight
{

    /**
     * @var $courseId
     */
    var $courseId;

    /**
     * @array $defaultToolActionList list action of the profile and their values
     */
    var $defaultToolActionList = array ( );

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
        $this->tbl[ 'module' ] = $tbl_mdb_names[ 'module' ];
    }

    /**
     * Load rights of a profile/course
     */
    public function load ( $profile )
    {
        // Load toolAction of the parent
        parent::load ( $profile );

        $this->defaultToolActionList = $this->getToolActionList ();

        // load value of action of the courseId
        $sql = " SELECT PA.action_id, PA.value, A.tool_id, A.name
                 FROM `" . $this->tbl[ 'rel_profile_action' ] . "` `PA`,
                      `" . $this->tbl[ 'action' ] . "` `A`
                 WHERE PA.profile_id = " . $this->profile->id . "
                 AND PA.action_id = A.id
                 AND PA.courseId = '" . claro_sql_escape ( $this->courseId ) . "'";

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

        // Remove deactivated tool
        $sql = "SELECT t.id
                FROM `" . $this->tbl[ 'module' ] . "`  AS m,
                    `" . $this->tbl[ 'course_tool' ] . "` AS t
                WHERE t.claro_label = m.label
                  AND m.activation <> 'activated'";

        $deactivatedToolList = claro_sql_query_fetch_all ( $sql );

        foreach ( $deactivatedToolList as $deactivatedTool )
        {
            if ( isset ( $this->toolActionList[ $deactivatedTool[ 'id' ] ] ) )
                unset ( $this->toolActionList[ $deactivatedTool[ 'id' ] ] );
            if ( isset ( $this->defaultToolActionList[ $deactivatedTool[ 'id' ] ] ) )
                unset ( $this->defaultToolActionList[ $deactivatedTool[ 'id' ] ] );
        }
    }

    /**
     * Save profile tool list action value
     */
    public function save ()
    {
        // delete all relation
        $sql = "DELETE FROM `" . $this->tbl[ 'rel_profile_action' ] . "`
                WHERE profile_id=" . $this->profile->id . "
                AND courseId = '" . claro_sql_escape ( $this->courseId ) . "'";

        claro_sql_query ( $sql );

        // insert new relation

        foreach ( $this->toolActionList as $toolId => $actionList )
        {
            // get difference between default and course
            $toolActionListDiff = array_diff_assoc ( $this->defaultToolActionList[ $toolId ], $actionList );

            if ( !empty ( $toolActionListDiff ) )
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
                            courseId = '" . claro_sql_escape ( $this->courseId ) . "'";

                    claro_sql_query ( $sql );
                }
            }
        }
    }

    /**
     * Get courseId
     */
    public function getCourseId ()
    {
        return $this->courseId;
    }

    /**
     * Set courseId
     */
    public function setCourseId ( $value )
    {
        $this->courseId = $value;
    }

    /**
     * Reset the values of the profile/course
     */
    public function reset ()
    {
        // Empty tool action list
        $this->toolActionList = array ( );

        // Set tool action list to default values
        $this->toolActionList = $this->defaultToolActionList;

        // Delete all relations
        $sql = "DELETE FROM `" . $this->tbl[ 'rel_profile_action' ] . "`
                WHERE profile_id=" . $this->profile->id . "
                AND courseId = '" . claro_sql_escape ( $this->courseId ) . "'";

        return claro_sql_query ( $sql );
    }

    /*
     * Delete all rights of a course
     * @param string $courseId
     * @return boolean
     */

    public static function resetAllRightProfile ( $courseId )
    {
        $tbl_mdb_names = claro_sql_get_main_tbl ();

        $tbl_rel_profile_action = $tbl_mdb_names[ 'right_rel_profile_action' ];

        // Delete all relations
        $sql = "DELETE FROM `" . $tbl_rel_profile_action . "`
                WHERE courseId = '" . claro_sql_escape ( $courseId ) . "'";

        return claro_sql_query ( $sql );
    }

}

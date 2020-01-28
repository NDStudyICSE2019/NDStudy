<?php // $Id: toolAction.class.php 14198 2012-07-06 13:24:06Z zefredz $

/**
 * CLAROLINE
 *
 * Class to manage tool action
 *
 * @version     1.11 $Revision: 14198 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     RIGHT
 * @author      Claro Team <cvs@claroline.net>
 */
require_once dirname ( __FILE__ ) . '/constants.inc.php';

class RightToolAction
{

    var $name;
    var $description;
    var $toolId;
    var $type;
    var $tbl = array ( );

    /**
     * Constructor
     */
    public function __construct ()
    {
        $this->id = '';
        $this->name = '';
        $this->description = '';
        $this->toolId = '';
        $this->type = PROFILE_TYPE_COURSE;

        $tbl_mdb_names = claro_sql_get_main_tbl ();
        $this->tbl[ 'action' ] = $tbl_mdb_names[ 'right_action' ];
        $this->tbl[ 'rel_profile_action' ] = $tbl_mdb_names[ 'right_rel_profile_action' ];
    }

    /**
     * Load action from DB
     *
     * @param $action_name
     * @param $toolId
     * @return boolean load successfull ?
     */
    public function load ( $actionName, $toolId )
    {
        $sql = "SELECT id,
                       name,
                       description,
                       tool_id,
                       type
                FROM `" . $this->tbl[ 'action' ] . "`
                WHERE name = '" . claro_sql_escape ( $actionName ) . "'
                AND `tool_id` =  " . (int) $toolId;

        $data = claro_sql_query_get_single_row ( $sql );

        if ( !empty ( $data ) )
        {
            $this->id = $data[ 'id' ];
            $this->name = $data[ 'name' ];
            $this->description = $data[ 'description' ];
            $this->toolId = $data[ 'tool_id' ];
            $this->type = $data[ 'type' ];

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Save action
     */
    public function save ()
    {
        if ( empty ( $this->name ) || empty ( $this->toolId ) || empty ( $this->type ) )
        {
            return false;
        }
        elseif ( !$this->exists () )
        {
            // insert action
            $sql = "INSERT INTO `" . $this->tbl[ 'action' ] . "`
                    SET `name` = '" . claro_sql_escape ( $this->name ) . "',
                        `description` = '" . claro_sql_escape ( $this->description ) . "',
                        `type` = '" . claro_sql_escape ( $this->type ) . "',
                        `tool_id` =" . (int) $this->toolId;

            return claro_sql_query ( $sql );
        }
        else
        {
            // update action
            $sql = "UPDATE `" . $this->tbl[ 'action' ] . "`
                    SET `description` = '" . claro_sql_escape ( $this->description ) . "'
                    WHERE name ='" . claro_sql_escape ( $this->name ) . "' AND
                          type ='" . claro_sql_escape ( $this->type ) . "' AND
                          tool_id = " . (int) $this->toolId;

            return claro_sql_query ( $sql );
        }
    }

    /**
     * Delete action
     */
    public function delete ()
    {
        // Delete from rel_profile_action
        $sql = "DELETE FROM `" . $this->tbl[ 'rel_profile_action' ] . "`
                WHERE action_id = " . (int) $this->id;
        claro_sql_query ( $sql );

        // Delete from action
        $sql = "DELETE FROM `" . $this->tbl[ 'action' ] . "`
                WHERE id = " . (int) $this->id;

        claro_sql_query ( $sql );

        $this->id = -1;

        return true;
    }

    /**
     * Check if action already exists
     */
    public function exists ()
    {
        $sql = " SELECT count(*)
                 FROM `" . $this->tbl[ 'action' ] . "`
                 WHERE name ='" . claro_sql_escape ( $this->name ) . "' AND
                       type ='" . claro_sql_escape ( $this->type ) . "' AND
                       tool_id = " . (int) $this->toolId;

        if ( claro_sql_query_get_single_value ( $sql ) == 0 )
            return false;
        else
            return true;
    }

    /**
     * Get action id
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * Get action name
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * Get action description
     */
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * Get tool identifier
     */
    public function getToolId ()
    {
        return $this->toolId;
    }

    /**
     * Get type
     */
    public function getType ()
    {
        return $this->type;
    }

    /**
     * Set name
     */
    public function setName ( $value )
    {
        $this->name = $value;
    }

    /**
     * Set description
     */
    public function setDescription ( $value )
    {
        $this->description = $value;
    }

    /**
     * Set tool identifier
     */
    public function setToolId ( $value )
    {
        $this->toolId = $value;
    }

    /**
     * set type
     */
    public function setType ( $value )
    {
        $this->type = $value;
    }

}

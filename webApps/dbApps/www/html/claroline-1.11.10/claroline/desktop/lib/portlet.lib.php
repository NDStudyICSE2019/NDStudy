<?php // $Id: portlet.lib.php 14466 2013-06-10 08:17:34Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * User desktop portlet classes.
 *
 * @version     $Revision: 14466 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     DESKTOP
 * @author      Claroline Team <info@claroline.net>
 */

require_once get_path('includePath') . '/lib/portlet.class.php';

abstract class UserDesktopPortlet extends Portlet
{
    /**
     * @var String
     */
    protected $name, $label;
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getLabel()
    {
        return $this->label;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function setLabel($label)
    {
        $this->label = $label;
    }
}

class PortletList
{
    private $tblDesktopPortlet;

    const UP = 'up';
    const DOWN = 'down';
    const VISIBLE = 'visible';
    const INVISIBLE = 'invisible';

    public function __construct()
    {
        // convert to Claroline course table names
        $tbl_lp_names = get_module_main_tbl( array('desktop_portlet') );
        $this->tblDesktopPortlet = $tbl_lp_names['desktop_portlet'];
    }

    // load
    public function loadPortlet( $label )
    {
        $sql = "SELECT
                    `label`,
                    `name`,
                    `rank`,
                    `visibility`
                FROM `".$this->tblDesktopPortlet."`
                WHERE label = '" . claro_sql_escape( $label ) . "'";

        $data = claro_sql_query_get_single_row($sql);

        if( empty($data) )
        {
            return false;
        }
        else
        {
            return $data;
        }
    }

    public function loadAll( $visibility = false )
    {
        $sql = "SELECT
                    `label`,
                    `name`,
                    `rank`,
                    `visibility`
                FROM `".$this->tblDesktopPortlet."`
                WHERE 1 "
                . ( $visibility == true ? "AND visibility = 'visible'" : '' ) .
                "ORDER BY `rank` ASC";

        if ( false === ( $data = claro_sql_query_fetch_all_rows($sql) ) )
        {
            return false;
        }
        else
        {
            return $data;
        }
    }

    // save
    public function addPortlet( $label, $name, $rank = null, $visible = true )
    {
        if ( Claroline::getDatabase()->query("SELECT `label` FROM `{$this->tblDesktopPortlet}` WHERE `label` = '" . claro_sql_escape($label) . "'")->numRows() )
        {
            return false;
        }
        
        $sql = "SELECT MAX(rank) FROM  `" . $this->tblDesktopPortlet . "`";
        $maxRank = claro_sql_query_get_single_value($sql);
        
        $sqlRank = empty( $rank )
            ? $maxRank + 1
            : (int) $rank
            ;
            
        $sqlVisibility = $visible
            ? "visible"
            : "invisible"
            ;
            
        // insert
        $sql = "INSERT INTO `".$this->tblDesktopPortlet."`
                SET `label` = '" . claro_sql_escape($label) . "',
                    `name` = '" . claro_sql_escape($name) . "',
                    `visibility` = '" . $sqlVisibility . "',
                    `rank` = " . $sqlRank;
                    
        return ( claro_sql_query($sql) != false );
    }

    private function movePortlet($label, $direction)
    {
        switch ($direction)
        {
            case self::UP :
            {
                //1-find value of current module rank in the dock
                $sql = "SELECT `rank`
                        FROM `" . $this->tblDesktopPortlet . "`
                        WHERE `label`='" . claro_sql_escape($label) . "'"
                        ;

                $result = claro_sql_query_get_single_value( $sql );

                //2-move down above module
                $sql = "UPDATE `" . $this->tblDesktopPortlet . "`
                        SET `rank` = `rank`+1
                        WHERE `label` != '" . claro_sql_escape($label) . "'
                        AND `rank`       = " . (int) $result['rank'] . " -1 "
                        ;

                claro_sql_query( $sql );

                //3-move up current module
                $sql = "UPDATE `" . $this->tblDesktopPortlet . "`
                        SET `rank` = `rank`-1
                        WHERE `label` = '" . claro_sql_escape($label) . "'
                        AND `rank` > 1"
                        ;

                claro_sql_query($sql);

                break;
            }
            case self::DOWN :
            {
                //1-find value of current module rank in the dock
                $sql = "SELECT `rank`
                        FROM `" . $this->tblDesktopPortlet . "`
                        WHERE `label`='" . claro_sql_escape($label) . "'"
                        ;

                $result = claro_sql_query_get_single_value($sql);

                //this second query is to avoid a page refreshment wrong update

                $sqlmax = "SELECT MAX(`rank`) AS `max_rank`
                          FROM `" . $this->tblDesktopPortlet . "`"
                          ;

                $resultmax = claro_sql_query_get_single_value( $sqlmax );

                if ( $resultmax['max_rank'] == $result['rank'] ) break;

                //2-move up above module
                $sql = "UPDATE `" . $this->tblDesktopPortlet . "`
                        SET `rank` = `rank` - 1
                        WHERE `label` != '" . claro_sql_escape($label) . "'
                        AND `rank` = " . (int) $result['rank'] . " + 1
                        AND `rank` > 1"
                        ;

                claro_sql_query($sql);

                //3-move down current module
                $sql = "UPDATE `" . $this->tblDesktopPortlet . "`
                        SET `rank` = `rank` + 1
                        WHERE `label`='" . claro_sql_escape($label) . "'"
                        ;

                claro_sql_query($sql);

                break;
            }
        }
    }

    public function moveUp( $label )
    {
        $this->movePortlet( $label, self::UP );
    }
    
    public function moveDown( $label )
    {
        $this->movePortlet( $label, self::DOWN );
    }
    
    private function setVisibility( $label, $visibility )
    {
        $sql = "UPDATE `".$this->tblDesktopPortlet."`
                SET `visibility` = '" . $visibility . "'
                WHERE `label` = '" . $label . "'"
                ;

        if( claro_sql_query($sql) == false ) return false;

        return true;
    }

    public function setVisible( $label )
    {
        $this->setVisibility( $label, self::VISIBLE);
    }

    public function setInvisible( $label )
    {
        $this->setVisibility( $label, self::INVISIBLE);
    }
}

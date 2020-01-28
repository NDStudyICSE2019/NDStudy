<?php // $Id: class.tree.php 12923 2011-03-03 14:23:57Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * Tree class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

class Tree
{
    // name of the sql table containing the tree nodes
    var $table;
    // name of the 'left' column in $table
    var $leftCol;
    // name of the 'right' column in $table
    var $rightCol;
    // name of the 'deep' column in table : # of ancestor of the node
    var $deepCol;
    // name of the 'tree' column in table : allows many trees in a same table
    // default value is empty and means that we will only  have one tree in our table
    // e.g. : it could be session id in works
    var $treeCol;

    /**
     * constructor, build a tree object
     *
     * @param string $table name of the sql table containing the tree nodes
     * @param string $leftCol name of the 'left' column in $table
     * @param string $rightCol name of the 'right' column in $table
     * @param string $deepCol name of the 'deep' column in $table
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function Tree($table, $leftCol = 'left', $rightCol = 'right', $deepCol = 'deep', $treeCol = '')
    {
        $this->table = $table;
        $this->leftCol = $leftCol;
        $this->rightCol = $rightCol;
        $this->deepCol = $deepCol;
        $this->treeCol = $treeCol;
    }

    /**
     * create root node
     *
     * @return int id of the inserted node
     * @uses _addNode
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function newRoot( $tree = "", $values = "" )
    {
        $node['left']     = 1;
        $node['right']     = 2;
        $node['deep']     = 0;
        $node['tree']     = $tree;

        return $this->_addNode($node, $values);
    }

    /**
     * create a node that will be the first child of node $id
     *
     * @param int $id id of the parent node
     * @return int id of the inserted node
     * @uses _shiftPositions
     * @uses _addNode
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function newFirstChild( $id , $values = "" )
    {
        $node = $this->getPosition( $id );

        $newNode['left']     = $node['left'] + 1;
        $newNode['right']     = $node['left'] + 2;
        $newNode['deep']     = $node['deep'] + 1;
        $newNode['tree']    = $node['tree'];

        $this->_shiftPositions($newNode['left'],2, $newNode['tree']);
        return $this->_addNode($newNode, $values);
    }
    /**
     * create a node that will be the last child of node $id
     *
     * @param $id id of the parent node
     * @return int id of the inserted node
     * @uses _shiftPositions
     * @uses _addNode
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function newLastChild( $id , $values = "" )
    {
        $node = $this->getPosition( $id );

        $newNode['left']     = $node['right'];
        $newNode['right']     = $node['right'] + 1;
        $newNode['deep']     = $node['deep'] + 1;
        $newNode['tree']    = $node['tree'];

        $this->_shiftPositions($newNode['left'],2, $newNode['tree']);
        return $this->_addNode($newNode, $values);
    }

    /**
     * create a node that will be the previous brother of node $id
     *
     * @param $id id of the brother node
     * @return int id of the inserted node
     * @uses Tree::_shiftPositions
     * @uses Tree::_addNode
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function newPrevBrother( $id , $values = "" )
    {
        $node = $this->getPosition( $id );

        $newNode['left']     = $node['left'];
        $newNode['right']     = $node['left'] + 1;
        $newNode['deep']     = $node['deep'];
        $newNode['tree']    = $node['tree'];

        $this->_shiftPositions($newNode['left'],2, $newNode['tree']);
        return $this->_addNode($newNode, $values);
    }

    /**
     * create a node that will be the next brother of node $id
     *
     * @param $id id of the brother node
     * @return int id of the inserted node
     * @uses _shiftPositions
     * @uses _addNode
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function newNextBrother( $id , $values = "" )
    {
        $node = $this->getPosition( $id );

        $newNode['left']     = $node['right'] + 1;
        $newNode['right']     = $node['right'] + 2;
        $newNode['deep']     = $node['deep'];
        $newNode['tree']    = $node['tree'];

        $this->_shiftPositions($newNode['left'],2, $newNode['tree']);
        return $this->_addNode($newNode, $values);
    }

    /**
     * delete a node and all its children
     *
     * @param int $id
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function deleteNode( $id )
    {
        $node = $this->getPosition( $id );

        if( is_array($node) )
        {
            $sql = "DELETE FROM `".$this->table."`
                    WHERE `".$this->leftCol."` >= ".$node['left']."
                    AND ".$this->rightCol." <= ".$node['right'];
            // handle multiple trees allowed in same table
            if( !empty($this->treeCol) ) $sql .= " AND ".$this->treeCol." = ".$node['tree'];

            claro_sql_query($sql);

            $this->_shiftPositions($node['right']+1, $node['left'] - $node['right'] - 1, $node['tree']);
        }
        else
        {
            return false;
        }

    }
    /**
     *
     *
     * @author Fragile <pir@cerdecam.be>
     * @access private
     */
    function _addNode($node, $values = "")
    {
        if ( strlen($values) > 0) $values .= ",";

        $sql = "INSERT INTO `".$this->table."`
                SET ".$values
                    ."`".$this->leftCol."` = ".$node['left'].",
                    `".$this->rightCol."` = ".$node['right'].",
                    `".$this->deepCol."` = ".$node['deep'];
        // handle multiple trees allowed in same table
        if( !empty($this->treeCol) ) $sql .= ", `".$this->treeCol."` = ".$node['tree'];

        // insert node and return inserted id
        return claro_sql_query_insert_id($sql);
    }

    /**
     *
     * @author Fragile <pir@cerdecam.be>
     * @access private
     */
    function _shiftPositions($from, $delta, $tree)
    {
        $sql = "UPDATE `".$this->table."`
                SET `".$this->leftCol."` = `".$this->leftCol."` + ".$delta."
                WHERE `".$this->leftCol."` >= ".$from;
        // handle multiple trees allowed in same table
        if( !empty($this->treeCol) ) $sql .= " AND ".$this->treeCol." = ".$tree;

        claro_sql_query($sql);

        $sql = "UPDATE `".$this->table."`
                SET `".$this->rightCol."` = `".$this->rightCol."` + ".$delta."
                WHERE `".$this->rightCol."` >= ".$from;
        // handle multiple trees allowed in same table
        if( !empty($this->treeCol) ) $sql .= " AND ".$this->treeCol." = ".$tree;
        claro_sql_query($sql);
    }


    /**
     * Get the left, right and deep attributes of a node
     *
     * @param $id id of the node
     * @return array $left, $right and $deep attributes of the father
     * @author Fragile <pir@cerdecam.be>
     * @access public
     * @desc required by makeRoom function
     */
    function getPosition($id)
    {
        $sql = "SELECT `".$this->leftCol."`, `".$this->rightCol."`, `".$this->deepCol."`";
        if( !empty($this->treeCol) ) $sql .= " , `".$this->treeCol."`";
        $sql .= " FROM `".$this->table."`
                WHERE `id` = ".$id;
        echo $sql."<br />";
        $res = claro_sql_query_fetch_all($sql);

        if( !$res || sizeof($res) == 0  )
        {
            return false;
        }
        else
        {
            $node['left']     = $res[0][$this->leftCol];
            $node['right']     = $res[0][$this->rightCol];
            $node['deep']     = $res[0][$this->deepCol];
            $node['tree']    = $res[0][$this->treeCol];
            var_dump($node);
            echo "<br />";
            return $node;
        }
    }


    /**
     *
     *
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function getChildren($id, $direct = FALSE)
    {
        $node = $this->getPosition( $id );

        $sql = "SELECT *
                FROM `".$this->table."`
                WHERE `".$this->leftCol."` > ".$node['left']
                ." AND `".$this->rightCol."` < ".$node['right'];

        if( $direct ) $sql .= " AND `".$this->deepCol."` = ".($node['deep']+1);
        // handle multiple trees allowed in same table
        if( !empty($this->treeCol) ) $sql .= " AND ".$this->treeCol." = ".$node['tree'];

        return claro_sql_query_fetch_all($sql);
    }

    /**
     * Count number of children    a node has.  $direct param specifies
     * if we count only direct children or direct children and all
     * their children
     *
     * @param int $id id of the node we want to count children
     * @param boolean $direct if true we only count direct children,
     * if false we count all children
     * @return int number of children
     * @author Fragile <pir@cerdecam.be>
     * @access public
     */
    function countChildren( $id, $direct = FALSE )
    {
        if( $direct )
        {
            // count direct children only
            $children = $this->getChildren($id, $direct);

            if(is_array($children))
            {
                return count($children);
            }
            else
            {
                return 0;
            }
        }
        else
        {
            // count all children
            $node = $this->getPosition( $id );
            return ($node['right'] - $node['left'] - 1) / 2;
        }

    }

    /**
     * Display the tree that has '$id' as root
     *
     * @param $id id of the root of the tree to display
     * @author Fragile <pir@cerdecam.be>
     * @access public
     * @desc required by makeRoom function
     */
    // this function exists mainly for debug purpose
    function printTree ( $id, $attributes = "" )
    {
        $node = $this->getPosition( $id );

        if( is_array($node) )
        {
            // get all nodes that are part of the 'id' tree or subtree
            $sql = "SELECT *
                    FROM `".$this->table."`
                    WHERE `".$this->leftCol."` >= ".$node['left']."
                    AND `".$this->rightCol."` <= ".$node['right'];
            // handle multiple trees allowed in same table
            if( !empty($this->treeCol) ) $sql .= " AND ".$this->treeCol." = ".$node['tree'];

            $sql .= " ORDER BY `".$this->leftCol."` ASC";

            $tree = claro_sql_query_fetch_all($sql);

            // display tree
            echo '<h3>Tree</h3>'."\n".'<p>'."\n";
            foreach( $tree as $node )
            {
                // indentation
                echo str_repeat("&nbsp;", $node[$this->deepCol] * 4);

                echo '<b>'.$node['id'].'</b><br />'."\n";
            }
            echo '</p>';
        }
        else
        {
            echo "<p><strong>Nothing at this id.</strong></p>";
        }
    }

}

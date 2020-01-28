<?php // $Id: userlist.lib.php 13708 2011-10-19 10:46:34Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * User list.
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


require_once dirname(__FILE__) . '/userstrategy.lib.php';

class UserList implements CountableIterator
{

    protected $userList = FALSE;
    protected $numberOfUser = FALSE;
    protected $index = 0;
    protected $userStrategy;
    
    
    public function __construct($userStrategy = NULL)
    {
        if (is_null($userStrategy))
        {
            $userStrategy = new UserStrategy();
        }
        $this->userStrategy = $userStrategy;
    }
    
    public function getSelector()
    {
        return $this->userStrategy;
    }
    
    public function setSelector($selector)
    {
        $this->userStrategy = $selector;
    }
    
    public function loadUserList()
    {
        
        if (!$this->userList)
        {
            
            $tableName = get_module_main_tbl(array('user'));
            
            if (!is_null($this->getSelector()))
            {
                $limit = $this->getSelector()->getLimit();
                $where = $this->getSelector()->getStrategy();
                $order = $this->getSelector()->getOrder();
            }
            else
            {
                $limit = "";
                $where = "";
                $order = "";
            }
            
            
            $sql =
             "SELECT user_id AS id, nom AS lastname, prenom AS firstname, username"
                ." FROM `".$tableName['user']."`"
                . " " . $where
                    . " " . $order
                    . " " . $limit
                ;
            $this->userList = claro_sql_query_fetch_all($sql);
        }
    }
    
    public function getNumberOfUser()
    {
        if (!$this->numberOfUser)
        {
            $this->loadNumberOfUser();
        }
        return $this->numberOfUser;
    }

    protected function loadNumberOfUser()
    {
        $tableName = get_module_main_tbl(array('user'));
        
        if (!is_null($this->getSelector()))
        {
            $where = $this->getSelector()->getStrategy();
        }
        else
        {
            $where = "";
        }
        
        $sql =
            "SELECT count(*)"
               ." FROM `".$tableName['user']."`"
               . " " . $where
               ;
        $this->numberOfUser = claro_sql_query_fetch_single_value($sql);
    }
    
    public function getNumberOfPage()
    {
        return ceil( $this->getNumberOfUser() / $this->getSelector()->getNumberOfUserPerPage() );
    }
    
    public function current()
    {
        
        return $this->userList[$this->index];
    }

    public function key()
    {
        
        // If message list not loaded, load it !
        $this->loadUserList();
        return $this->userList[$this->index]['id'];
    }

    public function next()
    {
        // If message list not loaded, load it !
        $this->loadUserList();
        
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        // If message list not loaded, load it !
        $this->loadUserList();
        
        return ($this->index < count($this->userList));
    }

    public function count()
    {
        return count($this->userList);
    }
}

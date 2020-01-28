<?php // $Id: receivedmessagestrategy.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * receivedmessagebox strategy class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

//load messagestrategy class
require_once dirname(__FILE__) . '/messagestrategy.lib.php';

class ReceivedMessageStrategy extends MessageStrategy 
{
    const ONLY_READ = "R.is_read = 1";
    const ONLY_UNREAD = "R.is_read = 0";
    
    const ONLY_DELETED = "R.is_deleted = 1";
    const ONLY_NOT_DELETED = "R.is_deleted = 0";

    const SEARCH_SELECT = "CONCAT(U.prenom,' ',U.nom) LIKE '%%search%%' OR U.prenom LIKE '%%search%%' OR U.nom LIKE '%%search%%' OR M.subject LIKE '%%search%%' OR M.course LIKE '%%search%%'";
    
    const ORDER_BY_DATE = "M.send_time %order%";
    const ORDER_BY_SENDER = "U.nom %order%, U.prenom %order%";

    protected $readStrategy = self::NO_FILTER;
    protected $deletedStrategy = self::NO_FILTER;
    
    /**
     * set read strategy
     *
     * @param string $readStrategy
     *             accepted value: 
     *                 ReceivedMessageStrategy::ONLY_READ
     *                 ReceivedMessageStrategy::ONLY_UNREAD
     *                 MessageStrategy::NO_FILTER
     */
    public function setReadStrategy($readStrategy)
    {
        if ( $readStrategy == parent::NO_FILTER
            || $readStrategy == self::ONLY_READ
            || $readStrategy == self::ONLY_UNREAD)
        {
            $this->readStrategy = $readStrategy;
        }
    }

     /**
     * set deleted strategy
     *
     * @param string $deletedStrategy
     *             accepted value: 
     *                 ReceivedMessageStrategy::ONLY_DELETED
     *                 ReceivedMessageStrategy::ONLY_NOT_DELETED
     *                 MessageStrategy::NO_FILTER
     */
    public function setDeletedStrategy($deletedStrategy)
    {
        if ( $deletedStrategy == self::NO_FILTER
            || $deletedStrategy == self::ONLY_DELETED
            || $deletedStrategy == self::ONLY_NOT_DELETED)
        {
            $this->deletedStrategy = $deletedStrategy;
        }
    }

    /**
     * set the field order
     *
     * @param string $fieldOrder
     *         accepted value: ReceivedMessageStrategy::ORDER_BY_DATE
     *                         ReceivedMessageStrategy::ORDER_BY_SENDER
     */
    public function setFieldOrder($fieldOrder)
    {
        if ($fieldOrder == self::ORDER_BY_DATE
                ||  $fieldOrder == self::ORDER_BY_SENDER)
        {
            $this->fieldOrder = $fieldOrder;    
        }
    }
    
    /**
     * return the conditions
     *
     * @return string conditions
     */
    public function getStrategy()
    {
        $condition = "";
        
        if ($this->readStrategy != self::NO_FILTER)
        {
            $condition .= " AND ".$this->readStrategy;
        }
        
        if ($this->deletedStrategy != self::NO_FILTER)
        {
            $condition .= " AND ".$this->deletedStrategy;
        }
        
        $this->search = trim($this->search);
        
        if ($this->search != "" && $this->search != "*")
        {
            $wordList = array();
            
            if ($this->searchStrategy == parent::SEARCH_STRATEGY_EXPRESSION)
            {
                $wordList = array($this->search);
            }
            elseif ($this->searchStrategy == parent::SEARCH_STRATEGY_WORD)
            {
                $wordList = preg_split('/\s+/',$this->search);
            }
            
            $searchCondition = "";
            
            foreach ($wordList as $key => $word)
            {
                if ($searchCondition != "")
                {
                    $searchCondition .= " OR ";
                }
                
                $searchCondition .= str_replace('%search%',claro_sql_escape($word),self::SEARCH_SELECT)."\n";
            }
            
            $condition .= " AND (".$searchCondition.")";
        }
        
        return $condition;
    }
}

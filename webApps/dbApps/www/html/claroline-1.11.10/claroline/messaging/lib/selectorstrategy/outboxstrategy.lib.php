<?php // $Id: outboxstrategy.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * outbox strategy
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

class OutBoxStrategy extends MessageStrategy 
{
    const SEARCH_SELECT = "subject LIKE '%%search%%'  OR course LIKE '%%search%%'";
    
    const ORDER_BY_DATE = "send_time %order%";

    /**
     * set the field order
     * accepted value: OutBoxStrategy::ORDER_BY_DATE
     *
     * @param string $fieldOrder constant of the field order
     */
    public function setFieldOrder($fieldOrder)
    {
        if ($fieldOrder == self::ORDER_BY_DATE)
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

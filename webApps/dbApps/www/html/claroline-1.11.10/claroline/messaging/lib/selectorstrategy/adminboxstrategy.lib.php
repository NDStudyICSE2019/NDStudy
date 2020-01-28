<?php // $Id: adminboxstrategy.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * adminmessage box strategy
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


//load selector strategy interface
require_once dirname(__FILE__) . '/selectorstrategy.lib.php';

class AdminBoxStrategy implements SelectorStrategy 
{
    const OLDER_THAN = "DATEDIFF(send_time,FROM_UNIXTIME(%date%)) < 0";
    const DATED_INTERVAL = "DATEDIFF(send_time,FROM_UNIXTIME(%date1%)) > 0 AND DATEDIFF(send_time,FROM_UNIXTIME(%date2%)) < 0";
    const SENT_BY = "U.username LIKE '%%name%%' OR U.nom LIKE '%%name%%' OR U.prenom LIKE '%%name%%' OR CONCAT(U.nom,' ',U.prenom) LIKE '%%name%%' OR CONCAT(U.prenom,' ',U.nom) LIKE '%%name%%'";
    const PLATFORM_MESSAGE = "R.user_id = 0";
    
    const FIELD_ORDER_NAME = "U.nom %order%, U.prenom %order%";
    const FIELD_ORDER_USERNAME = "U.username %order%";
    const FIELD_ORDER_DATE = "M.send_time %order%";
    
    const ORDER_DESC = "DESC";
    const ORDER_ASC = "ASC";
    
    protected $numberMessagePerPage;
    protected $pageToDisplay = 1;
    protected $order = self::ORDER_DESC;
    protected $orderField = self::FIELD_ORDER_DATE;
    
    protected $strategy = "";
    protected $valueList = array();
    
    public function __construct()
    {
        $this->numberMessagePerPage = get_conf('messagePerPage',40);
    }
    
    /**
     * Set the strategy to apply to the message list
     *
     * @param string $strategy 
     *             accepted value:
     *                 AdminBoxStrategy::OLDER_THAN  argument: date 
     *                 AdminBoxStrategy::DATED_INTERVAL  argument: date1 date 2 
     *                 AdminBoxStrategy::SENT_BY  argument: name
     *                 AdminBoxStrategy::PLATFORM_MESSAGE
     * @param array $valuesList value of the strategy
     */
    public function setStrategy($strategy,$valuesList = array())
    {
        if ($strategy == self::OLDER_THAN || $strategy == self::SENT_BY
             || $strategy == self::DATED_INTERVAL || $strategy == self::PLATFORM_MESSAGE)
        {
            $this->strategy = $strategy;
            
            if (!is_array($valuesList))
            {
                $valuesList = array($valuesList);
            }
            
            $this->valueList = $valuesList;
        }
    }
    
    /**
     * @see SelectorStrategy
     */
    public function getStrategy()
    {
        $condition = $this->strategy;
        
        foreach ($this->valueList as $key => $value)
        {
            $condition = str_replace('%'.$key.'%',claro_sql_escape($value),$condition);
        }
        
        return " WHERE ".$condition."\n";
    }
    
    /**
     * @see SelectorStrategy
     */
    public function getLimit()
    {
        if ($this->numberMessagePerPage <= 0)
        {
            throw new Exception("The number of message per page must be positif and not null");
        }
        
        if ($this->pageToDisplay <= 0)
        {
            throw new Exception("The page to display must be positif and not null");
        }
        
        return " LIMIT " . (int)($this->pageToDisplay - 1)*$this->numberMessagePerPage . ", " 
            . (int)$this->numberMessagePerPage."\n";
    }
    
    /**
     * set the field order
     *
     * @param string $fieldOrder
     *         accepted value: AdminBoxStrategy::FIELD_ORDER_NAME
                            AdminBoxStrategy::FIELD_ORDER_USERNAME
                            AdminBoxStrategy::FIELD_ORDER_DATE
     */
    public function setFieldOrder($fieldOrder)
    {
        if ($fieldOrder == self::FIELD_ORDER_DATE
                || $fieldOrder == self::FIELD_ORDER_NAME
                || $fieldOrder == self::FIELD_ORDER_USERNAME)
        {
            $this->orderField = $fieldOrder;
        }
    }
    
    /**
     * Set the order to apply
     *
     * @param string $order
     *         accepted value: AdminBoxStrategy::ORDER_DESC
                            AdminBoxStrategy::ORDER_ASC
     */
    public function setOrder($order)
    {
        if ($order == self::ORDER_ASC
                || $order == self::ORDER_DESC)
        {
            $this->order = $order;
        }
    }
    
    /**
     * @see SelectorStrategy
     */
    public function getOrder()
    {
        $orderString = $this->orderField;
        $orderString = str_replace('%order%',$this->order,$orderString);
        
        return " ORDER BY ".$orderString."\n";
    }
    
    public function getNumberOfMessagePerPage()
    {
        return $this->numberMessagePerPage;
    }
    
    /**
     * set the page to display
     *
     * @param int $page page to display
     */
    public function setPageToDisplay($page)
    {
        $this->pageToDisplay = (int)$page;
    }
}

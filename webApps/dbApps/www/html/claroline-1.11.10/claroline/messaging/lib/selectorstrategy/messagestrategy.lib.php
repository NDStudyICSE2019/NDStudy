<?php // $Id: messagestrategy.lib.php 14430 2013-04-25 09:28:50Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * messageStrategy class
 *
 * @version     1.9 $Revision: 14430 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

//load selector strategy interface
require_once dirname(__FILE__) . '/selectorstrategy.lib.php';

abstract class MessageStrategy implements SelectorStrategy 
{
    const NO_FILTER = "";
    
    const SEARCH_STRATEGY_EXPRESSION = "expression";
    const SEARCH_STRATEGY_WORD = "word";
    
    const ORDER_DESC = "DESC";
    const ORDER_ASC = "ASC";
    
    const ORDER_BY_DATE = "M.send_time %order%";
    
    protected $search = "";
    protected $searchStrategy = self::SEARCH_STRATEGY_WORD;
    
    protected $fieldOrder = self::ORDER_BY_DATE;
    protected $order = self::ORDER_DESC;
    
    protected $numberMessagePerPage;
    protected $pageToDisplay = 1;

    /**
     * create a message stratagy
     *
     */
    public function __construct()
    {
        $this->numberMessagePerPage = get_conf('messagePerPage',20);
    }
    
    /**
     * set the string to search
     *
     * @param string $search string to search
     */
    public function setSearch($search)
    {
        $this->search = $search;
    }
    
    /**
     * set the search strategy
     * MessageStrategy::SEARCH_STRATEGY_EXPRESSION to search the exact expression
     * MessageStrategy::SEARCH_STRATEGY_WORD to search message contain at least 1 word of the string
     *
     * @param unknown_type $searchStrategy
     */
    public function setSearchStrategy($searchStrategy)
    {
        if ($searchStrategy == self::SEARCH_STRATEGY_EXPRESSION || 
            $searchStrategy == self::SEARCH_STRATEGY_WORD)
        {
            $this->searchStrategy = $searchStrategy;
        }
    }
    
    /**
     * Set the order of search
     *
     * @param string $order: accpeted value MessageStrategy::ORDER_DESC and MessageStrategy::ORDER_ASC
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
     * Set the number of message per page (used for the desktop)
     *
     * @param int $numberOfPage number of message per page
     */
    public function setNumberOfMessagePerPage($numberOfPage)
    {
        $this->numberMessagePerPage = (int)$numberOfPage; 
    }
    
    /**
     * set the page to display
     *
     * @param int $page page to display
     */
    public function setPageToDisplay($page)
    {
        $this->pageToDisplay = $page;
    }
    
    /**
     * return the number of message per page
     *
     * @return int number of message per page
     */
    public function getNumberOfMessagePerPage()
    {
        return $this->numberMessagePerPage;
    }
    
    /**
     * return the part of the sql request to the pagination
     *
     * @return string the part of the sql request to limit results
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
     * return the part of the SQL request to order the result
     *
     * @return string Part of the SQL request to order the result
     */
    public function getOrder()
    {
        $orderString = $this->fieldOrder;
        $orderString = str_replace('%order%',$this->order,$orderString);
        return " ORDER BY ".$orderString."\n";
    }
}

<?php // $Id: messagebox.lib.php 13687 2011-10-14 12:50:06Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * message box  class (abstract)
 *
 * @version     1.9 $Revision: 13687 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

abstract class MessageBox implements CountableIterator
{
    protected $messageFilter = NULL;
    protected $messageList = FALSE;
    protected $numberOfMessage = FALSE;
    protected $index;
    protected $userId;

    /**
     * create an object MessageBox for the user in parameters and with strategy
     *
     * @param MessageStrategy $strategy strategy to apply
     * @param int $userId user identification of the message box (optionnal, default: current_user_id())
     *     
     */
    public function __construct($strategy, $userId = NULL)
    {
        if (is_null($userId))
        {
            $userId = claro_get_current_user_id();
        }
        
        $this->messageFilter = $strategy;
        $this->index = 0;
        $this->userId = $userId;
    }

    /**
     * add 1 to the index for the iterator
     *
     */
    public function next()
    {
        // If message list not loaded, load it !
        $this->loadMessageList();
        
        $this->index += 1;
    }

    /**
     * check if the message in index $index exist
     *
     * @return boolean
     */
    public function valid()
    {
        // If message list not loaded, load it !
        $this->loadMessageList();
        
        if ($this->index > count($this->messageList)-1)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * Load the list of message
     *
     */
    abstract public function loadMessageList();
    
    /**
     * return the number of message in the messageBox
     * 
     * @return int number of message
     */
    abstract public function getNumberOfMessage();
    
    /**
     *
     * @return int the identification of the current message
     */
    public function key()
    {
        return $this->messageList[$this->index]['message_id'];
    }

    /**
     * return the message strategy
     *
     * @return MessageStrategy
     */
    public function getMessageStrategy()
    {
        return $this->messageFilter;
    }
    
    /**
     * set the message filter
     *
     * @param MessageFilter $messageFilter filter
     */
    public function setMessageStrategy($messageFilter)
    {
        $this->messageFilter = $messageFilter;
    }
    
    /**
     * return the iterator to begin
     *
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * return the user identification of the current message box
     *
     * @return int user identification
     */
    public function getUserId()
    {
        return $this->userId;
    }
    
    /**
     * return the number of message of the iterator
     *
     * @return int return the number of message of the iterator
     */
    public function count()
    {
        $this->loadMessageList();
        
        return count($messageList);
    }
}

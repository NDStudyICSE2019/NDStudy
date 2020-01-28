<?php // $Id: messagetosend.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * message to send class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


//load internal message class
require_once dirname(__FILE__) . '/internalmessage.lib.php';
// class need to notify bye other way
require_once dirname(__FILE__) . '/../notification/notifier.lib.php';

class MessageToSend extends InternalMessage
{
    
    /**
     * create an message to send with the information in parameters
     *
     * @param int $sender user identification
     *         if it's not defined it use the current user id
     * @param string $subject subject of the message
     * @param string $message content of the message
     */
    public function __construct( $sender = NULL, 
        $subject = parent::NOSUBJECT, 
        $message = parent::NOMESSAGE )
    {
        if ( is_null( $sender ) )
        {
            $sender = claro_get_current_user_id();
        }
        else
        {
            $this->sender = $sender;
        }
        
        $this->subject = $subject;
        $this->message = $message;
    }

    /**
     * set the send identification
     *
     * @param int $senderId sender identification (optional, default NULL)
     */
    public function setSender($senderId = NULL)
    {
        if ( is_null( $senderId ) )
        {
            $sender = claro_get_current_user_id();
        }
        else
        {
            $this->sender = $senderId;
        }
    }

    /**
     * set the subject of the current message
     *
     * @param string $subject suject of the message
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * set the content of the current message
     *
     * @param string $message message 
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * set the course id of the current message
     *
     * @param string $course course identification
     */
    public function setCourse($course)
    {
        $this->course = $course;
    }
    
    /**
     * set the group id of the current message
     *
     * @param int $group groupe identification
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }
    
    /**
     * set the Tlabel of tool of the current message
     *
     * @param string $tools course identification
     */
    public function setTools($tools)
    {
      $this->tools = $tools;
    }

}

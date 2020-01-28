<?php // $Id: internalmessage.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Internal message class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

abstract class InternalMessage
{
    const NOSUBJECT = "No subject";
    const NOMESSAGE = "No message";

    protected $sender;
    protected $subject;
    protected $message;
    protected $course = NULL;
    protected $group = NULL;
    protected $tools = NULL;

    
    
    
    /**
     * return the subject of the current message
     *
     * @return string subject of the message
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * return the message of the current message
     *
     * @return string message of the current message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * return the sender identification
     *
     * @return int user identification
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * return the course code of the current message (NULL is not existant)
     *
     * @return string course code
     */
    public function getCourseCode()
    {
        return $this->course;
    }
    
    /**
     * return the goup identification of the current message (NULL is not existant)
     *
     * @return int group identification
     */
    public function getGroupId()
    {
        return $this->group;
    }
    
    /**
     * return the tool identification of the current message (NULL is not existant)
     *
     * @return string tool identification
     */
    public function getToolsLabel()
    {
        return $this->tools;
    }    
}

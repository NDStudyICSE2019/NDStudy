<?php // $Id: platformmessagetosend.lib.php 14367 2013-01-30 11:02:44Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * message from platform to send class
 *
 * @version     1.9 $Revision: 14367 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

require_once dirname(__FILE__) . '/messagetosend.lib.php';

class PlatformMessageToSend extends MessageToSend
{
    const CLARO_SYSTEM_USER_ID = 0;
    
    /**
     * create an message to send with the information in parameters
     *
     * @param int $sender user identification
     *         if it's not defined it use the current user id
     * @param string $subject subject of the message
     * @param string $message content of the message
     */
    public function __construct( $subject = parent::NOSUBJECT, 
        $message = parent::NOMESSAGE )
    {
        parent::__construct( self::CLARO_SYSTEM_USER_ID, $subject , $message );
    }
    
    /**
     * The sender cannot be changed
     * @see MessageToSend::setSender
     * @throws Exception when called
     */
    public function setSender( $userId = null )
    {
        throw new Exception( 'Sender cannot be changed!' );
    }
}
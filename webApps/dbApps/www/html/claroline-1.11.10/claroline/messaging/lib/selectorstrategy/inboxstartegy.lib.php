<?php // $Id: inboxstartegy.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * inbox strategy class
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */


//load receivedmessagestrategy class
require_once dirname(__FILE__).'/receivedmessagestrategy.lib.php';

class InBoxStrategy extends ReceivedMessageStrategy 
{
    /**
     * create default strategy for the inbox
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setDeletedStrategy(parent::ONLY_NOT_DELETED);
        $this->setReadStrategy(parent::NO_FILTER);
    }
}

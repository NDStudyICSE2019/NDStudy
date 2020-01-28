<?php // $Id: inboxcontroler.inc.php 12989 2011-03-18 15:42:50Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Inbox controler.
 *
 * @version     $Revision: 12989 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

require_once dirname(__FILE__) . '/lib/messagebox/inbox.lib.php';

$messageId = isset($_REQUEST['messageId']) ? (int)$_REQUEST['messageId'] : NULL;

$currentSection = isset( $_REQUEST['box'] )
    ? $_REQUEST['box']
    : 'inbox'
    ;

$acceptedCmdList = array('rqDeleteMessage','exDeleteMessage','exMarkUnread','exMarkRead','rqSearch');

if (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList))
{
    if ($_REQUEST['cmd'] == 'rqSearch')
    {
        $displaySearch = TRUE;
    }
    if ($_REQUEST['cmd'] == 'exDeleteMessage' && !is_null($messageId))
    {
        InBox::moveMessageToTrashBox($messageId,$currentUserId);
        $dialogbox = new DialogBox();
        $dialogbox->success(get_lang('The message in now in your trashbox'));
        
        $content .= $dialogbox->render();
    }
    elseif ($_REQUEST['cmd'] == 'rqDeleteMessage' && ! is_null($messageId))
    {
        $displayConfimation = TRUE;
    }
    elseif ($_REQUEST['cmd'] == 'exMarkUnread' && ! is_null($messageId))
    {
        InBox::markUnread($messageId,$currentUserId);
    }
    elseif ($_REQUEST['cmd'] == 'exMarkRead' && ! is_null($messageId))
    {
        InBox::markRead($messageId,$currentUserId);
    }
}
// create box
$box = new InBox($currentUserId);

// set the order
$messageStrategy = $box->getMessageStrategy();

if (isset($_REQUEST['fieldOrder']))
{
    $link_arg['fieldOrder'] = $_REQUEST['fieldOrder'] == 'sender' ? 'sender' : 'date';
    
    if ($link_arg['fieldOrder'] == 'sender')
    {
        $messageStrategy->setFieldOrder(ReceivedMessageStrategy::ORDER_BY_SENDER);
    }
    else
    {
        $messageStrategy->setFieldOrder(ReceivedMessageStrategy::ORDER_BY_DATE);
    }
}

if (isset($_REQUEST['order']))
{
    $order = $_REQUEST['order'] == 'asc' ? 'asc' : 'desc';
    
    $link_arg['order'] = $order;
    
    if ($order == 'asc')
    {
        $nextOrder = "desc";
        $messageStrategy->setOrder(ReceivedMessageStrategy::ORDER_ASC);
    }
    else
    {
        $nextOrder = "asc";
        $messageStrategy->setOrder(ReceivedMessageStrategy::ORDER_DESC);
    }
    
}
else
{
    $nextOrder = "asc";
}

// ----- read selector ------------
// a comenter

$link_arg['SelectorReadStatus'] = isset( $_REQUEST['SelectorReadStatus'] )
    && in_array( $_REQUEST['SelectorReadStatus'], array('all','read','unread') )
    ? $_REQUEST['SelectorReadStatus']
    : 'all'
    ;


if ($link_arg['SelectorReadStatus'] == "all")
{
    $messageStrategy->setReadStrategy(ReceivedMessageStrategy::NO_FILTER);
}
elseif ($link_arg['SelectorReadStatus'] == "read")
{
    $messageStrategy->setReadStrategy(ReceivedMessageStrategy::ONLY_READ);
}
else
{
    $messageStrategy->setReadStrategy(ReceivedMessageStrategy::ONLY_UNREAD);
}

// search
if (isset($_POST['search']) && $_POST['search'] != "")
{
    $link_arg['search'] = strip_tags($_POST['search']);
    if (isset($_POST['searchStrategy']))
    {
        $link_arg['searchStrategy'] = 1;
    }
    else
    {
        $link_arg['searchStrategy'] = 0;
    }
}
elseif (isset($_GET['search']) && $_GET['search'] != "")
{
    $link_arg['search'] = strip_tags($_GET['search']);
    $link_arg['searchStrategy'] = (int)$_GET['searchStrategy'];
}
else
{
    // nothing to retreive
}

if (isset($link_arg['search']))
{
    $messageStrategy->setSearch($link_arg['search']);
    if ($link_arg['searchStrategy'] == 1)
    {
        $messageStrategy->setSearchStrategy(MessageStrategy::SEARCH_STRATEGY_EXPRESSION);
    }
    elseif ($link_arg['searchStrategy'] == 0)
    {
        $messageStrategy->setSearchStrategy(MessageStrategy::SEARCH_STRATEGY_WORD);
    }
}

// ---------------- set limit -----------------------
// lets this part after selector/filter nb page depend of the selector/filter
        
if (isset($_GET['page']))
{
    $page = min(array((int)$_REQUEST['page'],$box->getNumberOfPage()));
    $page = max(array($page,1));
    $link_arg['page'] = $page;
    $messageStrategy->setPageToDisplay($link_arg['page']);
}

// ------------ set the strategy
$box->setMessageStrategy($messageStrategy);

$content .= getBarMessageBox($currentUserId, $currentSection);

include "receivedmessageboxview.inc.php";
<?php // $Id: outboxcontroler.inc.php 12989 2011-03-18 15:42:50Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Controler of the outbox.
 *
 * @version     $Revision: 12989 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

require_once dirname(__FILE__) . '/lib/messagebox/outbox.lib.php';

$deleteConfirmation = FALSE;

$acceptedCmdList = array('rqSearch');

if (isset($_REQUEST['cmd']) && in_array($_REQUEST['cmd'], $acceptedCmdList))
{
    if ($_REQUEST['cmd'] == 'rqSearch')
    {
        $displaySearch = TRUE;
    }
}

$currentSection = isset( $_REQUEST['box'] )
    ? $_REQUEST['box']
    : 'inbox'
    ;

// create box
$box = new OutBox($currentUserId);

$messageStrategy = $box->getMessageStrategy();

if (isset($_REQUEST['fieldOrder']))
{
    $link_arg['fieldOrder'] = $_REQUEST['fieldOrder'] == 'date' ? 'date' : 'date';
    
    if ($link_arg['fieldOrder'] == 'date')
    {
        $messageStrategy->setFieldOrder(OutBoxStrategy::ORDER_BY_DATE);
    }
}

if (isset($_REQUEST['order']))
{
    $order = $_REQUEST['order'] == 'asc' ? 'asc' : 'desc';
    
    $link_arg['order'] = $order;
    
    if ($link_arg['order'] == 'asc')
    {
        $nextOrder = "desc";
        $messageStrategy->setOrder(OutBoxStrategy::ORDER_ASC);
    }
    else
    {
        $nextOrder = "asc";
        $messageStrategy->setOrder(OutBoxStrategy::ORDER_DESC);
    }
}
else
{
    $nextOrder = "asc";
}

// search
if (isset($_POST['search']) && $_POST['search'] != "")
{
    $link_arg['search'] = $_POST['search'];
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

$content .= getBarMessageBox($currentUserId, $currentSection);

include "outboxview.inc.php";
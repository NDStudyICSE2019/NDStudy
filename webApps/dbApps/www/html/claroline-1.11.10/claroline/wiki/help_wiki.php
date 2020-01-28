<?php // $Id: help_wiki.php 14093 2012-03-22 10:22:57Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14093 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *              This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 *              as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 *              through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 * @author      Frederic Minne <zefredz@gmail.com>
 * @package     Wiki
 */

require '../inc/claro_init_global.inc.php';

$nameTools = get_lang("Wiki");
$hide_banner=TRUE;

$htmlHeadXtra[] =
    '<style type="text/css">
        dt{font-weight:bold;margin-top:5px;}
    </style>';

$out = '';

$help = ( isset( $_REQUEST['help'] ) ) ? $_REQUEST['help'] : 'syntax';

//$out .= '<center><a href="#" onclick="window.close()">'.get_lang("Close window").'</a></center>' . "\n";

switch( $help )
{
    case 'syntax':
    {
        $out .= get_block('blockWikiHelpSyntaxContent');
        break;
    }
    case 'admin':
    {
        $out .= get_block('blockWikiHelpAdminContent');
        break;
    }
    default:
    {
        $out .= '<center><h1>'.get_lang('Wrong parameters').'</h1></center>';
    }
}

//$out .= '<center><a href="#" onclick="window.close()">'.get_lang("Close window").'</a></center>' . "\n";

$hide_footer = true;

$claroline->setDisplayType(Claroline::POPUP);
$claroline->display->body->appendContent($out);

echo $claroline->display->render();

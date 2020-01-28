<?php // $Id: index.php 12977 2011-03-15 14:26:44Z abourguignon $

/**
 * CLAROLINE
 *
 * This is the index page of sdk tools.
 *
 * @version     $Revision: 12977 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     SDK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 */

require '../../inc/claro_init_global.inc.php';
if(file_exists(get_path('rootSys').'platform/currentVersion.inc.php')) include (get_path('rootSys').'platform/currentVersion.inc.php');
$is_allowedToUseSDK = claro_is_platform_admin();

if (! $is_allowedToUseSDK) claro_disp_auth_form();

$nameTools = get_lang('Technical Tools');

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );

$out = '';

$out .= claro_html_tool_title(array('mainTitle'=>$nameTools))
      . '<ul>'."\n"
      . '<li><a href="./files_stats.php">' . get_lang('Files statistics') . '</a></li>'."\n"
      . '<li><a href="./phpInfo.php">' . get_lang('PHP system information') . '</a></li>'."\n"
      . '</ul>'."\n";

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
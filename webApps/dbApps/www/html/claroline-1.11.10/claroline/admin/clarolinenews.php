<?php // $Id: clarolinenews.php 13049 2011-04-07 16:04:22Z abourguignon $

/**
 * CLAROLINE
 *
 * Show news read from claroline.net.
 *
 * @version     $Revision: 13049 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/CLNEWS/
 * @author      Claro Team <cvs@claroline.net>
 * @package     CLNEWS
 */

$cidReset = true;
$gidReset = true;

require '../inc/claro_init_global.inc.php';

// Security check
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();
if ( ! claro_is_platform_admin() ) claro_die(get_lang('Not allowed'));

require_once get_path('incRepositorySys') . '/lib/admin.lib.inc.php';
// rss reader library
require_once get_path('incRepositorySys') . '/lib/thirdparty/lastRSS/lastRSS.lib.php';


$nameTools = get_lang('Claroline.net news');
ClaroBreadCrumbs::getInstance()->prepend( get_lang('Administration'), get_path('rootAdminWeb') );
$noQUERY_STRING   = TRUE;


//----------------------------------
// prepare rss reader
//----------------------------------
// url where the reader will have to get the rss feed
$urlNewsClaroline = 'http://www.claroline.net/rss.php';

$rss = new lastRSS;

// where the cached file will be written
$rss->cache_dir = get_path('rootSys') . '/tmp/cache/';
// how long without refresh the cache
$rss->cache_time = 1200;

//----------------------------------
// DISPLAY
//----------------------------------
// title variable

$out = '';

$out .= claro_html_tool_title($nameTools);

if (false !== $rs = $rss->get($urlNewsClaroline))
{
    foreach ($rs['items'] as $item)
    {
        $href = $item['link'];
        $title = $item['title'];
        $summary = $rss->unhtmlentities($item['description']);
        $date = strtotime($item['pubDate']);

        $out .= '<div class="claroBlock">'."\n"
            .'<h3 class="blockHeader">'."\n"
            .'<a href="'.$href.'">'.$title.'</a>'."\n"
            .'<small> - '.claro_html_localised_date(get_locale('dateFormatLong'),$date).'</small>'."\n"
            .'</h3>'."\n"
            .'<div class="claroBlockContent">' . "\n"
            .$summary."\n"
            .'</div>'."\n"
            .'</div>'."\n\n";
    }

}
else
{
    $dialogBox = new dialogBox();
    $dialogBox->error( get_lang('Error : cannot read RSS feed (Check feed url and if php setting "allow_url_fopen" is turned on).') );
    $out .= $dialogBox->render();
}

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
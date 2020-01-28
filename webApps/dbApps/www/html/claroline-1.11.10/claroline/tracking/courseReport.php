<?php // $Id: courselist.lib.php 13685 2011-10-14 12:42:41Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <pir@cerdecam.be>
 */

/*
 * Kernel
 */
require_once dirname( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';



/*
 * Permissions
 */
if( ! get_conf('is_trackingEnabled') ) claro_die(get_lang('Tracking has been disabled by system administrator.'));
if( ! claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);
if( ! claro_is_course_manager() ) claro_die(get_lang('Not allowed'));

/*
 * Libraries
 */
require_once dirname( __FILE__ ) . '/lib/trackingRenderer.class.php';
require_once dirname( __FILE__ ) . '/lib/trackingRendererRegistry.class.php';

/*
 * Init some other vars
 */

// Command list
$cmdList = array();

$cmdList[] = array(
    'img' => 'delete',
    'name' => get_lang('Delete all course statistics'),
    'url' => claro_htmlspecialchars(Url::Contextualize('delete_course_stats.php'))
);


/*
 * Output
 */
CssLoader::getInstance()->load( 'tracking', 'screen');
JavascriptLoader::getInstance()->load('tracking');

// initialize output
$claroline->setDisplayType( Claroline::PAGE );

$nameTools = get_lang('Statistics');

$html = '';

$html .= claro_html_tool_title(
                array(
                    'mainTitle' => $nameTools,
                    'subTitle'  => get_lang('Statistics of course : %courseCode', array('%courseCode' => claro_get_current_course_data('officialCode')))
                ),
                null,
                $cmdList
            );

/*
 * Prepare rendering :
 * Load and loop through available tracking renderers
 * Order of renderers blocks is arranged using "first found, first display" in the registry
 * Modify the registry to change the load order if required
 */
// get all renderers by using registry
$trackingRendererRegistry = TrackingRendererRegistry::getInstance(claro_get_current_course_id());

// here we need course tracking renderers
$courseTrackingRendererList = $trackingRendererRegistry->getCourseRendererList();

foreach( $courseTrackingRendererList as $ctr )
{
    $renderer = new $ctr( claro_get_current_course_id() );
    $html .= $renderer->render();
}


/*
 * Output rendering
 */
$claroline->display->body->setContent($html);

echo $claroline->display->render();

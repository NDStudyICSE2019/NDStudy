<?php // $Id: platform_courses.php 14288 2012-10-17 08:02:02Z jrm_ $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14288 $
 * @license     http://www.gnu.org/licenses/agpl-3.0-standalone.html AGPL Affero General Public License
 * @copyright   Copyright 2010 Claroline Consortium
 * @author      Claro Team <cvs@claroline.net>
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since       1.10
 */

require '../inc/claro_init_global.inc.php';
require '../inc/lib/courselist.lib.php';
require_once dirname(__FILE__) . '/../inc/lib/coursesearchbox.class.php';

//load home page config file
require claro_get_conf_repository() . 'CLHOME.conf.php';

// Build the breadcrumb
$nameTools = get_lang('Platform courses');

$categoryId = ( !empty( $_REQUEST['categoryId']) ) ? ( (int) $_REQUEST['categoryId'] ) : ( 0 );

$categoryBrowser    = new CategoryBrowser($categoryId, claro_get_current_user_id());

if ( isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'search' )
{
    $categoriesList = array();
    $coursesList = search_course( $_REQUEST['keyword'] );
}

// Display
$template = $categoryBrowser->getTemplate();

$claroline->display->body->appendContent($template->render());

$searchbox = new CourseSearchBox($_SERVER['REQUEST_URI']);

$claroline->display->body->appendContent($searchbox->render());

echo $claroline->display->render();

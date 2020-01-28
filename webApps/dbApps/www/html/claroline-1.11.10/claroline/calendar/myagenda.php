<?php // $Id: myagenda.php 13282 2011-07-04 16:35:58Z abourguignon $

/**
 * CLAROLINE
 *
 * This file generates a general agenda of all items of the courses
 * the user is registered for.
 *
 * Based on the master-calendar code of Eric Remy (6 Oct 2003)
 * adapted by Toon Van Hoecke (Dec 2003) and Hugues Peeters (March 2004)
 *
 * @version     $Revision: 13282 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLCAL
 * @author      Claro Team <cvs@claroline.net>
 * @author      Eric Remy <eremy@rmwc.edu>
 * @author      Toon Van Hoecke <Toon.VanHoecke@UGent.be>
 */

$cidReset = true;

require_once dirname(__FILE__) . '/../../claroline/inc/claro_init_global.inc.php';

// check access
if ( ! claro_is_user_authenticated() ) claro_disp_auth_form();

require_once dirname( __FILE__ ) . '/../../claroline/calendar/lib/agenda.lib.php';


$nameTools = get_lang('My calendar');

$tbl_mdb_names       = claro_sql_get_main_tbl();

$tbl_course          = $tbl_mdb_names['course'];
$tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];

// Main

$sql = "SELECT cours.code                 AS sysCode,
               cours.administrativeNumber AS officialCode,
               cours.intitule             AS title,
               cours.titulaires           AS t,
               cours.dbName               AS db,
               cours.directory            AS dir

        FROM    `" . $tbl_course . "`          AS cours,
                `" . $tbl_rel_course_user . "` AS cours_user

        WHERE cours.code         = cours_user.code_cours
        AND   cours_user.user_id = " . (int) claro_get_current_user_id() ;

$userCourseList = claro_sql_query_fetch_all($sql);

$today = getdate();

if ( isset($_REQUEST['year']) ) $year = (int) $_REQUEST['year' ];
else                            $year = $today['year'];

if( isset($_REQUEST['month']) ) $month = (int) $_REQUEST['month'];
else                            $month = $today['mon' ];

$agendaItemList = get_agenda_items($userCourseList, $month, $year);
$langMonthNames = get_locale('langMonthNames');
$langDay_of_weekNames = get_locale('langDay_of_weekNames');

$monthName = $langMonthNames['long'][$month-1];

// Display
$out = '';

$out .= claro_html_tool_title($nameTools)

// Display Calendar
.    claro_html_monthly_calendar($agendaItemList, $month, $year, $langDay_of_weekNames['long'], $monthName)
;

$claroline->display->body->appendContent($out);

echo $claroline->display->render();
<?php // $Id: courseDescription.lib.php 14346 2012-12-13 11:30:25Z ldumorti $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 14346 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/CLDSC/
 * @author      Claro Team <cvs@claroline.net>
 * @package     CLDSC
 */


/**
 * get all the items
 *
 * @param $courseId string  glued dbName of the course to affect default: current course
 *
 * @return array of arrays with data of the item
 *
 * @author Christophe Gesché <moosh@claroline.net>
 *
 */

function course_description_get_item_list($courseId = null)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($courseId));
    $tblCourseDescription = $tbl['course_description'];

    if (get_conf('cldsc_use_new_ordering_of_labels'))
    {
	// sort first the principal categories
	    $sql = "SELECT `cd`.`id`,
	                   `cd`.`category`,
	                   `cd`.`title`,
	                   `cd`.`content`,
	                UNIX_TIMESTAMP(cd.`lastEditDate`)
	                   AS `unix_lastEditDate`,
	                   `cd`.`visibility`
	            FROM `" . $tblCourseDescription . "` AS `cd`
	            WHERE `cd`.`category` != '-1'
	            ORDER BY `cd`.`category` ASC";
	    // and then the "other" category ... by title
		$sql2 = "SELECT cd.`id`,
	                   cd.`category`,
	                   cd.`title`,
	                   cd.`content`,
	                UNIX_TIMESTAMP(`cd`.`lastEditDate`)
	                   AS `unix_lastEditDate`,
	                   `cd`.`visibility`
	            FROM `" . $tblCourseDescription . "` AS `cd`
	            WHERE `cd`.`category` = '-1'
	            ORDER BY `cd`.`title` ASC";
	    return  array_merge(claro_sql_query_fetch_all($sql),claro_sql_query_fetch_all($sql2));
    }
    else
    {
    	 $sql = "SELECT `cd`.`id`,
	                   `cd`.`category`,
	                   `cd`.`title`,
	                   `cd`.`content`,
	                UNIX_TIMESTAMP(cd.`lastEditDate`)
	                   AS `unix_lastEditDate`,
	                   `cd`.`visibility`
	            FROM `" . $tblCourseDescription . "` AS `cd`
	            ORDER BY `cd`.`category` ASC";
        return claro_sql_query_fetch_all($sql);
    }
}

/**
 * Return the tips list
 *
 * @return array ('title','isEditable','question','information')
 *
 */

function get_tiplistinit()
{
    $tipList = array();
    include_once dirname(__FILE__) . '/../tiplistinit.inc.php';
    return $tipList;
}

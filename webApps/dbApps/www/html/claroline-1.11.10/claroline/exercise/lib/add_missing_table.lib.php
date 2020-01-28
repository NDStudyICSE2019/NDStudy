<?php // $Id: add_missing_table.lib.php 13708 2011-10-19 10:46:34Z abourguignon $
/*
 * $id$ To change this template, choose Tools | Templates
 * $id$ and open the template in the editor.
 */
if ( count( get_included_files() ) == 1 ) die( '---' );

function init_qwz_questions_categories ()
{
    $currentCourseDbNameGlu = claro_get_course_db_name_glued(claro_get_current_course_id());

    $sql = "CREATE TABLE IF NOT EXISTS `" . $currentCourseDbNameGlu . "qwz_questions_categories` (
                `id` int(11) NOT NULL auto_increment,
                `title` varchar(50) NOT NULL,
                `description` TEXT,
                PRIMARY KEY (`id`)
                ) ENGINE=MyISAM COMMENT='Record the categories of questions';";

    claro_sql_query($sql);
}

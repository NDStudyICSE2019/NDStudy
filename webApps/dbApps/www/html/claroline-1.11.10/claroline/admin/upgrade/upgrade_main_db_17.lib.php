<?php // $Id: upgrade_main_db_17.lib.php 13348 2011-07-18 13:58:28Z abourguignon $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * Sql query to update main database.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/index.php/Upgrade_claroline_1.6
 * @package     UPGRADE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Mathieu Laurent <mla@claroline.net>
 * @author      Christophe Gesche <moosh@claroline.net>
 */

/*===========================================================================
 Upgrade to claroline 1.7
 ===========================================================================*/

function upgrade_main_database_to_17 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'MAINDB17';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // create notification table
            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" . $tbl_mdb_names['notify'] . "` (
              `id` int(11) NOT NULL auto_increment,
              `course_code` varchar(40) NOT NULL default '0',
              `tool_id` int(11) NOT NULL default '0',
              `ressource_id` varchar(255) NOT NULL default '0',
              `group_id` int(11) NOT NULL default '0',
              `user_id` int(11) NOT NULL default '0',
              `date` datetime default '0000-00-00 00:00:00',
              PRIMARY KEY  (`id`),
              KEY `course_id` (`course_code`)
            ) ENGINE=MyISAM";

            // add enrollment key
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['course'] . "` ADD `enrollment_key` varchar(255) default NULL";

            // remove old columns : cahier_charges, scoreShow, description
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['course'] . "` DROP COLUMN `cahier_charges`";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['course'] . "` DROP COLUMN `scoreShow`";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['course'] . "` DROP COLUMN `description`";

            // add index in rel_class_user table
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['rel_class_user'] . "` ADD INDEX ( `user_id` ) ";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['rel_class_user'] . "` ADD INDEX ( `class_id` ) ";

            if ( upgrade_apply_sql($sqlForUpdate) )
            {
                $step = set_upgrade_status($tool, 2);
            }
            else
            {
                return $step ;
            }

        case 2 :

            register_tool_in_main_database('CLWIKI__','wiki/wiki.php','wiki.gif');
            $step = set_upgrade_status($tool, 0);
            return $step ;

        default :
            $step = set_upgrade_status($tool, 0);
            return $step;
    }

    return false;
}
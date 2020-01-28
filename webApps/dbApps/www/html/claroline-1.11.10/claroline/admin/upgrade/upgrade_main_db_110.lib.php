<?php // $Id: upgrade_main_db_110.lib.php 14199 2012-07-09 12:14:53Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * Sql query to update main database
 *
 * @version     $Revision: 14199 $ *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE *
 * @package     UPGRADE *
 * @author      Claro Team <cvs@claroline.net>
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 *              (upgrades regarding session courses and categories)
 */


/*===========================================================================
 Upgrade to claroline 1.10
 ===========================================================================*/

function upgrade_category_to_110 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'CATEGORY_110';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :
            // Create new tables `category` and `rel_course_category`
            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" . $tbl_mdb_names['category'] . "` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `name` varchar(100) NOT NULL DEFAULT '',
                                `code` varchar(12) NOT NULL DEFAULT '',
                                `idParent` int(11) DEFAULT '0',
                                `rank` int(11) NOT NULL DEFAULT '0',
                                `visible` tinyint(1) NOT NULL DEFAULT '1',
                                `canHaveCoursesChild` tinyint(1) NOT NULL DEFAULT '1',
                                PRIMARY KEY (`id`),
                                UNIQUE KEY `code` (`code`)
                                ) ENGINE=MyISAM";
            
            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" . $tbl_mdb_names['rel_course_category'] . "` (
                                `courseId` int(11) NOT NULL,
                                `categoryId` int(11) NOT NULL,
                                `rootCourse` tinyint(1) NOT NULL DEFAULT '0',
                                PRIMARY KEY (`courseId`,`categoryId`)
                                ) ENGINE=MyISAM";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 2 :
            // Insert root category
            $sqlForUpdate[] = "INSERT INTO `" . $tbl_mdb_names['category'] . "`
                                ( `name`, `code`, `idParent`, `rank`, `visible`, `canHaveCoursesChild`)
                                VALUES
                                ( 'Root', 'ROOT', NULL, 0, 0, 0)";
                        
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 3 :
            // Update root category to 0
            $sqlForUpdate[] = " UPDATE `" . $tbl_mdb_names['category'] . "`
                                SET `id` = 0
                                WHERE `code` = 'ROOT';";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 4:
            // Insert all previous categories ("faculties") in the new table `category`
            $sql = "SELECT f1.`id`, f1.`name`, f1.`code`, f1.`code_P`, f1.`treePos`, f1.`nb_childs`,
                    f1.`canHaveCoursesChild`, f1.`canHaveCatChild`, f2.`id` as idParent
                    FROM `" . get_conf('mainTblPrefix') . "faculte` f1, `" . get_conf('mainTblPrefix') . "faculte` f2
                    WHERE f1.code_P = f2.code OR f1.code_P IS NULL
                    GROUP BY f1.id
                    ORDER BY idParent ASC, f1.`treePos` ASC";
            
            $categoriesList = claro_sql_query_fetch_all_rows( $sql );
            
            $tempIdParent     = null;
            $rank             = 0;
            $visibile         = 1; // Change this value if you want to change the default value of visibility (1 or 0)
            
            foreach ($categoriesList as $category)
            {
                // Manage the rank
                if ($tempIdParent != $category['idParent'])
                {
                    $tempIdParent = $category['idParent'];
                    $rank = 1;
                }
                else
                {
                    $rank++;
                }
                
                // what this for ????
                if ( /* $category['idParent'] == 1 || */ empty($category['code_P']) || empty($category['idParent']))
                {
                    $category['idParent'] = 0;
                }
                
                $sqlForUpdate[] = "INSERT INTO `" . $tbl_mdb_names['category'] . "`
                                   ( `id`, `name`, `code`, `idParent`, `rank`, `visible`, `canHaveCoursesChild`)
                                   VALUES
                                   ( " 
                                    . (int) $category['id'] . ", '" 
                                    . addslashes($category['name']) . "', '" 
                                    . $category['code'] . "', " 
                                    . $category['idParent'] . ", " 
                                    . $rank . ", $visibile, " 
                                    . $category['canHaveCoursesChild'] 
                                   . ")";
            }
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 5 :
            // Associate courses to new categories through `rel_course_categories`
            $sql = "SELECT co.cours_id AS courseId, ca.id AS categoryId
                    FROM `" . $tbl_mdb_names['course'] . "` co, `" . get_conf('mainTblPrefix') . "faculte` f, `" . $tbl_mdb_names['category'] . "` ca
                    WHERE co.faculte = f.code AND f.code = ca.code
                    ORDER BY co.`cours_id` ASC";
            
            $associationsList = claro_sql_query_fetch_all_rows( $sql );
            
            $rootCourse = 0; // Change this value if you want to change the default value of rootCourse (1 or 0)
            
            foreach ( $associationsList as $assoc )
            {
                $sqlForUpdate[] = "INSERT INTO `" . $tbl_mdb_names['rel_course_category'] . "`
                                   (`courseId`, `categoryId`, `rootCourse`)
                                   VALUES
                                   (" . (int) $assoc['courseId'] . ", " . (int) $assoc['categoryId'] . ", " . $rootCourse . ")";
            }
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 6 :
            // Drop deprecated attribute "faculte" from `cours` table
            $sqlForUpdate[] = "ALTER TABLE `" . $tbl_mdb_names['course'] . "` DROP `faculte`";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 7 :
            // Drop deprecated table `faculty`
            $sqlForUpdate[] = "DROP TABLE `" . get_conf('mainTblPrefix') . "faculte`";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        default :
            
            $step = set_upgrade_status($tool, 0);
            return $step;

    }

    return false;
}

function upgrade_session_course_to_110 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'SESSION_COURSE';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :
            
            // Add the attribute sourceCourseId to the course table
            $sqlForUpdate[] = "ALTER TABLE `" . $tbl_mdb_names['course'] . "`
                               ADD `sourceCourseId` INT NULL AFTER `code`  ";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;

            unset($sqlForUpdate);
            
        case 2 :
            
            // Add attribute "isSourceCours" to `cours`table
            $sqlForUpdate[] = "ALTER TABLE `" . $tbl_mdb_names['course'] . "` ADD `isSourceCourse` TINYINT(4) NOT NULL DEFAULT '0' AFTER `code`";
            //
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;

            unset($sqlForUpdate);
            
        default :

            $step = set_upgrade_status($tool, 0);
            return $step;

    }

    return false;
}

function upgrade_course_to_110 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'COURSE';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :
            
            // Add the field sourceCourseId to the course table
            $sqlForUpdate[] = "ALTER TABLE `" . $tbl_mdb_names['course'] . "`
                               CHANGE `registration` `registration` enum('open','close','validation')";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;

            unset($sqlForUpdate);
        
        case 2 :
            
            // Add the field userLimit to the course table
            $sqlForUpdate[] = "ALTER TABLE `" . $tbl_mdb_names['course'] . "`
                               ADD `userLimit` int(11) NOT NULL DEFAULT '0' AFTER `status`";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;

            unset($sqlForUpdate);
            
        default :

            $step = set_upgrade_status($tool, 0);
            return $step;

    }

    return false;
}

function upgrade_cours_user_to_110 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'REL_COURSE_USER';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :
            
            // Rename the table `cours_user` to `rel_course_user`
            $sqlForUpdate[] = "RENAME TABLE `" . get_conf('mainTblPrefix') . "cours_user` "
                            . "TO `" . get_conf('mainTblPrefix') . "rel_course_user`";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 2 :
            
            // Add the field isPending
            $sqlForUpdate[] = "ALTER TABLE `" . $tbl_mdb_names['rel_course_user'] . "` ADD `isPending` TINYINT(4) NOT NULL DEFAULT '0' ";
            //
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;

            unset($sqlForUpdate);


        case 3 :

            // Add the field isPending
            $sqlForUpdate[] = "ALTER TABLE `" . $tbl_mdb_names['rel_course_user'] . "` ADD `enrollment_date` DATETIME NULL ";
            //
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;

            unset($sqlForUpdate);
        default :

            $step = set_upgrade_status($tool, 0);
            return $step;

    }

    return false;
}

function upgrade_coursehomepage_to_110 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'COURSE_HOMEPAGE';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :
            
            // Create table
            $sqlForUpdate[] = "CREATE TABLE `" . get_conf('mainTblPrefix') . "rel_course_portlet` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `courseId` int(11) NOT NULL,
              `rank` int(11) NOT NULL,
              `label` varchar(255) NOT NULL,
              `visible` tinyint(4) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `courseId` (`courseId`,`label`)
            ) ENGINE=MyISAM";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 2 :
            
            // Create table
            $sqlForUpdate[] = "CREATE TABLE `" . get_conf('mainTblPrefix') . "coursehomepage_portlet` (
              `label` varchar(10) NOT NULL,
              `name` varchar(255) NOT NULL,
              PRIMARY KEY (`label`)
            ) ENGINE=MyISAM";
            //
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
            
        case 3 :
            
            // Insert data
            $sqlForUpdate[] = "INSERT INTO `" . get_conf('mainTblPrefix') . "coursehomepage_portlet`
            (`label`, `name`)
            VALUES
            ('CLTI',    'Headlines'),
            ('CLCAL',   'Calendar'),
            ('CLANN',   'Announcements')";
            //
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;
            
            unset($sqlForUpdate);
        default :
            
            $step = set_upgrade_status($tool, 0);
            return $step;

    }

    return false;
}

function upgrade_event_resource_to_110 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'EVENT_RESOURCE_LINKER';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // Create table
            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" . get_conf('mainTblPrefix') . "event_resource` (
              `event_id` INTEGER NOT NULL,
              `resource_id` INTEGER NOT NULL,
              `tool_id` INTEGER NOT NULL,
              `course_code` VARCHAR(40) NOT NULL,
              PRIMARY KEY (`event_id`, `resource_id`, `tool_id`, `course_code`),
              UNIQUE KEY (`event_id`, `course_code`)
            ) ENGINE=MyISAM";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step;

            unset($sqlForUpdate);

        default :

            $step = set_upgrade_status($tool, 0);
            return $step;

    }

    return false;
}
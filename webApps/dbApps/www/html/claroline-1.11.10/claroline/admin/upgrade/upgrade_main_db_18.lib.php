<?php // $Id: upgrade_main_db_18.lib.php 13348 2011-07-18 13:58:28Z abourguignon $

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
 * @author      Mathieu Laurent   <mla@claroline.net>
 * @author      Christophe Gesché <moosh@claroline.net>
 */

/*===========================================================================
 Upgrade to claroline 1.8
 ===========================================================================*/

/**
 * Upgrade table course (from main database) to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_course_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'COURSE_18' ;

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // Add defaultProfileId column

            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['course'] . "` ADD `defaultProfileId` int(11) NOT NULL";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;
        
        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    
    }
      
    return false;

}

/**
 * Upgrade table rel_course_user (from main database) to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_rel_course_user_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'COURSEUSER_18';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['rel_course_user'] . "` ADD `profile_id` int(11) NOT NULL ";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['rel_course_user'] . "` ADD `count_user_enrol` int(11) NOT NULL default 0 ";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['rel_course_user'] . "` ADD `count_class_enrol` int(11) NOT NULL default 0 ";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['rel_course_user'] . "` ADD `isCourseManager` tinyint(4) NOT NULL default 0 ";
                
            // `statut` tinyint(4) NOT NULL default '5' --> `isCourseManager` tinyint(4) NOT NULL default 0

            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['rel_course_user'] . "`
                               SET `isCourseManager` = 1
                               WHERE `statut` = 1 ";
            
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['rel_course_user'] . "` DROP COLUMN `statut` ";

            // count_user_enrol egals 1

            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['rel_course_user'] . "` SET `count_user_enrol` = 1 ";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    
    }
      
    return false;

}

/**
 * Upgrade table course_category (from main database) to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_course_category_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'COURSECAT_18';

    switch( $step = get_upgrade_status($tool) )
    {

        case 1 :

            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['category'] . "` DROP COLUMN `bc` ";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['category'] . "` CHANGE `nb_childs` `nb_childs` smallint(6) default 0";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    
    }
      
    return false;
}

/**
 * Upgrade table user (from main database) to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_user_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_mdb_names['admin'] = get_conf('mainDbName') . '`.`' . get_conf('mainTblPrefix') . 'admin' ;

    $tool = 'USER_18';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['user'] . "` ADD `language` varchar(15) default NULL";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['user'] . "` ADD `officialEmail` varchar(255) default NULL AFTER `officialCode`";

            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['user'] . "` CHANGE `email` `email` varchar(255) default NULL";
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['user'] . "` CHANGE `officialCode` `officialCode`  varchar(255) default NULL";

            // `statut` tinyint(4) default NULL, -->    `isCourseCreator` tinyint(4) default 0
            
            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['user'] . "` ADD `isCourseCreator` tinyint(4) default 0 ";

            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['user'] . "`
                               SET `isCourseCreator` = 1
                               WHERE `statut` = 1";

            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['user'] . "` DROP COLUMN `statut` ";

            $sqlForUpdate[] = "ALTER IGNORE TABLE `" . $tbl_mdb_names['user'] . "` ADD `isPlatformAdmin`  tinyint(4) default 0";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

            unset($sqlForUpdate);

        case 2 :
            
            // `isPlatformAdmin` --> from admin table
            
            $sql = " SELECT `idUser` FROM `" . $tbl_mdb_names['admin'] . "`";

            $result = claro_sql_query_fetch_all_cols($sql);

            $admin_uid_list = $result['idUser'];
    
            $sql = " UPDATE `" . $tbl_mdb_names['user'] . "`
                     SET `isPlatformAdmin` = 1
                     WHERE user_id IN (" . implode(',',$admin_uid_list) . ")";

            if ( upgrade_sql_query($sql) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

        case 3 :

            // drop table admin

            $sqlForUpdate[] = "DROP TABLE IF EXISTS `" . $tbl_mdb_names['admin'] . "`";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;
            
            unset($sqlForUpdate);
        
        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    
    }
      
    return false;

}

/**
 * Upgrade table rel_course_class (from main database) to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_course_class_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'COURSE_CLASS_18';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // course class

            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" .  $tbl_mdb_names['rel_course_class'] . "` (
                `courseId` varchar(40) NOT NULL,
                `classId` int(11) NOT NULL default '0',
                PRIMARY KEY  (`courseId`,`classId`) )
                ENGINE=MyISAM ";
            
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;
        
        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    
    }
      
    return false;
}

/**
 * Upgrade module (from main database) to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_module_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'MODULE_18';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // module
             
            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `" . $tbl_mdb_names['module'] . "` (
              `id`         smallint    unsigned             NOT NULL auto_increment,
              `label`      char(8)                          NOT NULL default '',
              `name`       char(100)                        NOT NULL default '',
              `activation` enum('activated','desactivated') NOT NULL default 'desactivated',
              `type`       enum('tool','applet')            NOT NULL default 'applet',
              `script_url` char(255)                        NOT NULL default 'entry.php',
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM";
            
            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `".$tbl_mdb_names['module_info'] . "` (
              id             smallint     NOT NULL auto_increment,
              module_id      smallint     NOT NULL default '0',
              version        varchar(10)  NOT NULL default '',
              author         varchar(50)  default NULL,
              author_email   varchar(100) default NULL,
              author_website varchar(255) default NULL,
              description    varchar(255) default NULL,
              website        varchar(255) default NULL,
              license        varchar(50)  default NULL,
              PRIMARY KEY (id)
            ) ENGINE=MyISAM AUTO_INCREMENT=0";
            
            $sqlForUpdate[]= "CREATE TABLE IF NOT EXISTS `" . $tbl_mdb_names['dock'] . "` (
              id        smallint unsigned NOT NULL auto_increment,
              module_id smallint unsigned NOT NULL default '0',
              name      varchar(50)          NOT NULL default '',
              rank      tinyint  unsigned NOT NULL default '0',
              PRIMARY KEY  (id)
            ) ENGINE=MyISAM AUTO_INCREMENT=0";
                        
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

            unset($sqlForUpdate);

        case 3 :

            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['tool'] . "`
                             SET claro_label = TRIM(TRAILING '_' FROM claro_label )";
           
            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['tool'] . "`
                             SET `script_url` = SUBSTRING_INDEX( `script_url` , '/', -1 ) ";
            
            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['tool'] . "`
                             SET `script_url` = 'exercise.php' WHERE `script_url` = 'exercice.php' ";
 
            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;
            
            unset($sqlForUpdate);

        case 4 :
            
            // include libray to manage module
            require_once $GLOBALS['includePath'] . '/lib/module/manage.lib.php';

            $error = false ;

            $sql = " SELECT id, claro_label, script_url, icon, def_access, def_rank, add_in_course, access_manager
                     FROM `" . $tbl_mdb_names['tool'] . "`";

            $toolList = claro_sql_query_fetch_all($sql);

            foreach ( $toolList as $tool )
            {
                $toolLabel = $tool['claro_label'];

                // get module path, for read module manifest
                $toolPath = get_module_path($toolLabel);

                if ( ( $toolInfo = readModuleManifest($toolPath) ) !== false )
                {
                    // get script url
                    if (isset($toolInfo['ENTRY']))
                    {
                        $script_url = $toolInfo['ENTRY'];
                    }
                    else
                    {
                        $script_url = 'entry.php';
                    }
                }
                else
                {
                    // init toolInfo
                    $toolInfo['LABEL'] = $tool['claro_label'];
                    $toolInfo['NAME'] = $tool['claro_label'];
                    $toolInfo['TYPE'] = 'tool';
                    $toolInfo['VERSION'] = '1.8';
                    $toolInfo['AUTHOR']['NAME'] = '' ;
                    $toolInfo['AUTHOR']['EMAIL'] = '' ;
                    $toolInfo['AUTHOR']['WEB'] = '' ;
                    $toolInfo['DESCRIPTION'] = '';
                    $toolInfo['LICENSE'] = 'unknown' ;
                    $script_url = $tool['script_url'];
                }

                // fill table module and module_info
                // code from register_module_core from inc/lib/module.manage.lib.php

                $sql = "INSERT INTO `" . $tbl_mdb_names['module'] . "`
                        SET label      = '" . claro_sql_escape($toolInfo['LABEL']) . "',
                            name       = '" . claro_sql_escape($toolInfo['NAME']) . "',
                            type       = '" . claro_sql_escape($toolInfo['TYPE']) . "',
                            activation = 'activated' ,
                            script_url = '" . claro_sql_escape($script_url). "'";

                $moduleId = claro_sql_query_insert_id($sql);

                $sql = "INSERT INTO `" . $tbl_mdb_names['module_info'] . "`
                        SET module_id    = " . (int) $moduleId . ",
                            version      = '" . claro_sql_escape($toolInfo['VERSION']) . "',
                            author       = '" . claro_sql_escape($toolInfo['AUTHOR']['NAME'  ]) . "',
                            author_email = '" . claro_sql_escape($toolInfo['AUTHOR']['EMAIL' ]) . "',
                            website      = '" . claro_sql_escape($toolInfo['AUTHOR']['WEB'   ]) . "',
                            description  = '" . claro_sql_escape($toolInfo['DESCRIPTION'     ]) . "',
                            license      = '" . claro_sql_escape($toolInfo['LICENSE'         ]) . "'";

                if ( upgrade_sql_query($sql) === false )
                {
                    $error = true ;
                    break;
                }
            }
            
            if ( ! $error ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;
        
        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    
    }
      
    return false;
}

/**
 * Upgrade right (from main database) to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_right_to_18 ()
{
    include_once $GLOBALS['includePath'] . '/lib/right/right_profile.lib.php' ;
    include_once $GLOBALS['includePath'] . '/../install/init_profile_right.lib.php' ;

    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'RIGHT_18';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // add right tables

            $sqlForUpdate[] = " CREATE TABLE IF NOT EXISTS `". $tbl_mdb_names['right_profile'] . "` (
               `profile_id` int(11) NOT NULL auto_increment,
               `type` enum('COURSE','PLATFORM') NOT NULL default 'COURSE',
               `name` varchar(255) NOT NULL default '',
               `label` varchar(50) NOT NULL default '',
               `description` varchar(255) default '',
               `courseManager` tinyint(4) default '0',
               `mailingList` tinyint(4) default '0',
               `userlistPublic` tinyint(4) default '0',
               `groupTutor` tinyint(4) default '0',
               `locked` tinyint(4) default '0',
               `required` tinyint(4) default '0',
               PRIMARY KEY  (`profile_id`),
               KEY `type` (`type`)
            )ENGINE=MyISAM " ;
             
            $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `".$tbl_mdb_names['right_action'] . "` (
               `id` int(11) NOT NULL auto_increment,
               `name` varchar(255) NOT NULL default '',
               `description` varchar(255) default '',
               `tool_id` int(11) default NULL,
               `rank` int(11) default '0',
               `type` enum('COURSE','PLATFORM') NOT NULL default 'COURSE',
               PRIMARY KEY  (`id`),
               KEY `tool_id` (`tool_id`),
               KEY `type` (`type`)
             )ENGINE=MyISAM ";
             
             $sqlForUpdate[] = "CREATE TABLE IF NOT EXISTS `".$tbl_mdb_names['right_rel_profile_action'] . "` (
               `profile_id` int(11) NOT NULL,
               `action_id` int(11) NOT NULL,
               `courseId`  varchar(40) NOT NULL default '',
               `value` tinyint(4) default '0',
               PRIMARY KEY  (`profile_id`,`action_id`,`courseId`)
             ) ENGINE=MyISAM ";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;
            
            unset($sqlForUpdate);

        case 2 :

            create_required_profile();
            $step = set_upgrade_status($tool, $step+1);
        
        case 3 :
            
            // Init action/right

            $sql = " SELECT id
                     FROM `" . $tbl_mdb_names['tool'] . "`";

            $result = claro_sql_query_fetch_all_cols($sql);

            $toolIdList = $result['id'];

            foreach ( $toolIdList as $toolId)
            {
                // Manage right - Add read action
                $action = new RightToolAction();
                $action->setName('read');
                $action->setToolId($toolId);
                $action->save();

                // Manage right - Add edit action
                $action = new RightToolAction();
                $action->setName('edit');
                $action->setToolId($toolId);
                $action->save();
            }
            
            $step = set_upgrade_status($tool, $step+1);

        case 4 :

            init_default_right_profile();
            $step = set_upgrade_status($tool, $step+1);
            
        case 5 :

            // set profile_id in rel course_user
            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['rel_course_user'] . "` SET `profile_id` = " . claro_get_profile_id(USER_PROFILE) . "
                               WHERE `isCourseManager` = 0";
            
            $sqlForUpdate[] = "UPDATE `" . $tbl_mdb_names['rel_course_user'] . "` SET `profile_id` = " . claro_get_profile_id(MANAGER_PROFILE) . "
                               WHERE `isCourseManager` = 1";

            // set default profile_id in course

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

            unset($sqlForUpdate);

        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    
    }
      
    return false;
}

/**
 * Upgrade user_property to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_user_property_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'USERPROP_18';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // create tables

            $sqlForUpdate[]= "CREATE TABLE IF NOT EXISTS `" . $tbl_mdb_names['user_property'] . "` (
              `userId`        int(10) unsigned NOT NULL default '0',
              `propertyId`    varchar(255) NOT NULL default '',
              `propertyValue` varchar(255) NOT NULL default '',
              `scope`         varchar(45) NOT NULL default '',
              PRIMARY KEY  (`scope`(2),`propertyId`,`userId`)
            ) ENGINE=MyISAM ";

            $sqlForUpdate[]= "CREATE TABLE IF NOT EXISTS `" . $tbl_mdb_names['property_definition'] . "` (
              `propertyId` varchar(50) NOT NULL default '',
              `contextScope` varchar(10) NOT NULL default '',
              `label` varchar(50) NOT NULL default '',
              `type` varchar(10) NOT NULL default '',
              `defaultValue` varchar(255) NOT NULL default '',
              `description` text NOT NULL,
              `required` tinyint(1) NOT NULL default '0',
              `rank` int(10) unsigned NOT NULL default '0',
              `acceptedValue` text NOT NULL,
              PRIMARY KEY  (`contextScope`(2),`propertyId`),
              KEY `rank` (`rank`)
            ) ENGINE=MyISAM ";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

            unset($sqlForUpdate);

        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    }
      
    return false;
}

/**
 * Upgrade tracking to 1.8
 * @return step value, 0 if succeed
 */

function upgrade_main_database_tracking_to_18 ()
{
    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tool = 'TRACKING_18';

    switch( $step = get_upgrade_status($tool) )
    {
        case 1 :

            // Add indexes
            $sqlForUpdate[]= "ALTER TABLE `" . $tbl_mdb_names['track_e_default'] . "` ADD INDEX `default_user_id` ( `default_user_id` )";
            $sqlForUpdate[]= "ALTER TABLE `" . $tbl_mdb_names['track_e_login'] . "` ADD INDEX `login_user_id` ( `login_user_id` )";

            if ( upgrade_apply_sql($sqlForUpdate) ) $step = set_upgrade_status($tool, $step+1);
            else return $step ;

            unset($sqlForUpdate);

        default :

            $step = set_upgrade_status($tool, 0);
            return $step;
    }
      
    return false;
}
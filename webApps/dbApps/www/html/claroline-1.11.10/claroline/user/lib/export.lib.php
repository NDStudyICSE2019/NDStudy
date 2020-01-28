<?php // $Id: export.lib.php 14410 2013-03-14 08:31:45Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14410 $
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline team <info@claroline.net>
 * @package     CLUSR
 */

FromKernel::uses( 
    'csv.class',
    'class.lib',
    'user_info.lib' );

class UserInfoList
{
    private $courseId;

    public function __construct( $courseId )
    {
        $this->courseId = $courseId;
    }

    public function getUserInfoLabels()
    {
        $labels = claro_user_info_claro_user_info_get_cat_def_list( $this->courseId );

        if ( $labels )
        {
            $ret = array();

            foreach ( $labels as $label )
            {
                $ret[$label['catId']] = $label['title'];
            }

            return $ret;
        }
        else
        {
            return array();
        }
    }

    public function getUserInfo( $catId )
    {
        $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));

        return Claroline::getDatabase()->query("
            SELECT
                content.user_id     AS userId,
                cat.id              AS catId,
                cat.title           AS title,
                content.content     AS content
            FROM
                `" . $tbl['userinfo_def'] . "`     AS cat
            LEFT JOIN
                `" . $tbl['userinfo_content'] . "` AS content
            ON
                cat.id = content.def_id
            WHERE
                cat.id = " . (int) $catId . "
            ORDER BY `cat`.`id`
        ");
    }
}


class csvUserList extends CsvRecordlistExporter
{
    private $course_id;
    private $exId;
    
    public function __construct( $course_id )
    {
        parent::__construct(); // call constructor of parent class
        
        $this->course_id = $course_id;
    }
    
    function buildRecords( $exportUserInfo = true )
    {
        $tbl_mdb_names = claro_sql_get_main_tbl();

        $tbl_user = $tbl_mdb_names['user'];
        $tbl_rel_course_user = $tbl_mdb_names['rel_course_user'];
        
        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->course_id));

        $tbl_team = $tbl_cdb_names['group_team'];
        $tbl_rel_team_user = $tbl_cdb_names['group_rel_team_user'];
        
        $username = ( claro_is_platform_admin() && get_conf( 'export_sensitive_data_for_admin', false ) )
                || get_conf('export_user_username', false)
            ? "`U`.`username`     AS `username`,"
            : ""
            ;
                 
        if ( ( claro_is_platform_admin() && get_conf( 'export_sensitive_data_for_admin', false ) )
            || get_conf('export_user_password', false) )
        {
            if ( ( claro_is_platform_admin() && get_conf( 'export_sensitive_data_for_admin', false ) )
                || get_conf('export_user_password_encrypted', true ) )
            {
                $password = "MD5(`U`.`password`)     AS `password`,";
            }
            else
            {
                $password = "`U`.`password`     AS `password`,";
            }
        }
        else
        {
            $password = '';
        }

        // get user list
        $sql = "SELECT `U`.`user_id`      AS `userId`,
                       `U`.`nom`          AS `lastname`,
                       `U`.`prenom`       AS `firstname`,
                       {$username}
                       {$password}
                       `U`.`email`        AS `email`,
                       `U`.`officialCode`     AS `officialCode`,
                       GROUP_CONCAT(`G`.`id`) AS `groupId`,
                       GROUP_CONCAT(`G`.`name`) AS `groupName`
               FROM
                    (
                    `" . $tbl_user . "`           AS `U`,
                    `" . $tbl_rel_course_user . "` AS `CU`
                    )
               LEFT JOIN `" . $tbl_rel_team_user . "` AS `GU`
                ON `U`.`user_id` = `GU`.`user`
               LEFT JOIN `" . $tbl_team . "` AS `G`
                ON `GU`.`team` = `G`.`id`
               WHERE `U`.`user_id` = `CU`.`user_id`
               AND   `CU`.`code_cours`= '" . claro_sql_escape($this->course_id) . "'
               GROUP BY U.`user_id`
               ORDER BY U.`user_id`";

        $userList = claro_sql_query_fetch_all($sql);

        // build recordlist with good values for answers
        if( is_array($userList) && !empty($userList) )
        {
            // add titles at row 0, for that get the keys of the first row of array
            $this->recordList[0] = array_keys($userList[0]);

            $i = 1;

            $userIdList = array();

            foreach( $userList as  $user )
            {
                $userIdList[$user['userId']] = $i;

                if ( !( ( claro_is_platform_admin() && get_conf( 'export_sensitive_data_for_admin', false ) )
                    || get_conf('export_user_id', false) ) )
                {
                    $user['userId'] = $i;
                }
                
                // $this->recordList is defined in parent class csv
                $this->recordList[$i] = $user;

                $i++;
            }

            if ( $exportUserInfo )
            {
                $userInfoList = new UserInfoList($this->course_id);

                $userInfoLabelList = $userInfoList->getUserInfoLabels();

                foreach ( $userInfoLabelList as $catId => $catTitle )
                {
                    $this->recordList[0][] = $catTitle;

                    $userCatInfo = $userInfoList->getUserInfo($catId);

                    foreach ( $userCatInfo as $userCatInfo )
                    {
                        $this->recordList[$userIdList[$userCatInfo['userId']]][] = $userCatInfo['content'];
                    }
                }
            }
        }
        
        if( is_array($this->recordList) && !empty($this->recordList) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}

/**
 * Exports the members of a course as a csv file 
 * @param string $course_id
 * @return string 
 */
function export_user_list( $course_id )
{
    $csvUserList = new csvUserList( $course_id );
    
    $csvUserList->buildRecords();
    $csvContent = $csvUserList->export();
    
    return $csvContent;
}

/**
 * Exports the users in a class to a CSV file
 * @param int $id id of the class
 * @param array $fields optionnal names for the headers of the generated csv
 * @return string
 * @author schampagne <http://forum.claroline.net/memberlist.php?mode=viewprofile&u=45044>
 */
function export_user_list_for_class( $class_id, $fields = array('user_id', 'username', 'lastname', 'firstname', 'email', 'officialCode') )
{
    return csv_export_user_list(get_class_list_user_id_list(array($class_id)), $fields);
}

/**
 * Exports a CSV file from a list of user id's
 * @param array $userIdList id of the class
 * @param array $fields optionnal names for the headers of the generated csv
 * @return string
 * @author schampagne <http://forum.claroline.net/memberlist.php?mode=viewprofile&u=45044>
 */
function csv_export_user_list( $userIdList, $fields = array('user_id', 'username', 'lastname', 'firstname', 'email', 'officialCode') )
{
    $csv = new CsvExporter(',', '"');
    
    $csvData = array();
    $csvData[0] = $fields;
    
    foreach($userIdList as $userId)
    {
        $userInfo = user_get_properties($userId);
        
        $row = array();
        
        foreach($fields as $field)
        {
            if(isset($userInfo[$field]))
            {
                $row[$field] = $userInfo[$field];
            }
            else
            {
                $row[$field] = '';
            }
        }
        
        $csvData[] = $row;
    }
    
    return $csv->export($csvData);
}

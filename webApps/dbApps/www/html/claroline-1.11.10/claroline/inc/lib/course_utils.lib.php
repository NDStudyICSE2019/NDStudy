<?php // $Id: course_utils.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * @version 1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see http://www.claroline.net/wiki/CLCRS/
 * @package CLCRS
 * @author Claro Team <cvs@claroline.net>
 */

/**
 * return the title of a course
 *
 * @param $course_sys_code id of a course
 * @return string a string with the title of the course
 * @todo replace content of this function with claro_get_course_officialCode and claro_get_course_name
 */
function get_course_title($cid)
{
    $k = claro_get_course_data($cid);
    if (isset($k['officialCode']) && isset($k['name'])) return stripslashes($k['officialCode'] . ' : ' . $k['name']);
    else                                                return NULL;
}


/**
 * return all info of a course
 *
 * @param $cid the id of a course
 * @return array (array) an associative array containing all info of the course
 * @todo use claro_get_course_data
 */
function get_info_course($cid)
{
    if ($cid)
    {
        $_course = claro_get_course_data($cid);
        $_groupProperties = claro_get_main_group_properties($cid);

        if ($_groupProperties === false) trigger_error ('WARNING !! NO GROUP PROPERTIES !!');

        $_course = array_merge($_course, $_groupProperties);
    }
    else
    {
        $_course = NULL;
        //// all groups of these course
        ///  ( theses properies  are from the link  between  course and  group,
        //// but a group  can be only in one course)

        $_course ['registrationAllowed'] = FALSE;
        $_course ['tools'] ['CLFRM'    ] = FALSE;
        $_course ['tools'] ['CLDOC'    ] = FALSE;
        $_course ['tools'] ['CLWIKI'   ] = FALSE;
        $_course ['tools'] ['CLCHT'   ] = FALSE;
        $_course ['private'            ] = TRUE;
    }

    return $_course;
}


/**
 * Get the name of a course.
 * @param  string $course_id (optionnal)  If not set, it use the current course
 *         will be taken.
 * @return string path
 * @author Christophe Gesche <moosh@claroline.net>
 * @since 1.7
 */
function claro_get_course_name($cid=NULL)
{
    $k =claro_get_course_data($cid);
    if (isset($k['name'])) return $k['name'];
    else                   return NULL;
}



/**
 * Get the official code of a course.
 * @param  string $course_id (optionnal)  If not set, it use the current course
 *         will be taken.
 * @return string path
 * @author Christophe Gesche <moosh@claroline.net>
 * @since 1.7
 */
function claro_get_course_officialCode($cid=NULL)
{
    $k =claro_get_course_data($cid);
    if (isset($k['officialCode'])) return $k['officialCode'];
    else                           return NULL;
}

/**
    * return all info of tool for a course
    *
    * @param $cid the id of a course
    * @return array (array) an associative array containing all info of tool for a course

    */
function get_course_tool_list($cid)
{


    $toolNameList = claro_get_tool_name_list();

    $_course = get_info_course($cid);

    $tbl_mdb_names = claro_sql_get_main_tbl();
    $tbl_tool = $tbl_mdb_names['tool'];

    $courseToolList = array();

    if ($cid) // have course keys to search data
    {
        $sql ="SELECT ctl.id             id,
                        pct.claro_label    label,
                        ctl.script_name    name,
                        ctl.visibility     visibility,
                        pct.icon           icon,
                        pct.access_manager access_manager,

                        IF(pct.script_url IS NULL ,
                           ctl.script_url,CONCAT('".get_path('clarolineRepositoryWeb')."',
                           pct.script_url)) url

                           FROM `".$_course['dbNameGlu']."tool_list` ctl

                           LEFT JOIN `" . $tbl_tool . "` pct
                           ON       pct.id = ctl.tool_id

                           ORDER BY ctl.rank";

        $result = claro_sql_query($sql)  or die ('WARNING !! DB QUERY FAILED ! '.__LINE__);

         while( $tlistData = mysql_fetch_array($result) )
        {
            $courseToolList[] = $tlistData;
           }

           $tmp = array();

           foreach($courseToolList as $courseTool)
           {
               if( isset($courseTool['label']) )
               {
                   $label = $courseTool['label'];
                   $courseTool['name'] = $toolNameList[$label];
               }
               $tmp[] = $courseTool;
           }

           $courseToolList = $tmp;
           unset( $tmp );
    }

    return $courseToolList;
}

/**
 * Save a course property in 'course_properties' table
 * @param  string $propertyName
 * @param  string $propertyValue
 * @param  string $cid
 * @return boolean true on success, false on failure
 * @author Jean-Roch Meurisse <jmeuriss@fundp.ac.be>
 * @since 1.9.5
 */
function save_course_property( $propertyName, $propertyValue, $cid )
{
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_course_properties = $tbl_cdb_names['course_properties'];
    $check =
        "SELECT `id`
           FROM `" . $tbl_course_properties . "`
          WHERE `name` = " . Claroline::getDatabase()->quote( $propertyName ) . "
            AND `category` = 'MAIN'";
    $exists = Claroline::getDatabase()->query( $check )->numRows();
    if( $exists )
    {
        $statement =
            "UPDATE `" . $tbl_course_properties . "`
                SET `value` = " . Claroline::getDatabase()->quote( $propertyValue ) . "
              WHERE `name` = " . Claroline::getDatabase()->quote( $propertyName ) . "
                AND `category` = 'MAIN'";
    }
    else
    {
        $statement =
            "INSERT INTO `" . $tbl_course_properties . "`
                     SET `name` = " . Claroline::getDatabase()->quote( $propertyName ) . ",
                         `value` = " . Claroline::getDatabase()->quote( $propertyValue ) . ",
                         `category` = 'MAIN'";
    }
    return Claroline::getDatabase()->exec( $statement );
}

<?php // $Id: user_info.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * Provide function to work on  personnal editable info  of each user
 * 
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Gesché <moosh@claroline.net>
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @package     CLUSR
 */


/*----------------------------------------
CATEGORIES DEFINITION TREATMENT
--------------------------------------*/

/**
 * create a new category definition for the user information
 *
 * @param  string $title - category title
 * @param  string $comment - title comment
 * @param  int    $nbline - lines number for the field the user will fill.
 * @return boolean TRUE if succeed, else boolean FALSE
 */

function claro_user_info_create_cat_def($title='', $comment='', $nbline='5', $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def = $tbl_cdb_names['userinfo_def'];

    if ( 0 == (int) $nbline || empty($title))
    {
        return FALSE;
    }

    $sql = "SELECT MAX(`rank`) maxRank
            FROM `" . $tbl_userinfo_def . "`";
    $result = claro_sql_query($sql);
    if ($result) $maxRank = mysql_fetch_array($result);

    $maxRank = $maxRank['maxRank'];

    $thisRank = $maxRank + 1;

    $title   = trim($title);
    $comment = trim($comment);

    $sql = "INSERT INTO `" . $tbl_userinfo_def."` SET
            `title`        = '" . claro_sql_escape($title) . "',
            `comment`      = '" . claro_sql_escape($comment) . "',
            `nbline`       = " . (int) $nbline . ",
            `rank`         = " . (int) $thisRank ;

    return claro_sql_query_insert_id($sql);
}

/**
 * modify the definition of a user information category
 *
 * @param   integer $id - id of the category
 * @param   string  $title - category title
 * @param   string  $comment - title comment
 * @param   integer $nbline - lines number for the field the user will fill.
 * @return  boolean true if succeed, else otherwise
 */

function claro_user_info_edit_cat_def($id, $title, $comment, $nbline, $course_id=NULL)
{

    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def = $tbl_cdb_names['userinfo_def'];

    if ( 0 == (int) $nbline || 0 == (int) $id )
    {
        return FALSE;
    }
    $title   = trim($title);
    $comment = trim($comment);

    $sql = "UPDATE `" . $tbl_userinfo_def."` SET
            `title`      = '" . claro_sql_escape($title) . "',
            `comment` = '" . claro_sql_escape($comment) . "',
            `nbline`  = " . (int) $nbline . "
            WHERE id  = " . (int) $id;

    claro_sql_query($sql);

    return TRUE;
}

/**
 * remove a category from the category list
 *
 * @param  int $id - id of the category
 *            or "ALL" for all category
 * @param  boolean $force - FALSE (default) : prevents removal if users have
 *                            already fill this category
 *                          TRUE : bypass user content existence check
 * @param  int $nbline - lines number for the field the user will fill.
 * @return bollean  - TRUE if succeed, ELSE otherwise
 *
 */

function claro_user_info_remove_cat_def($id, $force = false, $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def     = $tbl_cdb_names['userinfo_def'];
    $tbl_userinfo_content = $tbl_cdb_names['userinfo_content'];

    if ( (0 == (int) $id || $id == "ALL") || ! is_bool($force))
    {
        return false;
    }

    if ( $id != "ALL")
    {
        $sqlCondition = " WHERE id = ". (int) $id;
    }

    if ($force == false)
    {
        $sql = "SELECT *
                FROM `" . $tbl_userinfo_content . "`
               ".$sqlCondition;
        $result = claro_sql_query($sql);

        if ( mysql_num_rows($result) > 0)
        {
            return false;
        }
    }

    $sql = "DELETE FROM `" . $tbl_userinfo_def . "`
           " . $sqlCondition;
    return claro_sql_query($sql);
}

/**
 * Move a category in the category list
 *
 * @param  int $id - id of the category
 * @param  direction "up" or "down" :
 *                     "up"    decrease the rank of gived $id by switching rank with the just lower
 *                    "down"    increase the rank of gived $id by switching rank with the just upper
 *
 * @return - boolean true if succeed, else bolean false
 */

function claro_user_info_move_cat_rank($id, $direction, $course_id=NULL)
{

    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def     = $tbl_cdb_names['userinfo_def'];

    if ( 0 == (int) $id || ! ($direction == "up" || $direction == "down") )
    {
        return false;
    }

    $sql = "SELECT rank
            FROM `" . $tbl_userinfo_def . "`
            WHERE id = ". (int) $id;
    $result = claro_sql_query($sql);

    if (mysql_num_rows($result) < 1)
    {
        return false;
    }

    $cat = mysql_fetch_array($result);
    $rank = (int) $cat['rank'];
    return claro_user_info_move_cat_rank_by_rank($rank, $direction);
}

/**
 * move a category in the category list
 *
 * @param  int $rank - actual rank of the category
 * @param  direction "up" or "down" :
 *                    "up"    decrease the rank of gived $rank by switching rank with the just lower
 *                    "down"    increase the rank of gived $rank by switching rank with the just upper
 *
 * @return - boolean true if succeed, else bolean false
 */

function claro_user_info_move_cat_rank_by_rank($rank, $direction, $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def     = $tbl_cdb_names['userinfo_def'];

    if ( 0 == (int) $rank || ! ($direction == "up" || $direction == "down") )
    {
        return false;
    }

    if ($direction == 'down') // thus increase rank ...
    {
        $sort = 'ASC';
        $compOp = '>=';
    }
    elseif ($direction == 'up') // thus decrease rank ...
    {
        $sort = 'DESC';
        $compOp = '<=';
    }

    // this request find the 2 line to be switched (on rank value)
    $sql = "SELECT id, rank
            FROM `" . $tbl_userinfo_def . "`
            WHERE rank " . $compOp." " . $rank . "
            ORDER BY rank " . $sort . " LIMIT 2";

    $result = claro_sql_query($sql);

    if (mysql_num_rows($result) < 2)
    {
        return false;
    }

    $thisCat = mysql_fetch_array($result);
    $nextCat = mysql_fetch_array($result);

    $sql1 = "UPDATE `" . $tbl_userinfo_def . "`
             SET rank =" . (int) $nextCat['rank'] . "
             WHERE id = " . (int) $thisCat['id'];
    $sql2 = "UPDATE `" . $tbl_userinfo_def . "`
             SET rank = " . (int) $thisCat['rank'] . "
             WHERE id = " . (int) $nextCat['id'];

    claro_sql_query($sql1);
    claro_sql_query($sql2);

    return true;
}

/*----------------------------------------
CATEGORIES CONTENT TREATMENT
--------------------------------------*/


/**
 * fill a bloc for information category
 *
 * @param  integer $def_id
 * @param  integer $user_id,
 * @param  sting  $user_ip,
 * @param  string $content
 * @return boolean true if succeed, else bolean false
 */

function claro_user_info_fill_new_cat_content($def_id, $user_id, $content="", $user_ip="", $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_content = $tbl_cdb_names['userinfo_content'];

    if (empty($user_ip))
    {
        global $REMOTE_ADDR;
        $user_ip = $REMOTE_ADDR;
    }

    $content = trim($content);


    if ( 0 == (int) $def_id || 0 == (int) $user_id || $content == "")
    {
        // Here we should introduce an error handling system...

        return false;
    }

    // Do not create if already exist

    $sql = "SELECT id FROM `" . $tbl_userinfo_content . "`
            WHERE    `def_id`    = " . (int) $def_id . "
            AND      `user_id`   = " . (int) $user_id;

    $result = claro_sql_query($sql);

    if (mysql_num_rows($result) > 0)
    {
        return false;
    }

    $sql = "INSERT INTO `" . $tbl_userinfo_content . "` SET
            `content`    = '" . claro_sql_escape($content) . "',
            `def_id`     = " . (int) $def_id . ",
            `user_id`    = " . (int) $user_id . ",
            `ed_ip`      = '" . $user_ip . "',
            `ed_date`    = now()";

    claro_sql_query($sql);

    return true;
}

/**
 * edit a bloc for information category
 *
 * @param  integer $def_id,
 * @param  integer $user_id,
 * @param  string  $user_ip, DEFAULT $REMOTE_ADDR
 * @param  string  $content ; if empty call delete the bloc
 * @return boolean true if succeed, else bolean false
 */

function claro_user_info_edit_cat_content($def_id, $user_id, $content ="", $user_ip="", $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_content = $tbl_cdb_names['userinfo_content'];

    if (empty($user_ip))
    {
        global $REMOTE_ADDR;
        $user_ip = $REMOTE_ADDR;
    }

    if (0 == (int) $user_id || 0 == (int) $def_id)
    {
        return claro_failure::set_failure('id_nul');
    }

    $content = trim($content);

    if ( '' == trim($content) )
    {
        return claro_user_info_cleanout_cat_content($user_id, $def_id);
    }

    $sql= "UPDATE `" . $tbl_userinfo_content . "` SET
            `content`    = '" . claro_sql_escape($content) . "',
            `ed_ip`        = '" . $user_ip . "',
            `ed_date`    = now()
            WHERE def_id = " . (int) $def_id . "
              AND user_id = " . (int) $user_id;

    claro_sql_query($sql);

    return true;
}

/**
 * clean the content of a bloc for information category
 *
 * @param  integer $def_id
 * @param  integer $user_id
 * @return boolean true if succeed, else bolean false
 */

function claro_user_info_cleanout_cat_content($user_id, $def_id, $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_content = $tbl_cdb_names['userinfo_content'];

    if (0 == (int) $user_id || 0 == (int) $def_id)
    {
        return false;
    }

    $sql = "DELETE FROM `" . $tbl_userinfo_content . "`
            WHERE user_id = " . (int) $user_id ."
              AND def_id  = " . (int) $def_id ;

    claro_sql_query($sql);

    return true;
}


/*----------------------------------------
SHOW USER INFORMATION TREATMENT
--------------------------------------*/

/**
 * get the user info from the user id
 *
 * @param  int $user_id user id as stored in the claroline main db
 * @return  array containg user info sort by categories rank
 *          each rank contains 'title', 'comment', 'content', 'cat_id'
 *
 *
 */

function claro_user_info_get_course_user_info($user_id, $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def     = $tbl_cdb_names['userinfo_def'];
    $tbl_userinfo_content = $tbl_cdb_names['userinfo_content'];

    $sql = "SELECT    cat.id catId,    cat.title,
                    cat.comment ,    content.content
            FROM      `" . $tbl_userinfo_def . "` cat
            LEFT JOIN `" . $tbl_userinfo_content . "` content
            ON cat.id = content.def_id
               AND content.user_id = " . (int) $user_id . "
            ORDER BY cat.rank, content.id";

    $userInfos = claro_sql_query_fetch_all_rows($sql);
    
    if( ! empty($userInfos) )  return $userInfos;
    else                       return false;
}

/**
 * get the user content of a categories plus the categories definition
 * @param  integer $userId id of the user
 * @param  integer $catId  id of the categories
 *
 * @return array containing 'catId', 'title', 'comment',
 *           'nbline', 'contentId' and 'content'
 *
 */

function claro_user_info_get_cat_content($userId, $catId, $course_id = NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def     = $tbl_cdb_names['userinfo_def'];
    $tbl_userinfo_content = $tbl_cdb_names['userinfo_content'];

    $sql = "SELECT cat.id          AS catId,
                   cat.title       AS title,
                   cat.comment     AS comment,
                   cat.nbline      AS nbline,
                   content.id      AS contentId,
                   content.content AS content
            FROM      `" . $tbl_userinfo_def . "`     AS cat
            LEFT JOIN `" . $tbl_userinfo_content . "` AS content
            ON cat.id = content.def_id
            AND content.user_id = " . (int) $userId . "
            WHERE cat.id = " . (int) $catId ;

    $catContent = claro_sql_query_get_single_row($sql);
    
    return $catContent;
}

/**
 * get the definition of a category
 *
 * @param  int $catId - id of the categories
 * @return array containing 'id', 'title', 'comment', and 'nbline',
 */
function claro_user_info_get_cat_def($catId, $course_id=NULL)
{
    $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
    $tbl_userinfo_def     = $tbl_cdb_names['userinfo_def'];

    $sql = "SELECT id, title, comment, nbline, rank
            FROM `" . $tbl_userinfo_def . "`
            WHERE id = " . (int) $catId;

    $catDef = claro_sql_query_get_single_row($sql);
    
    return $catDef;
}


/**
 * Get list of all  user properties for this course
 *
 * @param string $course_id coude code of course
 * @return array containing a list of arrays.
 *           And each of these arrays contains
 *           'catId', 'title', 'comment', and 'nbline',
 *
 */
function claro_user_info_claro_user_info_get_cat_def_list($course_id=NULL)
{
    $tbl = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));

    $sql = "SELECT id catId, " . "\n"
    .      "          title," . "\n"
    .      "          comment ," . "\n"
    .      "          nbline" . "\n"
    .      "FROM  `" . $tbl['userinfo_def'] . "`" . "\n"
    .      "ORDER BY rank"
    ;

    $cat_def_list = claro_sql_query_fetch_all_rows($sql);
    
    if( ! empty($cat_def_list) )  return $cat_def_list;
    else                          return false;
}

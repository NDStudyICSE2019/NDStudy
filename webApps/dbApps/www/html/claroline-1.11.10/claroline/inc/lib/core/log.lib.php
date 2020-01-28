<?php // $Id: log.lib.php 14183 2012-06-13 11:31:59Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Logging class.
 *
 * @version     Claroline 1.11 $Revision: 14183 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 */

class Logger
{
    private $tbl_log;
    
    public function __construct()
    {
        $tbl_mdb_names = claro_sql_get_main_tbl();
        $this->tbl_log  = $tbl_mdb_names['log'];
    }
    
    /**
     * Add a message to the log. The message will be associated with the current
     * course_code, user_id, tool_id, date and IP address of the client
     * @param string $type
     * @param string $data
     * @return boolean 
     */
    public function log( $type, $data )
    {
        $cid        = claro_get_current_course_id();
        $tid        = claro_get_current_tool_id();
        $uid        = claro_get_current_user_id();
        $date       = claro_date("Y-m-d H:i:s");

        $ip         = !empty( $_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        $data = serialize( $data );

        $sql = "INSERT INTO `" . $this->tbl_log . "`
                SET `course_code` = " . ( is_null($cid) ? "NULL" : "'" . claro_sql_escape($cid) . "'" ) . ",
                    `tool_id` = ". ( is_null($tid) ? "NULL" : "'" . claro_sql_escape($tid) . "'" ) . ",
                    `user_id` = ". ( is_null($uid) ? "NULL" : "'" . claro_sql_escape($uid) . "'" ) . ",
                    `ip` = ". ( is_null($ip) ? "NULL" : "'" . claro_sql_escape($ip) . "'" ) . ",
                    `date` = '" . $date . "',
                    `type` = '" . claro_sql_escape($type) . "',
                    `data` = '" . claro_sql_escape($data) . "'";

        return claro_sql_query($sql);
    }
}
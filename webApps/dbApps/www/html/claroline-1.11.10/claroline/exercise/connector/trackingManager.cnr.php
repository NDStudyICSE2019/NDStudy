<?php // $Id: trackingManager.cnr.php 14305 2012-10-30 13:02:23Z zefredz $

/**
 * CLAROLINE
 *
 * @version     1.12 $Revision: 14305 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLQWZ
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <pir@cerdecam.be>
 */

/**
 * Delete
 */
class CLQWZ_TrackingManager extends TrackingManager
{
    private $tbl_qwz_tracking;
    private $tbl_qwz_tracking_questions;
    private $tbl_qwz_tracking_answers;
    
    public function __construct($courseId)
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise', 'qwz_tracking', 'qwz_tracking_questions', 'qwz_tracking_answers' ), $courseId );
        $this->tbl_qwz_tracking = $tbl_cdb_names['qwz_tracking'];
        $this->tbl_qwz_tracking_questions = $tbl_cdb_names['qwz_tracking_questions'];
        $this->tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];
    }
    
    public function deleteAll()
    {
        $sql1 = "TRUNCATE TABLE `".$this->tbl_qwz_tracking."`";
        $sql2 = "TRUNCATE TABLE `".$this->tbl_qwz_tracking_questions."`";
        $sql3 = "TRUNCATE TABLE `".$this->tbl_qwz_tracking_answers."`";
        
        if( claro_sql_query($sql1) && claro_sql_query($sql2) && claro_sql_query($sql3) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function deleteBefore( $date )
    {
        // get data to delete from exercise tracking table
        $sql = "SELECT `id` FROM `" . $this->tbl_qwz_tracking . "` WHERE  UNIX_TIMESTAMP(date)  < " . (int) $date;
        $exeList = claro_sql_query_fetch_all_cols($sql);
        
        if( is_array($exeList['id']) && !empty($exeList['id']) )
        {
            // delete
            $sql = "DELETE FROM `" . $this->tbl_qwz_tracking . "` WHERE  UNIX_TIMESTAMP(date)  < " . (int) $date;
            claro_sql_query($sql);
            
            // get data to delete
            $sql = "SELECT `id` FROM `".$this->tbl_qwz_tracking_questions."` WHERE `exercise_track_id` IN ('" . implode("', '",$exeList['id']) . "')";
            $detailList = claro_sql_query_fetch_all_cols($sql);
            
            if( is_array($detailList['id']) && !empty($detailList['id']) )
            {
                $sql = "DELETE FROM `" . $this->tbl_qwz_tracking_questions . "` WHERE `exercise_track_id` IN ('" . implode("', '",$exeList['id']) . "')";
                claro_sql_query($sql);
                
                $sql = "DELETE FROM `" . $this->tbl_qwz_tracking_answers . "` WHERE details_id  IN ('" . implode("', '",$detailList['id']) . "')";
                claro_sql_query($sql);
            }
        }
        
        return true;
    }
    
    public function deleteForUser( $userId, $date = null )
    {
        if( !is_null($date) && !empty($date) )
        {
            $dateCondition = " AND `date` < FROM_UNIXTIME('" . (int) $date . "') ";
        }

        // get data to delete from exercise tracking table
        $sql = "SELECT `id`
                FROM `" . $this->tbl_qwz_tracking . "`
                WHERE  `user_id` = ".(int) $userId
                . $dateCondition;
                
        $exeList = claro_sql_query_fetch_all_cols($sql);
        
        if( is_array($exeList['id']) && !empty($exeList['id']) )
        {
            // delete
            $sql = "DELETE FROM `" . $this->tbl_qwz_tracking . "`
                    WHERE  `user_id` = ".(int) $userId
                    . $dateCondition;
            claro_sql_query($sql);
            
            // get data to delete
            $sql = "SELECT `id` FROM `".$this->tbl_qwz_tracking_questions."` WHERE `exercise_track_id` IN ('" . implode("', '",$exeList['id']) . "')";
            $detailList = claro_sql_query_fetch_all_cols($sql);
            
            if( is_array($detailList['id']) && !empty($detailList['id']) )
            {
                $sql = "DELETE FROM `" . $this->tbl_qwz_tracking_questions . "` WHERE `exercise_track_id` IN ('" . implode("', '",$exeList['id']) . "')";
                claro_sql_query($sql);
                
                $sql = "DELETE FROM `" . $this->tbl_qwz_tracking_answers . "` WHERE details_id  IN ('" . implode("', '",$detailList['id']) . "')";
                claro_sql_query($sql);
            }
        }
        
        return true;
    }
}

TrackingManagerRegistry::register('CLQWZ_TrackingManager');

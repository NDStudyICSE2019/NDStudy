<?php // $Id: track_exercise_reset.php 14305 2012-10-30 13:02:23Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14305 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 */

try
{
    $tlabelReq = 'CLQWZ';

    require_once dirname ( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';
    
    if ( ! claro_is_in_a_course() || ! claro_is_user_authenticated() ) claro_disp_auth_form(true);
    
    if ( !claro_is_course_manager () )
    {
        claro_redirect(Url::Contextualize( "../exercise/exercise.php" ));
    }
    
    FromKernel::uses (
        'utils/input.lib', 
        'utils/validator.lib' 
    );
    
    $userInput = Claro_UserInput::getInstance ();

    $cmd = $userInput->get ( 'cmd' );
    
    $trackingReset = new CLQWZ_TrackingReset(  claro_get_current_course_id () );
    $dialogBox = new DialogBox;
    
    if ( 'resetAttemptForUser' == $cmd )
    {
        $userId = $userInput->getMandatory ( 'userId' );
        $trackId = $userInput->getMandatory ( 'trackId' );
        
        $trackingReset->resetAttemptForUser( $userId, $trackId );
        
        $dialogBox->success('<p>'.get_lang('User attempt deleted from tracking').'</p>');
    }
    elseif ( 'resetAllAttemptsForUser' == $cmd )
    {
        $userId = $userInput->getMandatory ( 'userId' );
        $exId = $userInput->getMandatory ( 'exId' );
        
        $trackingReset->resetAllAttemptsForUser( $userId, $exId );
        
        $dialogBox->success('<p>'.get_lang('All user attempts deleted from tracking').'</p>');
    }
    elseif ( 'resetResultsForAllUsers' == $cmd )
    {
        $exId = $userInput->getMandatory ( 'exId' );
        
        $trackingReset->resetResultsForAllUsers( $exId );
        
        $dialogBox->success('<p>'.get_lang('All results of exercise deleted from tracking').'</p>');
    }
    
    $toolTitle = new ToolTitle( get_lang('Reset exercise tracking') );
    
    Claroline::getDisplay()->body->appendContent( $toolTitle->render() );
    
    Claroline::getDisplay()->body->appendContent( $dialogBox->render() );
    
    echo Claroline::getDisplay ()->render ();
}
catch ( Exception $e )
{
    Claroline::getDisplay ()->body->appendContent ( $e->getMessage () );
    
    if ( claro_debug_mode() )
    {
        Claroline::getDisplay ()->body->appendContent ( $e->getTraceAsString () );
    }
    
    echo Claroline::getDisplay ()->render ();
}


class CLQWZ_TrackingReset
{
    private $tbl_qwz_tracking;
    private $tbl_qwz_tracking_questions;
    private $tbl_qwz_tracking_answers;
    private $database;
    
    public function __construct( $courseId, $database = null )
    {
        $this->database = $database ? $database :  Claroline::getDatabase();
        
        $tbl_cdb_names = get_module_course_tbl( 
            array( 
                'qwz_exercise', 'qwz_tracking', 
                'qwz_tracking_questions', 'qwz_tracking_answers' 
            ), $courseId 
        );
        $this->tbl_qwz_tracking = $tbl_cdb_names['qwz_tracking'];
        $this->tbl_qwz_tracking_questions = $tbl_cdb_names['qwz_tracking_questions'];
        $this->tbl_qwz_tracking_answers = $tbl_cdb_names['qwz_tracking_answers'];
    }
    
    public function resetAllAttemptsForUser( $userId, $exId )
    {
        $trackIdList = array();
        
        $trackIdIt = iterator_to_array( $this->database->query("SELECT `id`
                FROM `" . $this->tbl_qwz_tracking . "`
                WHERE  `user_id` = ".(int) $userId . " AND exo_id = ".(int) $exId ) );
        
        foreach ( $trackIdIt as $trackId )
        {
            $trackIdList[] = $trackId['id'];
        }
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking . "`
            WHERE  `id` IN (" . implode(",", $trackIdList ) . ")" );
        
        $detailIdList = array();
        
        $detailIdIt = $this->database->query( "
                SELECT 
                    `id` 
                FROM 
                    `".$this->tbl_qwz_tracking_questions."` 
                WHERE 
                    `exercise_track_id` IN (" . implode(",", $trackIdList ) . ")" );
        
        foreach ( $detailIdIt as $detailId )
        {
            $detailIdList[] = $detailId['id'];
        }
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking_answers . "` 
            WHERE details_id  IN (" . implode(",", $detailIdList ) . ")" );
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking_questions . "`
            WHERE  `exercise_track_id` IN (" . implode(",", $trackIdList ) . ")" );
        
        return true;
        
    }
    
    public function resetAttemptForUser( $userId, $trackId )
    {
        if ( $this->database->query("SELECT `id`
                FROM `" . $this->tbl_qwz_tracking . "`
                WHERE  `user_id` = ".(int) $userId . " AND id = ".(int)$trackId )->numRows() == 0 )
        {
            throw new Exception("Tracking entry does not seem to belong to given user");
        }
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking . "`
            WHERE  `id` = ".(int) $trackId );
        
        $detailIdList = array();
        
        $detailIdIt = $this->database->query( "
                SELECT 
                    `id` 
                FROM 
                    `".$this->tbl_qwz_tracking_questions."` 
                WHERE 
                    `exercise_track_id` = " . (int) $trackId );
        
        foreach ( $detailIdIt as $detailId )
        {
            $detailIdList[] = $detailId['id'];
        }
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking_answers . "` 
            WHERE details_id  IN (" . implode(",", $detailIdList ) . ")" );
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking_questions . "`
            WHERE  `exercise_track_id` = ".(int) $trackId );
        
        return true;
    }
    
    public function resetResultsForAllUsers( $exId )
    {
        $trackIdList = array();
        
        $trackIdIt = iterator_to_array( $this->database->query("SELECT `id`
                FROM `" . $this->tbl_qwz_tracking . "`
                WHERE  exo_id = ".(int) $exId ) );
        
        foreach ( $trackIdIt as $trackId )
        {
            $trackIdList[] = $trackId['id'];
        }
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking . "`
            WHERE  `id` IN (" . implode(",", $trackIdList ) . ")" );
        
        $detailIdList = array();
        
        $detailIdIt = $this->database->query( "
                SELECT 
                    `id` 
                FROM 
                    `".$this->tbl_qwz_tracking_questions."` 
                WHERE 
                    `exercise_track_id` IN (" . implode(",", $trackIdList ) . ")" );
        
        foreach ( $detailIdIt as $detailId )
        {
            $detailIdList[] = $detailId['id'];
        }
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking_answers . "` 
            WHERE details_id  IN (" . implode(",", $detailIdList ) . ")" );
        
        $this->database->exec("
            DELETE FROM `" . $this->tbl_qwz_tracking_questions . "`
            WHERE  `exercise_track_id` IN (" . implode(",", $trackIdList ) . ")" );
        
        return true;
    }
}
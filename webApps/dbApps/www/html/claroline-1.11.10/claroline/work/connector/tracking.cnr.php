<?php // $Id:tracking.cnr.php 10410 2008-06-10 14:16:26Z fragile_be $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision:10410 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLWRK
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */

/*
 * 
 */
class CLWRK_UserTrackingRenderer extends UserTrackingRenderer
{   
    private $tbl_wrk_assignment;
    private $tbl_wrk_submission;
    private $tbl_group_team;
    
    public function __construct($courseId, $userId)
    {
        $this->courseId = $courseId;
        $this->userId = (int) $userId;

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_wrk_assignment = $tbl_cdb_names['wrk_assignment'];
        $this->tbl_wrk_submission = $tbl_cdb_names['wrk_submission'];
        $this->tbl_group_team = $tbl_cdb_names['group_team'];
    }
    
    protected function renderHeader()
    {
        return claro_get_tool_name('CLWRK');
    }
    
    protected function renderContent()
    {
        $submittedWorks = $this->getUserWorks();
        
        $html = '';
        
        $html .= '<table class="claroTable emphaseLine" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">' . "\n"
        .    '<thead>' . "\n"
        .    '<tr class="headerX">' . "\n"
        .    '<th>' . get_lang('Assignment').'</th>' . "\n"
        .    '<th>' . get_lang('Work title').'</th>' . "\n"
        .    '<th>' . get_lang('Author(s)').'</th>' . "\n"
        .    '<th>' . get_lang('Score').'</th>' . "\n"
        .    '<th>' . get_lang('Date').'</th>' . "\n"
        .    '</tr>' . "\n"
        .    '</thead>' . "\n"
        ;
    
        if( !empty($submittedWorks) && is_array($submittedWorks) )
        {
            $html .= '<tbody>' . "\n";
    
            $prevAssignmentTitle = "";
            foreach($submittedWorks as $work)
            {
                if( $work['a_title'] == $prevAssignmentTitle )
                {
                    $assignmentTitle = "&nbsp;";
                }
                else
                {
                    $assignmentTitle = $work['a_title'];
                    $prevAssignmentTitle = $work['a_title'];
                }
    
                if( $work['score'] != 0 )
                {
                    $displayedScore = $work['score']." %";
                }
                else
                {
                    $displayedScore  = get_lang('No score');
                }
    
                if( isset($work['g_name']) )
                {
                    $authors = $work['authors']."( ".$work['g_name']." )";
                }
                else
                {
                    $authors = $work['authors'];
                }
    
                $timestamp = strtotime($work['last_edit_date']);
                $beautifulDate = claro_html_localised_date(get_locale('dateTimeFormatLong'),$timestamp);
    
    
                $html .= '<tr>' . "\n"
                .    '<td>'.$assignmentTitle.'</td>' . "\n"
                .    '<td>'.$work['s_title'].'</td>' . "\n"
                .    '<td>'.$authors.'</td>' . "\n"
                .    '<td>'.$displayedScore.'</td>' . "\n"
                .    '<td>'.$beautifulDate.'</td>' . "\n"
                .    '</tr>' . "\n";
            }
            $html .= '</tbody>' . "\n";
    
        }
        else
        {
            $html .= '<tbody><tr>' . "\n"
            .    '<td colspan="5" align="center">' . get_lang('No result').'</td>' . "\n"
            .    '</tr></tbody>' . "\n";
        }
        $html .= '</table>' . "\n";
        
        return $html;
    }
    
    protected function renderFooter()
    {
        return get_lang('Works uploaded by the student in the name of \'Authors\'');
    }
    
    private function getUserWorks()
    {
   
        $sql = "SELECT `A`.`title` AS `a_title`,
                   `A`.`assignment_type`,
                   `S`.`id`, `S`.`title` AS `s_title`,
                   `S`.`group_id`, `S`.`last_edit_date`, `S`.`authors`,
                   `S`.`score`,
                   `S`.`parent_id`,
                   `G`.`name` AS `g_name`
              FROM `" . $this->tbl_wrk_assignment . "` AS `A` ,
                   `" . $this->tbl_wrk_submission . "` AS `S`
              LEFT JOIN `" . $this->tbl_group_team . "` AS `G`
                     ON `G`.`id` = `S`.`group_id`
             WHERE `A`.`id` = `S`.`assignment_id`
               AND ( `S`.`user_id` = ". (int) $this->userId."
                      OR ( `S`.`parent_id` IS NOT NULL AND `S`.`parent_id` ) )
                    AND `A`.`visibility` = 'VISIBLE'
             ORDER BY `A`.`title` ASC, `S`.`last_edit_date` ASC";

        $results = claro_sql_query_fetch_all($sql);
    
        $submissionList = array();
    
        // store submission details in list
        foreach( $results as $submission )
        {
            if( empty($submission['parent_id']) )
            {
                // is a submission
                $submissionList[$submission['id']] = $submission;
            }
        }
    
        // get scores
        foreach( $results as $submission )
        {
            if( !empty($submission['parent_id']) && isset($submissionList[$submission['parent_id']]) && is_array($submissionList[$submission['parent_id']]) )
            {
                // is a feedback
                $submissionList[$submission['parent_id']]['score'] = $submission['score'];
            }
        }

        return $submissionList;
    }
    
}

TrackingRendererRegistry::registerUser('CLWRK_UserTrackingRenderer');

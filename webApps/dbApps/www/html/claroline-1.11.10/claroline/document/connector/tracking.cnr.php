<?php // $Id: tracking.cnr.php 14314 2012-11-07 09:09:19Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 14314 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLDOC
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */

class CLDOC_CourseTrackingRenderer extends CourseTrackingRenderer
{
    private $tbl_course_tracking_event;
    
    public function __construct($courseId)
    {
        $this->courseId = $courseId;

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];
    }
    protected function renderHeader()
    {
        return claro_get_tool_name('CLDOC');
    }
    
    protected function renderContent()
    {
        $html = '';
        
        $sql = "SELECT `data`,
                        COUNT(DISTINCT `user_id`) AS `nbr_distinct_user_downloads`,
                        COUNT(`data`) AS `nbr_total_downloads`
                    FROM `".$this->tbl_course_tracking_event."`
                    WHERE `type` = 'download'
                      AND `group_id` IS NULL
                    GROUP BY `data`
                    ORDER BY substring_index(data,'\"',-2)";

        $results = claro_sql_query_fetch_all($sql);

        $html .= '<table class="claroTable" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">'."\n"
            .'<thead>'."\n"
            .'<tr>'."\n"
            .'<th>&nbsp;'.get_lang('Document').'&nbsp;</th>'."\n"
            .'<th>&nbsp;'.get_lang('Users Downloads').'&nbsp;</th>'."\n"
            .'<th>&nbsp;'.get_lang('Total Downloads').'&nbsp;</th>'."\n"
            .'</tr>'."\n"
            .'</thead>'."\n"
            .'<tbody>'."\n"
            ;
        if( !empty($results) && is_array($results) )
        {
            foreach( $results as $result )
            {
                $data = unserialize($result['data']);
                if( !empty( $data['url']) )
                {
                    $path = $data['url'];
                    $html .= '<tr>'."\n"
                    .'<td>'.claro_htmlspecialchars($path).'</td>'."\n"
                    .'<td align="right"><a href="user_access_details.php?cmd=doc&amp;path='.urlencode($path).'">'.claro_htmlspecialchars($result['nbr_distinct_user_downloads']).'</a></td>'."\n"
                    .'<td align="right">'.$result['nbr_total_downloads'].'</td>'."\n"
                    .'</tr>'."\n\n"
                    ;
                }
                else
                {
                    // no data to display ... so drop this record
                }
            }

        }
        else
        {
            $html .=  '<tr>'."\n"
                .'<td colspan="3"><div align="center">'.get_lang('No result').'</div></td>'."\n"
                .'</tr>'."\n"
                ;
        }
        $html .= '</tbody>'
            .'</table>'."\n"
            ;
        return $html;
    }
    
    protected function renderFooter()
    {
        return '';
    }
}

TrackingRendererRegistry::registerCourse('CLDOC_CourseTrackingRenderer');


/*
 *
 */
class CLDOC_UserTrackingRenderer extends UserTrackingRenderer
{
    private $tbl_course_tracking_event;
    
    public function __construct($courseId, $userId)
    {
        $this->courseId = $courseId;
        $this->userId = (int) $userId;

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_course_tracking_event = $tbl_cdb_names['tracking_event'];
        
    }
    
    protected function renderHeader()
    {
        return claro_get_tool_name('CLDOC');
    }
    
    protected function renderContent()
    {
        $documentDownloads = $this->prepareContent();
        
        $html = '';
        
        $html .= '<table class="claroTable emphaseLine" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">' . "\n"
        .    '<thead>' . "\n"
        .    '<tr>' . "\n"
        .    '<th>' . get_lang('Document').'</th>' . "\n"
        .    '<th>' . get_lang('Last download').'</th>' . "\n"
        .    '<th>' . get_lang('Downloads').'</th>' . "\n"
        .    '</tr>' . "\n"
        .    '</thead>' . "\n"
        ;
    
        if( !empty($documentDownloads) && is_array($documentDownloads) )
        {
            $html .= '<tbody>' . "\n";
            foreach( $documentDownloads as $download )
            {
                $data = unserialize($download['data']);
                if( !empty( $data['url']) )
                {
                    $path = $data['url']; // TODO make document path shorter if needed
                    
                    $html .= '<tr>' . "\n"
                    .    '<td>'.$path.'</td>' . "\n"
                    .    '<td>'.claro_html_localised_date( get_locale('dateFormatLong'), $download['unix_date']).'</td>' . "\n"
                    .    '<td>'.$download['downloads'].'</td>' . "\n"
                    .    '</tr>' . "\n";
                }
            }
            $html .= '</tbody>' . "\n";
        }
        else
        {
            $html .= '<tbody>' . "\n"
            .    '<tr>' . "\n"
            .    '<td colspan="3" align="center">' . get_lang('No result').'</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</tbody>' . "\n";
        }
        $html .= '</table>' . "\n\n";
        
        return $html;
    }
    
    protected function renderFooter()
    {
        return '';
    }
    
    private function prepareContent()
    {
        $sql = "SELECT `data`,
                    UNIX_TIMESTAMP(`date`) AS `unix_date`,
                    COUNT(`user_id`) AS `downloads`
                FROM `" . $this->tbl_course_tracking_event . "`
                WHERE `user_id` = '". (int) $this->userId."'
                  AND `type` = 'download'
                GROUP BY `data`
                ORDER BY `data` ASC,`date` ASC";
    
        $results = claro_sql_query_fetch_all($sql);
    
        return $results;
    }
    
}

TrackingRendererRegistry::registerUser('CLDOC_UserTrackingRenderer');

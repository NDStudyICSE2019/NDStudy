<?php // $Id$
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 415 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLFRM
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 */

class CLFRM_CourseTrackingRenderer extends CourseTrackingRenderer
{   
    private $tbl_bb_topics;
    private $tbl_bb_posts;
    
    public function __construct($courseId)
    {
        $this->courseId = $courseId;
        
        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_bb_topics = $tbl_cdb_names['bb_topics'];
        $this->tbl_bb_posts  = $tbl_cdb_names['bb_posts'];        
    }
    protected function renderHeader()
    {
        return claro_get_tool_name('CLFRM');
    }
    
    protected function renderContent()
    {
        $html = '';
        
        // total number of posts
        $sql = "SELECT count(`post_id`)
                        FROM `".$this->tbl_bb_posts."`";
        $totalPosts = claro_sql_query_get_single_value($sql);

        // total number of threads
        $sql = "SELECT count(`topic_title`)
                        FROM `".$this->tbl_bb_topics."`";
        $totalTopics = claro_sql_query_get_single_value($sql);

        // display total of posts and threads
        $html .= '<ul>'."\n"
        .   '<li>'.get_lang('Messages posted').' : '.$totalPosts.'</li>'."\n"
        .   '<li>'.get_lang('Topics started').' : '.$totalTopics.'</li>'."\n"
        .   '</ul>' . "\n";
        
        // top 10 topics more active (more responses)
        $sql = "SELECT `topic_id`, `topic_title`, `topic_replies`
                    FROM `".$this->tbl_bb_topics."`
                    ORDER BY `topic_replies` DESC
                    LIMIT 10
                    ";
        $results = claro_sql_query_fetch_all($sql);
        $html .= '<table class="claroTable" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">'."\n"
        .   '<thead><tr class="headerX">'."\n"
        .   '<th>'.get_lang('More active topics').'</th>'."\n"
        .   '<th>'.get_lang('Replies').'</th>'."\n"
        .   '</tr></thead>'."\n";
        
        if( !empty($results) && is_array($results) )
        {
            $html .= '<tbody>'."\n";
            foreach( $results as $result )
            {
                $html .= '<tr>'."\n"
                    .'<td><a href="../phpbb/viewtopic.php?topic='.$result['topic_id'].'">'.$result['topic_title'].'</a></td>'."\n"
                    .'<td align="right">'.$result['topic_replies'].'</td>'."\n"
                    .'</tr>'."\n";
            }
            $html .= '</tbody>'."\n";

        }
        else
        {
            $html .= '<tfoot>'."\n".'<tr>'."\n"
            .   '<td align="center">'.get_lang('No result').'</td>'."\n"
            .   '</tr>'."\n".'</tfoot>'."\n";
        }
        $html .= '</table>'."\n";

        // top 10 topics more seen
        $sql = "SELECT `topic_id`, `topic_title`, `topic_views`
                    FROM `".$this->tbl_bb_topics."`
                    ORDER BY `topic_views` DESC
                    LIMIT 10
                    ";
        $results = claro_sql_query_fetch_all($sql);

        $html .= '<table class="claroTable" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">'."\n"
        .   '<tr class="headerX">'."\n"
        .   '<th>'.get_lang('More read topics').'</th>'."\n"
        .   '<th>'.get_lang('Seen').'</th>'."\n"
        .   '</tr>'."\n";
        
        if( !empty($results) && is_array($results) )
        {
            $html .= '<tbody>'."\n";
            foreach( $results as $result )
            {
                $html .= '<tr>'."\n"
                    .'<td><a href="../phpbb/viewtopic.php?topic='.$result['topic_id'].'">'.$result['topic_title'].'</a></td>'."\n"
                    .'<td align="right">'.$result['topic_views'].'</td>'."\n"
                    .'</tr>'."\n";
            }
            $html .= '</tbody>'."\n";

        }
        else
        {
            $html .= '<tfoot>'."\n".'<tr>'."\n"
            .   '<td align="center">'.get_lang('No result').'</td>'."\n"
            .   '</tr>'."\n".'</tfoot>'."\n";
        }
        $html .= '</table>'."\n";

        // last 10 distinct messages posted
        $sql = "SELECT `bb_t`.`topic_id`, `bb_t`.`topic_title`, max(`bb_t`.`topic_time`) as `last_message`
                FROM `".$this->tbl_bb_posts."` as `bb_p`, `".$this->tbl_bb_topics."` as `bb_t`
                WHERE `bb_t`.`topic_id` = `bb_p`.`topic_id`
                GROUP BY `bb_t`.`topic_title`
                ORDER BY `bb_p`.`post_time` DESC
                LIMIT 10";

        $results = claro_sql_query_fetch_all($sql);

        $html .= '<table class="claroTable" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">'."\n"
        .   '<thead><tr class="headerX">'."\n"
        .   '<th>'.get_lang('Most recently active topics').'</th>'."\n"
        .   '<th>'.get_lang('Last message').'</th>'."\n"
        .   '</tr></thead>'."\n";
        
        if (is_array($results))
        {
            $html .= '<tbody>'."\n";
            foreach( $results as $result )
            {
                $html .= '<tr>'."\n"
                .    '<td>'
                .    '<a href="../phpbb/viewtopic.php?topic=' . $result['topic_id'].'">' . $result['topic_title'] . '</a>'
                .    '</td>' . "\n"
                .    '<td align="right">' . $result['last_message'] . '</td>' . "\n"
                .    '</tr>' . "\n"
                ;
            }
            $html .= '</tbody>'."\n";

        }
        else
        {
            $html .= '<tfoot>' . "\n"
            .    '<tr>' . "\n"
            .    '<td align="center">'
            .    get_lang('No result')
            .    '</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</tfoot>' . "\n"
            ;
        }
        $html .= '</table>'."\n";

        
            
        return $html;
    }
    
    protected function renderFooter()
    {
        return '';
    }
}

TrackingRendererRegistry::registerCourse('CLFRM_CourseTrackingRenderer');

/*
 * 
 */
class CLFRM_UserTrackingRenderer extends UserTrackingRenderer
{   
    private $tbl_course_tracking_event;
    
    public function __construct($courseId, $userId)
    {
        $this->courseId = $courseId;
        $this->userId = (int) $userId;

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseId));
        $this->tbl_bb_topics = $tbl_cdb_names['bb_topics'];
        $this->tbl_bb_posts  = $tbl_cdb_names['bb_posts']; 
        
    }
    
    protected function renderHeader()
    {
        return claro_get_tool_name('CLFRM');
    }
    
    protected function renderContent()
    {
        $lastUserPosts = $this->getUserLastTenPosts();
        
        $html = '';
        
        $html = '<table class="claroTable emphaseLine" cellpadding="2" cellspacing="1" border="0" align="center" style="width: 99%;">' . "\n"
        .    '<thead>' . "\n"
        .    '<tr class="headerX">' . "\n"
        .    '<th>' . get_lang('Topic').'</th>' . "\n"
        .    '<th>' . get_lang('Last message').'</th>' . "\n"
        .    '</tr>' . "\n"
        .    '</thead>' . "\n"
        ;
    
        if( !empty($lastUserPosts) && is_array($lastUserPosts) )
        {
            $html .= '<tbody>' . "\n";
            foreach( $lastUserPosts as $result )
            {
                $html .= '<tr>' . "\n"
                .    '<td><a href="../phpbb/viewtopic.php?topic='.$result['topic_id'].'">'.$result['topic_title'].'</a></td>' . "\n"
                .    '<td>'.$result['last_message'].'</td>' . "\n"
                .    '</tr>' . "\n";
            }
            $html .= '</tbody>' . "\n";
    
        }
        else
        {
            $html .= '<tbody>' . "\n"
            .    '<tr>' . "\n"
            .    '<td align="center" colspan="2">' . get_lang('No result').'</td>' . "\n"
            .    '</tr>' . "\n"
            .    '</tbody>' . "\n";
        }
        $html .= '</table>' . "\n";


        return $html;
    }
    
    protected function renderFooter()
    {
        return get_lang('Messages posted') . ' : ' . $this->getUserTotalForumPost() . '<br />' . "\n"
        .    get_lang('Topics started') . ' : ' . $this->getUserTotalForumTopics() . '<br />' . "\n"
        .    '<a href="' . claro_htmlspecialchars( Url::Contextualize( get_module_url('CLFRM') . '/viewsearch.php?searchUser='.$this->userId ) ) . '">'
        .    get_lang('View all user\'s posts')
        .     '</a>' . "\n"
        ;
    }
    
    private function getUserTotalForumPost()
    {
        $sql = "SELECT count(`post_id`)
                    FROM `" . $this->tbl_bb_posts . "`
                    WHERE `poster_id` = '". (int) $this->userId . "'";
    
        $value = claro_sql_query_get_single_value($sql);
    
        if( is_numeric($value) )    return $value;
        else                         return 0;
    }
    
    private function getUserTotalForumTopics()
    {
        $sql = "SELECT count(`topic_title`)
                    FROM `" . $this->tbl_bb_topics . "`
                    WHERE `topic_poster` = '". (int) $this->userId . "'";
    
        $value = claro_sql_query_get_single_value($sql);
    
        if( is_numeric($value) )    return $value;
        else                         return 0;
    }
    
    private function getUserLastTenPosts()
    {
 
        $sql = "SELECT `bb_t`.`topic_id`,
                        `bb_t`.`topic_title`,
                        max(`bb_t`.`topic_time`) AS `last_message`
                    FROM `" . $this->tbl_bb_posts . "`  AS `bb_p`
                       , `" . $this->tbl_bb_topics . "` AS `bb_t`
                    WHERE `bb_p`.`poster_id` = '". (int) $this->userId."'
                      AND `bb_t`.`topic_id` = `bb_p`.`topic_id`
                    GROUP BY `bb_t`.`topic_title`
                    ORDER BY `bb_p`.`post_time` DESC
                    LIMIT 10";
    
        $results = claro_sql_query_fetch_all($sql);
    
        return $results;
    }
    
}

TrackingRendererRegistry::registerUser('CLFRM_UserTrackingRenderer');

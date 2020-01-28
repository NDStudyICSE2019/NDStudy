<?php // $Id: export.class.php 13708 2011-10-19 10:46:34Z abourguignon $
/**
 * CLAROLINE
 *
 * Script export topic/forum in PDF
 *
 * @version 1.9 $Revision: 13708 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @copyright (C) 2001 The phpBB Group
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Dimitri Rambout <dim@claroline.net>
 *
 * @package CLFRM
 *
 */
 
class export
{
  var $output;
  var $topicId;
  
  var $pdf;
  
  public function __construct( $topicId, $output = 'file')
  {
    $acceptedOutput = array( 'screen', 'file');
    
    $this->setTopicId( (int) $topicId );
    
    if( !in_array( $output, $acceptedOutput ) )
    {
      $this->output = 'file';
    }
    else
    {
      $this->output = $output;
    }
  }
  
  protected function setTopicId( $topidId )
  {
    $this->topicId = (int) $topidId;
    
    return true;
  }
  
  protected function getTopicId()
  {
    return $this->topicId;
  }
  
  protected function loadTopic( $topicId )
  {
    $tbl_cdb_names = claro_sql_get_course_tbl();
    $tbl_posts            = $tbl_cdb_names['bb_posts'];
    $tbl_posts_text       = $tbl_cdb_names['bb_posts_text'];
    
    $sql = "SELECT  p.`post_id`,   p.`topic_id`,  p.`forum_id`,
                    p.`poster_id`, p.`post_time`, p.`poster_ip`,
                    p.`nom` lastname, p.`prenom` firstname,
                    pt.`post_text`

           FROM     `" . $tbl_posts . "`      p,
                    `" . $tbl_posts_text . "` pt

           WHERE    topic_id  = '" . (int) $topicId . "'
             AND    p.post_id = pt.`post_id`

           ORDER BY post_id";
    
    $postsList = claro_sql_query_fetch_all( $sql );
    
    return $postsList;
  }
}

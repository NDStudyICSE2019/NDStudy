<?php // $Id: toolintroductioniterator.class.php 13760 2011-10-28 09:26:29Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 13760 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTI
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.10
 */


require get_module_path('CLTI').'/lib/toolintroduction.class.php';

class ToolIntroductionIterator implements CountableIterator
{
    private     $courseCode;
    
    /**
     * @var Database_ResultSet
     */
    private     $toolIntroductions;
    
    protected   $n = 0;
    
    public function __construct($courseCode)
    {
        $this->courseCode = $courseCode;
        
        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->courseCode));
        $tblToolIntro = $tbl_cdb_names['tool_intro'];
        
        $sql = "SELECT id, tool_id, title, display_date,
                content, rank, visibility
                FROM `{$tblToolIntro}`
                ORDER BY rank ASC";
        
        $this->toolIntroductions = Claroline::getDatabase()->query($sql);
    }
    
    public function rewind()
    {
        $this->n = 0;
        $this->toolIntroductions->rewind();
    }
    
    public function next()
    {
        $this->n++;
        $this->toolIntroductions->next();
    }
    
    public function key()
    {
        return $this->toolIntroductions->key();
    }
    
    public function current()
    {
        $toolIntro = $this->toolIntroductions->current();
        
        $toolIntroObj = new ToolIntro(
                $toolIntro['id'],
                $this->courseCode,
                $toolIntro['tool_id'],
                $toolIntro['title'],
                $toolIntro['content'],
                $toolIntro['rank'],
                $toolIntro['display_date'],
                $toolIntro['visibility']
            );
        
        return $toolIntroObj;
    }
    
    public function valid()
    {
        return $this->toolIntroductions->valid();
    }
    
    public function count()
    {
        return count( $this->toolIntroductions );
    }
    
    public function hasNext()
    {
        return ( $this->n < $this->count() -1 );
    }
}

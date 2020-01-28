<?php // $Id: score.lib.php 14490 2013-07-03 13:02:00Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Assignments Score
 *
 * @version     Claroline 1.11 $Revision: 14490 $
 * @copyright   (c) 2001-2013, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.course
 * @since       Claroline 1.11
 */

class CLWRK_SubmissionScore
{
    private
        $title,
        $data;
    
    public function __construct ( $title, $data )
    {
        $this->title = $title;
        $this->data = $data;
    }
    
    public function __get ( $name )
    {
        if ( $name == 'title' )
        {
            return $this->title;
        }
        elseif ( isset( $this->data[$name] ) )
        {
            return $this->data[$name];
        }
        else
        {
            return null;
        }
    }
}

class CLWRK_AssignementScoreIterator extends RowToObjectIteratorIterator
{
    private $titleList;
    
    public function __construct ( CountableIterator $iterator, $titleList )
    {
        parent::__construct ( $iterator );
        $this->titleList = $titleList;
    }

    public function current ()
    {
        $data = $this->internalIterator->current();
        
        if ( isset($this->titleList[$data['submissionId']]) )
        {
            $score = new CLWRK_SubmissionScore( $this->titleList[$data['submissionId']], $data  );
        }
        else
        {
            $score = new CLWRK_SubmissionScore( '-', $data  );
        }
        
        return $score;
    }
}

class CLWRK_AssignementScoreList
{

    private
        $database, 
        $assignement, 
        $courseId;
    
    private
        $optAllUsers = false;

    public function __construct ( $assignement, $courseId = null, $database = null )
    {
        $this->database    = $database ? $database : Claroline::getDatabase ();
        $this->assignement = $assignement;
        $this->courseId    = $courseId ? $courseId : claro_get_current_course_id ();
        $this->tbl         = array_merge (
            get_module_main_tbl ( array ( 'rel_course_user', 'user' ) ), get_module_course_tbl ( array ( 'wrk_submission', 'group_team' ), $this->courseId )
        );
        $this->submissionTitleList = array();
    }
    
    public function setOptAllUsers()
    {
        $this->optAllUsers = true;
    }
    
    private function getSubmissionTitleList()
    {
        $sqlAssignmentId = $this->database->escape ( $this->assignement->getId () );
        
        $result = $this->database->query("
            SELECT
                `s`.id,
                `s`.`title`
            FROM
                `{$this->tbl['wrk_submission']}` AS `s`
            WHERE
                `s`.`assignment_id` = {$sqlAssignmentId}
        ");
                
        $submissionTitleList = array();
        
        foreach ($result as $submission )
        {
            $submissionTitleList[$submission['id']] = $submission['title'];
        }
        
        return $submissionTitleList;
    }

    public function getScoreList ()
    {
        if ( $this->assignement->getAssignmentType () == 'INDIVIDUAL' )
        {
            $sqlCourseId      = $this->database->quote ( $this->courseId );
            $sqlAssignementId = $this->database->escape ( $this->assignement->getId () );
            
            if ( $this->optAllUsers )
            {
                $onlyFromCourse = "";
            }
            else
            {
                $onlyFromCourse = "
#ONLY FROM COURSE
INNER JOIN 
    `{$this->tbl[ 'rel_course_user' ]}` AS `cu`
ON 
    `u`.`user_id` = `cu`.`user_id`
AND 
    `cu`.`code_cours` = {$sqlCourseId}
                ";
            }

            $scoreListIterator = $this->database->query ( "
SELECT 
    `u`.`user_id` AS `authId`,
    CONCAT(`u`.`nom`, ' ', `u`.`prenom`) AS `author`,
    `s`.`title`,
    `s`.`id` as `submissionId`,
    COUNT(DISTINCT(`s`.`id`)) AS `submissionCount`,
    COUNT(DISTINCT(`fb`.`id`)) AS `feedbackCount`,
    MAX(`fb`.`score`) AS `maxScore`,
    MIN(`fb`.`score`) AS `minScore`,
    AVG(`fb`.`score`) AS `avgScore`,
    MAX(`s`.`last_edit_date`) AS `last_edit_date`

#GET USER LIST
FROM 
    `{$this->tbl[ 'user' ]}` AS `u`

{$onlyFromCourse}

# SEARCH ON SUBMISSIONS
LEFT JOIN 
    `{$this->tbl[ 'wrk_submission' ]}` AS `s`
ON 
    ( `s`.`assignment_id` = {$sqlAssignementId} OR `s`.`assignment_id` IS NULL)
AND 
    `s`.`user_id` = `u`.`user_id`
AND 
    `s`.`original_id` IS NULL


# SEARCH ON FEEDBACKS
LEFT JOIN 
    `{$this->tbl[ 'wrk_submission' ]}` as `fb`
ON 
    `fb`.`parent_id` = `s`.`id`

# GROUP BY USERS
GROUP BY 
    `u`.`user_id`, 
    `s`.`original_id`

ORDER BY 
    u.nom ASC,
    u.prenom ASC
            " );
        }
        else // group assignement
        {
            $sqlCourseId      = $this->database->quote ( $this->courseId );
            $sqlAssignementId = $this->database->escape ( $this->assignement->getId () );

            $scoreListIterator = $this->database->query ( "
SELECT 
    `g`.`id` AS `authId`,
    `g`.`name` AS `author`,
    `s`.`title`,
    `s`.`id` as `submissionId`,
    COUNT(DISTINCT(`s`.`id`)) AS `submissionCount`,
    COUNT(DISTINCT(`fb`.`id`)) AS `feedbackCount`,
    MAX(`fb`.`score`) AS `maxScore`,
    MIN(`fb`.`score`) AS `minScore`,
    AVG(`fb`.`score`) AS `avgScore`,
    MAX(`s`.`last_edit_date`) AS `last_edit_date`

#GET USER LIST
FROM 
    `{$this->tbl[ 'group_team' ]}` AS `g`

# SEARCH ON SUBMISSIONS
LEFT JOIN 
    `{$this->tbl[ 'wrk_submission' ]}` AS `s`
ON 
    ( `s`.`assignment_id` = {$sqlAssignementId} OR `s`.`assignment_id` IS NULL)
AND 
    `s`.`group_id` = `g`.`id`
AND 
    `s`.`original_id` IS NULL


# SEARCH ON FEEDBACKS
LEFT JOIN 
    `{$this->tbl[ 'wrk_submission' ]}` as `fb`
ON 
    `fb`.`parent_id` = `s`.`id`

# GROUP BY USERS
GROUP BY 
    `g`.`id`, 
    `s`.`original_id`

ORDER BY 
    g.name ASC
            " );
        }
        
        $iterator = new CLWRK_AssignementScoreIterator( $scoreListIterator, $this->getSubmissionTitleList() );
    
        return $iterator;
    }
}

class CLWRK_ScoreListRenderer extends ModuleTemplate
{
    public function __construct ( $course, $assignment, $scoreList )
    {
        parent::__construct('CLWRK', 'work_score.tpl.php');
        
        $this->assign( 'course', $course );
        $this->assign( 'scoreList', $scoreList );
        $this->assign( 'assignment', $assignment );
    }
}
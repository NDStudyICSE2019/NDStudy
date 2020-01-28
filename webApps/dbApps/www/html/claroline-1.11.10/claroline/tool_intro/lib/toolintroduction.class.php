<?php // $Id: toolintroduction.class.php 13637 2011-10-03 15:24:13Z abourguignon $

/**
 * CLAROLINE
 *
 * @version     $Revision: 13637 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTI
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.10
 */


class ToolIntro implements Display
{
    /**
     * @protected $id
     */
    protected $id;
    
    /**
     * @protected $courseCode
     */
    protected $courseCode;
    
    /**
     * @protected $toolId
     */
    protected $toolId;
     
    /**
     * @protected $title name of the description
     */
    protected $title;
    
    /**
     * @protected $content description text
     */
    protected $content;
   
    /**
     * @protected $rank
     */
    protected $rank;
     
    /**
     * @protected $displayDate
     */
    protected $displayDate;
    
    /**
     * @protected $visibility
     */
    protected $visibility;
    
    /**
     * @protected $tblToolIntro
     */
    protected $tblToolIntro;
    
    
    /**
     * Constructor
     *
     * @param integer $id
     * @param string $courseCode
     */
    public function __construct($id = null, $courseCode = null,
        $toolId = null, $title = '', $content = '', $rank = null,
        $displayDate = null, $visibility = 'SHOW')
    {
        $this->id           = $id;
        $this->couseCode    = $courseCode;
        $this->toolId       = $toolId;
        $this->title        = $title;
        $this->content      = $content;
        $this->rank         = $rank;
        $this->displayDate  = $displayDate;
        $this->visibility   = $visibility;
        
        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($this->couseCode));
        $this->tblToolIntro = $tbl_cdb_names['tool_intro'];
    }
    
    
    /**
     * Load from DB
     *
     * @param integer $id
     * @return boolean true if load is successfull false otherwise
     */
    public function load($id = null)
    {
        if (!empty($id))
        {
            $this->id = $id;
        }
        
        $sql = "SELECT `id`,
                       `tool_id`,
                       `title`,
                       UNIX_TIMESTAMP(`display_date`) AS `display_date`,
                       `content`,
                       `rank`,
                       `visibility`
                FROM `".$this->tblToolIntro."`
                WHERE `id` = ".(int) $this->id;
        
        $res = Claroline::getDatabase()->query($sql);
        $toolIntro = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        if (!empty($toolIntro))
        {
            $this->toolId       = $toolIntro['tool_id'];
            $this->title        = $toolIntro['title'];
            $this->content      = $toolIntro['content'];
            $this->rank         = $toolIntro['rank'];
            $this->displayDate  = $toolIntro['display_date'];
            $this->visibility   = $toolIntro['visibility'];
            
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * Save to DB
     *
     * @return mixed false or id of the record
     */
    public function save()
    {
        if (empty($this->id))
        {
            return $this->insert();
        }
        else
        {
            return $this->update();
        }
    }
    
    
    /**
     * Insert into DB
     *
     * @return mixed false or id of the record
     */
    public function insert()
    {
        // Select the current highest rank
        $sql = "SELECT MAX(rank) AS maxRank
                FROM `".$this->tblToolIntro."`";
        
        $res = Claroline::getDatabase()->query($sql);
        $toolIntro = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        if ($toolIntro)
        {
            $this->rank = $toolIntro['maxRank']+1;
        }
        else
        {
            $this->rank = 1;
        }
        
        // Insert datas
        $sql = "INSERT INTO `".$this->tblToolIntro."`
                SET `tool_id` = ". (int) $this->toolId .",
                    `title` = " . Claroline::getDatabase()->quote($this->title) . ",
                    `display_date` = NULL,
                    `content` = " . Claroline::getDatabase()->quote($this->content) . ",
                    `rank` = " . (int) $this->rank . ",
                    `visibility` = " . Claroline::getDatabase()->quote($this->visibility);
        
        if (Claroline::getDatabase()->exec($sql))
        {
            $this->id = Claroline::getDatabase()->insertId();
            
            return $this->id;
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * Update entry in DB
     *
     * @return mixed false or id of the record
     */
    public function update()
    {
        $sql = "UPDATE `" . $this->tblToolIntro . "`
                SET `title` = " . Claroline::getDatabase()->quote($this->title) . ",
                    `tool_id` = " . (int) $this->toolId . ",
                    `display_date` = NULL,
                    `content` = " . Claroline::getDatabase()->quote($this->content) . ",
                    `rank` = " . (int) $this->rank . ",
                    `visibility` = " . Claroline::getDatabase()->quote($this->visibility) . "
                WHERE `id` = " . (int) $this->id;
        
        if (Claroline::getDatabase()->exec($sql))
        {
            return $this->id;
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * Delete from DB
     *
     * @return boolean true if delete is successfull false otherwise
     */
    public function delete()
    {
        $sql = "DELETE FROM `" . $this->tblToolIntro . "`
                WHERE `id` = " . (int) $this->id;
        
        if( Claroline::getDatabase()->exec($sql) )
        {
            $this->id = null;
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    public function renderForm()
    {
        $template = new ModuleTemplate('CLTI', 'form.tpl.php');
        $template->assign('formAction', Url::Contextualize($_SERVER['PHP_SELF']));
        $template->assign('relayContext', claro_form_relay_context());
        $template->assign('cmd', $this->id ? 'exEd' : 'exAdd');
        $template->assign('intro', $this);
        
        return $template->render();
    }
    
    
    public function handleForm()
    {
        $this->setToolId(isset($_REQUEST['introId'])?$_REQUEST['introId']:null);
        $this->setTitle(isset($_REQUEST['title'])?$_REQUEST['title']:'');
        $this->setContent(isset($_REQUEST['content'])?$_REQUEST['content']:'');
        $this->setRank(isset($_REQUEST['rank'])?$_REQUEST['rank']:null);
        $this->setDisplayDate(isset($_REQUEST['displayDate'])?$_REQUEST['displayDate']:null);
        $this->setVisibility(isset($_REQUEST['visibility'])?$_REQUEST['visibility']:'SHOW');
    }
    
    
    /**
     * Exchange ranks between the current item and the next one.
     *
     * @return bool     success
     */
    public function moveDown()
    {
        // Select the id of the following item
        $sql = "SELECT `id`, `rank`
                FROM `".$this->tblToolIntro."`
                WHERE `rank` = (SELECT MIN(`rank`)
                                FROM `".$this->tblToolIntro."`
                                WHERE `rank` > ".(int) $this->rank.")";
        
        $res = Claroline::getDatabase()->query($sql);
        $toolIntro = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        // If there is a following item, swap the two item's ranks
        if (!is_null($this->rank) && $toolIntro['id'])
        {
            // Next item's rank is decreased
            $sql1 = "UPDATE `".$this->tblToolIntro."`
                     SET `rank` = " . (int) $this->rank . "
                     WHERE `id` = " . (int) $toolIntro['id'];
            
            // Current item's rank is increased
            $sql2 = "UPDATE `".$this->tblToolIntro."`
                     SET `rank` = " . (int) $toolIntro['rank'] . "
                     WHERE `id` = " . (int) $this->id;
            
            if (Claroline::getDatabase()->exec($sql1) && Claroline::getDatabase()->exec($sql2))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * Exchange ranks between the current item and the previous one.
     *
     * @return bool     success
     */
    public function moveUp()
    {
        // Select the id of the previous item
        $sql = "SELECT `id`, `rank`
                FROM `".$this->tblToolIntro."`
                WHERE `rank` = (SELECT MAX(`rank`)
                                FROM `".$this->tblToolIntro."`
                                WHERE `rank` < ".(int) $this->rank.")";
        
        $res = Claroline::getDatabase()->query($sql);
        $toolIntro = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        // If there is a following item, swap the two item's ranks
        if (!is_null($this->rank) && $toolIntro['id'])
        {
            // Previous item's rank is increased
            $sql1 = "UPDATE `".$this->tblToolIntro."`
                     SET `rank` = " . (int) $this->rank . "
                     WHERE `id` = " . (int) $toolIntro['id'];
            
            // Current item's rank is decreased
            $sql2 = "UPDATE `".$this->tblToolIntro."`
                     SET `rank` = " . (int) $toolIntro['rank'] . "
                     WHERE `id` = " . (int) $this->id;
            
            if (Claroline::getDatabase()->exec($sql1) && Claroline::getDatabase()->exec($sql2))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    
    
    public function render()
    {
        
        $template = new ModuleTemplate('CLTI', 'item.tpl.php');
        $template->assign('intro', $this);
        $template->assign('rsLocator', ResourceLinker::$Navigator->getCurrentLocator(array('id' => $this->id)));
        
        return $template->render();
    }
    
    
    // get and set
    public function getId()
    {
        return (int) $this->id;
    }
    
    
    public function setId($id)
    {
        $this->id = (int) $id;
    }
    
    
    public function getCourseCode()
    {
        return $this->courseCode;
    }
    
    
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;
    }
    
    
    public function getToolId()
    {
        return $this->toolId;
    }
    
    
    public function setToolId($toolId)
    {
        $this->toolId = $toolId;
    }
    
    
    public function getTitle()
    {
        return $this->title;
    }
    
    
    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    
    public function getContent()
    {
        return $this->content;
    }
    
    
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    
    public function getRank()
    {
        return $this->rank;
    }
    
    
    public function setRank($rank)
    {
        $this->rank = $rank;
    }
    
    
    public function getDisplayDate()
    {
        return $this->displayDate;
    }
    
    
    public function setDisplayDate($displayDate)
    {
        $this->displayDate = $displayDate;
    }
    
    
    public function getVisibility()
    {
        return $this->visibility;
    }
    
    
    public function setVisibility($visibility)
    {
        $acceptedValues = array('SHOW', 'HIDE');
        
        if( in_array($visibility, $acceptedValues) )
        {
            $this->visibility = $visibility;
            return true;
        }
        return false;
    }
}
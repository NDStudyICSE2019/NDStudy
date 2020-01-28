<?php // $Id: coursehomepageportlet.class.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Course home page portlet class
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     DESKTOP
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @author      Claroline team <info@claroline.net>
 */

require_once get_path('includePath') . '/lib/portlet.class.php';

abstract class CourseHomePagePortlet extends Portlet
{
    protected $id;
    protected $courseId;
    protected $rank;
    protected $label;
    protected $visible;
    
    protected $courseCode;
    protected $tblRelCoursePortlet;
    
    public function __construct($id = null, $courseCode = '', $courseId = null,
        $rank = null, $label = '', $visible = 1)
    {
        $this->id           = $id;
        $this->courseId     = $courseId;
        $this->rank         = $rank;
        $this->label        = $label;
        $this->visible      = $visible;
        
        $this->courseCode   = $courseCode;
        
        // Get table
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $this->tblRelCoursePortlet  = $tbl_mdb_names['rel_course_portlet'];
    }
    
    
    /**
     * Load from DB
     *
     * @param integer $courseId
     * @param string $label
     * @return boolean true if load is successfull false otherwise
     */
    public function load($id)
    {
        if (!empty($id))
        {
            $this->id = $id;
        }
        
        $sql = "SELECT `courseId`,
                       `rank`,
                       `label`,
                       `visible`
                FROM `".$this->tblRelCoursePortlet."`
                WHERE `id` = ".(int) $this->id;
        
        $res = Claroline::getDatabase()->query($sql);
        $portlet = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        if(!empty($portlet))
        {
            $this->courseId = $portlet['courseId'];
            $this->rank     = $portlet['rank'];
            $this->label    = $portlet['label'];
            $this->visible  = $portlet['visible'];
            
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
        if(empty($this->id))
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
        $this->repairRanks();
        
        // Verify that the portlet doesn't already exist
        $sql = "SELECT id
                FROM `".$this->tblRelCoursePortlet."`
                WHERE `courseId` = ".(int) $this->courseId."
                AND `label` = ".Claroline::getDatabase()->quote($this->label);
        
        $res = Claroline::getDatabase()->query($sql);
        $portlet = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        if (!empty($portlet))
        {
            return false;
        }
        
        // All exiting portlets' rank get incremented by 1
        $sql = "UPDATE `".$this->tblRelCoursePortlet."`
                SET `rank` = `rank`+1";
        
        Claroline::getDatabase()->exec($sql);
        
        // Insert datas
        $sql = "INSERT INTO `".$this->tblRelCoursePortlet."`
                SET `courseId` = ". (int) $this->courseId .",
                    `rank` = 1,
                    `label` = " . Claroline::getDatabase()->quote($this->label) . ",
                    `visible` = " . (int) $this->visible;
        
        if(Claroline::getDatabase()->exec($sql))
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
        $sql = "UPDATE `".$this->tblRelCoursePortlet."`
                SET `courseId` = ". (int) $this->courseId .",
                    `rank` = " . (int) $this->rank . ",
                    `label` = " . Claroline::getDatabase()->quote($this->label) . ",
                    `visible` = " . (int) $this->visible . "
                WHERE `id` = " . (int) $this->id;
        
        if(Claroline::getDatabase()->exec($sql))
        {
            return $this->id;
        }
        else
        {
            return false;
        }
    }
    
    private function repairRanks()
    {
        $sql = "SELECT id
                FROM `".$this->tblRelCoursePortlet."`
                WHERE `courseId` = " . (int) $this->courseId . "
                ORDER BY rank ASC";
        
        $res = Claroline::getDatabase()->query($sql);
        
        $rank = 1;
        
        foreach ( $res as $portlet )
        {
            $sql1 = "UPDATE `".$this->tblRelCoursePortlet."`
                     SET `rank` = " . (int) $rank . "
                     WHERE `id` = " . (int) $portlet['id'];
            
            $res = Claroline::getDatabase()->exec($sql1);
            
            $rank += 1;
        }
    }
    
    
    /**
     * Delete from DB
     *
     * @return boolean true if delete is successfull false otherwise
     */
    public function delete()
    {
        // Verify that the portlet doesn't already exist
        $sql = "SELECT id
                FROM `".$this->tblRelCoursePortlet."`
                WHERE `id` = " . (int) $this->id;
        
        $res = Claroline::getDatabase()->query($sql);
        $portlet = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        if (empty($portlet))
        {
            return false;
        }
        
        $sql = "DELETE FROM `".$this->tblRelCoursePortlet."`
                WHERE `id` = " . (int) $this->id;
        
        $this->repairRanks();
        
        if(Claroline::getDatabase()->exec($sql))
        {
            $this->id = null;
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    public function moveUp()
    {
        $this->repairRanks();
        
        // Select the id of the previous item
        $sql = "SELECT `id`
                FROM `".$this->tblRelCoursePortlet."`
                WHERE `rank` = ".(int) ($this->rank-1)."
                AND `courseId` = ".(int) $this->courseId;
        
        $res = Claroline::getDatabase()->query($sql);
        $portlet = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        // If there is a following item, swap the two item's ranks
        if (!is_null($this->rank) && $portlet)
        {
            // Previous item's rank is increased by 1
            $sql1 = "UPDATE `".$this->tblRelCoursePortlet."`
                     SET `rank` = " . (int) $this->rank . "
                     WHERE `id` = " . (int) $portlet['id'];
            
            $this->rank = $this->rank-1;
            
            // Current item's rank is decreased by 1
            $sql2 = "UPDATE `".$this->tblRelCoursePortlet."`
                     SET `rank` = " . (int) $this->rank . "
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
    
    
    public function moveDown()
    {
        $this->repairRanks();
        
        // Select the id of the following item
        $sql = "SELECT `id`
                FROM `".$this->tblRelCoursePortlet."`
                WHERE `rank` = ".(int) ($this->rank+1)."
                AND `courseId` = ".(int) $this->courseId;
        
        $res = Claroline::getDatabase()->query($sql);
        $portlet = $res->fetch(Database_ResultSet::FETCH_ASSOC);
        
        // If there is a following item, swap the two item's ranks
        if (!is_null($this->rank) && $portlet)
        {
            // Next item's rank is decreased by 1
            $sql1 = "UPDATE `".$this->tblRelCoursePortlet."`
                     SET `rank` = " . (int) $this->rank . "
                     WHERE `id` = " . (int) $portlet['id'];
            
            $this->rank = $this->rank+1;
            
            // Current item's rank is increased by 1
            $sql2 = "UPDATE `".$this->tblRelCoursePortlet."`
                     SET `rank` = " . (int) $this->rank . "
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
    
    
    public function makeVisible()
    {
        $this->visible = 1;
    }
    
    
    public function makeInvisible()
    {
        $this->visible = 0;
    }
    
    
    public function handleForm()
    {
        $this->label    = (isset($_REQUEST['portletLabel'])?$_REQUEST['portletLabel']:'');
        $this->courseId = (isset($_REQUEST['courseId'])?$_REQUEST['courseId']:'');
    }
    
    
    /**
     * Return a list of activable portlets for the current course.
     *
     * @return array list of activable portlets
     */
    public static function getActivablePortlets()
    {
        $courseCode = claro_get_current_course_id();
        
        // Get table name
        $tbl_mdb_names              = claro_sql_get_main_tbl();
        $tbl_coursehomepage_portlet = $tbl_mdb_names['coursehomepage_portlet'];
        $tbl_rel_course_portlet     = $tbl_mdb_names['rel_course_portlet'];
        
        // Get available portlets for the current course
        $sql = "SELECT `label`, `name`
                FROM `".$tbl_coursehomepage_portlet."`
                WHERE `label` NOT IN (
                    SELECT CONCAT_WS(',', label)
                    FROM `".$tbl_rel_course_portlet."`
                    WHERE `courseId` = ".(int) ClaroCourse::getIdFromCode($courseCode)."
                )
                ORDER BY `name` ASC";
        
        $res = Claroline::getDatabase()->query($sql);
        
        if (!$res->isEmpty())
        {
            return $res;
        }
        else
        {
            return array();
        }
    }
    
    
    public function render()
    {
        // Portlet's management commands
        if (claro_is_allowed_to_edit())
        {
             $commands = '<span style="float: right;">'
                   . '<a href="'
                   . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] .'?portletCmd=moveUp&portletLabel='.$this->label.'&portletId='.$this->id))
                   . '">'
                   . '<img src="' . get_icon_url('go_up') . '" alt="'.get_lang('Move up').'" />'
                   . '</a> <a href="'
                   . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] .'?portletCmd=moveDown&portletLabel='.$this->label.'&portletId='.$this->id))
                   . '">'
                   . '<img src="' . get_icon_url('go_down') . '" alt="'.get_lang('Move down').'" />'
                   . '</a> <a href="'
                   . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] .'?portletCmd='
                   . ($this->visible?'makeInvisible':'makeVisible') . '&portletLabel='.$this->label.'&portletId='.$this->id))
                   . '" title="'
                   . ($this->visible?get_lang('Hide this item'):get_lang('Show this item')). '">'
                   . '<img src="'
                   . ($this->visible?get_icon_url('visible'):get_icon_url('invisible'))
                   . '" alt="'.get_lang('Swap visibility').'" />'
                   . '</a> <a href="'
                   . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] .'?portletCmd=delete&portletLabel='.$this->label.'&portletId='.$this->id))
                   . '">'
                   . '<img src="' . get_icon_url('delete') . '" alt="'.get_lang('Delete').'" />'
                   . '</a> '
                   . '</span>';
        }
        else
        {
            $commands = '';
        }
        
        if ($this->visible)
        {
            $out = '<div class="portlet">' . "\n"
                 . '<h1>' . "\n"
                 . $this->renderTitle() . $commands . "\n"
                 . '</h1>' . "\n"
                 . '<div class="content">' . "\n"
                 . $this->renderContent()
                 . '</div>' . "\n"
                 . '</div>' . "\n\n";
        }
        else
        {
            // If not visible, only render the title bar
            $out = '<div class="portlet hidden">' . "\n"
                 . '<h1>' . "\n"
                 . $this->renderTitle() . $commands . "\n"
                 . '</h1>' . "\n"
                 . '</div>' . "\n\n";
        }
        
        return $out;
    }
    
    
    public function getVisible()
    {
        return $this->visible;
    }
    
    
    public function setVisible($visibility)
    {
        if ($visibility == 1 || $visibility == 0)
        {
            $this->visible = $visibility;
        }
    }
    
    
    public function getLabel()
    {
        return $this->label;
    }
}
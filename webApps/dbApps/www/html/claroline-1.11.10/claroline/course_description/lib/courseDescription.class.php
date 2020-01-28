<?php // $Id: courseDescription.class.php 12923 2011-03-03 14:23:57Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.9
 */

/*
    From 1.8 to 1.9 :
    -   'id' becomes int(11) auto_increment
    -   add 1 field 'category' that takes the 'id' value of 1.8 table for values lower that size of $titreBloc
    -   'category' is -1 when id was upper than size of $titreBloc
    -   'upDate' becomes 'lastEditDate'
    
    
CREATE TABLE `__CL_COURSE__course_description` (
  `id` int(11) NOT NULL auto_increment,
  `category` int(11) NOT NULL default '-1',
  `title` protectedchar(255) default NULL,
  `content` text,
  `lastEditDate` DATETIME NOT NULL,
  `visibility` enum('VISIBLE','INVISIBLE') NOT NULL default 'VISIBLE',
  PRIMARY KEY `id` (`id`)
);

*/


class CourseDescription
{
    /**
     * @protected $id id of description, -1 if description doesn't exist already
     */
    protected $id;

    /**
     * @protected $category id of predefined 'type' of description, > 0 for predefined categories, -1 for "others"
     */
    protected $category;
     
    /**
     * @protected $title name of the description
     */
    protected $title;

    /**
     * @protected $content description text
     */
    protected $content;
   
    /**
     * @protected $lastEditDate last edition date of the description timestamp
     */
    protected $lastEditDate;

    /**
     * @protected $visibility
     */
    protected $visibility;
    
    
    /**
     * constructor
     *
     * @param $course_id
     */
    public function __construct($course_id = null)
    {
        $this->id           = (int) -1;
        $this->category     = -1;
        $this->title        = '';
        $this->content      = '';
        $this->lastEditDate = time();
        $this->visibility   = 'VISIBLE';

        $tbl_cdb_names = claro_sql_get_course_tbl(claro_get_course_db_name_glued($course_id));
        $this->tblCourseDescription = $tbl_cdb_names['course_description'];
    }
    
    
    /**
     * load a description from DB
     *
     * @param integer $id id of description
     * @return boolean true if load is successfull false otherwise
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function load($id)
    {
        $sql = "SELECT `id`,
                       `category`,
                       `title`,
                       `content`,
                       UNIX_TIMESTAMP(`lastEditDate`) AS `unix_lastEditDate`,
                       `visibility`
                FROM `".$this->tblCourseDescription."`
                WHERE `id` = ".(int) $id;

        $data = claro_sql_query_get_single_row($sql);

        if( !empty($data) )
        {
            $this->setId($id);
            $this->setCategory($data['category']);
            $this->setTitle($data['title']);
            $this->setContent($data['content']);
            $this->setLastEditDate($data['unix_lastEditDate']);
            $this->setVisibility($data['visibility']);

            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * save description to DB
     *
     * @return mixed false or id of the record
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function save()
    {
        if( $this->id == -1 )
        {
            return $this->insert();
        }
        else
        {
            return $this->update();
        }
    }
    
    
    /**
     * insert a new description to DB
     *
     * @return mixed false or id of the record
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function insert()
    {
           // insert
        $sql = "INSERT INTO `".$this->tblCourseDescription."`
                SET `category` = ".$this->getCategory().",
                    `title` = '".claro_sql_escape($this->getTitle())."',
                    `content` = '".claro_sql_escape($this->getContent())."',
                    `lastEditDate` = NOW(),
                    `visibility` = '".claro_sql_escape($this->getVisibility())."'";

        // execute the creation query and get id of inserted assignment
        $insertedId = claro_sql_query_insert_id($sql);

        if( $insertedId )
        {
            $this->setId($insertedId);

            return $this->getId();
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * update description to DB
     *
     * @return mixed false or id of the record
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function update()
    {
        // update, main query
        $sql = "UPDATE `".$this->tblCourseDescription."`
                SET `category` = ".$this->getCategory().",
                    `title` = '".claro_sql_escape($this->getTitle())."',
                    `content` = '".claro_sql_escape($this->getContent())."',
                    `lastEditDate` = NOW(),
                    `visibility` = '".claro_sql_escape($this->getVisibility())."'
                WHERE `id` = ".$this->getId();

        // execute and return main query
        if( claro_sql_query($sql) )
        {
            return $this->getId();
        }
        else
        {
            return false;
        }
    }
    
    
    /**
     * delete description
     *
     * @return boolean true if delete is successfull false otherwise
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function delete()
    {
        $sql = "DELETE FROM `".$this->tblCourseDescription."`
                WHERE `id` = ".$this->getId();

        if( claro_sql_query($sql) )
        {
            $this->setId(-1);
            return true;
        }
        else
        {
            return true;
        }
    }
    
    
    /**
     * validate object content
     *
     * @return boolean true if delete is successfull false otherwise
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function validate()
    {
        // there is nothing to validate at this time as a course description
        // is valide without title or content
        
        // so while we use this comportment validate() always returns true
        
        return true;
    }
    
    
    // get and set
    
    /**
     * get id of description
     *
     * @return integer id of description
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function getId()
    {
        return (int) $this->id;
    }
    
    
    /**
     * set id of description
     *
     * @param integer id of description
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }
    
    
    /**
     * get category of description
     *
     * @return integer category of description
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function getCategory()
    {
        return (int) $this->category;
    }
    
    
    /**
     * set category of description
     *
     * @param integer category of description, < 0 for others, > 0 for predefined categories
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function setCategory($category)
    {
        if( $category < 0 ) $this->category = (int) -1;
        else                $this->category = (int) $category;
    }
    
    
    /**
     * get title of description
     *
     * @return integer title of description
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    
    /**
     * set title of description
     *
     * @param string title of description
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function setTitle($title)
    {
        $this->title = trim($title);
    }
    
    
    /**
     * get content of description
     *
     * @return integer content of description
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function getContent()
    {
        return $this->content;
    }
    
    
    /**
     * set content of description
     *
     * @param string content of description
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function setContent($content)
    {
        $this->content = trim($content);
    }
    
    
    /**
     * get last edition date
     *
     * @return integer last edition date timestamp
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function getLastEditDate()
    {
        return $this->lastEditDate;
    }
    
    
    /**
     * set last edition date timestamp
     *
     * @param integer last edition date timestamp
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function setLastEditDate($date)
    {
        $this->lastEditDate = $date;
    }
    
    
    /**
     * get visibility of description
     *
     * @return string visibility of description 'VISIBLE' or 'INVISIBLE'
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function getVisibility()
    {
        return $this->visibility;
    }
    
    
    /**
     * set visibility
     *
     * @param string visibility of description 'VISIBLE' or 'INVISIBLE'
     * @author Sebastien Piraux <pir@cerdecam.be>
     */
    public function setVisibility($visibility)
    {
        $acceptedValues = array('VISIBLE', 'INVISIBLE');

        if( in_array($visibility, $acceptedValues) )
        {
            $this->visibility = $visibility;
            return true;
        }
        return false;
    }
}
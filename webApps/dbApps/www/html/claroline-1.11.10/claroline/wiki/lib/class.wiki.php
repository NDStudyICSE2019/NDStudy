<?php // $Id: class.wiki.php 14581 2013-11-07 15:39:52Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14581 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 * as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 * through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 *
 * @author Frederic Minne <zefredz@gmail.com>
 *
 * @package Wiki
 */
require_once dirname(__FILE__) . "/class.wikipage.php";

// Error codes
!defined("WIKI_NO_TITLE_ERROR") && define("WIKI_NO_TITLE_ERROR", "Missing title");
!defined("WIKI_NO_TITLE_ERRNO") && define("WIKI_NO_TITLE_ERRNO", 1);
!defined("WIKI_ALREADY_EXISTS_ERROR") && define("WIKI_ALREADY_EXISTS_ERROR", "Wiki already exists");
!defined("WIKI_ALREADY_EXISTS_ERRNO") && define("WIKI_ALREADY_EXISTS_ERRNO", 2);
!defined("WIKI_CANNOT_BE_UPDATED_ERROR") && define("WIKI_CANNOT_BE_UPDATED_ERROR", "Wiki cannot be updated");
!defined("WIKI_CANNOT_BE_UPDATED_ERRNO") && define("WIKI_CANNOT_BE_UPDATED_ERRNO", 3);
!defined("WIKI_NOT_FOUND_ERROR") && define("WIKI_NOT_FOUND_ERROR", "Wiki not found");
!defined("WIKI_NOT_FOUND_ERRNO") && define("WIKI_NOT_FOUND_ERRNO", 4);

/**
 * This class represents a Wiki
 */
class Wiki
{

    private $wikiId;
    private $title;
    private $desc;
    private $accessControlList;
    private $groupId;
    private $con;
    // default configuration
    private $config = array (
        'tbl_wiki_pages' => 'wiki_pages',
        'tbl_wiki_pages_content' => 'wiki_pages_content',
        'tbl_wiki_properties' => 'wiki_properties',
        'tbl_wiki_acls' => 'wiki_acls'
    );
    // error handling
    private $error = '';
    private $errno = 0;

    /**
     * Constructor
     * @param Database_Connection con connection to the database
     * @param array config associative array containing tables name
     */
    public function __construct( $con, $config = null)
    {
        if (is_array($config))
        {
            $this->config = array_merge($this->config, $config);
        }
        $this->con = $con;

        $this->wikiId = 0;
    }
    
    public function getDatabaseConnection()
    {
        return $this->con;
    }
    
    public function getConfig()
    {
        return $this->config;
    }

    // accessors

    /**
     * Set Wiki title
     * @param string wikiTitle
     */
    public function setTitle($wikiTitle)
    {
        $this->title = $wikiTitle;
    }

    /**
     * Get the Wiki title
     * @return string title of the wiki
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the description of the Wiki
     * @param string wikiDesc description of the wiki
     */
    public function setDescription($wikiDesc = '')
    {
        $this->desc = $wikiDesc;
    }

    /**
     * Get the description of the Wiki
     * @param string description of the wiki
     */
    public function getDescription()
    {
        return $this->desc;
    }

    /**
     * Set the access control list of the Wiki
     * @param array accessControlList wiki access control list
     */
    public function setACL($accessControlList)
    {
        $this->accessControlList = $accessControlList;
    }

    /**
     * Get the access control list of the Wiki
     * @return array wiki access control list
     */
    public function getACL()
    {
        return $this->accessControlList;
    }

    /**
     * Set the group ID of the Wiki
     * @param int groupId group ID
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * Get the group ID of the Wiki
     * @return int group ID
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set the ID of the Wiki
     * @param int wikiId ID of the Wiki
     */
    public function setWikiId($wikiId)
    {
        $this->wikiId = $wikiId;
    }

    /**
     * Set the ID of the Wiki
     * @return int ID of the Wiki
     */
    public function getWikiId()
    {
        return $this->wikiId;
    }

    // load and save

    /**
     * Load a Wiki
     * @param int wikiId ID of the Wiki
     */
    public function load($wikiId)
    {
        if ($this->wikiIdExists($wikiId))
        {
            $this->loadProperties($wikiId);
            $this->loadACL($wikiId);
        }
        else
        {
            $this->setError(WIKI_NOT_FOUND_ERROR, WIKI_NOT_FOUND_ERRNO);
        }
    }

    /**
     * Load the properties of the Wiki
     * @param int wikiId ID of the Wiki
     */
    private function loadProperties($wikiId)
    {
        $rs = $this->con->query( "
            SELECT 
                `id`, 
                `title`, 
                `description`, 
                `group_id` 
            FROM 
                `" . $this->config['tbl_wiki_properties'] . "` 
            WHERE 
                `id` = " . $this->con->escape( $wikiId ) 
        );

        $result = $rs->fetch();

        $this->setWikiId($result['id']);
        $this->setTitle(stripslashes($result['title']));
        $this->setDescription(stripslashes($result['description']));
        $this->setGroupId($result['group_id']);
    }

    /**
     * Load the access control list of the Wiki
     * @param int wikiId ID of the Wiki
     */
    private function loadACL($wikiId)
    {
        $result = $this->con->query( "
            SELECT 
                `flag`, 
                `value`
            FROM 
                `" . $this->config['tbl_wiki_acls'] . "` 
            WHERE 
                `wiki_id` = " . $this->con->escape( $wikiId ) 
        );
        
        $acl = array();
        
        foreach ($result as $row)
        {
            $value = ( $row['value'] == 'true' ) ? true : false;
            $acl[$row['flag']] = $value;
        }

        $this->setACL($acl);
    }

    /**
     * Save the Wiki
     */
    public function save()
    {
        $this->saveProperties();

        $this->saveACL();

        if ($this->hasError())
        {
            return 0;
        }
        else
        {
            return $this->wikiId;
        }
    }

    /**
     * Save the access control list of the Wiki
     */
    private function saveACL()
    {
        $aclExists = ( $this->con->query ( "
            SELECT 
                `wiki_id` 
            FROM 
                `" . $this->config['tbl_wiki_acls'] . "` 
            WHERE 
                `wiki_id` = " . $this->con->escape( $this->getWikiId() 
        ) )->numRows() > 0 );

        // wiki already exists
        if ( $aclExists )
        {
            $acl = $this->getACL();

            foreach ($acl as $flag => $value)
            {
                $value = ( $value == false ) ? 'false' : 'true';

                $this->con->exec( "
                    UPDATE 
                        `" . $this->config['tbl_wiki_acls'] . "` 
                    SET 
                        `value` = " . $this->con->quote( $value ) . "
                    WHERE 
                        `wiki_id` = " . $this->con->escape( $this->getWikiId() ) . " 
                    AND 
                        `flag`= " . $this->con->quote( $flag )  );
            }
        }
        // new wiki
        else
        {
            $acl = $this->getACL();

            foreach ($acl as $flag => $value)
            {
                $value = ( $value == false ) ? 'false' : 'true';

                $this->con->exec( "
                    INSERT INTO 
                        `" . $this->config['tbl_wiki_acls'] . "`
                    ( `wiki_id`, `flag`, `value` )
                    
                    VALUES (
                    " . $this->con->escape($this->getWikiId()) . ",
                    " . $this->con->quote($flag) . ",
                    " . $this->con->quote($value) . " )"
                );
            }
        }
    }

    /**
     * Save the properties of the Wiki
     */
    private function saveProperties()
    {
        // new wiki
        if ($this->getWikiId() === 0)
        {
            // INSERT PROPERTIES
            $this->con->exec( "
                INSERT INTO 
                    `" . $this->config['tbl_wiki_properties']."`
                (`title`,`description`,`group_id`)
                VALUES
                (" . $this->con->quote( $this->getTitle() ) . ", 
                 " . $this->con->quote( $this->getDescription() ) . ",
                 " . $this->con->escape( $this->getGroupId() ) . ")" 
            );

            $this->setWikiId($this->con->insertId());
        }
        // Wiki already exists
        else
        {
            // UPDATE PROPERTIES
            $this->con->exec( "
                UPDATE 
                    `" . $this->config['tbl_wiki_properties'] . "` 
                SET 
                    `title`= " . $this->con->quote($this->getTitle()) . ", 
                    `description`= " . $this->con->quote($this->getDescription()) . ", 
                    `group_id`= " . $this->con->escape( $this->getGroupId()) . "
                WHERE 
                    `id`= " . $this->con->escape($this->getWikiId()) );
        }
    }

    // utility methods

    /**
     * Check if a page exists in the wiki
     * @param string title page title
     * @return boolean
     */
    public function pageExists($title)
    {
        return $this->con->query( "
            SELECT 
                `id` 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "` 
            WHERE 
                `title` = " . $this->con->quote($title) . " 
            AND 
                `wiki_id` = " . $this->con->escape($this->getWikiId())
        )->numRows() > 0;
    }

    /**
     * Check if a wiki exists using its title
     * @param string title wiki title
     * @return boolean
     */
    public function wikiExists($title)
    {
        return $this->con->query( "
            SELECT 
                `id` 
            FROM 
                `" . $this->config['tbl_wiki_properties'] . "` 
            WHERE 
                `title` = " . $this->con->quote($title)
        )->numRows() > 0;
    }

    /**
     * Check if a wiki exists usind its ID
     * @param int id wiki ID
     * @return boolean
     */
    public function wikiIdExists($id)
    {
        return $this->con->query( "
            SELECT 
                `id` 
            FROM 
                `" . $this->config['tbl_wiki_properties'] . "` 
            WHERE 
                `id` = " . $this->con->escape($id)
        )->numRows() > 0;
    }

    /**
     * Get all the pages of this wiki (at this time the method returns
     * only the titles of the pages...)
     * @return array containing thes pages
     */
    public function allPages()
    {
        return $this->con->query( "
            SELECT 
                `title` 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "` 
            WHERE 
                `wiki_id` = " . $this->con->escape( $this->getWikiId() ) . " 
            ORDER BY 
                `title` ASC"
        );
    }

    /**
     * Get all the pages of this wiki (at this time the method returns
     * only the titles of the pages...) ordered by creation date
     * @return array containing thes pages
     */
    public function allPagesByCreationDate()
    {
        return $this->con->query( "
            SELECT 
                `title` 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "` 
            WHERE 
                `wiki_id` = " . $this->con->escape( $this->getWikiId() ) . " 
            ORDER BY 
                `ctime` ASC"
        );
    }

    /**
     * Get recently modified wiki pages
     * @param int offset start at given offset
     * @param int count number of record to return starting at offset
     * @return array recently modified pages (title, last_mtime, editor_id)
     */
    public function recentChanges($offset = 0, $count = 50)
    {
        $limit = ($count == 0 ) ? "" : "LIMIT " . $this->con->escape($offset) . ", " . $this->con->escape($count);

        return $this->con->query( "
            SELECT 
                `page`.`title`, 
                `page`.`last_mtime`, 
                `content`.`editor_id` 
            FROM
                `" . $this->config['tbl_wiki_pages'] . "` AS `page`, 
                `" . $this->config['tbl_wiki_pages_content'] . "` AS `content` 
            WHERE 
                `page`.`wiki_id` = " . $this->con->escape( $this->getWikiId() ) . " 
            AND 
                `page`.`last_version` = `content`.`id` 
            ORDER BY 
                `page`.`last_mtime` DESC "
            . $limit
        );
    }

    public function getNumberOfPages()
    {
        $result = $this->con->query( "
            SELECT 
                count( `id` ) as `pages` 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "` 
            WHERE 
                `wiki_id` = " . $this->con->escape( $this->wikiId ) 
        )->fetch();

        return $result['pages'];
    }

    // error handling

    private function setError($errmsg = '', $errno = 0)
    {
        $this->error = ($errmsg != '') ? $errmsg : 'Unknown error';
        $this->errno = $errno;
    }

    public function getError()
    {
        if ($this->error != '')
        {
            $errno = $this->errno;
            $error = $this->error;
            $this->error = '';
            $this->errno = 0;
            return $errno . ' - ' . $error;
        }
        else
        {
            return false;
        }
    }

    public function hasError()
    {
        return ( $this->error != '' );
    }

}

<?php // $Id: class.wikipage.php 14581 2013-11-07 15:39:52Z zefredz $

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

define("PAGE_NO_TITLE_ERROR", "Missing title");
define("PAGE_NO_TITLE_ERRNO", 1);
define("PAGE_ALREADY_EXISTS_ERROR", "Page already exists");
define("PAGE_ALREADY_EXISTS_ERRNO", 2);
define("PAGE_CANNOT_BE_UPDATED_ERROR", "Page cannot be updated");
define("PAGE_CANNOT_BE_UPDATED_ERRNO", 3);
define("PAGE_NOT_FOUND_ERROR", "Page not found");
define("PAGE_NOT_FOUND_ERRNO", 4);


// TODO rewrite WikiPage as a subclass of DatabaseConnection ?

/**
 * This class represents page of a Wiki
 */
class WikiPage
{

    // public fields
    protected $pageId = 0;            // attr_reader:
    protected $title = '';            // attr_accessor:
    protected $content = '';          // attr_accessor:
    protected $ownerId = 0;           // attr_accessor:
    protected $creationTime = '';     // attr_reader:
    protected $lastEditorId = 0;      // attr_accessor:
    protected $lastEditTime = '';     // attr_reader:
    protected $lastVersionId = 0;     // attr_reader:
    protected $wikiId = 0;            // attr_reader:
    protected $currentVersionMtime = '0000-00-00 00:00:00'; // attr_reader:
    protected $currentVersionEditorId = 0; // attr_reader:
    // private fields
    private $con = null;            // private
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
     * @param DatabaseConnection con connection to the database
     * @param array config associative array containing tables name
     */
    public function __construct($con, $config = null, $wikiId = 0)
    {
        if (is_array($config))
        {
            $this->config = array_merge($this->config, $config);
        }
        
        $this->wikiId = $wikiId;
        $this->con = $con;
    }

    // public methods

    /**
     * Edit an existing page
     * @param int editorId ID of the user who edits the page
     * @param string content page content
     * @param string mtime modification time YYYY-MM-DD hh:mm:ss
     * @param boolean auto_save save automaticaly the modification
     *      to database if set to true (default false)
     * @return boolean true on success, false on failure
     */
    public function edit($editorId, $content = '', $mtime = '', $auto_save = false)
    {
        if (( $auto_save === true ) && (!$this->pageExists($this->getTitle()) ))
        {
            $this->setError(PAGE_NOT_FOUND_ERROR, PAGE_NOT_FOUND_ERROR);
            return false;
        }
        else if (( $auto_save === false ) && ( $this->getTitle() === '' ))
        {
            $this->setError(PAGE_NO_TITLE_ERROR, PAGE_NO_TITLE_ERRNO);
            return false;
        }
        else
        {
            $this->setEditorId($editorId);
            $this->setLastEditTime($mtime);
            $this->setContent($content);
            if ($auto_save === true)
            {
                return $this->save();
            }
            else
            {
                return true;
            }
        }
    }

    /**
     * Create a new page
     * @param int ownerId ID of the user who creates the page
     * @param string title title of the page
     * @param string content page content
     * @param string ctime creation time YYYY-MM-DD hh:mm:ss
     * @param boolean auto_save save automaticaly the page
     *      to database if set to true (default false)
     * @return boolean true on success, false on failure
     */
    public function create($ownerId, $title, $content = '', $ctime = '', $auto_save = false)
    {
        if (!$title)
        {
            $this->setError(PAGE_NO_TITLE_ERROR, PAGE_NO_TITLE_ERRNO);
            return false;
        }
        else
        {
            if (( $auto_save === true ) && ( $this->pageExists($title) ))
            {
                $this->setError(PAGE_ALREADY_EXISTS_ERROR, PAGE_ALREADY_EXISTS_ERRNO);
                return false;
            }
            else
            {
                $this->setOwnerId($ownerId);
                $this->setTitle($title);
                $this->setContent($content);
                $this->setCreationTime($ctime);
                $this->setEditorId($ownerId);
                $this->setLastEditTime($ctime);

                if ($auto_save === true)
                {
                    return $this->save();
                }
                else
                {
                    return true;
                }
            }
        }
    }

    /**
     * Delete the page
     * @return boolean true on success, false on failure
     */
    public function delete()
    {
        // (OPT) backup last version
        // 1st delete page info
        $numrows = $this->con->exec( "
            DELETE 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "`
            WHERE 
                `id` = " . $this->con->escape( $this->getPageId() ) );

        if ($numrows == 1)
        {
            // 2nd delete page versions
            $numrows = $this->con->exec( "
                DELETE 
                FROM 
                    `" . $this->config['tbl_wiki_pages_content'] . "` 
                WHERE 
                    `pid` = " . $this->con->escape( $this->getPageId() ) );

            $this->_setPageId(0);
            $this->_setLastVersionId(0);

            return ( $numrows > 0 );
        }
        else
        {
            return false;
        }
    }

    /**
     * Save the page
     * @return boolean true on success, false on failure
     */
    public function save()
    {
        if ($this->getCreationTime() === '')
        {
            $this->setCreationTime(date("Y-m-d H:i:s"));
        }

        if ($this->getLastEditTime() === '')
        {
            $this->setLastEditTime(date("Y-m-d H:i:s"));
        }

        if ($this->getPageId() === 0)
        {
            if ($this->pageExists($this->getTitle()))
            {
                $this->setError(PAGE_ALREADY_EXISTS_ERROR, PAGE_ALREADY_EXISTS_ERRNO);
                return false;
            }
            else
            {
                // insert new page
                // 1st insert page info
                $this->con->exec( "
                    INSERT 
                    INTO 
                        `" . $this->config['tbl_wiki_pages'] . "`
                    (`wiki_id`, `owner_id`,`title`,`ctime`, `last_mtime`) 
                    
                    VALUES("
                    . $this->con->escape($this->getWikiId()) . ", "
                    . $this->con->escape((int) $this->getOwnerId()) . ", "
                    . $this->con->quote($this->getTitle()) . ", "
                    . $this->con->quote($this->getCreationTime()) . ", "
                    . $this->con->quote($this->getLastEditTime()) . ")" );

                // 2nd update pageId
                $pageId = $this->con->insertId();
                
                $this->_setPageId($pageId);

                // 3rd update version
                return $this->_updateVersion();
            }
        }
        else
        {
            // update version
            return $this->_updateVersion();
        }
    }

    /**
     * Get page version history
     * @return array page history on success, null on failure
     */
    public function history($offset = 0, $limit = 0, $order = 'DESC')
    {
        $limit = ( $limit == 0 && $offset == 0 ) 
            ? "" 
            : "LIMIT " . $this->con->escape($offset) . "," . $this->con->escape($limit) . " "
            ;

        $order = ($order === 'ASC') ? " ORDER BY `id` ASC " : " ORDER BY `id` DESC ";
        // retreive versionId and editorId and mtime for each version
        // of the page

        return $this->con->query( "
            SELECT 
                `id`, `editor_id`, `mtime` 
            FROM 
                `" . $this->config['tbl_wiki_pages_content'] . "` 
            WHERE 
                `pid` = " . $this->con->escape( $this->getPageId() )
            . $order
            . $limit
        );
    }

    public function countVersion()
    {
        $res =  $this->con->query( "
            SELECT 
                COUNT(`id`) as `nbversion` 
            FROM 
                `" . $this->config['tbl_wiki_pages_content'] . "` 
            WHERE 
                `pid` = " . (int) $this->getPageId()
        )->fetch();
        
        if ( $res )
        {
            return $res['nbversion'];
        }
        else
        {
            return 0;
        }
    }

    /**
     * Check if a page exists in the wiki
     * @param string title page title
     * @return boolean true on success, false on failure
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
                `wiki_id` = " . $this->con->escape( $this->getWikiId() )
        )->numRows() > 0;
    }

    // public factory methods

    /**
     * Load a page using its title
     * @param string title title of the page
     * @return boolean true on success, false on failure
     */
    public function loadPage($title)
    {
        // retreive page (last version)
        return $this->_updatePageFields( "
            SELECT 
                p.`id`, p.`owner_id`, p.`title`, 
                p.`ctime`, p.`last_version`, p.`last_mtime`, 
                c.`editor_id`, c.`content` 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "` AS p, 
                `" . $this->config['tbl_wiki_pages_content'] . "` AS c 
            WHERE 
                p.`title` = " . $this->con->quote($title) . " 
            AND 
                c.`id` = p.`last_version`
            AND 
                `wiki_id` = " . $this->con->escape( $this->getWikiId() )
        );
    }

    /**
     * Load a given version of a page using its title
     * @param int versionId ID of the version
     * @return boolean true on success, false on failure
     */
    public function loadPageVersion($versionId)
    {
        // retreive page (given version)
        $sql = "
            SELECT 
                p.`id`, p.`owner_id`, p.`title`, 
                p.`ctime`, p.`last_version`, p.`last_mtime`, 
                c.`editor_id`, c.`content`, c.`mtime` AS `current_mtime`, c.`id` AS `current_version` 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "` AS p, 
                `" . $this->config['tbl_wiki_pages_content'] . "` AS c 
            WHERE 
                c.`id` = " . $this->con->escape( $versionId ) . " 
            AND 
                p.`id` = c.`pid`"
        ;

        if ( $this->_updatePageFields( $sql ) )
        {
            $this->_setCurrentVersionId( $versionId );
            
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Load a page using its ID
     * @param int pageId ID of the page
     */
    public function loadPageById($pageId)
    {
        // retreive page (last version)
        $sql = "
            SELECT 
                p.`id`, p.`owner_id`, p.`title`, 
                p.`ctime`, p.`last_version`, p.`last_mtime`, 
                c.`editor_id`, c.`content` 
            FROM 
                `" . $this->config['tbl_wiki_pages'] . "` AS p,
                `" . $this->config['tbl_wiki_pages_content'] . "` AS c 
            WHERE 
                p.`id` = '" . $this->con->escape( $pageId ) . "' 
            AND 
                c.`id` = p.`last_version`"
        ;

        return $this->_updatePageFields($sql);
    }

    /**
     * Restore a given version of the page
     * @param int editorId ID of the user who restores the page
     * @param int versionId ID of the version to restore
     */
    public function restoreVersion($editorId, $versionId)
    {
        $this->loadPageVersion($versionId);
        $this->edit($editorId, $this->getContent(), date("Y-m-d H:i:s"), true);
    }

    // private methods

    /**
     * Update a page
     * @access private
     * @return boolean true on success, false on failure
     */
    private function _updateVersion()
    {
        // 1st insert page content
        $this->con->exec( "
            INSERT INTO 
                `" . $this->config['tbl_wiki_pages_content'] . "`
            (`pid`,`editor_id`,`mtime`, `content`) 
            
            VALUES("
            . $this->con->escape($this->getPageId()) . ", "
            . $this->con->escape($this->getEditorId()) . ", "
            . $this->con->quote($this->getLastEditTime()) . ", "
            . $this->con->quote($this->getContent())
            . ")"
        );

        // update last version id
        $lastVersionId = $this->con->insertId();

        $this->_setLastVersionId($lastVersionId);
        $this->_setCurrentVersionId($lastVersionId);

        // 2nd update page info
        $this->con->exec( "
            UPDATE `" . $this->config['tbl_wiki_pages'] . "`
            SET 
                `last_version` = ".$this->con->escape($this->getLastVersionId()) . ",
                `last_mtime` = " . $this->con->quote($this->getLastEditTime()) . "
            WHERE `id` = " . $this->con->escape( $this->getPageId() ) );
        
        return true;
    }

    /**
     * Update the fields of the page
     * @access private
     * @param string sql SQL query
     * @return boolean true on success, false on failure
     */
    private function _updatePageFields($sql)
    {
        try
        {
            $res = $this->con->query($sql);

            if ($res->numRows())
            {
                $page = $res->fetch();

                $this->_setPageId($page['id']);
                $this->setOwnerId($page['owner_id']);
                $this->setTitle($this->stripSlashesForWiki($page['title']));
                $this->_setLastVersionId($page['last_version']);
                $this->_setCurrentVersionId($page['last_version']);
                $this->setCreationTime($page['ctime']);
                $this->setLastEditTime($page['last_mtime']);
                $this->setEditorId($page['editor_id']);
                $this->setContent($this->stripSlashesForWiki($page['content']));

                $this->currentVersionId = ( isset($page['current_version']) ) 
                    ? $page['current_version']
                    : $page['last_version']
                    ;

                $this->currentVersionMtime = ( isset($page['current_mtime']) ) 
                    ? $page['current_mtime'] 
                    : $page['last_mtime']
                    ;

                return $this;
            }
            else
            {
                return null;
            }
        }
        catch ( Exception $e )
        {
            $this->setError( PAGE_CANNOT_BE_UPDATED_ERROR.' : '.$e->getMessage (), PAGE_CANNOT_BE_UPDATED_ERRNO );
            Console::error( "CLWIKI : ".PAGE_CANNOT_BE_UPDATED_ERROR.' : '.$e->getMessage (), PAGE_CANNOT_BE_UPDATED_ERRNO );
        }
    }

    // error handling

    private function setError($errmsg = '', $errno = 0)
    {
        $this->error = ($errmsg != '') ? $errmsg : "Unknown error";
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

    // public accessors

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function setEditorId($editorId)
    {
        $this->lastEditorId = $editorId;
    }

    public function setLastEditTime($mtime = '')
    {
        $this->lastEditTime = ($mtime == '') ? date("Y-m-d H:i:s") : $mtime;
    }

    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    public function setCreationTime($ctime = '')
    {
        $this->creationTime = ($ctime == '') ? date("Y-m-d H:i:s") : $ctime;
    }

    public function getWikiId()
    {
        return $this->wikiId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getEditorId()
    {
        return $this->lastEditorId;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function getLastEditTime()
    {
        return $this->lastEditTime;
    }

    public function getCreationTime()
    {
        return $this->creationTime;
    }

    public function getLastVersionId()
    {
        return $this->lastVersionId;
    }

    public function getCurrentVersionId()
    {
        return $this->currentVersionId;
    }

    public function getCurrentVersionMtime()
    {
        return $this->currentVersionMtime;
    }

    public function getPageId()
    {
        return $this->pageId;
    }

    // private accessors

    private function _setPageId($pageId)
    {
        $this->pageId = $pageId;
    }

    private function _setLastVersionId($lastVersionId)
    {
        $this->lastVersionId = $lastVersionId;
    }

    private function _setCurrentVersionId($currentVersionId)
    {
        $this->currentVersionId = $currentVersionId;
    }

    // static methods

    public static function stripSlashesForWiki($str)
    {
        return str_replace(
                '\\', "\\", str_replace(
                    '\"', '"', str_replace(
                        '\\"""', '\\\"""', $str) ) );
    }

}

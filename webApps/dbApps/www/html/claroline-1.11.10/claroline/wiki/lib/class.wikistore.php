<?php // $Id: class.wikistore.php 14581 2013-11-07 15:39:52Z zefredz $

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

require_once dirname(__FILE__) . "/class.wiki.php";

// Error codes
!defined("WIKI_NO_TITLE_ERROR") && define( "WIKI_NO_TITLE_ERROR", "Missing title" );
!defined("WIKI_NO_TITLE_ERRNO") && define( "WIKI_NO_TITLE_ERRNO", 1 );
!defined("WIKI_ALREADY_EXISTS_ERROR") && define( "WIKI_ALREADY_EXISTS_ERROR", "Wiki already exists" );
!defined("WIKI_ALREADY_EXISTS_ERRNO") && define( "WIKI_ALREADY_EXISTS_ERRNO", 2 );
!defined( "WIKI_CANNOT_BE_UPDATED_ERROR") && define( "WIKI_CANNOT_BE_UPDATED_ERROR", "Wiki cannot be updated" );
!defined( "WIKI_CANNOT_BE_UPDATED_ERRNO") && define( "WIKI_CANNOT_BE_UPDATED_ERRNO", 3 );
!defined( "WIKI_NOT_FOUND_ERROR") && define( "WIKI_NOT_FOUND_ERROR", "Wiki not found" );
!defined( "WIKI_NOT_FOUND_ERRNO") && define( "WIKI_NOT_FOUND_ERRNO", 4 );

/**
 * Class representing the WikiStore
 * (ie the place where the wiki are stored)
 */
class WikiStore
{
    // private fields
    private $con;

    // default configuration
    private $config = array(
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
    public function __construct( $con, $config = null )
    {
        if ( is_array( $config ) )
        {
            $this->config = array_merge( $this->config, $config );
        }
        $this->con = $con;
    }

    // load and save
    /**
     * Load a Wiki
     * @param int wikiId ID of the Wiki
     * @return Wiki the loaded Wiki
     */
    public function loadWiki( $wikiId )
    {
        $wiki = new Wiki( $this->con, $this->config );

        $wiki->load( $wikiId );

        if ( $wiki->hasError() )
        {
            $this->setError( $wiki->error, $wiki->errno );
        }

        return $wiki;
    }

    /**
     * Check if a page exists in a given wiki
     * @param int wikiId ID of the Wiki
     * @param string title page title
     * @return boolean
     */
    public function pageExists( $wikiId, $title )
    {
        return $this->con->query( "
            SELECT 
                `id` 
            FROM 
                `".$this->config['tbl_wiki_pages']."` 
            WHERE 
                `title` = ".$this->con->quote( $title )." 
            AND 
                `wiki_id` = " . $this->con->escape( $wikiId ) 
        )->numRows() > 0;
    }

    /**
     * Check if a wiki exists usind its ID
     * @param int id wiki ID
     * @return boolean
     */
    public function wikiIdExists( $wikiId )
    {
        return $this->con->query( "
            SELECT 
                `id` 
            FROM 
                `".$this->config['tbl_wiki_properties']."`
            WHERE 
                `id` = '". $this->con->escape( $wikiId )."'"
        )->numRows() > 0;
    }

    // Wiki methods

    /**
     * Get the list of the wiki's for a given group
     * @param int groupId ID of the group, Zero for a course
     * @return array list of the wiki's for the given group
     */
    public function getWikiListByGroup( $groupId )
    {
        return $this->con->query( "
            SELECT 
                `id`, 
                `title`, 
                `description` 
            FROM 
                `".$this->config['tbl_wiki_properties']."` 
            WHERE 
                `group_id` = ". $this->con->escape( $groupId ) . "
            ORDER BY 
                `id` ASC"
        );
    }

    /**
     * Get the list of the wiki's in a course
     * @return array list of the wiki's in the course
     * @see WikiStore::getWikiListByGroup( $groupId )
     */
    public function getCourseWikiList( )
    {
        return $this->getWikiListByGroup( 0 );
    }

    /**
     * Get the list of the wiki's in all groups (exept course wiki's)
     * @return array list of all the group wiki's
     */
    public function getGroupWikiList()
    {
        return $this->con->query( "
            SELECT 
                `id`, 
                `title`, 
                `description` 
            FROM 
                `".$this->config['tbl_wiki_properties']."` 
            WHERE 
                `group_id` != 0
            ORDER BY 
                `group_id` ASC"
        );
    }

    public function getNumberOfPagesInWiki( $wikiId )
    {
        if ( $this->wikiIdExists( $wikiId ) )
        {
            $result = $this->con->query( "
                SELECT 
                    COUNT( `id` ) as `pages` 
                FROM 
                    `".$this->config['tbl_wiki_pages']."` 
                WHERE 
                    `wiki_id` = " . $this->con->escape( $wikiId )
            )->fetch();

            return $result['pages'];
        }
        else
        {
            $this->setError( WIKI_NOT_FOUND_ERROR, WIKI_NOT_FOUND_ERRNO );
            return false;
        }
    }

    /**
     * Delete a Wiki from the store
     * @param int wikiId ID of the wiki
     * @return boolean true on success, false on failure
     */
    public function deleteWiki( $wikiId )
    {
        if ( $this->wikiIdExists( $wikiId ) )
        {
            // delete properties
            $affectedRows = $this->con->exec( "
                DELETE 
                FROM 
                    `".$this->config['tbl_wiki_properties']."` 
                WHERE `id` = " . $this->con->escape( $wikiId )
            );

            if ( $affectedRows < 1 )
            {
                return false;
            }

            // delete wiki acl
            $affectedRows = $this->con->exec( "
                DELETE 
                FROM 
                    `".$this->config['tbl_wiki_acls']."` 
                WHERE 
                    `wiki_id` = " . $this->con->escape( $wikiId )
            );

            if ( $affectedRows < 1 )
            {
                return false;
            }

            $pageIds = $this->con->query( "
                SELECT 
                    `id` 
                FROM 
                    `" . $this->config['tbl_wiki_pages'] . "` 
                WHERE 
                    `wiki_id` = " . $this->con->escape( $wikiId )
            );

            $idList = array();

            foreach ( $pageIds as $pageId )
            {
                $idList[] = (int) $pageId['id'];
            }
            
            if ( count( $idList) )
            {

                $idListStr = '(' . implode( ',', $idList ) . ')';

                $this->con->exec("
                    DELETE 
                    FROM 
                        `".$this->config['tbl_wiki_pages_content']."` 
                    WHERE 
                        `pid` IN " . $idListStr );
            }
            
            $this->con->exec( "
                DELETE 
                FROM 
                    `".$this->config['tbl_wiki_pages']."` 
                WHERE 
                    `wiki_id` = " . $this->con->escape( $wikiId )
            );

            return true;
        }
        else
        {
            $this->setError( WIKI_NOT_FOUND_ERROR, WIKI_NOT_FOUND_ERRNO );
            return false;
        }
    }

    // error handling

    private function setError( $errmsg = '', $errno = 0 )
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
            return $errno.' - '.$error;
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

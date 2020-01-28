<?php // $Id: class.wikisearchengine.php 14094 2012-03-22 13:34:16Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14094 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license GENERAL PUBLIC LICENSE (GPL)
 * This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 * as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 * through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 *
 * @author Frederic Minne <zefredz@gmail.com>
 *
 * @package Wiki
 */

!defined ( "CLWIKI_SEARCH_ANY" ) && define ( "CLWIKI_SEARCH_ANY", "CLWIKI_SEARCH_ANY" );
!defined ( "CLWIKI_SEARCH_ALL" ) && define ( "CLWIKI_SEARCH_ALL", "CLWIKI_SEARCH_ALL" );
!defined ( "CLWIKI_SEARCH_EXP" ) && define ( "CLWIKI_SEARCH_EXP", "CLWIKI_SEARCH_EXP" );

/**
 * Search engine for the Wiki
 */
class WikiSearchEngine
{
    private $con = null;

    private $config = array(
        'tbl_wiki_pages' => 'wiki_pages',
        'tbl_wiki_pages_content' => 'wiki_pages_content',
        'tbl_wiki_properties' => 'wiki_properties',
        'tbl_wiki_acls' => 'wiki_acls'
    );

    /**
     * Constructor
     * @param Database_Connection connection
     * @param Array config
     */
    public function __construct( $connection, $config = null )
    {
        if ( is_array( $config ) )
        {
            $this->config = array_merge( $this->config, $config );
        }

        $this->con = $connection;
    }

    /**
     * Search for a given pattern in Wiki pages in a given Wiki
     * @param int wikiId
     * @param String pattern
     * @param Const mode
     * @return Array of Wiki pages
     */
    public function searchInWiki( $pattern, $wikiId, $mode = CLWIKI_SEARCH_ANY )
    {
        $searchStr = $this->makePageSearchQuery( $pattern, null, $mode );

        return $this->con->query( "
            SELECT 
                p.`id`, p.`wiki_id`, p.`title`, c.`content`
            FROM 
                `" .$this->config['tbl_wiki_properties']."` AS w, 
                `" . $this->config['tbl_wiki_pages']."` AS p, 
                `" . $this->config['tbl_wiki_pages_content']."` AS c 
            WHERE 
                p.`wiki_id` = " . $this->con->escape( $wikiId ) . "
            AND " 
                . $searchStr
        );
    }

    /**
     * Search for a given pattern in Wiki pages in a given Wiki, light version
     * @param int wikiId
     * @param String pattern
     * @param Const mode
     * @return Array of Wiki pages ids and titles
     */
    public function lightSearchInWiki( $wikiId, $pattern, $mode = CLWIKI_SEARCH_ANY )
    {
        $searchStr = $this->makePageSearchQuery( $pattern, null, $mode );

        return $this->con->query( "
            SELECT 
                p.`wiki_id`, p.`title`
            FROM 
                `" .$this->config['tbl_wiki_properties']."` AS w, 
                `" . $this->config['tbl_wiki_pages']."` AS p, 
                `" . $this->config['tbl_wiki_pages_content']."` AS c 
            WHERE 
                p.`wiki_id` = " . $this->con->escape( $wikiId ) . "
            AND " 
                . $searchStr
        );
    }

    /**
     * Search for a given pattern in all Wiki pages
     * @param String pattern
     * @param int groupId (default null) FIXME magic value !
     * @param Const mode
     * @return Array of Wiki properties
     */
    public function searchAllWiki( $pattern, $groupId = null, $mode = CLWIKI_SEARCH_ANY, $getPageTitles = false )
    {
        $ret = array();
        
        $wikiList = array();

        $searchPageStr = $this->makePageSearchQuery( $pattern, $groupId, $mode );

        /*$groupStr = ( ! is_null( $groupId ) )
            ? "( w.`group_id` = " . (int) $groupId . " ) AND"
            : ""
            ;*/

        $searchWikiStr = $this->makeWikiPropertiesSearchQuery( $pattern, $groupId, $mode );

        $wikiList = $this->con->query( "
            SELECT 
                DISTINCT w.`id`, w.`title`, w.`description` 
            FROM 
                `" . $this->config['tbl_wiki_properties']."` AS w, 
                `" . $this->config['tbl_wiki_pages']."` AS p, 
                `" . $this->config['tbl_wiki_pages_content']."` AS c 
            WHERE 
                " . $searchPageStr . "
            OR 
                " . $searchWikiStr
        );

        if ( count( $wikiList ) )
        {
            # search for Wiki pages
            foreach ( $wikiList as $wiki )
            {
                if ( true === $getPageTitles )
                {
                    $pages = $this->lightSearchInWiki( $wiki['id'], $pattern, $mode );
                    
                    if ( false !== $pages && !is_null( $pages) )
                    {
                        $wiki['pages'] = is_null($pages) ? array() : $pages;
                    }
                    else
                    {
                        return false;
                    }
                }
                
                $ret[] = $wiki;
            }

            unset( $wikiList );
        }

        return $ret;
    }

    // utility functions

    /**
     * Split a search pattern for the given search mode
     * @param String pattern
     * @param Const mode
     * @return Array ( keywords, implode_word )
     */
    private function splitPattern( $pattern, $mode = CLWIKI_SEARCH_ANY )
    {
        $pattern = claro_sql_escape( $pattern );
        $pattern = str_replace('_', '\_', $pattern);
        $pattern = str_replace('%', '\%', $pattern);
        $pattern = str_replace('?', '_' , $pattern);
        $pattern = str_replace('*', '%' , $pattern);

        switch( $mode )
        {
            case CLWIKI_SEARCH_ALL:
            {
                $impl = "AND";
                $keywords = preg_split( '~\s~', $pattern );
                break;
            }
            case CLWIKI_SEARCH_EXP:
            {
                $impl = "";
                $keywords = array( $pattern );
                break;
            }
            case CLWIKI_SEARCH_ANY:
            default:
            {
                $impl = "OR";
                $keywords = preg_split( '~\s~', $pattern );
                break;
            }
        }
        
        $ret = array( $keywords, $impl );

        return $ret;
    }

    /**
     * Generate search string for a given pattern in wiki pages
     * @param String pattern
     * @param Const mode
     * @return String
     */
    private function makePageSearchQuery( $pattern, $groupId = null, $mode = CLWIKI_SEARCH_ANY )
    {
        list( $keywords, $impl ) = $this->splitPattern( $pattern, $mode );

        $searchTitleArr = array();
        $searchPageArr = array();

        $groupstr = ( ! is_null( $groupId ) )
            ? "( w.`group_id` = " . $this->con->escape($groupId) . "  AND w.`id` = p.`wiki_id`)"
            : "(w.`id` = p.`wiki_id`)"
            ;

        foreach ( $keywords as $keyword )
        {
            $searchTitleArr[] = " p.`title` LIKE '%".$this->con->escape($keyword)."%' ";
            $searchPageArr[] = " c.`content` LIKE '%".$this->con->escape($keyword)."%' ";
        }

        $searchTitle = implode ( $impl, $searchTitleArr );

        if ( count ( $searchTitleArr ) > 1 )
        {
            $searchTitle = " ( " . $searchTitle . ") ";
        }

        $searchPage = implode ( $impl, $searchPageArr );

        if ( count ( $searchPageArr ) > 1 )
        {
            $searchPage = " ( " . $searchPage . ") ";
        }

        $searchStr = "( ".$groupstr." AND c.`id` = p.`last_version` AND " . $searchTitle . " ) OR "
            . "( ".$groupstr." AND c.`id` = p.`last_version` AND " . $searchPage . " )"
            ;

        return "($searchStr)";
    }

    /**
     * Generate search string for a given pattern in wiki properties
     * @param String pattern
     * @param Const mode
     * @return String
     */
    private function makeWikiPropertiesSearchQuery( $pattern, $groupId = null, $mode = CLWIKI_SEARCH_ANY )
    {
        list( $keywords, $impl ) = $this->splitPattern( $pattern, $mode );

        $searchTitleArr = array();

        $groupstr = ( ! is_null( $groupId ) )
            ? "( w.`group_id` = " . $this->con->escape( $groupId ) . "  AND w.`id` = p.`wiki_id`)"
            : "(w.`id` = p.`wiki_id`)"
            ;

        foreach ( $keywords as $keyword )
        {
            $searchTitleArr[] = $groupstr." AND (w.`title` LIKE '%".$this->escape(keyword)."%' "
                . "OR w.`description` LIKE '%".$this->con->escape($keyword)."%') "
                ;
        }

        $searchStr = implode ( $impl, $searchTitleArr );

        return "($searchStr)";
    }

    // error handling

    private $error = null;
    
    private $errno = 0;

    private function setError( $errmsg = '', $errno = 0 )
    {
        $this->error = ($errmsg != '') ? $errmsg : "Unknown error";
        $this->errno = $errno;
    }

    public function getError()
    {
        if (! is_null( $this->error ) )
        {

            $errno = $this->errno;
            $error = $this->error;
            $this->error = null;
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
        return ( ! is_null( $this->error ) );
    }
}

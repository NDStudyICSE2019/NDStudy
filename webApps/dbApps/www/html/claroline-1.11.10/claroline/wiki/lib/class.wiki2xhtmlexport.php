<?php // $Id: class.wiki2xhtmlexport.php 14585 2013-11-08 12:36:31Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14585 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
 * as published by the FREE SOFTWARE FOUNDATION. The GPL is available
 * through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
 *
 * @author Frederic Minne <zefredz@gmail.com>
 */

require_once dirname(__FILE__) . '/class.wiki2xhtmlrenderer.php';
require_once dirname(__FILE__) . '/class.wikistore.php';
require_once dirname(__FILE__) . '/class.wikipage.php';
require_once dirname(__FILE__) . '/class.wiki.php';

/**
 * Export a Wiki to a single HTML formated string
 * @todo    some refatoring
 */
class WikiToSingleHTMLExporter extends Wiki2xhtmlRenderer
{
    protected $wiki;
    protected $style = '';

    /**
     * Constructor
     * @param   $wiki Wiki, Wiki to export
     */
    public function __construct( $wiki )
    {
        parent::__construct( $wiki );
        $this->setOpt( 'first_title_level', 3 );
        $this->setOpt('note_str','<div class="footnotes"><h5>Notes</h5>%s</div>');
        $this->wiki =& $wiki;
    }

    /**
     * Export a whole Wiki to a single HTML String
     * @return  string Wiki content in HTML
     */
    public function export()
    {
        $pageList = $this->wiki->allPagesByCreationDate();

        $result = $this->_htmlHeader();

        $result .= '<h1>' . $this->wiki->getTitle() . '</h1>' . "\n";

        foreach ( $pageList as $page )
        {
            $wikiPage = new WikiPage(
                $this->wiki->getDatabaseConnection()
                , $this->wiki->getConfig()
                , $this->wiki->getWikiId() );

            $wikiPage->loadPage($page['title']);

            $this->setOpt('note_prefix', $page['title']);

            if ( $wikiPage->hasError() )
            {
                $result .= '<h2><a name="'
                    . $this->_makePageTitleAnchor( $page['title'] ) .'">'
                    . $page['title']
                    . '</a></h2>'
                    . "\n"
                    ;

                $result .= get_lang( "Could not load page %page"
                    , array( '%page' => $page['title'] ) ) . "\n";
                $wikiPage = null;
            }
            else
            {
                $pgTitle = $wikiPage->getTitle();

                if ( '__MainPage__' === $pgTitle )
                {
                    $pgTitle = get_lang( 'Main page' );
                }

                $result .= '<h2><a name="'
                    . $this->_makePageTitleAnchor( $page['title'] ) .'">'
                    . $pgTitle
                    .'</a></h2>'
                    . "\n"
                    ;

                $content = $wikiPage->getContent();
                $result .= $this->render($content) . "\n";

                $wikiPage = null;
            }
        }

        $result .= $this->_htmlFooter();

        return $result;
    }

    // private methods

    /**
     * Make HTML anchor name from page title
     * @access  private
     * @return  string anchor name
     * @todo    implement...
     */
    private function _makePageTitleAnchor( $pageTitle )
    {
        return $pageTitle;
    }

    /**
     * Get Wiki style sheet
     * @access  private
     * @return  string CSS style to insert in HTML (style tags already added)
     * @todo    remove style tags and add support for multiple media
     */
    private function _getWikiStyle()
    {
        $style = '<style type="text/css" media="screen">
h1{
    color: Black;
    background: none;
    font-size: 200%;
    font-weight: bold;
    border-bottom: 2px solid #aaaaaa;
}
h2,h3,h4{
    color: Black;
    background: none;
}
h2{
    border-bottom: 1px solid #aaaaaa;
    font-size:175%;
    font-weight:bold;
}
h3{
    border-bottom: 1px groove #aaaaaa;
    font-size:150%;
    font-weight:bold;
}
h4{
    font-size:125%;
    font-weight:bold;
}
h5{
    font-size: 100%;
    font-style: italic;
    border-bottom: 1px groove #aaaaaa;
}

a.wikiEdit{
    color: red;
}

table {
    border: black outset 1px;
}
td {
    border: black inset 1px;
}
</style>' . "\n";

        return $style;
    }

    /**
     * Generate HTML page header
     * @access  private
     * @return  string HTML header
     */
    private function _htmlHeader()
    {
        $header = '<html>' . "\n" . '<head>' . "\n"
            . '<meta charset="' . get_conf ('charset') . '">' . "\n"
            . '<title>' . $this->wiki->getTitle() . '</title>' . "\n"
            . $this->_getWikiStyle()
            . '</head>' . "\n" . '<body>' . "\n"
            ;

        return $header;
    }

    /**
     * Generate HTML page footer
     * @access  private
     * @return  string HTML footer
     */
    private function _htmlFooter()
    {
        $footer = '</body>' . "\n" . '</html>' . "\n";

        return $footer;
    }

    // Wiki2XHTML private methods

    /**
     * @see Wiki2xhtmlRenderer
     */
    protected function parseWikiWord( $str, &$tag, &$attr, &$type )
    {
        $tag = '';
        $attr = '';

        if ( $this->wiki->pageExists( $str ) )
        {
            return '<a href="#'.$this->_makePageTitleAnchor( $str )
                . '" class="wikiShow">'
                . $str
                . '</a>'
                ;
        }
        else
        {
            return '<span class="wikiEdit">'
                . $str
                . '</span>'
                ;
        }
    }

    /**
     * @see Wiki2xhtmlRenderer
     */
    protected function _getWikiPageLink( $pageName, &$tag, &$attr, &$type )
    {
        // allow links to use wikiwords for wiki page locations
        if ($this->getOpt('active_wikiwords') && $this->getOpt('words_pattern'))
        {
            $pageName = preg_replace('/¶¶¶'.$this->getOpt('words_pattern').'¶¶¶/msU', '$1', $pageName);
        }

        if ($this->wiki->pageExists( $pageName ) )
        {
            $attr = ' href="#' . $this->_makePageTitleAnchor( $pageName )
                . '" class="wikiShow"'
                ;
        }
        else
        {
            # FIXME
            $attr = ' class="wikiEdit"';
            $tag = 'span';
        }
    }
}

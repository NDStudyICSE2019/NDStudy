<?php // $Id: breadcrumbs.lib.php 14414 2013-03-20 09:19:53Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * BreadCrumbs.
 *
 * @version     1.9 $Revision: 14414 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

/*
 *  Usage :
 *
 *      ClaroBreadCrumbs::getInstance()->prepend( $name, $url );
 *      ClaroBreadCrumbs::getInstance()->current( $name, $url );
 *      ClaroBreadCrumbs::getInstance()->append( $name, $url );
 *
 *      Platform, course and group are added automaticaly
 *
 *  Sample :
 *
 *      ClaroBreadCrumbs::getInstance()->prepend( 'b' );
 *      ClaroBreadCrumbs::getInstance()->prepend( 'a' );
 *      ClaroBreadCrumbs::getInstance()->setCurrent( 'c' );
 *      ClaroBreadCrumbs::getInstance()->prepend( 'd' );
 *      ClaroBreadCrumbs::getInstance()->prepend( 'e' );
 *
 *      --> a > b > c > d > e
 *
 * FIXME : reverse order for prepend !!!!
 *
 * @version     1.9 $Revision: 14414 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     display
 */
 
class BreadCrumbs implements Display
{
    // protected $breadCrumbs = array();
    protected $prependBc    = array();
    protected $currentNode  = array();
    protected $appendBc     = array();
    
    public function render()
    {
        if ( $this->isEmpty() )
        {
            return '';
        }
        
        $breadCrumbs = array_merge(
            array_reverse($this->prependBc),
            $this->currentNode,
            $this->appendBc );
        
        $lastNode = count( $breadCrumbs ) - 1;
        $currentNode = 0;

        $out = '<ul class="breadCrumbs">' . "\n";

        $nodeList = array();

        foreach ( $breadCrumbs as $node )
        {
            $nodeStr = '';

            if ( $currentNode == $lastNode )
            {
                $nodeStr .= '<li class="breadCrumbsNode lastBreadCrumbsNode">';
            }
            elseif ( $currentNode == 0 )
            {
                $nodeStr .= '<li class="breadCrumbsNode firstBreadCrumbsNode">';
            }
            else
            {
                $nodeStr .= '<li class="breadCrumbsNode">';
            }

            $nodeStr .= $node->render();

            if ( $currentNode == $lastNode )
            {
                $nodeStr .= '</li>';
            }
            else
            {
                $nodeStr .= '&nbsp;&gt;&nbsp;</li>';
            }

            $nodeList[] = $nodeStr;

            $currentNode++;
        }

        $out .= implode ( "\n", $nodeList );

        $out .= "\n" . '</ul>' . "\n";

        return $out;
    }
    
    public function append( $name, $url = null, $icon = null )
    {
        $this->appendNode( new BreadCrumbsNode( $name, $url, $icon ) );
    }

    public function prepend( $name, $url = null, $icon = null )
    {
        $this->prependNode( new BreadCrumbsNode( $name, $url, $icon ) );
    }
    
    public function setCurrent( $name, $url = null, $icon = null )
    {
        $this->setCurrentNode( new BreadCrumbsNode( $name, $url, $icon ) );
    }
    
    public function setCurrentNode( $node )
    {
        $this->currentNode = array( $node );
    }

    public function appendNode( $node )
    {
        $this->appendBc[] = $node;
    }

    public function prependNode( $node )
    {
        $this->prependBc[] = $node;
    }
    
    public function size()
    {
        return count( $this->prependBc ) +
            count( $this->currentNode ) +
            count( $this->appendBc )
            ;
    }
    
    public function isEmpty()
    {
        return $this->size() == 0;
    }
}

class BreadCrumbsNode
{
    private $name, $url, $icon;

    public function __construct( $name, $url = null, $icon = null )
    {
        $this->icon = $icon;
        $this->name = $name;
        $this->url = $url;
    }

    public function render()
    {
        $nodeHtml = '';

        if ( ! empty( $this->url ) )
        {
            $nodeHtml .= '<a href="'.$this->url.'"  target="_top">';
        }

        if ( ! empty( $this->icon ) )
        {
            $nodeHtml .= claro_html_icon( 'home', null, null ).'&nbsp;';
        }

        $nodeHtml .= $this->name;

        if ( ! empty( $this->url ) )
        {
            $nodeHtml .= '</a>';
        }

        return $nodeHtml;
    }
}

class ClaroBreadCrumbs extends BreadCrumbs
{
    private static $instance = false;
    // private $breadCrumbs = array();
    
    private function __construct()
    {
    }
    
    public function init()
    {
        $this->_compatVars();
        $this->autoPrepend();
    }
    
    public function render()
    {
        $this->init();
        
        return parent::render();
    }
    
    private function autoPrepend()
    {
        if ( empty( $this->currentNode )
            && array_key_exists( 'nameTools', $GLOBALS ) )
        {
            $name = $GLOBALS['nameTools'];
            
            if ( array_key_exists( 'noPHP_SELF', $GLOBALS )
                && $GLOBALS['noPHP_SELF'] )
            {
                $url = null;
            }
            elseif ( array_key_exists( 'noQUERY_STRING', $GLOBALS )
                && $GLOBALS['noQUERY_STRING'] )
            {
                $url = $_SERVER['PHP_SELF'];
            }
            else
            {
                if ( ! array_key_exists( 'noQUERY_STRING', $_SERVER ) )
                {
                    $url = $_SERVER['PHP_SELF'];
                }
                else
                {
                    $url  = $_SERVER['PHP_SELF'] .'?'. claro_htmlspecialchars(strip_tags($_SERVER['QUERY_STRING']));
                }
            }
            
            $url = claro_htmlspecialchars( Url::Contextualize( $url ) );
            
            $this->setCurrentNode( new BreadCrumbsNode( $name, $url ) );
        }
        
        if ( claro_is_in_a_group() )
        {
            $this->prependNode( new BreadCrumbsNode( claro_get_current_group_data('name')
                , claro_htmlspecialchars( get_module_url('CLGRP') . '/group_space.php?cidReq='
                    . claro_htmlspecialchars(claro_get_current_course_id())
                    .'&gidReq=' . (int) claro_get_current_group_id() ) ) );
            $this->prependNode( new BreadCrumbsNode( get_lang('Groups')
                , claro_htmlspecialchars( get_module_url('CLGRP') . '/index.php?cidReq='
                    . claro_htmlspecialchars(claro_get_current_course_id()) ) ) );
        }
        
        if ( claro_is_in_a_course() )
        {
            $this->prependNode( new BreadCrumbsNode( claro_get_current_course_data('officialCode')
                , claro_htmlspecialchars( get_path('clarolineRepositoryWeb') . 'course/index.php?cid='
                    . claro_get_current_course_id() ) ) );
        }
            
        $this->prependNode( new BreadCrumbsNode( get_conf('siteName')
            , claro_htmlspecialchars( get_path('url') . '/index.php' )
            , get_icon_url('home') ) );
    }
    
    private function _compatVars()
    {
        if ( array_key_exists( 'interbredcrump', $GLOBALS )
            && is_array( $GLOBALS['interbredcrump'] ) )
        {
            foreach ( $GLOBALS['interbredcrump'] as $node )
            {
                $this->append( $node['name'], $node['url'] );
            }
        }
    }
    
    public static function getInstance()
    {
        if ( ! ClaroBreadCrumbs::$instance )
        {
            ClaroBreadCrumbs::$instance = new ClaroBreadCrumbs;
        }

        return ClaroBreadCrumbs::$instance;
    }
}
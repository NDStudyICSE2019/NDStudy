<?php // $Id: linker.cnr.php 13348 2011-07-18 13:58:28Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Resource Resolver for the Wiki tool.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      claroline Team <cvs@claroline.net>
 * @package     CLWIKI
 */

class CLWIKI_Resolver implements ModuleResourceResolver
{
    public function resolve ( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            $parts = explode( '/', ltrim( $locator->getResourceId(), '/' ) );
            
            if( count($parts) == 1 )
            {
                $url = new Url( get_module_url('CLWIKI') . '/wiki.php' );
                $url->addParam( 'wikiId', (int) $parts[0] );
                
                return $url->toUrl();
            }
            elseif( count( $parts ) == 2 )
            {
                $url = new Url( get_module_url('CLWIKI') . '/page.php' );
                $url->addParam( 'wikiId', (int) $parts[0] );
                $url->addParam( 'title', $parts[1] );
                
                return $url->toUrl();
            }
            else
            {
                return get_module_entry_url( 'CLWIKI' );
            }
        }
        else
        {
            return get_module_entry_url('CLWIKI');
        }
    }

    public function getResourceName( ResourceLocator $locator)
    {
        if ( $locator->hasResourceId() )
        {
            $parts = explode( '/', ltrim( $locator->getResourceId(), '/' ) );
            
            $tbl = get_module_course_tbl( array('wiki_properties'), $locator->getCourseId() );
            
            if( count($parts) == 1 )
            {
                $sql = "SELECT `title`\n"
                    . "FROM `".$tbl['wiki_properties']."`\n"
                    . "WHERE `id` = ". (int) $parts[0]
                    ;
                
                $res = Claroline::getDatabase()->query($sql);
                $res->setFetchMode( Database_ResultSet::FETCH_VALUE );
                
                return $res->fetch();
            }
            elseif( count( $parts ) == 2 )
            {
                $sql = "SELECT `title`\n"
                    . "FROM `".$tbl['wiki_properties']."`\n"
                    . "WHERE `id` = ". (int) $parts[0]
                    ;
                
                $res = Claroline::getDatabase()->query($sql);
                $res->setFetchMode( Database_ResultSet::FETCH_VALUE );
                
                $pageName = ( $parts[1] == '__MainPage__' )
                    ? get_lang("Main page")
                    : rawurldecode( $parts[1] )
                    ;
                
                return $res->fetch() . ' > ' . $pageName;
            }
            else
            {
                $moduleName = get_module_data('CLWIKI', 'moduleName' );
                return get_lang( $moduleName );
            }
        }
        else
        {
            $moduleName = get_module_data('CLWIKI', 'moduleName' );
            return get_lang( $moduleName );
        }
    }
}

class CLWIKI_Navigator implements ModuleResourceNavigator
{
    public function getResourceId( $params = array() )
    {
        if ( isset( $params['wikiId'] ) )
        {
            $resourceId = $params['wikiId'];
            
            if ( isset( $params['pageId'] ) )
            {
                $resourceId .= '/' . rawurlencode( $params['pageId'] );
            }
            
            return $resourceId;
        }
        else
        {
            return false;
        }
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        if (  $locator->hasResourceId() )
        {
            $parts = explode( '/', ltrim( $locator->getResourceId(), '/' ) );
            
            if ( count( $parts ) <= 1 )
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
            return $locator->inModule() && $locator->getModuleLabel() == 'CLWIKI';
        }
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            $parts = explode( '/', ltrim( $locator->getResourceId(), '/' ) );
            
            if ( count($parts) == 2 )
            {
                return $parts[0];
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
    
    public function getResourceList( ResourceLocator $locator )
    {
        $tbl = get_module_course_tbl( array('wiki_properties','wiki_pages'), $locator->getCourseId() );
        
        if ( $locator->inGroup() )
        {
            $groupSql = "WHERE group_id = "
                . Claroline::getDatabase()->escape($locator->getGroupId())
                ;
        }
        else
        {
            $groupSql = "WHERE group_id = 0";
        }
        
        $resourceList = new LinkerResourceIterator;
        
        if ( $locator->hasResourceId() )
        {
            $parts = explode( '/', ltrim( $locator->getResourceId(), '/' ) );
            
            if ( count( $parts ) == 1 )
            {
                $sql = "SELECT `title`\n"
                    . "FROM `{$tbl['wiki_pages']}`\n"
                    . "WHERE wiki_id = " . Claroline::getDatabase()->escape($parts[0])
                    ;
                
                $res = Claroline::getDatabase()->query($sql);
                
                foreach ( $res as $page )
                {
                    $pageLoc = new ClarolineResourceLocator(
                        $locator->getCourseId(),
                        'CLWIKI',
                        (int) $parts[0] . '/' . rawurlencode( $page['title'] )
                    );
                    
                    $pageResource = new LinkerResource(
                        ( $page['title'] == '__MainPage__'
                            ? get_lang('Main page')
                            : $page['title'] ),
                        $pageLoc,
                        true,
                        true,
                        false
                    );
                    
                    $resourceList->addResource( $pageResource );
                }
            }
        }
        else
        {
            $sql = "SELECT `id`, `title`\n"
                . "FROM `{$tbl['wiki_properties']}`\n"
                . $groupSql
                ;
            
            $res = Claroline::getDatabase()->query($sql);
            
            foreach ( $res as $wiki )
            {
                $wikiLoc = new ClarolineResourceLocator(
                    $locator->getCourseId(),
                    'CLWIKI',
                    (int) $wiki['id']
                );
                
                $wikiResource = new LinkerResource(
                    $wiki['title'],
                    $wikiLoc,
                    true,
                    true,
                    true
                );
                
                $resourceList->addResource( $wikiResource );
            }
        }
        
        return $resourceList;
    }
}
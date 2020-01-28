<?php // $Id: linker.cnr.php 14166 2012-05-29 08:44:18Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Resource Resolver for the assignment tool.
 *
 * @version     Claroline 1.11 $Revision: 14166 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      claroline Team <cvs@claroline.net>
 * @package     CLWIKI
 */

class CLWRK_Resolver implements ModuleResourceResolver
{
    public function resolve ( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            $assignement_id = $locator->getResourceId();
            
           
            $url = new Url( get_module_url('CLWRK') . '/work_list.php' );
            $url->addParam( 'assigId', (int) $assignement_id );

            return $url->toUrl();
            
        }
        else
        {
            return get_module_entry_url('CLWRK');
        }
    }

    public function getResourceName( ResourceLocator $locator)
    {
        if ( $locator->hasResourceId() )
        {
            $assignementId =  $locator->getResourceId();
            
            $tbl = get_module_course_tbl( array('wrk_assignment'), $locator->getCourseId() );
            
            
            $sql = "SELECT `title`\n"
                . "FROM `".$tbl['wrk_assignment']."`\n"
                . "WHERE `id` = ". (int) $assignementId
                ;

            $res = Claroline::getDatabase()->query($sql);
            $res->setFetchMode( Database_ResultSet::FETCH_VALUE );

            return $res->fetch();
            
        }
        else
        {
            $moduleName = get_module_data('CLWRK', 'moduleName' );
            return get_lang( $moduleName );
        }
    }
}

class CLWRK_Navigator implements ModuleResourceNavigator
{
    public function getResourceId( $params = array() )
    {
        if ( isset( $params['assignementId'] ) )
        {
            $resourceId = $params['assignementId'];
            
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
            return false;
        }
        else
        {
            return $locator->inModule() && $locator->getModuleLabel() == 'CLWRK';
        }
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        return false;
    }
    
    public function getResourceList( ResourceLocator $locator )
    {
        $tbl = get_module_course_tbl( array('wrk_assignment'), $locator->getCourseId() );
        
        $resourceList = new LinkerResourceIterator;
        
        if ( !$locator->hasResourceId() )
        {
            $sql = "SELECT `title`, `visibility`, `id`\n"
                . "FROM `{$tbl['wrk_assignment']}`\n"
                ;

            $res = Claroline::getDatabase()->query($sql);

            foreach ( $res as $assig )
            {
                $loc = new ClarolineResourceLocator(
                    $locator->getCourseId(),
                    'CLWRK',
                    (int) $assig['id']
                );

                $resource = new LinkerResource(
                    $assig['title'],
                    $loc,
                    true,
                    $assig['visibility'] == 'VISIBLE',
                    false
                );

                $resourceList->addResource( $resource );
            }
        }
        
        return $resourceList;
    }
}

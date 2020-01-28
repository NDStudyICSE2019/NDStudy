<?php // $Id: linker.cnr.php 14358 2013-01-24 12:35:36Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Resource Resolver for the Annoucements tool
 *
 * @version     $Revision: 14358 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline Team <cvs@claroline.net>
 * @package     CLANN
 */

FromKernel::uses('fileManage.lib', 'file.lib');

class CLANN_Resolver implements ModuleResourceResolver
{
    public function resolve ( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            return get_module_entry_url('CLANN') . "#ann{$locator->getResourceId()}";
        }
        else
        {
            return get_module_entry_url('CLANN');
        }
    }

    public function getResourceName( ResourceLocator $locator)
    {
        if ( ! $locator->hasResourceId() )
        {
            return false;
        }
        
        $tbl = get_module_course_tbl( array('announcement'), $locator->getCourseId() );
        
        $sql = "SELECT `title`\n"
            . "FROM `{$tbl['announcement']}`\n"
            . "WHERE `id`=". Claroline::getDatabase()->escape( $locator->getResourceId() )
            ;
        
        $res = Claroline::getDatabase()->query($sql);
        $res->setFetchMode(Database_ResultSet::FETCH_VALUE);
        
        $title = $res->fetch();
        
        if ( $title )
        {
            $title = trim ( $title );
        
            if ( empty( $title ) )
            {
                $title = get_lang('Untitled');
            }

            return $title;
        }
        else
        {
            Console::debug ("Cannot load ressource " 
                . var_export( $locator, true )
                . " in " . __CLASS__
                . " : query returned " 
                . var_export( $title, true ) );
            return null;
        }
    }
}

class CLANN_Navigator implements ModuleResourceNavigator
{
    public function getResourceId( $params = array() )
    {
        if ( isset( $params['id'] ) )
        {
            return $params['id'];
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
            return $locator->inModule() && $locator->getModuleLabel() == 'CLANN';
        }
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        return false;
    }
    
    public function getResourceList( ResourceLocator $locator )
    {
        $tbl = get_module_course_tbl( array('announcement'), $locator->getCourseId() );
        
        $sql = "SELECT `id`, `title`, `visibility`\n"
            . "FROM `{$tbl['announcement']}`"
            ;
        
        $res = Claroline::getDatabase()->query($sql);
        
        $resourceList = new LinkerResourceIterator;
        
        foreach ( $res as $annoucement )
        {
            $annoucementLoc = new ClarolineResourceLocator(
                $locator->getCourseId(),
                'CLANN',
                (int) $annoucement['id']
            );
            
            $annoucementResource = new LinkerResource(
                ( empty( $annoucement['title'] )
                    ? get_lang('Untitled')
                    : $annoucement['title'] ),
                $annoucementLoc,
                true,
                ( $annoucement['visibility'] == 'HIDE'
                    ? false
                    : true ),
                false
            );
            
            $resourceList->addResource( $annoucementResource );
        }
        
        return $resourceList;
    }
}
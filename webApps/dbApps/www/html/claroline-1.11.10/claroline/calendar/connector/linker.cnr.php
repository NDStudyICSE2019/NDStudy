<?php // $Id: linker.cnr.php 14356 2013-01-24 12:26:38Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Resource Resolver for the Calendar tool
 *
 * @version     $Revision: 14356 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline Team <cvs@claroline.net>
 * @package     CLCAL
 */

FromKernel::uses('fileManage.lib', 'file.lib');

class CLCAL_Resolver implements ModuleResourceResolver
{
    public function resolve ( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            return get_module_entry_url('CLCAL') . "#item{$locator->getResourceId()}";
        }
        else
        {
            return get_module_entry_url('CLCAL');
        }
    }

    public function getResourceName( ResourceLocator $locator )
    {
        if ( ! $locator->hasResourceId() )
        {
            return false;
        }
        
        $tbl = get_module_course_tbl( array('calendar_event'), $locator->getCourseId() );
        
        $sql = "SELECT `titre`,`day`\n"
            . "FROM `{$tbl['calendar_event']}`\n"
            . "WHERE `id`=". Claroline::getDatabase()->escape( $locator->getResourceId() )
            ;
        
        $res = Claroline::getDatabase()->query($sql);
        $res->setFetchMode(Database_ResultSet::FETCH_OBJECT);
        $event = $res->fetch();
        
        if ( $event )
        {
            $titre = trim( $event->titre );

            if ( empty( $titre ) )
            {
                $titre = $event->day;
            }

            return $titre;
        }
        else
        {
            Console::debug ("Cannot load ressource " 
                . var_export( $locator, true )
                . " in " . __CLASS__
                . " : query returned " 
                . var_export( $event, true ) );
            return null;
        }
    }
}

class CLCAL_Navigator implements ModuleResourceNavigator
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
            return $locator->inModule() && $locator->getModuleLabel() == 'CLCAL';
        }
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        return false;
    }
    
    public function getResourceList( ResourceLocator $locator )
    {
        $tbl = get_module_course_tbl( array('calendar_event'), $locator->getCourseId() );
        
        $sql = "SELECT `id`, `titre`, `day`, `visibility`\n"
            . "FROM `{$tbl['calendar_event']}`"
            ;
        
        $res = Claroline::getDatabase()->query($sql);
        
        $resourceList = new LinkerResourceIterator;
        
        foreach ( $res as $event )
        {
            $eventLoc = new ClarolineResourceLocator(
                $locator->getCourseId(),
                'CLCAL',
                (int) $event['id']
            );
            
            $eventResource = new LinkerResource(
                ( empty( $event['titre'] )
                    ? $event['day']
                    : $event['titre'] ),
                $eventLoc,
                true,
                ( $event['visibility'] == 'HIDE'
                    ? false
                    : true ),
                false
            );
            
            $resourceList->addResource( $eventResource );
        }
        
        return $resourceList;
    }
}
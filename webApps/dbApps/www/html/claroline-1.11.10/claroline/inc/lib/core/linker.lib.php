<?php // $Id: linker.lib.php 14587 2013-11-08 12:47:41Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:
/*
 FIXME upgrade old CRL :
 
 foreach ( $oldResource as $resource )
 {
    $sql = "UPDATE {$tbl['lnk_resource']}
    SET crl = '".convert_crl_from_18_to_19( $resource['crl'] )."'
    WHERE crl = '{$resource['crl']}'"
    
    claro_Sql_query( $sql );
 }
 
 function convert_crl_from_18_to_19( $crl )
 {
    if (preg_match(
        '!(crl://'.get_conf('platform_id').'/[^/]+/)([^/])(.*)!'),
        $crl, $matches ) )
    {
        return $matches[1] . rtrim( $matches[2], '_' ) . $matches[3];
    }
    elseif (preg_match(
        '!(crl://'.get_conf('platform_id').'/[^/]+/groups/\d+/)([^/])(.*)!'),
        $crl, $matches ) )
    {
        return $matches[1] . rtrim( $matches[2], '_' ) . $matches[3];
    }
    else
    {
        return $crl;
    }
 }
 */

/**
 * CLAROLINE
 *
 * Claroline Resource Linker library.
 *
 * @version     $Revision: 14587 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 */

require_once dirname(__FILE__) . '/url.lib.php';
require_once dirname(__FILE__) . '/../utils/iterators.lib.php';
require_once dirname(__FILE__) . '/../group.lib.inc.php';

interface ResourceLocator
{
}

/**
 * Define a Claroline resource locator and provides a static method to parse a CRL into a locator
 *
 */
class ClarolineResourceLocator implements ResourceLocator
{
    protected $platformId,
            $courseId,
            $moduleLabel,
            $resourceId,
            $teamId;
            
    public function __construct(
            $courseId = null,
            $moduleLabel = null,
            $resourceId = null,
            $teamId = null )
    {
        $this->platformId = get_conf('platform_id');
        $this->courseId = $courseId;
        $this->moduleLabel = rtrim( $moduleLabel, '_' );
        $this->resourceId = $resourceId;
        $this->teamId = $teamId;
    }
    
    public function getPlatformId()
    {
        return $this->platformId;
    }
    
    public function setPlatformId( $platformId )
    {
        $this->platformId = $platformId;
    }
    
    public function getCourseId()
    {
        return $this->courseId;
    }
    
    public function setCourseId( $courseId )
    {
        $this->courseId = $courseId;
    }
    
    public function inCourse()
    {
        return !empty( $this->courseId );
    }
    
    public function getModuleLabel()
    {
        return $this->moduleLabel;
    }
    
    public function setModuleLabel( $moduleLabel )
    {
        $this->moduleLabel = rtrim( $moduleLabel, '_' );
    }
    
    public function inModule()
    {
        return !empty( $this->moduleLabel );
    }
    
    public function getGroupId()
    {
        return $this->teamId;
    }
    
    public function setGroupId( $teamId )
    {
        $this->teamId = $teamId;
    }
    
    public function inGroup()
    {
        return !empty( $this->teamId );
    }
    
    public function getResourceId()
    {
        return $this->resourceId;
    }
    
    public function setResourceId( $ressourceId )
    {
        $this->resourceId = $ressourceId;
    }
    
    public function hasResourceId()
    {
        return !empty( $this->resourceId );
    }
    
    public function __toString()
    {
        $crl = "crl://claroline.net/{$this->platformId}/{$this->courseId}";
        
        if ( !empty($this->teamId) )
        {
            $crl.= "/groups/{$this->teamId}";
        }
        
        if ( !empty($this->moduleLabel) )
        {
            $crl.= "/{$this->moduleLabel}";
            
            if ( !empty($this->resourceId) )
            {
                $crl.= '/'. ltrim($this->resourceId, '/');
            }
        }
        
        return $crl;
    }
    
    public static function parse( $locatorString )
    {
        if ( substr($locatorString,0,6) != 'crl://'
            && preg_match( '~^([a-zA-Z0-9]+\://|[a-zA-Z0-9]+\:)~', $locatorString ) )
        {
            return new ExternalResourceLocator( $locatorString );
        }
        
        $matches = array();
        
        $locatorString = rtrim( $locatorString, '/' );
        
        if ( preg_match( '~^crl://claroline\.net/(\w+)/(\w+)$~', $locatorString, $matches ) )
        {
            // course
            $locator = new self( $matches[2] );
            $locator->setPlatformId( $matches[1] );
        }
        elseif ( preg_match( '~^crl://claroline\.net/(\w+)/(\w+)/groups$~', $locatorString, $matches ) )
        {
            // course and group
            $locator = new self( $matches[2], 'CLGRP', null, null );
            $locator->setPlatformId( $matches[1] );
        }
        elseif ( preg_match( '~^crl://claroline\.net/(\w+)/(\w+)/groups/(\d+)$~', $locatorString, $matches ) )
        {
            // course and group
            $locator = new self( $matches[2], null, null, $matches[3] );
            $locator->setPlatformId( $matches[1] );
        }
        elseif ( preg_match( '~^crl://claroline\.net/(\w+)/(\w+)/groups/(\d+)/(\w+)$~', $locatorString, $matches ) )
        {
            // course, group and tool
            $locator = new self( $matches[2], $matches[4], null, $matches[3] );
            $locator->setPlatformId( $matches[1] );
        }
        elseif ( preg_match( '~^crl://claroline\.net/(\w+)/(\w+)/groups/(\d+)/(\w+)/(.+)$~', $locatorString, $matches ) )
        {
            // course, group, tool and resource
            $locator = new self( $matches[2], $matches[4], $matches[5], $matches[3] );
            $locator->setPlatformId( $matches[1] );
        }
        elseif ( preg_match( '~^crl://claroline\.net/(\w+)/(\w+)/(\w+)/(.+)$~', $locatorString, $matches ) )
        {
            // course, tool and resource
            $locator = new self( $matches[2], $matches[3], $matches[4] );
            $locator->setPlatformId( $matches[1] );
        }
        elseif ( preg_match( '~^crl://claroline\.net/(\w+)/(\w+)/(.+)$~', $locatorString, $matches ) )
        {
            // a course and a tool
            $locator = new self( $matches[2], $matches[3] );
            $locator->setPlatformId( $matches[1] );
        }
        elseif ( preg_match( '~^crl://claroline\.net/(\w+)$~', $locatorString, $matches ) )
        {
            // the platform itself
            $locator = new self();
            $locator->setPlatformId( $matches[1] );
        }
        else
        {
            // ???? error ????
            throw new Exception("Invalid Resource Locator {$locatorString}");
        }
        
        return $locator;
    }
    
    public static function crlToId( $crl )
    {
        $id = rawurlencode( $crl );
        $id = str_replace( '%', '::', $id );
        
        return $id;
    }
    
    public static function idToCrl( $id )
    {
        $crl = str_replace( '::', '%', $id );
        $crl = rawurldecode( $crl );
        
        return $crl;
    }
}

/**
 * Defines a locator for external links...
 *
 */
class ExternalResourceLocator implements ResourceLocator
{
    protected $url;
    
    public function __construct( $url )
    {
        $this->url = $url;
    }
    
    public function __toString()
    {
        return $this->url;
    }
}

/**
 * Defines a resource
 *
 */
class LinkerResource
{
    protected $isLinkable;
    protected $isVisible;
    protected $isNavigable;
    protected $locator;
    protected $name;
    
    public function __construct( $name, ResourceLocator $locator, $isLinkable = true, $isVisible = true, $isNavigable = false)
    {
        $this->isLinkable = $isLinkable;
        $this->isVisible = $isVisible;
        $this->isNavigable = $isNavigable;
        $this->name = $name;
        $this->locator = $locator;
    }
    
    public function isVisible()
    {
        return $this->isVisible;
    }
    
    public function isLinkable()
    {
        return $this->isLinkable;
    }
    
    public function isNavigable()
    {
        return $this->isNavigable;
    }
    
    public function getLocator()
    {
        return $this->locator;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function toArray()
    {
        ResourceLinker::init();
        
        $parent = ResourceLinker::$Navigator->getParent( $this->getLocator() );
        $locator = $this->getLocator();
        
        if ( $locator instanceof ExternalResourceLocator )
        {
            $clext_resolver = new CLEXT_Resolver;
            
            return array(
                'name' => $clext_resolver->getResourceName($locator),
                'icon' => get_icon_url('link'),
                'crl' => $this->getLocator()->__toString(),
                'parent' => false,
                'isVisible' => true,
                'isLinkable' => $this->isLinkable() ? true : false,
                'isNavigable' => false
            );
        }
        else
        {
            if ( $locator->inModule() )
            {
                $moduleIcon = get_module_data( $locator->getModuleLabel(), 'icon' );
                $iconUrl = get_module_url($locator->getModuleLabel() )
                    . '/'
                    . $moduleIcon
                    ;
            }
            elseif ( $locator->inGroup() )
            {
                $iconUrl = get_icon_url('group');
            }
            elseif ( $locator->inCourse() )
            {
                $iconUrl = get_icon_url('course');
            }
            else
            {
                $iconUrl = get_icon_url('forbidden');
            }
            
            return array(
                'name' => $this->getName(),
                'icon' => $iconUrl,
                'crl' => $this->getLocator()->__toString(),
                'parent' => !empty($parent) ? $parent->__toString() : false,
                'isVisible' => $this->isVisible() ? true : false,
                'isLinkable' => $this->isLinkable() ? true : false,
                'isNavigable' => $this->isNavigable() ? true : false
            );
        }
    }
    
    public function __toString()
    {
        return get_class($this).' : '.$this->getName() .' at '.$this->getLocator();
    }
}

/**
 * Defines a resource that contains other resources such as
 * a tool or a directory in document tool
 *
 */
class LinkerResourceIterator
    implements CountableSeekableIterator
{
    protected $elementList;
    
    public function __construct( )
    {
        $this->elementList = array();
    }
    
    public function addResource( $resource )
    {
        $this->elementList[] = $resource;
    }
    
    public function first()
    {
        $this->seek(0);
        return $this->current();
    }
    
    public function last()
    {
        $this->seek( count( $this->elementList ) - 1 );
        return $this->current();
    }
    
    public function toArray()
    {
        $elementArr = array();
        
        foreach ( $this->elementList as $element )
        {
            $elementArr[] = $element->toArray();
        }
        
        return $elementArr;
    }
    
    // Countable
    
    public function count()
    {
        return count( $this->elementList );
    }
    
    // Iterator
    
    protected $idx = 0;
    
    public function valid()
    {
        return !empty($this->elementList)
            && $this->idx >= 0
            && $this->idx < count( $this );
    }
    
    public function rewind()
    {
        $this->idx = 0;
    }
    
    public function next()
    {
        $this->idx++;
    }
    
    public function current()
    {
        return $this->elementList[$this->idx];
    }
    
    public function key()
    {
        return $this->idx;
    }
    
    // SeekableIterator
    
    public function seek( $index )
    {
        $this->idx = $index;
        
        if ( !$this->valid() )
        {
            throw new OutOfBoundsException('Invalid seek position');
        }
    }
}

/**
 * Translate a locator to a real url and allows to find the name of a resource
 * from its locator
 *
 */
class ResourceLinkerResolver
{
    public function resolve( ResourceLocator $locator )
    {
        if ( $locator instanceof ExternalResourceLocator )
        {
            return $locator->__toString();
        }
        else // ClarolineResourceLocator
        {
            // 1 . get most accurate resolver
            //  1.1 if Module
            if ( $locator->inModule() )
            {
                $resolver = $this->loadModuleResolver( $locator->getModuleLabel() );
                
                if ( !$resolver )
                {
                    $resolver = new ToolResolver;
                }
            }
            //  1.2 elseif Group
            elseif ( $locator->inGroup() )
            {
                $resolver = new GroupResolver;
            }
            //  1.3 elseif Course
            elseif ( $locator->inCourse() )
            {
                $resolver = new CourseResolver;
            }
            
            //  1.4 get base url
            if( $resolver )
            {
                $url = $resolver->resolve( $locator );
            }
            else
            {
                $url = get_path('rootWeb');
            }
            
            $urlObj = new Url( $url );
            
            // 2. add context information
            $context = Claro_Context::getCurrentContext();
            
            if ( $locator->inGroup() )
            {
                $context[CLARO_CONTEXT_GROUP] = $locator->getGroupId();
            }
            else
            {
                if ( isset( $context[CLARO_CONTEXT_GROUP] ) )
                {
                    unset($context[CLARO_CONTEXT_GROUP]);
                }
            }
            
            if ( $locator->inCourse() )
            {
                $context[CLARO_CONTEXT_COURSE] = $locator->getCourseId();
            }
            else
            {
                if ( isset( $context[CLARO_CONTEXT_COURSE] ) )
                {
                    unset($context[CLARO_CONTEXT_COURSE]);
                }
            }
            
            $urlObj->relayContext( Claro_Context::getUrlContext( $context ) );
            
            return $urlObj->toUrl();
        }
    }
    
    public function loadModuleResolver( $moduleLabel )
    {
        $resolverClass = $moduleLabel . '_Resolver';
        
        if ( ! class_exists( $resolverClass ) )
        {
            $resolverPath = get_module_path( $moduleLabel ) . '/connector/linker.cnr.php';
            
            if ( file_exists( $resolverPath ) )
            {
                include_once $resolverPath;
            }
        }
        
        if ( class_exists( $resolverClass ) )
        {
            $resolver = new $resolverClass();
            
            return $resolver;
        }
        
        return false;
    }
    
    public function getResourceName( ResourceLocator $locator )
    {
        if ( $locator instanceof ExternalResourceLocator )
        {
            return $locator->__toString();
        }
        else // ClarolineResourceLocator
        {
            $nameParts = array();
            
            if ( $locator->inCourse() )
            {
                $resolver = new CourseResolver;
                $nameParts[] = $resolver->getResourceName( $locator );
            }
            
            if ( $locator->inGroup() )
            {
                $resolver = new GroupResolver;
                $nameParts[] = $resolver->getResourceName( $locator );
            }
            
            if ( $locator->inModule() )
            {
                $resolver = new ToolResolver;
                $nameParts[] = $resolver->getResourceName( $locator );
            }
            
            if( $locator->inModule() && $locator->hasResourceId() )
            {
                $resolver = $this->loadModuleResolver( $locator->getModuleLabel() );
                
                if ( $resolver )
                {
                    $nameParts[] = $resolver->getResourceName( $locator );
                }
            }
            
            return implode(' > ', $nameParts);
        }
    }
}

/**
 * Resolver for course
 */
class CourseResolver
{
    public function resolve( ResourceLocator $locator )
    {
        return get_path('clarolineRepositoryWeb') . 'course/index.php?cid='.$locator->getCourseId();
    }
    
    public function getResourceName( ResourceLocator $locator )
    {
        $courseData = claro_get_course_data( $locator->getCourseId() );
        
        return $courseData['officialCode'] . ' : ' . $courseData['name'];
    }
}

/**
 * Resolver for group
 */
class GroupResolver
{
    public function resolve( ResourceLocator $locator )
    {
        return get_path('clarolineRepositoryWeb') . 'group/group_space.php';
    }
    
    public function getResourceName( ResourceLocator $locator )
    {
        $groupData = claro_get_group_data( array(
            CLARO_CONTEXT_COURSE => $locator->getCourseId(),
            CLARO_CONTEXT_GROUP => $locator->getGroupId() ) );
        
        return $groupData['name'];
    }
}

class ToolResolver
{
    public function resolve( ResourceLocator $locator )
    {
        return get_module_entry_url($locator->getModuleLabel());
    }
    
    public function getResourceName( ResourceLocator $locator )
    {
        return get_lang( get_module_data($locator->getModuleLabel(), 'moduleName' ) );
    }
}

/**
 * Interface that should be implemented in each module
 *
 */
interface ModuleResourceResolver
{
    public function resolve( ResourceLocator $locator );
    public function getResourceName( ResourceLocator $locator );
}

class CLEXT_Resolver implements ModuleResourceResolver
{
    public function resolve( ResourceLocator $locator )
    {
        return $locator->getResourceId();
    }
    
    public function getResourceName( ResourceLocator $locator )
    {
        if ( $locator instanceof ExternalResourceLocator )
        {
            return $locator->__toString();
        }
        else
        {
            $url = $locator->getResourceId();
            $externalCourseToolList = claro_get_course_external_link_list( $locator->getCourseId() );
            
            foreach ( $externalCourseToolList as $externalCourseTool )
            {
                if ( $externalCourseTool['url'] == $url )
                {
                    return $externalCourseTool['name'];
                }
            }
            
            return $url;
        }
    }
}

/*class CLGRP_Resolver implements ModuleResourceResolver
{
    public function resolve( ResourceLocator $locator )
    {
        return '';
    }
    
    public function getResourceName()
    {
        return get_lang('');
    }
}*/

/**
 * Returns the list of available resources from a locator
 *
 */
class ResourceLinkerNavigator
{
    public function getResourceList( ResourceLocator $rootNodeLocator = null )
    {
        $rootNodeLocator = empty( $rootNodeLocator )
            ? new ClarolineResourceLocator( claro_get_current_course_id() )
            : $rootNodeLocator
            ;
            
        if ( $rootNodeLocator->inGroup()
            && ! $rootNodeLocator->inModule() )
        {
            $navigator = new GroupNavigator;
            return $navigator->getResourceList($rootNodeLocator);
        }
        elseif ( $rootNodeLocator->inCourse()
            && ! $rootNodeLocator->inModule() )
        {
            $navigator = new CourseNavigator;
            return $navigator->getResourceList($rootNodeLocator);
        }
        elseif ( $rootNodeLocator->inModule() )
        {
            $navigator = self::loadModuleNavigator( $rootNodeLocator->getModuleLabel() );
                
            if ( $navigator )
            {
                return $navigator->getResourceList($rootNodeLocator);
            }
            else
            {
                return $this->moduleResource( $rootNodeLocator->getModuleLabel() );
            }
            
        }
        else
        {
            throw new Exception( "Not supported yet !" );
        }
    }
    
    public function isNavigable( $locator )
    {
        if ( $locator instanceof ExternalResourceLocator )
        {
            return false;
        }
        
        if ( $locator->inModule() )
        {
            if ( $navigator = self::loadModuleNavigator( $locator->getModuleLabel() ) )
            {
                return $navigator->isNavigable( $locator );
            }
            else
            {
                return false;
            }
        }
        else
        {
            if ( $locator->inGroup() )
            {
                $navigator = new GroupNavigator;
                
                return $navigator->isNavigable( $locator );
            }
            elseif ( $locator->inCourse() )
            {
                $navigator = new CourseNavigator;
                
                return $navigator->isNavigable( $locator );
            }
            else
            {
                return false;
            }
        }
    }
    
    protected function moduleResource( $moduleLabel, ResourceLocator $rootNodeLocator )
    {
        $resource = new LinkerResource( $moduleLabel, $rootNodeLocator, true, claro_is_tool_visible($moduleLabel), false );
        
        return $resource;
    }
    
    public static function loadModuleNavigator( $moduleLabel )
    {
        $navigatorClass = $moduleLabel . '_Navigator';
        
        if ( ! class_exists( $navigatorClass ) )
        {
            $navigatorPath = get_module_path( $moduleLabel ) . '/connector/linker.cnr.php';
            
            if ( file_exists( $navigatorPath ) )
            {
                include_once $navigatorPath;
            }
        }
        
        if ( class_exists( $navigatorClass ) )
        {
            $navigator = new $navigatorClass();
            
            return $navigator;
        }
        
        return false;
    }
    
    public function getCurrentLocator( $params = array() )
    {
        $locator = new ClarolineResourceLocator;
        
        if ( claro_is_in_a_course() )
        {
            $locator->setCourseId( claro_get_current_course_id() );
        }
        
        if ( claro_is_in_a_group() )
        {
            $locator->setGroupId( claro_get_current_group_id() );
        }
        
        if ( get_current_module_label() )
        {
            $locator->setModuleLabel(get_current_module_label());
            
            $navigator = $this->loadModuleNavigator( get_current_module_label() );
            
            if ( $resourceId = $navigator->getResourceId( $params ) )
            {
                $locator->setResourceId( $resourceId );
            }
        }
        
        return $locator;
    }
    
    public function getParent( ResourceLocator $locator )
    {
        if ( $locator instanceof ExternalResourceLocator )
        {
            $parent = false;
        }
        elseif ( $locator->hasResourceId() )
        {
            if ( $navigator = $this->loadModuleNavigator($locator->getModuleLabel() ) )
            {
                $resourceId = $navigator->getParentResourceId( $locator );
            }
            else
            {
                $resourceId = null;
            }
            
            $parent = new ClarolineResourceLocator(
                $locator->getCourseId(),
                $locator->getModuleLabel(),
                $resourceId,
                $locator->getGroupId()
            );
        }
        elseif ( $locator->inModule() )
        {
            $parent = new ClarolineResourceLocator(
                $locator->getCourseId(),
                null,
                null,
                $locator->getGroupId()
            );
        }
        elseif ( $locator->inGroup() )
        {
            $parent = new ClarolineResourceLocator(
                $locator->getCourseId(),
                'CLGRP',
                null,
                null
            );
        }
        else
        {
            $parent = false;
        }
        
        return $parent;
    }
}

/**
 * Defines a basic ResourceNavigator
 *
 */
interface ResourceNavigator
{
    public function getResourceList( ResourceLocator $rootNodeLocator );
    
    public function isNavigable( ResourceLocator $locator );
}

/**
 * Interface that should be implemented in each module
 *
 */
interface ModuleResourceNavigator extends ResourceNavigator
{
    public function getResourceId( $params = array() );
    
    public function getParentResourceId( ResourceLocator $locator );
}

/**
 * This navigator allows navigation through tools of a course
 *
 */
class CourseNavigator implements ResourceNavigator
{
    public function getResourceList( ResourceLocator $rootNodeLocator )
    {
        $courseToolList = claro_get_course_tool_list(
            $rootNodeLocator->getCourseId(),
            claro_get_current_user_profile_id_in_course( $rootNodeLocator->getCourseId() )
        );
        
        $courseResource = new LinkerResourceIterator();
        
        foreach ( $courseToolList as $courseTool )
        {
            if( ! is_null( $courseTool['label'] ) )
            {
                $locator = new ClarolineResourceLocator(
                    $rootNodeLocator->getCourseId(),
                    $courseTool['label']
                );
                
                $name = get_lang( $courseTool['name'] );
            }
            else
            {
                $locator = new ExternalResourceLocator( $courseTool['url'] );
                $name = $courseTool['name'];
            }
            
            if ( ! is_null( $courseTool['label'] )
                && ResourceLinkerNavigator::loadModuleNavigator( $courseTool['label'] ) )
            {
                $isNavigable = true;
            }
            else
            {
                $isNavigable = false;
            }
            
            $resource = new LinkerResource(
                $name,
                $locator,
                true,
                $courseTool['visibility'] ? true : false,
                $isNavigable
            );
            
            $courseResource->addResource( $resource );
        }
        
        return $courseResource;
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        // FIXME : a bit more security here !!!!
        return true;
    }
}

/**
 * Thie navigator allows to navigate through tools of groups
 *
 */
class GroupNavigator implements ResourceNavigator
{
    public function getResourceList( ResourceLocator $rootNodeLocator )
    {
        $groupToolList = get_activated_group_tool_label_list( $rootNodeLocator->getCourseId() );
        $groupProperties = claro_get_main_group_properties($rootNodeLocator->getCourseId());
        
        $groupResource = new LinkerResourceIterator();
        
        foreach ( $groupToolList as $groupTool )
        {
            // skip disabled group tools
            if ( ! array_key_exists( $groupTool['label'], $groupProperties['tools'] )
                || ! $groupProperties['tools'][$groupTool['label']] )
            {
                continue;
            }
            
            $locator = new ClarolineResourceLocator(
                $rootNodeLocator->getCourseId(),
                $groupTool['label'],
                null,
                $rootNodeLocator->getGroupId()
            );
            
            if ( ResourceLinkerNavigator::loadModuleNavigator( $groupTool['label'] ) )
            {
                $isNavigable = true;
            }
            else
            {
                $isNavigable = false;
            }
            
            $resource = new LinkerResource(
                $groupTool['name'],
                $locator,
                true,
                $groupTool['visibility'] ? true : false,
                $isNavigable
            );
            
            $groupResource->addResource( $resource );
        }
        
        return $groupResource;
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        // FIXME : a bit more security here !!!!
        return true;
    }
}

class CLGRP_Navigator implements ModuleResourceNavigator
{
    public function getResourceList( ResourceLocator $rootNodeLocator )
    {
        $tbl_cdb_names = get_module_course_tbl(array('group_team'), $rootNodeLocator->getCourseId() );
        $tbl_groups = $tbl_cdb_names['group_team'];

        $sql = 'SELECT `id`,`name` FROM `'.$tbl_groups.'`';
        
        $groups = claro_sql_query_fetch_all($sql);
        $groupProperties = claro_get_main_group_properties($rootNodeLocator->getCourseId());
        
        $groupList = new LinkerResourceIterator;

        foreach ( $groups as $group )
        {
            $locator = new ClarolineResourceLocator(
                $rootNodeLocator->getCourseId(),
                null,
                null,
                (int)$group['id']
            );
            
            $resource = new LinkerResource(
                $group['name'],
                $locator,
                true,
                $groupProperties['private'] ? true : false,
                true
            );
            
            $groupList->addResource( $resource );
        }
        
        return $groupList;
    }
    
    public function getResourceId( $params = array() )
    {
        if ( ! isset($params['gid']) )
        {
            throw new Exception("Missing parameter");
        }
        
        return "groups/{$params['gid']}";
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        return false;
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        return true;
    }
}

/**
 * This navigator is mainly used to link resources from course home page
 *
 */
class CLINTRO_Navigator implements ModuleResourceNavigator
{
    public function getResourceList( ResourceLocator $rootNodeLocator )
    {
        // should not be called
    }
    
    public function getResourceId( $params = array() )
    {
        if ( ! isset($params['id']) )
        {
            throw new Exception("Missing parameter");
        }
        
        return $params['id'];
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        return null;
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        return false;
    }
}

/**
 * A helper for main functions
 *
 */
class ResourceLinker
{
    public static $Resolver;
    public static $Navigator;
    
    private static $_initialized = false;
    private static $_userAgentInitialized = false;
    
    public static function init()
    {
        if ( ! self::$_initialized )
        {
            self::$Navigator = new ResourceLinkerNavigator;
            self::$Resolver = new ResourceLinkerResolver;
            
            self::$_initialized = true;
        }
    }
    
    public static function initUserAgent()
    {
        if ( ! self::$_userAgentInitialized )
        {
            JavascriptLoader::getInstance()->load('claroline.linker');
            CssLoader::getInstance()->load('linker', 'all');
        }
    }
    
    public static function setCurrentLocator( ResourceLocator $locator )
    {
        // Init Client Side Linker
        self::initUserAgent();
        
        // Set current CRL
        ClaroHeader::getInstance()->addInlineJavascript(
             'linkerFrontend.currentCrl = "'.$locator->__toString().'";' . "\n"
        );
    }
    
    public static function renderLinkerBlock($backendUrl = null)
    {
        if( empty($backendUrl) )
        {
            $backendUrl = get_path('clarolineRepositoryWeb').'backends/linker.php';
        }
        
        self::init();
        
        // Init Client Side Linker
        self::initUserAgent();
        
        JavascriptLanguage::getInstance ()->addLangVar('Attach');
        JavascriptLanguage::getInstance ()->addLangVar('Delete');
        JavascriptLanguage::getInstance()->addLangVar('The resource is invisible. Are you sure that you want to attach this resource ?');
        
        // init linkerFronted
        ClaroHeader::getInstance()->addInlineJavascript(
             'linkerFrontend.base_url = "'.$backendUrl.'";' . "\n"
            .'linkerFrontend.deleteIconUrl = "'.get_icon_url('delete').'";'. "\n"
            .'linkerFrontend.invisibleIconUrl = "'.get_icon_url('invisible').'"; '. "\n"
        );
        
        return '<div id="lnk_panel">' . "\n"
            . '<div id="lnk_selected_resources"></div>' . "\n"
            . '<p id="lnk_toggle">' . "\n"
            . '<a href="#" id="lnk_show_browser" class="attach">'.get_lang('Attach an existing resource').'</a>' . "\n"
            . '<a href="#" id="lnk_hide_browser">'.get_lang('Close').'</a>' . "\n"
            . '</p>' . "\n"
            . '<div id="lnk_browser">' . "\n"
            . '<div id="lnk_ajax_loading"><img src="'.get_icon_url('loading').'" alt="" /></div>' . "\n"
            . '<h4 id="lnk_location"></h4>' . "\n"
            . '<div id="lnk_back_link"></div>'
            . '<div id="lnk_resources"></div>' . "\n"
            . '</div>' . "\n"
            . '<div id="lnk_hidden_fields"></div>' . "\n"
            . '</div>' . "\n\n"
            ;
    }
    
    public static function renderLinkList( ResourceLocator $locator, $forExternalUse = false )
    {
        self::init();
        
        CssLoader::getInstance()->load('linker', 'all');
        
        $linkList = self::getLinkList( $locator );
        $linkList->setFetchMode( Database_ResultSet::FETCH_OBJECT );
        
        $htmlLinkList = '<div class="lnk_link_panel">' . "\n";
        
        if ( count( $linkList ) )
        {
            $htmlLinkList .= '<h2 class="lnk_link_list">'
                . get_lang('Attached resources') . '</h2>'
                . "\n"
                ;
                
            $htmlLinkList .= '<ul class="lnk_link_list" id="'.ClarolineResourceLocator::crlToId( $locator->__toString() ).'">' . "\n";
            
            foreach ( $linkList as $link )
            {
                $locator = ClarolineResourceLocator::parse($link->crl);
                
                $url = self::$Resolver->resolve( $locator );
                
                if ( $forExternalUse == true )
                {
                    $url = rtrim( str_replace( get_conf('urlAppend'), '', get_path( 'rootWeb' ) ), '/')
                        . '/' . ltrim( $url, '/' )
                        ;
                }
                
                $htmlLinkList .= '<li><a href="'
                    . claro_htmlspecialchars( $url )
                    . '" class="lnk_link" rel="' . ClarolineResourceLocator::crlToId( $link->crl ) . '">'
                    . claro_htmlspecialchars( self::$Resolver->getResourceName( $locator ) )
                    . '</a></li>' . "\n"
                    ;
            }
            
            $htmlLinkList .= '</ul>' . "\n";
        }
        else
        {
            // $htmlLinkList .= get_lang('Nothing to display');
        }
        
        $htmlLinkList .= '</div>' . "\n";
        
        return $htmlLinkList;
    }
    
    public static function updateLinkList( ResourceLocator $locator, array $resourceList = array() )
    {
        $alreadyLinkedResourceList = self::getLinkList( $locator );
        $alreadyLinkedResourceList->setFetchMode( Database_ResultSet::FETCH_COLUMN );
        
        if ( count( $alreadyLinkedResourceList ) )
        {
            $alreadyLinkedResourceList = iterator_to_array($alreadyLinkedResourceList);
        }
        else
        {
            $alreadyLinkedResourceList = array();
        }
        
        $deletedResourceList = array();
        $addedResourceList = array();
        
        foreach ( $alreadyLinkedResourceList as $crl )
        {
            if ( ! in_array( $crl, $resourceList ) )
            {
                self::removeLink( $locator, ClarolineResourceLocator::parse( $crl ) );
            }
        }
        
        foreach ( $resourceList as $crl )
        {
            if ( ! in_array( $crl, $alreadyLinkedResourceList ) )
            {
                self::addLink( $locator, ClarolineResourceLocator::parse( $crl ) );
            }
        }
    }
    
    /**
     * Get a resource URL from its parameters
     *
     * @param integer $courseId
     * @param string $moduleLabel
     * @param mixed $resourceId
     * @param integer $teamId
     * @return string url of resource
     */
    public static function getRessourceUrl(
        $courseId,
        $moduleLabel = null,
        $resourceId= null,
        $teamId = null )
    {
        self::init();
        
        $locator = new ClarolineResourceLocator(
            $courseId,
            $moduleLabel,
            $resourceId,
            $teamId );
        
        return self::$Resolver->resolve( $locator );
    }
    
    public static function getCurrentLocator( $params = array() )
    {
        self::init();
        return self::$Navigator->getCurrentLocator( $params );
    }
    
    public static function getLocatorIdAndAddIfMissing( ResourceLocator $locator )
    {
        $tbl = claro_sql_get_course_tbl();
        
        $sql = "SELECT `id` FROM `{$tbl['resources']}`\n"
            . "WHERE BINARY `crl` = " . Claroline::getDatabase()->quote($locator->__toString())
            ;
        
        $res = Claroline::getDatabase()->query( $sql );
        
        if ( $res->numRows() )
        {
            return (int) $res->fetch( Database_ResultSet::FETCH_VALUE );
        }
        else
        {
            $sql = "INSERT INTO `{$tbl['resources']}`\n"
                . "SET\n"
                . "`crl` = " . Claroline::getDatabase()->quote($locator->__toString()) ."\n"
                . ",`title` = ''"
                ;
            
            Claroline::getDatabase()->exec ( $sql );
            
            return (int) Claroline::getDatabase()->insertId();
        }
    }
    
    public static function addLink( $locatorFrom, $locatorTo )
    {
        $crlFromId = self::getLocatorIdAndAddIfMissing( $locatorFrom );
        $crlToId = self::getLocatorIdAndAddIfMissing( $locatorTo );
        
        $tbl = claro_sql_get_course_tbl();
        
        $sql = "SELECT `id` FROM `{$tbl['links']}`\n"
            . "WHERE `src_id` = " . Claroline::getDatabase()->escape( $crlFromId ) ."\n"
            . "AND\n"
            . "`dest_id` = " . Claroline::getDatabase()->escape( $crlToId )
            ;
        
        $res = Claroline::getDatabase()->query( $sql );
        
        if ( $res->numRows() )
        {
            return false;
        }
        else
        {
            $sql = "INSERT INTO `{$tbl['links']}`\n"
                . "SET\n"
                . "`src_id` = " . Claroline::getDatabase()->escape( $crlFromId ) ."\n"
                . ",\n"
                . "`dest_id` = " . Claroline::getDatabase()->escape( $crlToId )
                ;
            
            Claroline::getDatabase()->exec ( $sql );
            
            return true;
        }
    }
    
    public static function removeLink( $locatorFrom, $locatorTo )
    {
        $crlFromId = self::getLocatorIdAndAddIfMissing( $locatorFrom );
        $crlToId = self::getLocatorIdAndAddIfMissing( $locatorTo );
        
        $tbl = claro_sql_get_course_tbl();
        
        $sql = "DELETE FROM `{$tbl['links']}`\n"
            . "WHERE\n"
            . "`src_id` = " . Claroline::getDatabase()->escape( $crlFromId ) . "\n"
            . "AND\n"
            . "`dest_id` = " . Claroline::getDatabase()->escape( $crlToId )
            ;
        
        Claroline::getDatabase()->exec ( $sql );
        
        return Claroline::getDatabase()->affectedRows();
    }
    
    public static function getLinkList( $locator )
    {
        $tbl = claro_sql_get_course_tbl();
        
        $sql = "SELECT `dest`.`crl` AS `crl`, `dest`.`title` AS `title`\n"
            . "FROM `{$tbl['links']}` AS `lnk`,\n"
            . "`{$tbl['resources']}` AS `dest`,\n"
            . "`{$tbl['resources']}` AS `src`\n"
            . "WHERE `src`.`crl` = " . Claroline::getDatabase()->quote( $locator->__toString() ) . "\n"
            . "AND `dest`.`id` = `lnk`.`dest_id`\n"
            . "AND `src`.`id` = `lnk`.`src_id`\n"
            ;
            
        $res = Claroline::getDatabase()->query( $sql );
        
        return $res;
    }
}
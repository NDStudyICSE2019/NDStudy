<?php // $Id: linker.cnr.php 13779 2011-11-02 14:21:14Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

FromKernel::uses('fileManage.lib', 'file.lib');

/**
 * Resource Resolver for the CLLNP tool
 * 
 * CLLP_Resolver is used to list the resources that can be added in a path from an other path.
 * Only items (and not an entire path) can be added as a new resource in a path.
 *
 * @version 1.9 $Revision: 13779 $
 * @copyright (c) 2001-2008 Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author claroline Team <cvs@claroline.net>
 * @author Dimitri Rambout <dim@claroline.net>
 * @package CLLP
 */
class CLLNP_Resolver implements ModuleResourceResolver
{
    /**
     * Function called to resolve an URL based on a resourceId.
     *
     * @param ResourceLocator $locator The locator of the resource.
     * @return string the URL of the item
     */
    public function resolve ( ResourceLocator $locator )
    {
        if( $locator->hasResourceId() )
        {
            return get_module_url('CLLNP') . "/learningPath.php?path_id={$locator->getResourceId()}&cidReq={$locator->getCourseId()}";
        }
        else
        {
            return get_module_entry_url('CLLNP');
        }
    }

    /**
     * Return the title of a Resource
     *
     * @param ResourceLocator $locator The locator of the resource.
     * @return string The title of the resource (false if there is no resourceId or is not in a course)
     */
    public function getResourceName( ResourceLocator $locator)
    {
        if( $locator->hasResourceId() && $locator->inCourse() )
        {
            return $this->_getTitle( $locator->getCourseId(), $locator->getResourceId() );
        }
        
        return false;
    }
    
    /**
     * Return the title of an item in a course
     *
     * @param  $courseId identifies a course in database
     * @param  $itemId integer who identifies the exercice
     * @return string The title of the item
     */
    function _getTitle( $courseId , $itemId )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'lp_learnPath' ), $courseId );
        $tblItem = $tbl_cdb_names['lp_learnPath'];

        $sql = 'SELECT `name`
                FROM `'.$tblItem.'`
                WHERE `learnPath_id`='. (int) $itemId;
        
        $title = claro_sql_query_get_single_value($sql);

        return $title;
    }
}

/**
 * Resource Navigator for the CLLP tool
 *
 * CLLP_Navigator is used to navigate in a resource and provide the children list of the resource.
 *
 * @version 1.9 $Revision: 13779 $
 * @copyright (c) 2001-2008 Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author claroline Team <cvs@claroline.net>
 * @author Dimitri Rambout <dim@claroline.net>
 * @package CLLP
 */
class CLLNP_Navigator implements ModuleResourceNavigator
{
    /**
     * Get the id of a resource.
     *
     * @param array $params An array of params
     * @return boolean False
     * @deprecated 0.1
     */
    public function getResourceId( $params = array() )
    {
        return false;
    }
    
    /**
     * Check if a resource is navigable.
     * If the resource is an item, the method will return true. In other cases, it will return false.
     *
     * @param ResourceLocator $locator The resource locator.
     * @return boolean True or False
     */    
    public function isNavigable( ResourceLocator $locator )
    {
        if (  $locator->hasResourceId() )
        {
            return false;
        }
        else
        {
            return $locator->inModule() && $locator->getModuleLabel() == 'CLLNP';
        }
    }
    
    /**
     * Get the id of the parent
     *
     * @param ResourceLocator $locator The resource locator
     * @return boolean false
     * @deprecated 0.1
     */
    public function getParentResourceId( ResourceLocator $locator )
    {
        return false;
    }
    /**
     * Provide the list of available resources for a resource
     *
     * @para ResourceLocator $locator The resource locator.
     * @return LinkerResourceIterator Resource list as an iterator
     */
    public function getResourceList( ResourceLocator $locator )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'lp_learnPath' ), $locator->getCourseId() );
        $tblPath = $tbl_cdb_names['lp_learnPath'];

        $resourceList = new LinkerResourceIterator();
        
        
        $sql = "SELECT `learnPath_id` AS `id`, `name`, `visibility`
                FROM `". $tblPath ."`
                ORDER BY `name` ASC";

        $pathList = claro_sql_query_fetch_all_rows($sql);

        foreach( $pathList as $path )
        {    
            $fileLoc = new ClarolineResourceLocator(
                $locator->getCourseId(),
                'CLLNP',
                $path['id']
            );

            $fileResource = new LinkerResource(
                $path['name'],
                $fileLoc,
                true,
                ($path['visibility'] == 'SHOW' ? true : false),
                false
            );

            $resourceList->addResource( $fileResource );
        }

        return $resourceList;
    }
}

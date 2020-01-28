<?php // $Id: linker.cnr.php 13348 2011-07-18 13:58:28Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Resource Resolver for the exercises tool
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline Team <cvs@claroline.net>
 * @package     CLQWZ
 */

FromKernel::uses('fileManage.lib', 'file.lib');

class CLQWZ_Resolver implements ModuleResourceResolver
{
    public function resolve ( ResourceLocator $locator )
    {
        $baseUrl = get_module_url('CLQWZ');
        
        if ( $locator->hasResourceId() )
        {
            $url = "exercise_submit.php?exId={$locator->getResourceId()}&cidReq={$locator->getCourseId()}";
            return $baseUrl . '/' . $url;
        }
        else
        {
            return get_module_entry_url('CLQWZ');
        }
    }

    public function getResourceName( ResourceLocator $locator)
    {
        if( $locator->hasResourceId() && $locator->inCourse() )
        {
            return $this->_getTitle( $locator->getCourseId(), $locator->getResourceId() );
        }
        
        return false;
    }
    
    /**
     * @param  $course_sys_code identifies a course in data base
     * @param  $id integer who identifies the exercice
     * @return the title of a annoncement
     */
    function _getTitle( $courseId , $qwzId )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise' ), $courseId );
        $tblExercise = $tbl_cdb_names['qwz_exercise'];

        $sql = 'SELECT `title`
                FROM `'.$tblExercise.'`
                WHERE `id`='. (int) $qwzId;
        $title = claro_sql_query_get_single_value($sql);

        return $title;
    }
}

/**
 * Class Exercise Navigator
 *
 * @package CLQWZ
 *
 */
class CLQWZ_Navigator implements ModuleResourceNavigator
{
    public function getResourceId( $params = array() )
    {
        return false;
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        if (  $locator->hasResourceId() )
        {
            return false;
        }
        else
        {
            return $locator->inModule() && $locator->getModuleLabel() == 'CLQWZ';
        }
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        return false;
    }
    
    public function getResourceList( ResourceLocator $locator )
    {
        $tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise', 'qwz_question', 'qwz_rel_exercise_question' ), $locator->getCourseId() );
        $tblExercise = $tbl_cdb_names['qwz_exercise'];

        $sql = "SELECT `id`, `title`, `visibility`
              FROM `".$tblExercise."`
              ORDER BY `title` ASC";
        $exerciseList = claro_sql_query_fetch_all_rows($sql);

        $resourceList = new LinkerResourceIterator();

        foreach( $exerciseList as $exercise )
        {
            $fileLoc = new ClarolineResourceLocator(
                $locator->getCourseId(),
                'CLQWZ',
                $exercise['id']
            );

            $fileResource = new LinkerResource(
                $exercise['title'],
                $fileLoc,
                true,
                ($exercise['visibility'] == 'VISIBLE' ? true : false),
                false
            );

            $resourceList->addResource( $fileResource );
        }

        return $resourceList;
    }
}
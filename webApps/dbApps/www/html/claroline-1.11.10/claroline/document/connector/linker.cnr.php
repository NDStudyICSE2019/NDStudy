<?php // $Id: linker.cnr.php 13348 2011-07-18 13:58:28Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Resource Resolver for the Document tool
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claroline Team <cvs@claroline.net>
 * @package     CLDOC
 */

FromKernel::uses('fileManage.lib', 'file.lib', 'fileDisplay.lib');

class CLDOC_Resolver implements ModuleResourceResolver
{
    protected function urlDecodePath( $path )
    {
        $pathElementList = explode('/', $path);
    
        for ($i = 0; $i < sizeof($pathElementList); $i++)
        {
            $pathElementList[$i] = rawurldecode($pathElementList[$i]);
        }
    
        return implode('/',$pathElementList);
    }
    
    public function resolve ( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            $context = Claro_Context::getCurrentContext();
            $context[CLARO_CONTEXT_COURSE] = $locator->getCourseId();
            
            if ( $locator->inGroup() )
            {
                $context[CLARO_CONTEXT_GROUP] = $locator->getGroupId();
            }
                
            $path = get_path('coursesRepositorySys') . claro_get_course_path( $locator->getCourseId() );
            
            // in a group
            if ( $locator->inGroup() )
            {
               $groupData = claro_get_group_data ( $context );
                
                $path .= '/group/' . $groupData['directory'];
                $groupId = $locator->getGroupId();
            }
            else
            {
                $path .= '/document';
            }
            
            $path .= '/' . $this->urlDecodePath( ltrim( $locator->getResourceId(), '/' ) );
            $resourcePath = '/' . $this->urlDecodePath( ltrim( $locator->getResourceId(), '/' ) );
            
            $path = secure_file_path( $path );
            
            if ( !file_exists($path) )
            {
                // throw new Exception("Resource not found {$path}");
            
                return false;
            }
            elseif ( is_dir( $path ) )
            {
                $url = new Url( get_module_entry_url('CLDOC') );
                $url->addParam('cmd', 'exChDir' );
                $url->addParam( 'file', base64_encode( $resourcePath ) );
                
                return $url->toUrl();
            }
            else
            {
                
                
                return claro_get_file_download_url( $resourcePath, Claro_Context::getUrlContext($context) );
            }
        }
        else
        {
            return get_module_entry_url('CLDOC');
        }
    }

    public function getResourceName( ResourceLocator $locator)
    {
        $path = $this->urlDecodePath( $locator->getResourceId() );
        
        return str_replace( '/', ' > ', $path );
    }
}

/**
 * Class Document Navigator
 *
 * @package CLDOC
 * @subpackage CLLINKER
 *
 * @author Fallier Renaud <renaud.claroline@gmail.com>
 */
class CLDOC_Navigator implements ModuleResourceNavigator
{
    protected function getPath( $locator )
    {
        $path = get_path('coursesRepositorySys') . claro_get_course_path( $locator->getCourseId() );
        
        // $groupId = null;
        
        // in a group
        if ( $locator->inGroup() )
        {
            $groupData = claro_get_group_data ( array(
                CLARO_CONTEXT_COURSE => $locator->getCourseId(),
                CLARO_CONTEXT_GROUP => $locator->getGroupId()
            ));
            
            $path .= '/group/' . $groupData['directory'];
            // $groupId = $locator->getGroupId();
        }
        else
        {
            $path .= '/document';
        }
        
        if ( $locator->hasResourceId() )
        {
            $path .= '/' . ltrim( $locator->getResourceId(), '/' );
        }
        
        $path = secure_file_path( $path );
        
        return $path;
    }
    
    protected function isPathNavigable( $path )
    {
        if ( !file_exists($path) || !is_dir( $path ) )
        {
            return false;
        }
        else
        {
            return true;
        }
    }
    
    public function getResourceId( $params = array() )
    {
        if ( empty( $params ) || !isset($params['path']) )
        {
            return false;
        }
        else
        {
            return $params['path'];
        }
    }
    
    public function isNavigable( ResourceLocator $locator )
    {
        if (  $locator->hasResourceId() )
        {
            return $this->isPathNavigable( $this->getPath( $locator ) );
        }
        else
        {
            return $locator->inModule() && $locator->getModuleLabel() == 'CLDOC';
        }
    }
    
    public function getParentResourceId( ResourceLocator $locator )
    {
        if ( $locator->hasResourceId() )
        {
            $resourceId = '/' . ltrim( $locator->getResourceId(), '/' );
            $parentResourceId = ltrim( str_replace( '\\', '/', dirname( $resourceId) ), '/' );
            
            if ( $parentResourceId == ''
                || $parentResourceId == '/'
                || $parentResourceId == '.'
                || $parentResourceId == '..' )
            {
                return false;
            }
            else
            {
                
                return $parentResourceId;
            }
        }
        else
        {
            return false;
        }
    }
    
    public function getResourceList( ResourceLocator $locator )
    {
        $groupId = null;
        
        if ( $locator->inGroup() )
        {
            $groupData = claro_get_group_data ( array(
                CLARO_CONTEXT_COURSE => $locator->getCourseId(),
                CLARO_CONTEXT_GROUP => $locator->getGroupId()
            ));
            
            $groupId = $locator->getGroupId();
        }
        
        $path = $this->getPath( $locator );
        
        if ( ! $this->isPathNavigable( $path ) )
        {
            throw new Exception("{$path} does not exists or is not a directory");
        }
        else
        {
            $tbl = get_module_course_tbl( array('document'), $locator->getCourseId() );
            
            $fileProperties = array();
            
            if ( ! $locator->inGroup() )
            {
                $sql = "SELECT `path`, `visibility`, `comment`\n"
                    . "FROM `{$tbl['document']}`\n"
                    . "WHERE 1"
                    ;
                    
                $res = Claroline::getDatabase()->query( $sql );
                
                foreach ( $res as $row )
                {
                    $fileProperties[$row['path']] = $row;
                }
            }
            
            $it = new DirectoryIterator( $path );
            
            $dirList = array();
            $fileList = array();
            
            foreach ( $it as $file )
            {
                if ( $file->isDir() && $file->isDot() )
                {
                    continue;
                }
                
                $relativePath = str_replace( '\\', '/', str_replace( $file->getPath(), '', $file->getPathname() ) );
                
                if ( $locator->hasResourceId() )
                {
                    $relativePath = '/' . ltrim(
                         ltrim( $locator->getResourceId(), '/' )
                        . '/' . ltrim( $relativePath, '/' ), '/' )
                        ;
                }
                
                if ( $file->isDir() )
                {
                    $dirList[] = $relativePath;
                }
                elseif ( $file->isFile() )
                {
                    $fileList[] = $relativePath;
                }
            }

            natcasesort( $dirList );
            natcasesort( $fileList );

            $resourceList = new LinkerResourceIterator;

            foreach ( $dirList as $relativePath )
            {
                $isVisible = true;
                
                if ( array_key_exists( $relativePath, $fileProperties ) )
                {
                    $isVisible = $fileProperties[$relativePath]['visibility'] != 'i' ? true : false;
                }
                
                $resourceList->addResource( $this->createResourceLocator(
                    $locator->getCourseId(), $relativePath, $isVisible, true, $groupId )
                );
            }

            foreach ( $fileList as $relativePath )
            {
                $isVisible = true;

                if ( array_key_exists( $relativePath, $fileProperties ) )
                {
                    $isVisible = $fileProperties[$relativePath]['visibility'] != 'i' ? true : false;
                }

                $resourceList->addResource( $this->createResourceLocator(
                    $locator->getCourseId(), $relativePath, $isVisible, false, $groupId )
                );
            }

            return $resourceList;
        }
    }

    public function createResourceLocator( $courseId, $relativePath, $isVisible, $isNavigable, $groupId )
    {
        $fileLoc = new ClarolineResourceLocator(
            $courseId,
            'CLDOC',
            format_url_path( $relativePath ),
            $groupId
        );
                
        $fileResource = new LinkerResource(
            basename($relativePath),
            $fileLoc,
            true,
            $isVisible,
            $isNavigable
        );
                
        return $fileResource;
    }
}
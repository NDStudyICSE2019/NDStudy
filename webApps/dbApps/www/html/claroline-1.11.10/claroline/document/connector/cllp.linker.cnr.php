<?php
/*
 * If this file is *empty* it only tells to learning path that resources of this tool may
 * be linked in a learning path and will use some SCORM API calls to records progress
 *
 * Navigator and resolvers defined in linker.cnr.php may also be overwritten in this file
 * to resolve to another url or to offer different navigation possibilities.
 */

FromKernel::uses('fileManage.lib', 'file.lib');

class CLDOC_Resolver implements ModuleResourceResolver
{
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
            
            $path .= '/' . ltrim( $locator->getResourceId(), '/' );
            $resourcePath = '/' . ltrim( $locator->getResourceId(), '/' );
            
            $path = secure_file_path( $path );
            
            if ( !file_exists($path) )
            {
                throw new Exception("Resource not found {$path}");
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
                return get_module_url('CLDOC') . '/connector/cllp.frames.cnr.php';
                
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
        $path = $locator->getResourceId();
        
        return str_replace( '/', ' > ', $path );
    }
}

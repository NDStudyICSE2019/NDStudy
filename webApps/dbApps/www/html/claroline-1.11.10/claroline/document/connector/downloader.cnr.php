<?php // $Id: downloader.cnr.php 13395 2011-08-09 09:59:37Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Document downloader class
 *
 * @version     1.10 $Revision: 13395 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLDOC
 */

class CLDOC_Downloader extends Claro_Generic_Module_Downloader
{
    protected function isDocumentDownloadableInCourse( $requestedUrl )
    {
        if (claro_is_in_a_group())
        {
            $groupContext  = true;
            $courseContext = false;
            $is_allowedToEdit = claro_is_group_member() ||  claro_is_group_tutor() || claro_is_course_manager();
        }
        else
        {
            $groupContext  = false;
            $courseContext = true;
            $is_allowedToEdit = claro_is_course_manager();
        }

        if ($courseContext)
        {
            $courseTblList = claro_sql_get_course_tbl();
            $tbl_document =  $courseTblList['document'];

            if ( strtoupper(substr(PHP_OS, 0, 3)) == "WIN" )
            {
                $modifier = '';
            }
            else
            {
                $modifier = 'BINARY ';
            }

            $sql = "SELECT visibility
                    FROM `{$tbl_document}`
                    WHERE {$modifier} path = '".claro_sql_escape($requestedUrl)."'";

            $docVisibilityStatus = claro_sql_query_get_single_value($sql);

            if ( ( ! is_null($docVisibilityStatus) ) // hidden document can only be viewed by course manager
                 && $docVisibilityStatus == 'i'
                 && ( ! $is_allowedToEdit ) )
            {
                return false;
            }
            else

            {
                return true;
            }
        }
        else
        {
            // ????
        }
    }
    
    public function getFilePath( $requestedUrl )
    {
        if ( claro_is_in_a_course() )
        {
            if (claro_is_in_a_group() && claro_is_group_allowed())
            {
                $intermediatePath = get_path('coursesRepositorySys') . claro_get_course_path(). '/group/'.claro_get_current_group_data('directory');
            }
            else
            {
                $intermediatePath = get_path('coursesRepositorySys') . claro_get_course_path(). '/document';
            }
        }
        else
        {
            $intermediatePath = rtrim( str_replace( '\\', '/', get_path('rootSys') ), '/' ) . '/platform/document';
        }
        
        if ( get_conf('secureDocumentDownload') && $GLOBALS['is_Apache'] )
        {
            // pretty url
            $path = realpath( $intermediatePath . '/' . $requestedUrl);
        }
        else
        {
            // TODO check if we can remove rawurldecode
            $path = $intermediatePath
                        . implode ( '/',
                                array_map('rawurldecode', explode('/',$requestedUrl)));
        }
        
        return $path;
    }
    
    public function isAllowedToDownload( $requestedUrl )
    {
        if ( ! $this->isModuleAllowed() )
        {
            return false;
        } 
        
        if ( claro_is_in_a_course() )
        {
            if ( !claro_is_course_allowed() )
            {
                pushClaroMessage('course not allowed', 'debug');
                return false;
            }
            else
            {
                
                if ( claro_is_in_a_group() )
                {
                    if ( !claro_is_group_allowed() )
                    {
                        pushClaroMessage('group not allowed', 'debug');
                        return false;
                    }
                    else
                    {
                        return true;
                    }
                }
                else
                {
                    return $this->isDocumentDownloadableInCourse($requestedUrl);
                }
            }
        }
        else
        {
            return false;
        }
    }
}

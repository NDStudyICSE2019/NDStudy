<?php // $Id: downloader.cnr.php 14407 2013-02-25 15:30:41Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Document downloader class
 *
 * @version     1.10 $Revision: 14407 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLLNP
 */

class CLLNP_Downloader extends Claro_Generic_Module_Downloader
{
    protected function isDocumentDownloadableInCourse( $requestedUrl )
    {
        $groupContext  = false;
        $courseContext = true;
        
        // @todo : use accessmanager instead of claro_is_course_manager()
        $is_allowedToEdit = claro_is_course_manager();
        
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
            $intermediatePath = get_path('coursesRepositorySys') . claro_get_course_path(). '/document';
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
        $fromCLLNP = (isset( $_SESSION['fromCLLNP'] ) && $_SESSION['fromCLLNP'] === true) ? true : false;
        
        // unset CLLNP mode
        unset( $_SESSION['fromCLLNP'] );
        
        if ( !$fromCLLNP || ! $this->isModuleAllowed() )
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
                
                return $this->isDocumentDownloadableInCourse($requestedUrl);
            }
        }
        else
        {
            return false;
        }
    }
}

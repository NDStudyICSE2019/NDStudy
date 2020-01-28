<?php // $Id: downloader.lib.php 13884 2011-12-12 11:57:59Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Downloader classes
 *
 * @version     1.10 $Revision: 13884 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 */

/**
 * Module Downloader Interface
 * @since Claroline 1.10.5
 */
interface Claro_Downloader
{
    /**
     * Check if the current user can acces the requested file
     * @param string $requestedUrl
     * @return bool
     */
    public function isAllowedToDownload( $requestedUrl );
    
    /**
     * Get the system path to the requested file
     * @param string $requestedUrl
     * @return string
     */
    public function getFilePath( $requestedUrl );
}

class Claro_PlatformDocumentsDownloader implements Claro_Downloader
{
    public function isAllowedToDownload( $requestedUrl )
    {
        return true;
    }
    
    public function getFilePath( $requestedUrl )
    {
        $requestedUrl = secure_file_path( $requestedUrl );
        
        return realpath( rtrim( str_replace( '\\', '/', get_path('rootSys') ), '/' ) 
            . '/platform/document' . '/' . $requestedUrl );
    }
}

/**
 * Generic module downloader : implements the default rules. Can be replaced by
 * a connector in a specific module to implement specific behaviour.
 */
class Claro_Generic_Module_Downloader implements Claro_Downloader
{
    protected $moduleLabel;
    
    public function __construct( $moduleLabel )
    {
        $this->moduleLabel = $moduleLabel;
    }
    
    protected function isModuleAllowed()
    {
        $moduleData = get_module_data( $this->moduleLabel );

        if ( $moduleData['type'] == 'tool' )
        {
            $contextList = get_module_context_list( $this->moduleLabel );

            

            if ( claro_is_in_a_course() )
            {
                $_mainToolId = get_tool_id_from_module_label( $this->moduleLabel );
                $_profileId = claro_get_current_user_profile_id_in_course();
                $_cid = claro_get_current_course_id();
                
                if ( claro_is_in_a_group() )
                {
                    $_groupProperties = claro_get_main_group_properties( claro_get_current_course_id() );
                    $_mainToolId = get_tool_id_from_module_label('CLGRP');

                    $is_toolAllowed = array_key_exists( $this->moduleLabel, $_groupProperties ['tools'] )
                        && $_groupProperties ['tools'] [$this->moduleLabel]
                        // do not allow to access group tools when groups are not allowed for current profile
                        && claro_is_allowed_tool_read( $_mainToolId, $_profileId, $_cid );

                    if ( $_groupProperties ['private'] )
                    {
                        $is_toolAllowed = $is_toolAllowed && ( claro_is_group_member() || claro_is_group_tutor() );
                    }

                    $is_toolAllowed = $is_toolAllowed || ( claro_is_course_manager() || claro_is_platform_admin() );
                }
                else
                {
                    // we ignore course visibility
                    if ( ( ! claro_is_allowed_tool_edit( $_mainToolId, $_profileId, $_cid) )
                        && ! claro_is_allowed_tool_read( $_mainToolId, $_profileId, $_cid ) )
                    {
                        $is_toolAllowed = false;
                    }
                    else
                    {
                        $is_toolAllowed = true;
                    }
                }
            }
            else
            {
                if ( in_array( 'platform', iterator_to_array($contextList) ) )
                {
                    $is_toolAllowed = get_module_data( $this->moduleLabel,'activation' ) == 'activated';
                }
                else
                {
                    $is_toolAllowed = false;
                }
            }
            
            return $is_toolAllowed;
        }
        else
        {
            // if an applet "tool", return true if activated
            // and let module manage it's access by itself
            return ( $moduleData['activation'] == 'activated' );
        }
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
                    return true;
                }
            }
        }
        else
        {
            return true;
        }
    }
    
    public function getFilePath( $requestedUrl )
    {
        if ( claro_is_in_a_course() )
        {
            $basePath = get_path('coursesRepositorySys') 
                . claro_get_course_path( claro_get_current_course_id() )
                . '/' . $this->moduleLabel
                ;
            
            if ( claro_is_in_a_group() )
            {
                $basePath .= '/.group/'.claro_get_current_group_id();
            }
        }
        else
        {
            $basePath = get_path('rootSys') . 'platform/module_data/' 
                . $this->moduleLabel
                ;
        }
        
        return $basePath . '/' . $requestedUrl;
    }
}

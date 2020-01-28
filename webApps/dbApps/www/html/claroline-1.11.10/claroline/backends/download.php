<?php // $Id: download.php 13884 2011-12-12 11:57:59Z zefredz $

/**
 * CLAROLINE
 *
 * Download a file given it's file location within a course or group document
 * directory.
 *
 * @version     $Revision: 13884 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2.0
 * @package     KERNEL
 */

require dirname(__FILE__) . '/../inc/claro_init_global.inc.php';

require_once get_path('incRepositorySys') . '/lib/url.lib.php';
require_once get_path('incRepositorySys') . '/lib/file.lib.php';
require_once get_path('incRepositorySys') . '/lib/file/downloader.lib.php';

$nameTools = get_lang('Display file');

$dialogBox = new DialogBox();

$noPHP_SELF=true;

$isDownloadable = true ;

if ( claro_is_in_a_course() && ! claro_is_course_allowed() ) claro_disp_auth_form(true);

$claroline->notification->addListener( 'download', 'trackInCourse' );

if ( isset($_REQUEST['url']) )
{
    $requestUrl = strip_tags($_REQUEST['url']);
}
else
{
    $requestUrl = strip_tags(get_path_info());
}

if ( is_download_url_encoded($requestUrl) )
{
    $requestUrl = download_url_decode( $requestUrl );
}

if ( empty($requestUrl) )
{
    $isDownloadable = false ;
    $dialogBox->error( get_lang('Missing parameters') );
}
else
{
    if ( isset( $_REQUEST['moduleLabel'] ) && !empty( $_REQUEST['moduleLabel'] ) )
    {
        $moduleLabel = $_REQUEST['moduleLabel'];
    }
    else
    {
        if ( !claro_is_in_a_course() )
        {
            $moduleLabel = null;
        }
        else
        {
            $moduleLabel = 'CLDOC';
        }
    }
    
    if ( $moduleLabel )
    {
        $connectorPath = secure_file_path(get_module_path( $moduleLabel ) . '/connector/downloader.cnr.php');

        if ( file_exists( $connectorPath ) )
        {
            require_once $connectorPath;
            $className = $moduleLabel.'_Downloader';
            $downloader = new $className( $moduleLabel );
        }
        else
        {
            $downloader = false;
            // $downloader = new Claro_Generic_Module_Downloader($moduleLabel);
            
            pushClaroMessage( 'No downloader found for module '.strip_tags( $moduleLabel ), 'warning' );
        }
    }
    else
    {
        $downloader = new Claro_PlatformDocumentsDownloader();
    }
    
    if ( $downloader && $downloader->isAllowedToDownload( $requestUrl) ) 
    {
        $pathInfo = $downloader->getFilePath( $requestUrl );
        
        // use slashes instead of backslashes in file path
        if (claro_debug_mode() )
        {
            pushClaroMessage('<p>File path : ' . $pathInfo . '</p>','pathInfo');
        }

        $pathInfo = secure_file_path( $pathInfo );

        // Check if path exists in course folder
        if ( ! file_exists($pathInfo) || is_dir($pathInfo) )
        {
            $isDownloadable = false ;

            $dialogBox->title( get_lang('Not found') );
            $dialogBox->error( get_lang('The requested file <strong>%file</strong> was not found on the platform.',
                                    array('%file' => basename($pathInfo) ) ) );
        }
    }
    else
    {
        $isDownloadable = false;
        
        pushClaroMessage('downloader said no!', 'debug');
        
        $dialogBox->title( get_lang('Not allowed') );
    }
}

// Output section

if ( $isDownloadable )
{
    // end session to avoid lock
    session_write_close();

    $extension = get_file_extension($pathInfo);
    $mimeType = get_mime_on_ext($pathInfo);

    // workaround for HTML files and Links
    if ( $mimeType == 'text/html' && $extension != 'url' )
    {
        $claroline->notifier->event('download', array( 'data' => array('url' => $requestUrl) ) );

        if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN")
        {
            $rootSys =  str_replace( '//', '/', strtolower( str_replace('\\', '/', $rootSys) ) );
            $pathInfo = strtolower( str_replace('\\', '/', $pathInfo) );
        }

        $document_url = str_replace($rootSys,$urlAppend.'/',$pathInfo);

        // redirect to document
        claro_redirect( str_ireplace( '%2F', '/', urlencode( $document_url ) ) );

        die();
    }
    else
    {
        if( get_conf('useSendfile', true) )
        {
            if ( claro_send_file( $pathInfo )  !== false )
            {
                $claroline->notifier->event('download', array( 'data' => array('url' => $requestUrl) ) );
            }
            else
            {
                header('HTTP/1.1 404 Not Found');
                claro_die( get_lang('File download failed : %failureMSg%',
                    array( '%failureMsg%' => claro_failure::get_last_failure() ) ) );
                die();
            }
        }
        else
        {
            if (strtoupper(substr(PHP_OS, 0, 3)) == "WIN")
            {
                $rootSys =  str_replace( '//', '/', strtolower( str_replace('\\', '/', $rootSys) ) );
                $pathInfo = strtolower( str_replace('\\', '/', $pathInfo) );
            }

            $document_url = str_replace($rootSys,$urlAppend.'/',$pathInfo);

            // redirect to document
            claro_redirect($document_url);

            die();
        }
    }
}
else
{
    header('HTTP/1.1 404 Not Found');

    $out = '';

    $out .= $dialogBox->render();

    $claroline->display->body->appendContent($out);

    echo $claroline->display->render();

    exit;
}

die();

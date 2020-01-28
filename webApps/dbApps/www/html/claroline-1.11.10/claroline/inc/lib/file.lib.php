<?php  // $Id: file.lib.php 14350 2012-12-19 10:32:41Z ffervaille $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * File handling functions.
 *
 * @version     $Revision: 14350 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

require_once dirname(__FILE__) . '/core/url.lib.php';

function file_upload_failed( $file )
{
    return get_file_upload_errno( $file ) > 0;
}

function get_file_upload_errno( $file )
{
    return (int) $file['error'];
}

function get_file_upload_error_message( $file )
{
    return get_file_upload_errstring_from_errno( get_file_upload_errno( $file ) );
}

function get_file_upload_errstring_from_errno( $errorLevel )
{
    if ( !defined( 'UPLOAD_ERR_CANT_WRITE' ) )
    {
        // Introduced in PHP 5.1.0
        define( 'UPLOAD_ERR_CANT_WRITE', 5 );
    }
    
    switch( $errorLevel )
    {
        case UPLOAD_ERR_OK:
        {
            $details = get_lang('No error');
        }
        case UPLOAD_ERR_INI_SIZE:
        {
            $details = get_lang('File too large. Notice : Max file size %size', array ( '%size' => get_cfg_var('upload_max_filesize')) );
        }   break;
        case UPLOAD_ERR_FORM_SIZE:
        {
            $details = get_lang('File size exceeds');
        }   break;
        case UPLOAD_ERR_PARTIAL:
        {
            $details = get_lang('File upload incomplete');
        }   break;
        case UPLOAD_ERR_NO_FILE:
        {
            $details = get_lang('No file uploaded');
        }   break;
        case UPLOAD_ERR_NO_TMP_DIR:
        {
            $details = get_lang('Temporary folder missing');
        }   break;
        case UPLOAD_ERR_CANT_WRITE:
        {
            $details = get_lang('Failed to write file to disk');
        }   break;
        default:
        {
            $details = get_lang('Unknown error code %errCode%'
                , array('%errCode%' => $errorLevel ));
        }   break;
    }
    
    return $details;
}

/**
 * Extract the extention of the filename

 * @param string $fileName name of the file
 * @return string extension
 */
function get_file_extension ( $fileName )
{
    $fileExtension = strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );

    return $fileExtension;
}

/**
 * Get file MIME type from file name based on extension
 * @param string $fileName name of the file
 * @return string file MIME type
 *
 */
function get_mime_on_ext($fileName)
{
    $mimeType = null;

    /*
     * Check if the file has an extension AND if the browser has send a MIME Type
     */
     
    $fileExtension = strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );
    
    $defaultMimeType = 'document/unknown';

    if( $fileExtension )
    {
        /*
         * Build a "MIME-types / extensions" connection table
         */

        $mimeTypeList = array(
            'aif'   => 'audio/x-aiff',
            'avi'   => 'video/x-msvideo',
            'bmp'   => 'image/bmp',
            'css'   => 'text/css',
            'doc'   => 'application/msword',
            'fla'   => 'application/octet-stream',
            'gif'   => 'image/gif',
            'gz'    => 'application/x-gzip',
            'htm'   => 'text/html',
            'html'  => 'text/html',
            'hqx'   => 'application/mac-binhex40',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'js'    => 'text/javascript',
            'm3u'   => 'audio/x-mpegurl',
            'mid'   => 'audio/midi',
            'mov'   => 'video/quicktime',
            'mp3'   => 'audio/mpeg',
            'mp4'   => 'video/mp4',
            'mpg'   => 'video/mpeg',
            'mpeg'  => 'video/mpeg',
            'ogg'   => 'application/x-ogg',
            
            # Open Document Formats
            'odt'   => 'application/vnd.oasis.opendocument.text',
            'ott'   => 'application/vnd.oasis.opendocument.text-template',
            'oth'   => 'application/vnd.oasis.opendocument.text-web',
            'odm'   => 'application/vnd.oasis.opendocument.text-master',
            'odg'   => 'application/vnd.oasis.opendocument.graphics',
            'otg'   => 'application/vnd.oasis.opendocument.graphics-template',
            'odp'   => 'application/vnd.oasis.opendocument.presentation',
            'otp'   => 'application/vnd.oasis.opendocument.presentation-template',
            'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
            'ots'   => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'odc'   => 'application/vnd.oasis.opendocument.chart',
            'odf'   => 'application/vnd.oasis.opendocument.formula',
            'odb'   => 'application/vnd.oasis.opendocument.database',
            'odi'   => 'application/vnd.oasis.opendocument.image',
            
            'pdf'   => 'application/pdf',
            'png'   => 'image/png',
            'ppt'   => 'application/vnd.ms-powerpoint',
            'pps'   => 'application/vnd.ms-powerpoint',
            'ps'    => 'application/postscript',
            'ra'    => 'audio/x-realaudio',
            'ram'   => 'audio/x-pn-realaudio',
            'rm'    => 'audio/x-pn-realaudio',
            'rpm'   => 'audio/x-pn-realaudio-plugin',
            'rtf'   => 'application/rtf',
            'sit'   => 'application/x-stuffit',
            'svg'   => 'image/svg+xml',
            'swf'   => 'application/x-shockwave-flash',
            
            # Open Office 1 Documents
            'sxw'   => 'application/vnd.sun.xml.writer',
            'stw'   => 'application/vnd.sun.xml.writer.template',
            'sxc'   => 'application/vnd.sun.xml.calc',
            'stc'   => 'application/vnd.sun.xml.calc.template',
            'sxd'   => 'application/vnd.sun.xml.draw',
            'std'   => 'application/vnd.sun.xml.draw.template',
            'sxi'   => 'application/vnd.sun.xml.impress',
            'sti'   => 'application/vnd.sun.xml.impress.template',
            'sxg'   => 'application/vnd.sun.xml.writer.global',
            'sxm'   => 'application/vnd.sun.xml.math',
            
            'tar'   => 'application/x-tar',
            'tex'   => 'application/x-tex',
            'tgz'   => 'application/x-gzip',
            'tif'   => 'image/tiff',
            'tiff'  => 'image/tiff',
            'txt'   => 'text/plain',
            'url'   => 'text/html',
            'wav'   => 'audio/x-wav',
            'wmv'   => 'video/x-ms-wmv',
            'xml'   => 'application/xml',
            'xls'   => 'application/vnd.ms-excel',
            'xsl'   => 'text/xml',
            'zip'   => 'application/zip',
            
            # Syndication
            'ics'   => 'text/Calendar',
            'xcs'   => 'text/Calendar',
            'rdf'   => 'text/xml',
            'rss'   => 'application/rss+xml',
            'opml'  => 'text/x-opml',
            
            # Microsoft Office 2007 (sucks)
            'docm'  => 'application/vnd.ms-word.document.macroEnabled.12',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotm'  => 'application/vnd.ms-word.template.macroEnabled.12',
            'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'potm'  => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppam'  => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'ppsm'  => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptm'  => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xlam'  => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'xlsb'  => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xlsm'  => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltm'  => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template'
        );

        $mimeType = array_key_exists( $fileExtension, $mimeTypeList )
            ? $mimeTypeList[$fileExtension]
            : $defaultMimeType
            ;
    }
    else
    {
        $mimeType = $defaultMimeType;
    }

    return $mimeType;
}

/**
 * Send a file over HTTP
 * @param   string $path file path
 * @param   string $name file name to force (optional)
 * @return  true on success,
 *          false if file not found or file empty,
 *          set a claro_failure if file not found
 */
function claro_send_file( $path, $name = '', $charset = null )
{
    if ( file_exists( $path ) )
    {
        if ( empty( $name ) ) $name = basename( $path );
        $charset = empty( $charset )
            ? ''
            : '; charset=' . $charset
            ;
        
        $mimeType = get_mime_on_ext( $path );
    
        header( 'Content-Type: ' . $mimeType . $charset );
            
        // IE no-cache bug
        
        // TODO move $lifetime to config
        $lifetime = 60;
        
        header( 'Cache-Control: max-age=' . $lifetime );
        header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $lifetime ) .' GMT' );
        header( 'Pragma: ' );
        
        // Patch proposed by Diego Conde <dconde@uvigo.es> - Universidade de Vigo
        // It seems that with the combination of OfficeXP and Internet Explorer 6 the
        // downloading of powerpoints fails sometimes. I captured the network packets
        // and the viewer of the office doesn't send all the needed cookies,
        // therefore claroline redirects the viewer to the login page because its not
        // correctly authenticated.
        if ( strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) == "ppt" )
        {
            // force file name for ppt
            header( 'Content-Disposition: attachment; filename="' . $name . '"' );
        }
        else
        {
            // force file name for other files
            header( 'Content-Disposition: inline; filename="' . $name . '"' );
        }
        
        header( 'Content-Length: '. filesize( $path ) );
        
        return ( readfile( $path ) );
    }
    else
    {
        return claro_failure::set_failure( 'FILE_NOT_FOUND' );
    }
}


/**
 * Send a stream over HTTP
 * @param   string $stream file stream
 * @param   string $name file name to force (optional)
 * @param   string $mimeType mime type of the stream if none given, the function
 *  will try to guess it from $name
 * @param   string $charset character encoding of the strem, if none given the
 *  function will use the encoding of the current page
 * @return  number of octets sent
 * @since Claroline 1.9.5
 */
function claro_send_stream( $stream, $name, $mimeType = null , $charset = null )
{
    $charset = empty( $charset )
        ? '; charset=' . get_locale( 'charset' )
        : '; charset=' . $charset
        ;
    
    if( is_null( $mimeType ) )
    {
        $mimeType = get_mime_on_ext( $name );
    }
    
    header( 'Content-Type: ' . $mimeType . $charset );
        
    // IE no-cache bug
    
    // TODO move $lifetime to config
    $lifetime = 60;
    
    header( 'Cache-Control: max-age=' . $lifetime );
    header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $lifetime ) .' GMT' );
    header( 'Pragma: ' );
    
    // Patch proposed by Diego Conde <dconde@uvigo.es> - Universidade de Vigo
    // It seems that with the combination of OfficeXP and Internet Explorer 6 the
    // downloading of powerpoints fails sometimes. I captured the network packets
    // and the viewer of the office doesn't send all the needed cookies,
    // therefore claroline redirects the viewer to the login page because its not
    // correctly authenticated.
    if ( strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) == "ppt" )
    {
        // force file name for ppt
        header( 'Content-Disposition: attachment; filename="' . $name . '"' );
    }
    else
    {
        // force file name for other files
        header( 'Content-Disposition: inline; filename="' . $name . '"' );
    }
    
    header( 'Content-Length: '. strlen( $stream ) );
    
    echo $stream;
    
    return strlen( $stream );
}


/**
 * Remove /.. ../ from file path
 * @param   string $path file path
 * @return  string, clean file path
 */
function secure_file_path( $path )
{
    while ( false !== strpos( $path, '://' )
        || false !== strpos( $path, '../' )
        || false !== strpos( $path, '/..' ) )
    {
        // protect against remote file inclusion
        $path = str_replace( '://', '', $path );
        // protect against arbitrary file inclusion
        $path = str_replace( '../', './', $path );
        $path = str_replace( '/..', '/.', $path );
    }

    return $path;
}

/**
 * Read a file from the file system and echo it
 *
 * Workaround for the readfile bug in PHP 5.0.4 and with host where
 * PHP readfile is deactivated
 *
 * @param   string $path file path
 * @param   boolean $retbytes return file length (default true)
 * @return  int file length if $retbytes
 *          boolean true on success if not $retbytes
 *          boolean false on failure
 *          set claro_failure on failure
 * @deprecated since Claroline 1.9 and PHP 5.1
 */
function claro_readfile( $path, $retbytes = true )
{
    if ( ! file_exists( $path ) )
    {
        return claro_failure::set_failure( 'FILE_NOT_FOUND' );
    }
    
    $chunksize = 1*(1024*1024); // how many bytes per chunk
    $buffer = '';
    $cnt =0;
    
    $handle = fopen( $path, 'rb' );
    
    if ( ! $handle )
    {
        return claro_failure::set_failure( 'CANNOT_OPEN_FILE' );
    }
    
    while (!feof($handle))
    {
        $buffer = fread($handle, $chunksize);
        
        if ( $buffer === false )
        {
            return claro_failure::set_failure( 'CANNOT_READ_FILE' );
        }
        
        echo $buffer;
        
        if ( $retbytes )
        {
            $cnt += strlen($buffer);
        }
    }
    
    $status = fclose( $handle );
    
    if ( $retbytes && $status )
    {
        return $cnt;
    }
    else
    {
        return $status;
    }
}

/**
 * Check if a relative path is encoded or not
 * @param string $str
 * @return boolean
 */
function is_download_url_encoded( $str )
{
    $str = ltrim($str, '/');
    return preg_match("!^[0-9a-zA-Z\+/=]+$!", $str);
}

/**
 * WARNING : DO NOT USE IN Url OBJET : ALREADY URLENCODED
 *  USE BASE64_ENCODE INSTEAD !
 * Encode course relative file path to use with backend/download
 * @param string file relative path
 * @return string
 */
function download_url_encode( $str )
{
    if ( $GLOBALS['is_Apache'] && get_conf('usePrettyUrl', false) )
    {
        $str = ltrim($str, '/');
        return '/' . urlencode(base64_encode( $str ) );
    }
    else
    {
        return urlencode(base64_encode( $str ) );
    }
}

/**
 * Decode encoded relative file path
 * @param string $str
 * @return string
 */
function download_url_decode( $str )
{
    if ( $GLOBALS['is_Apache'] && get_conf('usePrettyUrl', false) )
    {
        $str = ltrim($str, '/');
        return '/' . ltrim( base64_decode( $str ), '/' );
    }
    else
    {
        return base64_decode( $str );
    }
}

/**
 * Get the url to download the file at the given file path
 * @param string $file path to the file
 * @param array $context
 * @param string $moduleLabel
 * @since Claroline 1.10.5
 * @return string url to the file
 */
function claro_get_file_download_url( $file, $context = null, $moduleLabel = null )
{
    $file = download_url_encode( $file );
    
    if ( $GLOBALS['is_Apache'] && get_conf('usePrettyUrl', false) )
    {
        // slash argument method - only compatible with Apache
        $url = get_path('url') . '/claroline/backends/download.php'.str_replace('%2F', '/', $file);
    }
    else
    {
        // question mark argument method, for IIS ...
        $url = get_path('url') . '/claroline/backends/download.php?url=' . $file;
    }
    
    $urlObj = new Url( $url );
    
    if ( !empty ( $context ) )
    {
        $urlObj->relayContext( Claro_Context::getUrlContext( $context ) );
    }
    else
    {
        $urlObj->relayCurrentContext();
    }
    
    if ( $moduleLabel )
    {
        $urlObj->addParam( 'moduleLabel', $moduleLabel );
    }

    return $urlObj->toUrl();
}

/**
 * replaces some dangerous character in a file name
 * This function is broken !
 *
 * @param   string $string
 * @param   string $strict (optional) removes also scores and simple quotes
 * @since   Claroline 1.11.0-beta1 $strict is ignored !
 * @return  string : the string cleaned of dangerous character
 * @todo    function broken !
 */
function replace_dangerous_char($string, $strict = 'loose')
{
    // workaround for mac os x
    $string = preg_replace('/&#\d+;/', '_', $string );
    
    $search[] = ' ';  $replace[] = '_';
    $search[] = '/';  $replace[] = '-';
    $search[] = '\\'; $replace[] = '-';
    $search[] = '"';  $replace[] = '-';
    $search[] = '\''; $replace[] = '_';
    $search[] = '?';  $replace[] = '-';
    $search[] = '*';  $replace[] = '-';
    $search[] = '>';  $replace[] = '';
    $search[] = '<';  $replace[] = '-';
    $search[] = '|';  $replace[] = '-';
    $search[] = ':';  $replace[] = '-';
    $search[] = '$';  $replace[] = '-';
    $search[] = '(';  $replace[] = '-';
    $search[] = ')';  $replace[] = '-';
    $search[] = '^';  $replace[] = '-';
    $search[] = '[';  $replace[] = '-';
    $search[] = ']';  $replace[] = '-';
    $search[] = '°';  $replace[] = '';

    foreach($search as $key=>$char )
    {
        $string = str_replace($char, $replace[$key], $string);
    }
    
    /* if ( function_exists('iconv') )
    {
        $string = iconv(get_conf('charset'), "US-ASCII//TRANSLIT", $string);
    }
    else */
    {
        $string = preg_replace( 
            '~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', 
            '$1', 
            claro_htmlentities( claro_utf8_encode( $string ) , ENT_QUOTES , 'UTF-8' ) 
        );
    }

    $string = str_replace("'", '', $string);
    
    return $string;
}

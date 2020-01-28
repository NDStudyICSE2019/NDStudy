<?php // $Id: fileDisplay.lib.php 14587 2013-11-08 12:47:41Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * @version     1.9 $Revision: 14587 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/config_def/
 * @package     KERNEL
 * @author      Claro Team <cvs@claroline.net>
 *
 */


/**
 * Define the image to display for each file extension
 * This needs an existing image repository to works
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @param  - fileName (string) - name of a file
 * @retrun - the gif image to chose
 */

function choose_image($fileName)
{
    static $type, $image;

    /* TABLES INITILIASATION */

    if (!$type || !$image)
    {
        $type['package']    = array("gz", "bz2", "zip", "tar", "rar");
        $image['package']   = "package-x-generic";
        
        $type['pgp']        = array("pgp");
        $image['pgp']       = "text-x-pgp";

        $type['link']       = array("url", "htm", "html", "htx", "swf");
        $image['link']      = "link";

        $type['exe']        = array("sh", "exe");
        $image['exe']       = "applications-system";
        
        $type['script']     = array("js", "css", "xsl", "pl", "plm", "ml" ,"lsp", "cls");
        $image['script']    = "text-x-script";
        
        $type['php']        = array("php");
        $image['php']       = "application-x-php";
        
        $type['python']     = array("py");
        $image['python']    = "text-x-python";
        
        $type['ruby']       = array("rb");
        $image['ruby']      = "application-x-ruby";
        
        $type['code']       = array("c", "h", "cpp", "java");
        $image['code']      = "text-x-code";

        $type['text']       = array("xml", "tex", "txt", "rtf");
        $image['text']      = "text-x-generic";

        $type['pdf']        = array("pdf");
        $image['pdf']       = "pdf";
        
        $type['ps']         = array("ps");
        $image['ps']        = "x-office-document";

        $type['audio']      = array("ogg", "wav", "midi", "mp2", "mp3", "mp4", "vqf");
        $image['audio']     = "audio-x-generic";
        
        $type['video']      = array("avi", "mpg", "mpeg", "mov", "wmv");
        $image['video']     = "video-x-generic";

        $type['image']      = array("png", "jpeg", "jpg", "xcf", "gif", "bmp");
        $image['image']     = "image-x-generic";
        
        $type['drawing']    = array("svg", "odg");
        $image['drawing']   = "x-office-drawing";

        $type['model']      = array("step", "stp", "iges", "igs", "e3", "sldprt", "sldasm","3dm");
        $image['model']     = "model-generic";
        
        $type['model-facet'] = array("blend", "stl", "wrl", "obj");
        $image['model-facet'] = "model-facet";
        
        $type['odt']        = array("odt", "doc", "docx", "dot", "mcw", "wps");
        $image['odt']       = "x-office-document";
        
        $type['ods']        = array("ods", "xls", "xlsx", "xlt");
        $image['ods']       = "x-office-spreadsheet";
        
        $type['odp']        = array("odp", "ppt", "pptx", "pps");
        $image['odp']       = "x-office-presentation";
        
        $type['odf']        = array("odf");
        $image['odf']       = "x-office-formula";

        $type['font']       = array("ttf");
        $image['font']      = "font-x-generic";        
    }

    /* FUNCTION CORE */
    $extension= null;

    if (preg_match('/\.([[:alnum:]]+)$/', $fileName, $extension))
    {
        $extension[1] = strtolower ($extension[1]);

        foreach( $type as $genericType => $typeList)
        {
            if (in_array($extension[1], $typeList))
            {
                return 'mime/' . $image[$genericType];
            }
        }
    }

    return 'mime/default';
}

//------------------------------------------------------------------------------

/**
 * Transform the file size in a human readable format
 *
 * @author - ???
 * @param  - fileSize (int) - size of the file in bytes
 */

function format_file_size($fileSize)
{
    // byteUnits is setted in trad4all
    global $byteUnits;

    if($fileSize >= 1073741824)
    {
        $fileSize = round($fileSize / 1073741824 * 100) / 100 . '&nbsp;' . $byteUnits[3]; //GB
    }
    elseif($fileSize >= 1048576)
    {
        $fileSize = round($fileSize / 1048576 * 100) / 100 . '&nbsp;' . $byteUnits[2]; //MB
    }
    elseif($fileSize >= 1024)
    {
        $fileSize = round($fileSize / 1024 * 100) / 100 . '&nbsp;' . $byteUnits[1]; //KB
    }
    else
    {
        $fileSize = $fileSize . '&nbsp;' . $byteUnits[0];
    }

    return $fileSize;
}


//------------------------------------------------------------------------------


/**
 * Transform a UNIX time stamp in human readable format date
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @param - date - UNIX time stamp
 */

function format_date($fileDate)
{
    return claro_html_localised_date( get_locale( 'dateFormatNumeric' ), $fileDate );
}

//------------------------------------------------------------------------------


/**
 * Transform the file path in a url
 *
 * @param - url (string) - relative local path of the file on the Hard disk
 * @return - relative url
 */

function url_already_encoded( $url )
{
    return ( false !== strpos( $url, '%' ) );
}

function format_url($url)
{
    if ( url_already_encoded( $url ) )
    {
        return $url;
    }

    $urlArray = parse_url( $url );


    $urlToRet = isset($urlArray['scheme'])
        ? $urlArray['scheme']
        : ''
        ;

    if ( isset($urlArray['scheme'])
        && 'mailto' == $urlArray['scheme'] )
    {
        $urlToRet .= ':';
    }
    elseif ( isset($urlArray['scheme']) )
    {
        $urlToRet .= '://';
    }

    if ( isset( $urlArray['user'] ) )
    {
        $urlToRet = $urlArray['user'];
        $urlToRet .= isset( $urlArray['pass'] )
            ? ':'.$urlArray['pass']
            : ''
            ;
        $urlToRet .= '@';
    }

    $urlToRet .= isset( $urlArray['host']  )
        ? $urlArray['host']
        : ''
        ;
    $urlToRet .= isset( $urlArray['port']  )
        ? ':' . $urlArray['port']
        : ''
        ;

    $urlToRet .= isset( $urlArray['path'] )
        ? '/' . format_url_path( substr( $urlArray['path'],  1 ) )
        : ''
        ;

    $urlToRet .= isset( $urlArray['query'] )
        ? '?' . format_url_query( $urlArray['query'] )
        : ''
        ;

    $urlToRet .= isset( $urlArray['fragment'] )
        ? '#' . $urlArray['fragment']
        : ''
        ;

    return $urlToRet;
}

/**
 * Enter description here...
 *
 * @param string $path
 * @return string
 *
 */
function format_url_path( $path )
{
    $pathElementList = explode('/', $path);

    for ($i = 0; $i < sizeof($pathElementList); $i++)
    {
        $pathElementList[$i] = rawurlencode($pathElementList[$i]);
    }

    return implode('/',$pathElementList);
}

/**
 * Enter description here...
 *
 * @param string $query
 * @return string
 */
function format_url_query( $query )
{
    $ret = '';

    if ( strpos( $query, '&' ) !== false
        || strpos( $query, '&amp;' ) !== false
        || strpos( $query, '=' ) !== false )
    {
        $queryArray = preg_split( '~(&|&amp;)~', $query );
        $parts = array();
        foreach ( $queryArray as $part )
        {
            if ( preg_match( '~(.*?)=(.*?)~', $part ) )
            {
                $parts[] = preg_replace_callback( '~(.+?)=(.+)~', 'query_make_part', $part );
            }
            elseif ( preg_match( '~/?[^=]+~', $part ) )
            {
                // option 1 :
                $parts[] = '/' . format_url_path( substr( $part,  1 ) );
                // option 2
                // $parts[] = $part;
                // option 3
                // $parts[] = rawurlencode($part);
            }
            else
            {
                // option 1
                // $parts[] = $part;
                // option 2
                // $parts[] = rawurlencode($part);
            }
        }
        $ret = implode( '&', $parts );
    }
    elseif ( strpos( $query, '/' ) !== false )
    {
        $ret = format_url_path( $query );
    }
    else
    {
        $ret = rawurlencode( $query );
    }

    return $ret;
}


/**
 * Callbacked function
 *
 * @param array $matches
 * @return string
 */
function query_make_part( $matches )
{
    return $matches[1] . '=' . rawurlencode( $matches[2] );
}


//------------------------------------------------------------------------------


/**
 *
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @param string $curDirPath current path in the documents tree navugation
 * @return string breadcrumb trail
 */

function claro_disp_document_breadcrumb($curDirPath)
{
    pushClaroMessage( (function_exists('claro_html_debug_backtrace')
                 ? claro_html_debug_backtrace()
                 : 'claro_html_debug_backtrace() not defined'
                 )
                 .'claro_disp_document_breadcrumb is deprecated , use claro_html_document_breadcrumb','error');
   return claro_html_document_breadcrumb($curDirPath);
}
function claro_html_document_breadcrumb($curDirPath)
{
    $curDirPathList = explode('/', $curDirPath);

    $urlTrail = '';
    
    $bc = new BreadCrumbs;

    foreach($curDirPathList as $thisDir)
    {
        if ( empty($thisDir) )
        {
            $bc->appendNode( new BreadCrumbsNode( get_lang('Root'),
                claro_htmlspecialchars( Url::Contextualize(get_module_entry_url('CLDOC')) ) ) );
        }
        else
        {
            $urlTrail .= '/'.$thisDir;
            $bc->appendNode( new BreadCrumbsNode( get_lang($thisDir),
                claro_htmlspecialchars( Url::Contextualize( get_module_entry_url('CLDOC') . '?cmd=exChDir&amp;file='.base64_encode($urlTrail) ) ) ));
        }
    }
    
    if ( $bc->size() < 2 )
    {
        return '';
    }
    else
    {
        return '<div class="breadcrumbTrails">' . $bc->render().'</div>' . "\n";
    }
}

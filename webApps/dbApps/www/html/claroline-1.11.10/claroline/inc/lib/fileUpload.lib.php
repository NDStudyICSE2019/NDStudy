<?php // $Id: fileUpload.lib.php 14362 2013-01-28 10:51:22Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * FILE UPLOAD LIBRARY
 *
 * @version     1.9 $Revision: 14362 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLDOC
 * @author      Hugues Peeters <hugues@claroline.net>
 * @author      Claro Team <cvs@claroline.net>
 *
 */

//------------------------------------------------------------------------------

require_once dirname(__FILE__) . '/file.lib.php';

/**
 * Change the file name extension from .php to .phps
 * Useful to secure a site !!
 *
 * @param  string $fileName Name of a file
 * @return string : the filename phps'ized
 */

function php2phps ($fileName)
{
    $fileName = preg_replace('/\.(php.?|phtml.?)(\.){0,1}.*$/i', '.phps', $fileName);
    return $fileName;
}

/**
 * change the file named .htacess in htacess.txt
 * Useful to secure a site working on Apache.
 *
 * @param  string $fileName Name of a file
 *
 * @return string : 'Apache safe' file name
 */


function htaccess2txt($fileName)
{
    $fileName = str_ireplace('.htaccess', 'htaccess.txt', $fileName);
    return $fileName;
}


/**
 * change the file named .htacess in htacess.txt
 * Useful to secure a site working on Apache.
 *
 * @param  - fileName (string) name of a file
 * @return - string innocuous filename
 * @see    - htaccess2txt and php2phps
 */


function get_secure_file_name($fileName)
{
    $fileName = php2phps($fileName);
    $fileName = htaccess2txt($fileName);
    return $fileName;
}

//------------------------------------------------------------------------------


/**
 * Check if there is enough place to add a file on a directory
 * on the base of a maximum directory size allowed
 *
 * @param  interger $fileSize  Size of the file in byte
 * @param  string   $dir       Path of the directory where
 *                             the file should be added
 * @param  interger $maxDirSpace Maximum size of the diretory in byte
 *
 * @return mixed : boolean true if there is enough space false otherwise
 *
 * @see    - enough_size() uses  dir_total_space() function
 */

function enough_size($fileSize, $dir, $maxDirSpace)
{
    if ($maxDirSpace)
    {
        $alreadyFilledSpace = dir_total_space($dir);

        if ( ($fileSize + $alreadyFilledSpace) > $maxDirSpace)
        {
            return false;
        }
    }

    return true;
}


//------------------------------------------------------------------------------

/**
 * Compute the size already occupied by a directory and is subdirectories
 * @param string $dirPath Size of the file in byte
 * @return integer : the directory size in bytes
 */

function dir_total_space($dirPath)
{
    $sumSize = 0;
    
    if( !empty( $dirPath ) 
        && !is_null($dirPath) 
        && file_exists ( $dirPath ) 
        && is_dir ( $dirPath ) )
    {
        $it = new RecursiveIteratorIterator( 
            new RecursiveDirectoryIterator( $dirPath ) );
        
        foreach ( $it as $element ) 
        {
            if ( $element->isFile() )
            {
                $sumSize += $element->getSize();
            }
        }
    }

    return $sumSize;
}


//------------------------------------------------------------------------------

/**
 * Try to add an extension to files witout extension
 * Some applications on Macintosh computers don't add an extension to the files.
 * This subroutine try to fix this on the basis of the MIME type send
 * by the browser.
 *
 * Note : some browsers don't send the MIME Type (e.g. Netscape 4).
 *        We don't have solution for this kind of situation
 *
 * @param  array $uploadedFile
 *         It has to be the superglobals $_FILE['myFile'] array
 *
 * @return string : extension (empty string if the file has already an extension)
 *
 */

function add_extension_for_uploaded_file($uploadedFile)
{
    // CHECK IF THE FILE NAME HAS ALREADY AN EXTENSION

    if( get_extension_from_file_name($uploadedFile['name']) )
    {
        $extension = null; // no need for an extension there is already one.
    }
    elseif( isset($uploadedFile['type']) )
    {
        $extension = get_extension_from_mime_type($uploadedFile['type']);
    }
    else
    {
        $extension = null;
    }

    if ( $extension ) return '.' . $extension;
    else              return '';
}

/**
 * Return the extention of a given filename
 *
 * @param string $fileName
 *
 * @return mixed : string  : extension
 *              or false   : if no extension is found
 */

function get_extension_from_file_name($fileName)
{
    if ( preg_match('/.+\.([a-zA-Z0-9]+)$/', $fileName, $matchList) )
    {
        return  $matchList[1];
    }
    else return null;
}

/**
 * return the common extension for a given mimeType
 * @param string $mimeType
 *
 * @return mixed : string  : extension
 *              or false   : if no extension is found
 */

function get_extension_from_mime_type($mimeType)
{
    list($mimeTypeList, $extensionList) = get_mime_type_extension_map();

    $key = array_search(strtolower($mimeType), $mimeTypeList);

    if ( is_int($key) ) return $extensionList[$key];
    else                return false;
}

/**
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @param string $extension (doc, rtf, ...)
 * @return string - corresponding mime type
 */

function get_mime_type_from_extension($extension)
{
    // remove the dot prefix, in case of ...
    $extension = str_replace('.', '', $extension);

    list($mimeTypeList, $extensionList) = get_mime_type_extension_map();

    $key = array_search(strtolower($extension), $extensionList);

    if ( is_int($key) ) return $mimeTypeList[$key];
    else                return false;
}

/**
 * Typical use :
 *      list(mimeTypeLis, $extensionList) = get_mime_type_extension_map()
 *
 * @return array : nested array containing two other arrays,
 *         the firt one with the MIME TYPES, and the second with the
 *         corresponding EXTENSIONS. keys of both sub arrays are mapped
 */

function get_mime_type_extension_map()
{
    static $typeList = array(); static $extList= array();

    $typeList[] = 'text/plain';                     $extList[] = 'txt';
    $typeList[] = 'application/msword';             $extList[] = 'doc';
    $typeList[] = 'application/rtf';                $extList[] = 'rtf';
    $typeList[] = 'application/vnd.ms-powerpoint';  $extList[] = 'ppt';
    $typeList[] = 'application/vnd.ms-excel';       $extList[] = 'xls';
    $typeList[] = 'application/pdf';                $extList[] = 'pdf';
    $typeList[] = 'application/postscript';         $extList[] = 'ps';
    $typeList[] = 'application/mac-binhex40';       $extList[] = 'hqx';
    $typeList[] = 'application/x-gzip';             $extList[] = 'gz';
    $typeList[] = 'application/x-shockwave-flash';  $extList[] = 'swf';
    $typeList[] = 'application/x-stuffit';          $extList[] = 'sit';
    $typeList[] = 'application/x-tar';              $extList[] = 'tar';
    $typeList[] = 'application/zip';                $extList[] = 'zip';
    $typeList[] = 'application/x-tar';              $extList[] = 'tar';
    $typeList[] = 'application/x-tar';              $extList[] = 'tgz';
    $typeList[] = 'text/html';                      $extList[] = 'htm';
    $typeList[] = 'text/plain';                     $extList[] = 'txt';
    $typeList[] = 'text/rtf';                       $extList[] = 'rtf';
    $typeList[] = 'image/gif';                      $extList[] = 'gif';
    $typeList[] = 'image/jpeg';                     $extList[] = 'jpg';
    $typeList[] = 'image/png';                      $extList[] = 'png';
    $typeList[] = 'audio/midi';                     $extList[] = 'mid';
    $typeList[] = 'audio/mpeg';                     $extList[] = 'mp3';
    $typeList[] = 'audio/x-aiff';                   $extList[] = 'aif';
    $typeList[] = 'audio/x-pn-realaudio';           $extList[] = 'rm';
    $typeList[] = 'audio/x-pn-realaudio-plugin';    $extList[] = 'rpm';
    $typeList[] = 'audio/x-wav';                    $extList[] = 'wav';
    $typeList[] = 'video/mpeg';                     $extList[] = 'mpg';
    $typeList[] = 'video/quicktime';                $extList[] = 'mov';
    $typeList[] = 'video/x-msvideo';                $extList[] = 'avi';

    return array($typeList, $extList);
}

/**
 * Executes all the necessary operation to upload the file in the document tool
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param  array $uploadedFile - follows the $_FILES Structure
 * @param  string $baseWorkDir - base working directory of the module
 * @param  string $uploadPath  - destination of the upload.
 *                               This path is to append to $baseWorkDir
 * @param  int $maxFilledSpace - amount of bytes to not exceed in the base
 *                               working directory
 * @param  string $uncompress  - whether 'unzip' and file is a zip;
 *                               extract the content.
 * @param string $allowPHP     - if set to true, then there is no security check for .php files (works for zip archives only)
 * @return boolean : true if it succeds, false otherwise
 */

function treat_uploaded_file($uploadedFile, $baseWorkDir, $uploadPath, $maxFilledSpace, $uncompress= '', $allowPHP = false)
{

    if ( file_upload_failed($uploadedFile) )
      {
        $failureStr = get_file_upload_error_message($uploadedFile);
        return claro_failure::set_failure($failureStr);
    }

    if ( ! enough_size($uploadedFile['size'], $baseWorkDir, $maxFilledSpace))
    {
        return claro_failure::set_failure(get_lang('The upload has failed. There is not enough space in your directory'));
    }

    if (   $uncompress == 'unzip'
        && preg_match('/.zip$/i', $uploadedFile['name']) )
    {
        return treat_secure_uploaded_file_unzip($uploadedFile, $uploadPath,
                                                $baseWorkDir, $maxFilledSpace, $allowPHP);
    }
    else
    {
        /* TRY TO ADD AN EXTENSION TO FILES WITOUT EXTENSION */
        $fileName = $uploadedFile['name'] . add_extension_for_uploaded_file($uploadedFile);

        $fileName = trim($uploadedFile['name']);

        /* CHECK FOR NO DESIRED CHARACTERS */
        $fileName = replace_dangerous_char($fileName);

        /* HANDLE DANGEROUS FILE NAME FOR SERVER SECURITY */
        $fileName = get_secure_file_name($fileName);

        /* COPY THE FILE TO THE DESIRED DESTINATION */
        if ( move_uploaded_file($uploadedFile['tmp_name'],
            $baseWorkDir.$uploadPath . '/' . $fileName) )
        {
            chmod($baseWorkDir.$uploadPath . '/' . $fileName,CLARO_FILE_PERMISSIONS);
            return $fileName;
        }
        else return claro_failure::set_failure(get_lang('File upload failed'));
    }
}



/**
 * Securely manage all the unzipping process of an uploaded document
 *
 * @author Christophe Gesch� <moosh@claroline.net>
 *
 * @param  array  $uploadedFile - follows the $_FILES Structure
 * @param  string $uploadPath   - destination of the upload.
 *                                This path is to append to $baseWorkDir
 * @param  string $baseWorkDir  - base working directory of the module
 * @param  int $maxFilledSpace  - amount of bytes to not exceed in the base
 *                                working directory
 * @param string $allowPHP     - if set to true, then there is no security check for .php files
 *
 * @return boolean true if it succeeds false otherwise
 */

function treat_secure_uploaded_file_unzip($uploadedFile, $uploadPath,
                                          $baseWorkDir, $maxFilledSpace,$allowPHP= false)
{
    $uploadedFileName = $uploadedFile['tmp_name'];
    return treat_secure_file_unzip($uploadedFileName, $uploadPath,
                                          $baseWorkDir, $maxFilledSpace,$allowPHP);
}

/**
 * unzip safly a zipfile
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param string $fileName file name of zip
 * @param string $filePath file path of zip
 * @param string $extractPath
 * @param integer $maxFilledSpace (byte) count  of byte size aivailable
 * @param boolean $allowPHP whether True the file can't contain php or phtml files
 * @return true
 * @throws claro_failure on error
 */
function treat_secure_file_unzip($fileName, $filePath,
                                          $extractPath, $maxFilledSpace,$allowPHP= false)
{
    $zipFile = new pclZip($fileName);

    // Check the zip content (real size and file extension)

    $zipContentArray = $zipFile->listContent();
    
    if( ! is_array($zipContentArray) ) return false;
    
    foreach($zipContentArray as $thisContent)
    {
        if (!$allowPHP)
        {
            if ( preg_match('~.(php.?|phtml)$~i', $thisContent['filename']))
            {
                 return claro_failure::set_failure(get_lang('The zip file can not contain .PHP files'));
            }
        }
        if (!isset($realFileSize)) $realFileSize = 0;

        $realFileSize += $thisContent['size'];
    }

    if ( ! enough_size($realFileSize, $extractPath, $maxFilledSpace) )
    {
        return claro_failure::set_failure(get_lang('The upload has failed. There is not enough space in your directory'));
    }

    $extractedFileNameList = $zipFile->extract(
        PCLZIP_OPT_PATH,        $extractPath . $filePath,
        PCLZIP_OPT_SET_CHMOD,   CLARO_FILE_PERMISSIONS );

    if ( is_array($extractedFileNameList) )
    {
        return $extractedFileNameList;
    }
    else
    {
        return false;
    }
}

/**
 * retrieve the image path list in a html file
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param  string $htmlFile
 * @return array -  images path list
 */

function search_img_from_html($htmlFile)
{
    $imgPathList = array();

    $buffer = file_get_contents( $htmlFile );

    if ( preg_match_all('~<[[:space:]]*img[^>]*>~i', $buffer, $matches) )
    {
        $imgTagList = $matches[0];
    }

    unset($buffer);

    // Search the image file path from all the <IMG> tag detected

    if ( isset($imgTagList) && sizeof($imgTagList)  > 0)
    {
        foreach($imgTagList as $thisImgTag)
        {

            if ( preg_match('~src[[:space:]]*=[[:space:]]*[\"\']{1}([^\"\']+)[\"\']{1}~i',
                            $thisImgTag, $matches) )
            {
                $imgPathList[] = $matches[1];
            }
        }

        $imgPathList = array_unique($imgPathList);        // remove duplicate entries
    }

    return $imgPathList;

}

/**
 * creates a new directory trying to find a directory name
 * that doesn't already exist
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param string $desiredDirName complete path of the desired name
 * @return string actual directory name if it succeeds,
 *         boolean false otherwise
 */

function create_unexisting_directory($desiredDirName)
{

    $finalName = get_unexisting_file_name($desiredDirName);

    if ( mkdir($finalName, CLARO_FILE_PERMISSIONS) ) return $finalName;
    else                                             return false;
}

/**
 * creates a guinely file name that doesn't already exist
 * inside a specific path
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param string $desiredDirName complete path of the desired name
 * @return string actual file name if it succeeds,
 *         boolean false otherwise
 */


function get_unexisting_file_name($desiredDirName)
{
    $nb = '';

    $fileName = $desiredDirName;

    while ( file_exists($fileName.$nb) )
    {
        $nb += 1;
    }

    return $fileName.$nb;
}

/**
 *
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param array $uploadedFileCollection - follows the $_FILES Structure
 * @param  string $destPath
 * @return string $destPath
 */

function move_uploaded_file_collection_into_directory($uploadedFileCollection, $destPath)
{
    $uploadedFileNb = count($uploadedFileCollection['name']);
    
    $newFileList = array();

    for ($i=0; $i < $uploadedFileNb; $i++)
    {
        if ( !empty ( $uploadedFileCollection['name'] ) )
        {
            if (is_uploaded_file($uploadedFileCollection['tmp_name'][$i]))
            {
                if ( move_uploaded_file($uploadedFileCollection['tmp_name'][$i],
                                        $destPath.'/'.php2phps($uploadedFileCollection['name'][$i])) )
                {
                    $newFileList[$i] = basename($destPath).'/'.$uploadedFileCollection['name'][$i];
                }
                else
                {
                    // FIXME use clro_die or a better error reporting system
                    die('<center>can not move uploaded file</center>');
                }
            }
        }
    }

    return $newFileList;
}

function replace_img_path_in_html_file($originalImgPath, $newImgPath, $htmlFile)
{
    /*
     * Open the old html file and replace the src path into the img tag
     */

    $fp = fopen($htmlFile, 'r') or die ('<center>cannot open file</center>');

    $newHtmlFileContent = '';

    while ( !feof($fp) )
    {
        $buffer = fgets($fp, 4096);

        for ($i = 0, $fileNb = count($originalImgPath); $i < $fileNb ; $i++)
        {
            if ( array_key_exists($i, $newImgPath) )
            {
                $buffer = str_replace(    $originalImgPath[$i],
                                        './'.$newImgPath[$i],
                                        $buffer);
            }
        }

        $newHtmlFileContent .= $buffer;

    } // end while !feof

    fclose ($fp) or die ('<center>cannot close file</center>');;

    /*
     * Write the resulted new file
     */

    $fp = fopen($htmlFile, 'w')      or die('<center>cannot open file</center>');
    return  fwrite($fp, $newHtmlFileContent) or die('<center>cannot write in file</center>');
}

/**
 * Creates a file containing an html redirection to a given url
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param string $filePath
 * @param string $url
 * @return void
 */

function create_link_file($filePath, $url)
{

    $fileContent = '<html>'
                  .'<head>'
                  .'<meta http-equiv="content-Type" content="text/html;charset='.get_locale('charset').'">'
                  .'<meta http-equiv="refresh" content="0;url='.format_url($url).'">'
                  .'</head>'
                  .'<body>'
                  .'<div align="center">'
                  .'<a href="'.format_url($url).'">'.$url.'</a>'
                  .'</div>'
                  .'</body>'
                  .'</html>';

    create_file($filePath, $fileContent);
}

function create_file($filePath, $fileContent)
{
    $fp = fopen ($filePath, 'w') or die ('can not create file');
    
    if ( ( $ret = fwrite($fp, $fileContent) ) !== false )
    {
        @chmod($filePath,CLARO_FILE_PERMISSIONS);
    }

    fclose($fp);

    return $ret;
}

/**
 * Determine the maximum size allowed to upload. This size is based on
 * the tool $maxFilledSpace regarding the space already opccupied
 * by previous uploaded files, and the php.ini upload_max_filesize
 * and post_max_size parameters. This value is diplayed on the upload
 * form.
 *
 * @param integer $maxFilledSpace local max allowed file size
 *                                e.g. remaining place in
 *                                an allocated course directory
 *
 * @return integer : lower value between php.ini values of upload_max_filesize
 *                   and post_max_size
 *                   and the claroline value of size left in directory
 *
 * @see    - get_max_upload_size() uses  dir_total_space() function
 */
function get_max_upload_size($maxFilledSpace, $baseWorkDir)
{
        $php_uploadMaxFile = ini_get('upload_max_filesize');
        if (strstr($php_uploadMaxFile, 'M')) $php_uploadMaxFile = intval($php_uploadMaxFile) * 1048576;
        $php_postMaxFile  = ini_get('post_max_size');
        if (strstr($php_postMaxFile, 'M')) $php_postMaxFile     = intval($php_postMaxFile) * 1048576;
        $docRepSpaceAvailable  = $maxFilledSpace - dir_total_space($baseWorkDir);

        $fileSizeLimitList = array( $php_uploadMaxFile, $php_postMaxFile , $docRepSpaceAvailable );
        sort($fileSizeLimitList);
        list($maxFileSize) = $fileSizeLimitList;

    return $maxFileSize;
}

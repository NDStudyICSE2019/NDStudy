<?php // $Id: thumbnails.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * Thumbnails library
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 * @todo        phpdoc
 */

function img_get_extension( $imgPath )
{
    $pathInfo = pathinfo( $imgPath );
    
    return strtolower( $pathInfo['extension'] );
}

function img_is_type_supported( $type )
{
    $imgSupportedType = array( 'jpg', 'jpeg', 'gif', 'png', 'bmp' );
    return in_array( strtolower($type), $imgSupportedType );
}

class Thumbnailer
{
    var $thumbnailDirectory;
    var $documentRootDir;
    
    function Thumbnailer( $thumbnailDirectory, $documentRootDir )
    {
        $this->thumbnailDirectory = $thumbnailDirectory;
        $this->documentRootDir = $documentRootDir;
    }
    
    function createThumbnail( $srcFile, $thumbHeight, $thumbWidth )
    {
        $srcPath = $this->documentRootDir . '/' . $srcFile;
        
        if ( ! function_exists( 'gd_info' ) )
        {
            return $srcPath;
        }
        
        $type = img_get_extension( $srcFile );
        
        if ( ! file_exists( $this->thumbnailDirectory ) )
        {
            claro_mkdir( $this->thumbnailDirectory, CLARO_FILE_PERMISSIONS, true );
        }
        
        if ( ! img_is_type_supported( $type ) )
        {
            return false;
        }
        
        switch ( $type )
        {
            case 'png':
            {
                $image = @imagecreatefrompng( $srcPath );
            } break;
            case 'jpg':
            case 'jpeg':
            {
                $image = @imagecreatefromjpeg( $srcPath );
            } break;
            case 'gif':
            {
                $image = @imagecreatefromgif( $srcPath );
            } break;
            case 'bmp':
            {
                $image = @imagecreatefromwbmp( $srcPath );
            } break;
            default:
            {
                return false;
            }
        }
        
        // image loading failed use srcPath instead
        if ( ! $image )
        {
            Console::warning("Failed to create GD image from {$srcPath}");
            return $srcPath;
        }

        $oldWidth = imageSX( $image );
        $oldHeight = imageSY( $image );
        
        $thumbnail = imagecreatetruecolor( $thumbWidth, $thumbHeight );

        imagecopyresampled( $thumbnail, $image
            , 0,0,0,0, $thumbWidth, $thumbHeight, $oldWidth, $oldHeight );

        $thumbName = md5($srcFile) . '_' . $thumbWidth . 'x' . $thumbHeight . '.jpg';
        $thumbPath = $this->thumbnailDirectory . '/' . $thumbName;

        imagejpeg( $thumbnail, $thumbPath );
        
        imagedestroy($image);
        imagedestroy($thumbnail);
        
        return $thumbPath;
    }
    
    function getThumbnail( $imgPath, $newHeight, $newWidth )
    {
        $thumbName = md5($imgPath) . '_' . $newWidth . 'x' . $newHeight . '.jpg';
        $thumbPath = $this->thumbnailDirectory . '/' . $thumbName;
        
        
        if ( file_exists( $thumbPath )
            && filectime($this->documentRootDir . '/' . $imgPath) < filectime($thumbPath)
            && filemtime($this->documentRootDir . '/' . $imgPath) < filemtime($thumbPath) )
        {
            return $thumbPath;
        }
        else
        {
            if ( claro_debug_mode() )
            {
                Console::debug("Regenerating thumbnail for {$imgPath}");
            }
            
            return $this->createThumbnail( $imgPath, $newHeight, $newWidth );
        }
    }
}

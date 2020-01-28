<?php // $Id: thumbnail.php 13348 2011-07-18 13:58:28Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Thumbnail generator.
 *
 * @version     $Revision: 13348 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2.0
 * @package     KERNEL
 */

require dirname(__FILE__) . '/../inc/claro_init_global.inc.php';

require_once get_path('includePath') . '/lib/thumbnails.lib.php';
require_once get_path('includePath') . '/lib/fileManage.lib.php';
require_once get_path('includePath') . '/lib/file.lib.php';

if (claro_is_in_a_group() && claro_is_group_allowed())
{
    $documentRootDir = get_path('coursesRepositorySys') . claro_get_course_path(). '/group/'.claro_get_current_group_data('directory');
    $thumbnailsDirectory = get_path('coursesRepositorySys') . claro_get_course_path() . '/tmp/thumbs/'.claro_get_current_group_data('directory');
}
elseif (claro_is_in_a_course() && claro_is_course_allowed() )
{
    $documentRootDir = get_path('coursesRepositorySys') . claro_get_course_path(). '/document';
    $thumbnailsDirectory = get_path('coursesRepositorySys') . claro_get_course_path() . '/tmp/thumbs';
}
else
{
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$image = array_key_exists ( 'img', $_REQUEST )
    ? secure_file_path( $_REQUEST['img'] )
    : null
    ;
    
if ( is_null( $image ) )
{
    header('HTTP/1.1 403 Forbidden');
    exit;
}
    
$imagePath = $documentRootDir . $image;

if ( file_exists( $imagePath ) )
{
    list($width, $height, $type, $attr) = getimagesize($imagePath);

    $thumbWidth = 75;
    $newHeight = round( $height * $thumbWidth / $width );
    
    $thumbnailer = new Thumbnailer( $thumbnailsDirectory, $documentRootDir );

    $thumbPath = $thumbnailer->getThumbnail( $image, $newHeight, $thumbWidth );

    if ( ! $thumbPath )
    {
        $thumbPath = $imagePath;
    }

    // end session to avoid lock
    session_write_close();
    claro_send_file( $thumbPath );
    exit;
}
else
{
    header('HTTP/1.1 404 Not Found');
    exit;
}
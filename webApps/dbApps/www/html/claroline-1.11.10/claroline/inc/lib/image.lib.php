<?php // $Id: image.lib.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * Image manipulation library
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE
 * @package     KERNEL
 */

FromKernel::uses( 'core/url.lib' );


/*============================================================================
                        IMAGE MANIPULATION LIBRARY
  ============================================================================*/

/**
* @private allowedImageTypes
*/
// allowed image extensions
$allowedImageTypes = 'jpg|png|gif|jpeg|bmp';

/**
* cut string allowing word integrity preservation
*
* TODO : move to a more accurate library
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  string (string) string
* @param  length (int) length of the resulting string
* @param  allow_cut_word (boolean) allow word cutting default : true
* @param  extra_length (int) allow extra length to the string to
*        preserve word integrity
* @param  ending (string) append the given string at the end of the
*        cutted one
* @return (string) the cutted string
*/
function cutstring( $str, $length, $allow_cut_word = true,
    $extra_length = 0, $ending = "" )
{
    if( $allow_cut_word )
    {
        return substr( $str, 0, $length );
    }
    else
    {
        $words = preg_split( "~\s~", $str );

        $ret = "";

        foreach( $words as $word )
        {
            if( strlen( $ret . $word ) + 1 <= $length + $extra_length )
            {
                $ret.= $word. " ";
            }
            else
            {
                $ret = trim( $ret ) . $ending;
                break;
            }
        }

        return $ret;
    }
}


/**
* identifies images (i.e. if file extension is an allowed image extension)
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  string (string) file name
* @return (bool) true if the given file is an image file
*    else return false
* @global allowedImageTypes
* @see    images.lib.php#$allowedImagesType
*/
function is_image($fileName)
{
    global $allowedImageTypes;

    // if file extension is an allowed image extension
    if (preg_match("/\.(" . $allowedImageTypes . ")$/i", $fileName))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
* get image list from fileList
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  fileList (array) list of files in the current directory
* @param  allowed (bool) true if current user is allowed to view invisible images
* @return (array) array containing the index of image files in fileList
* @see    document.php#$fileList
*/
function get_image_list($fileList, $allowed = false)
{
    $imageList = array();

    if (is_array($fileList))
    {
        foreach($fileList as $numKey => $thisFile)
        {
            if (is_image( $thisFile['path'] )
                && ( ( $thisFile['visibility'] != 'i' ) || $allowed))
            {
                $imageList[] = $numKey;
            }
        }
    }

    return $imageList;
}

/**
* get image color depth from image info
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  img (string) path to image file
* @return (int) image depth in bits
* @see    document.php#$fileList
*/
function get_image_color_depth($img)
{
    $info = getimagesize($img);
    return $info['bits'];
}

function get_image_thumbnail_url( $file, $context = null )
{
    $url = get_path('url') . '/claroline/backends/thumbnail.php?img=' . rawurlencode($file);
    
    return Url::Contextualize( $url );
}

// THE EVIL NASTY ONE !
/**
* create thumbnails end return html code to display it
*
* this function could be modified to use any other method to
* create thumbnails
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  file (string) image name
* @param  thumbWidth (int) width for thumbnails
* @param  title (string) long description of the image
* @return (string) html code to display thumbnail
* @global curDirPath
* @global coursesRepositoryWeb;
* @global coursesRepositorySys;
* @global _course;
*/
function create_thumbnail($file, $thumbWidth, $title = '')
{
    global $courseDir;

    $imgPath = get_path('coursesRepositorySys')
        . $courseDir
        . $file
        ;

    list($width, $height, $type, $attr) = getimagesize($imgPath);

    if ($width > $thumbWidth)
    {
        $newHeight = round($height * $thumbWidth / $width);
    }
    else
    {
        $thumbWidth = $width;
        $newHeight = $height;
    }

    $img_url = get_image_thumbnail_url( $file );

    return '<img src="' . claro_htmlspecialchars( $img_url ) . '"
                 width="' . $thumbWidth . '"
                 height="' . $newHeight . '"
                 ' . $title . '
                 alt="' . $file . '" />' . "\n"
        ;

}

function image_search($file, $filePathList)
{
    // return array_search( $file, $filePathList );
    for ( $i = 0; $i < count( $filePathList ); $i++ )
    {
        if ( $filePathList[$i]['path'] == $file )
        {
            return $i;
        }
    }

    return 0;
}

/*-------------------------------------------------------------------------------
                             FUNCTIONS FOR IMAGE VIEWER
  -------------------------------------------------------------------------------*/

/**
* get the index of the current image in imageList from its index in fileList
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of index of image files in the current directory
* @param  fileIndex (array) index of image in fileList
* @return (int) index of current image in imageList
* @see    document.php#$fileList
*/
function get_current_index($imageList, $fileIndex)
{
    /*$index = array_search($fileIndex, $imageList);
    return $index;*/

    for ( $i = 0; $i < count( $imageList ); $i++ )
    {
        if ( $imageList[$i] == $fileIndex ) return $i;
    }

    return 0;
}

/**
* return true if there one or more image after the current image in imageList
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  index (int) index of current image in imageList
* @return (bool) true if there is one or more images after the current image
*              in imageList, else return false
*/
function has_next_image($imageList, $index)
{
    return (($index >= 0) && ($index < (count($imageList) - 1 )));
}

/**
* return true if there one or more image before the current image in imageList
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  index (int) index of current image in imageList
* @return (bool) true if there is one or more images before the current image
*              in imageList, else return false
*/
function has_previous_image($imageList, $index)
{
    return (($index > 0) && (count($imageList) > 0));
}

/**
* return the index of the next image in imageList
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  index (int) index of current image in imageList
* @return (int) index of the next image in imageList
*/
function get_next_image_index($imageList, $index)
{
    // @pre index is a valid index (ie 0 <= index < sizeof(imageList)
    // @pre imageList is not empty and has at least one element after index
    return $imageList[$index + 1];
    // @post return next index in imageList
}

/**
* return the index of the previous image in imageList
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  index (int) index of current image in imageList
* @return (int) index of the previous image in imageList
*/
function get_previous_image_index($imageList, $index)
{
    // @pre index is a valid index (ie 0 <= index < sizeof(imageList)
    // @pre index is not the first index of imageList (ie index > 0)
    // @pre imageList is not empty
    return $imageList[$index - 1];
    // @post return previous index in imageList
}

/**
* display link and thumbnail of previous image
* TODO : see if this function can be merge with display_link_to_next_image
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  fileList (array) list of files in the current directory
* @param  current (int) index of current image in imageList
* @global curDirPath
* @global thumbnailWidth
*/
function display_link_to_previous_image($imageList, $fileList, $current)
{
    global $curDirPath;
    global $searchCmdUrl;

    // get previous image
    $prevStyle = 'prev';

    if (has_previous_image($imageList, $current))
    {
        $prev = get_previous_image_index($imageList, $current);

        $prevName = $fileList[$prev]['path'];

        if ($fileList[$prev]['visibility'] == 'i')
        {
            $prevStyle = 'prev invisible';
        }

        $html = "<th class=\"". $prevStyle
            . "\" width=\"30%\">\n"
            ;

        $html .= "<a href=\"" . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF'] . "?docView=image&file="
            . download_url_encode($prevName) . "&cwd=" . $curDirPath
            . $searchCmdUrl ) ) . "\">" . "&lt;&lt;&nbsp;" . basename($prevName) . "</a>\n"
            ;

        $html .= "<br /><br />\n";

        // display thumbnail
        $html .= "<a href=\"" . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
            . "?docView=image&file=" . download_url_encode($prevName)
            . "&cwd=" . $curDirPath . $searchCmdUrl )) . "\">"
            . create_thumbnail($prevName, get_conf('thumbnailWidth'))
            ."</a>\n"
            ;

        $html .= "</th>\n";
        
        return $html;
    }
    else
    {
        return "<th class=\"". $prevStyle . "\" width=\"30%\">\n"
            . "<!-- empty -->\n"
            . "</th>\n"
            ;
    } // end if has previous image
}

/**
* display link and thumbnail of next image
* TODO : see if this function can be merge with display_link_to_previous_image
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  fileList (array) list of files in the current directory
* @param  current (int) index of current image in imageList
* @global curDirPath
* @global thumbnailWidth
*/
function display_link_to_next_image($imageList, $fileList, $current)
{
    global $curDirPath;
    global $searchCmdUrl;

    // get next image
    $nextStyle = 'next';

    if (has_next_image($imageList, $current))
    {
        $next = get_next_image_index($imageList, $current);

        $nextName = $fileList[$next]['path'];

        if ( $fileList[$next]['visibility'] == 'i')
        {
            $nextStyle = 'next invisible';
        }

        $html = "<th class=\"". $nextStyle . "\" width=\"30%\">\n";

        $html .= "<a href=\"" . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
            . "?docView=image&file=" . download_url_encode($nextName)
            . "&cwd=" . $curDirPath . $searchCmdUrl )) ."\">". basename($nextName)
            . "&nbsp;&gt;&gt;</a>\n"
            ;

        $html .= "<br /><br />\n";

        // display thumbnail
        $html .= "<a href=\"" . claro_htmlspecialchars( Url::Contextualize( $_SERVER['PHP_SELF']
            . "?docView=image&file=" . download_url_encode($nextName)
            . "&cwd=" . $curDirPath . $searchCmdUrl )). "\">"
            . create_thumbnail($nextName, get_conf('thumbnailWidth') )
            . "</a>\n"
            ;

        $html .= "</th>\n";
        
        return $html;
    }
    else
    {
        return "<th class=\"". $nextStyle . "\" width=\"30%\">"
            . "<!-- empty -->\n"
            . "</th>\n"
            ;
    } // enf if previous image

}


/*-------------------------------------------------------------------------------
                        FUNCTIONS FOR THUMBNAILS VIEWER
  -------------------------------------------------------------------------------*/

/**
* return true if there are one or more pages left to display after the current one
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  page (int) number of current page
* @return (bool) true if there are one or more pages left to display after the current one
*/
function has_next_page($imageList, $page)
{
    $numberOfCols = get_conf('numberOfCols');
    $numberOfRows = get_conf('numberOfRows');

    if (($page * $numberOfCols * $numberOfRows) < count($imageList))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
* return true if there one or more pages left to display before the current one
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  imageList (array) list of image indices
* @param  index (int) index of current image in imageList
* @return (bool) true if there are one or more pages left to display before the current one
*/
function has_previous_page($imageList, $page)
{
    return ($page != 1 && count($imageList) != 0);
}

/**
* return the index of the first image of the given page in imageList
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  page (int) number of the page
* @return (int) index of the first image of the given page in imageList
*/
function get_offset($page)
{
    $numberOfCols = get_conf('numberOfCols');
    $numberOfRows = get_conf('numberOfRows');

    if ($page == 1)
    {
        $offset = 0;
    }
    else
    {
        $offset = (($page - 1) * $numberOfCols * $numberOfRows);
    }

    return $offset;
}

/**
* return the number of the page on which the image is located
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param  offset (int) index of the image in imageList
* @return (int) number of the page on which the image is located
*/
function get_page_number($offset)
{
    $numberOfCols = get_conf('numberOfCols');
    $numberOfRows = get_conf('numberOfRows');

    $page = floor($offset / ($numberOfCols * $numberOfRows)) + 1;

    return $page;
}

/**
* display a page of thumbnails
*
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
* @param imageList (array) list containing all image file names
* @param fileList (array) file properties
* @param page (int) current page number
* @param thumbnailWidth (int) width of thumbnails
* @param colWidth (int) width of columns
* @param numberOfCols (int) number of columns
* @param numberOfRows (int) number of rows
* @global curDirPath
*/
function display_thumbnails($imageList, $fileList, $page
    , $thumbnailWidth, $colWidth, $numberOfCols, $numberOfRows)
{
    global $curDirPath;
    global $searchCmdUrl;

    // get index of first thumbnail on the page
    $displayed = get_offset($page);
    
    $html = '';

    // loop on rows
    for($rows = 0; $rows < $numberOfRows; $rows++)
    {
        $html .= "<tr>\n";

        // loop on columns
        for($cols = 0; $cols < $numberOfCols; $cols++)
        {
            // get index of image
            $num = $imageList[$displayed];

            // get file name
            $fileName = $fileList[$num]['path'];

            // visibility style
            if ( $fileList[$num]['visibility'] == 'i' )
            {
                $style = "style=\"font-style: italic; color: silver;\"";
            }
            else
            {
                $style = '';
            }

            // display thumbnail
            /*echo "<td style=\"text-align: center;\" style=\"width:"
                . $colWidth . "%;\">\n"
                ;*/
            
            // omit colwidth since already in th
                
            $html .= "<td style=\"text-align: center;\">\n"
                ;

            $html .= "<a href=\""
                . claro_htmlspecialchars(
                    Url::Contextualize( $_SERVER['PHP_SELF'] . "?docView=image&file="
                    . download_url_encode($fileName)
                    . "&cwd=". $curDirPath . $searchCmdUrl ))
                ."\">"
                ;

            // display image description using title attribute
            $title = "";
            if ( $fileList[$num]['comment'] )
            {
                $text = $fileList[$num]['comment'];
                $text = cutstring( $text, 40, false, 5, "..." );

                $title = "title=\"" . $text . "\"";
            }

            $html .= create_thumbnail($fileName, $thumbnailWidth, $title);

            // unset title for the next pass in the loop
            unset($title );

            $html .= "</a>\n";

            // display image name
            $imgName = ( strlen( basename( $fileList[$num]['path'] ) ) > 25 )
                ? substr( basename( $fileList[$num]['path'] ), 0, 25 ) .  "..."
                : basename( $fileList[$num]['path'] )
                ;

            $html .= "<p " . $style . ">" . $imgName  . "</p>";

            $html .= "</td>\n";

            // update image number
            $displayed++;

            // finished ?
            if ($displayed >= count($imageList))
            {
                $html .= "</tr>\n";
                return $html;
            }
        } // end loop on columns

        $html .= "</tr>\n";

    } // end loop on rows
    
    return $html;
}

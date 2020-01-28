<?php // $Id: htmlxtra.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

/**
 * return html to display a media from the media.
 *
 * jpg/jpeg, gif, png, bmp are include in a img tag
 * swf, flv,mp3 are embed
 * other are linked
 *
 * @param string $filePath
 * @return string html to include in the page
 */

function claro_html_media_player($filePath, $fileUrl)
{
     //if( !file_exists($filePath) )return false;

    // get extension
    $pathParts = pathinfo($filePath);

    // filename
    if( isset($pathParts['basename']) ) $basename = $pathParts['basename'];
    else                                $basename = '';
    
    // extension
    if( isset($pathParts['extension']) )    $extension = strtolower($pathParts['extension']);
    else                                    $extension = '';

    $returnedString = '<p>'."\n";
    switch($extension)
    {
        //-- image
        case 'jpg' :
        case 'jpeg' :
        case 'gif' :
        case 'png' :
        case 'bmp' :
            $returnedString .= '<img src="'.$fileUrl.'" alt="'.$basename.'" />'."\n";
            break;

        //-- flash animation
        case 'swf' :
            $returnedString .=
                '<object type="application/x-shockwave-flash" data="'.$fileUrl.'" width="320" height="240">' . "\n"
                .'<param name="movie" value="'.$fileUrl.'">' . "\n"
                .'<param name="wmode" value="transparent" />'
                .'<small>' . "\n"
                .'<a href="'.$fileUrl.'">'.get_lang('Download file').'</a>' . "\n"
                .'</small>'."\n"
                .'</object>' . "\n";
        break;

        //-- flash video
        case 'flv' :
            $playerUrl = get_conf('urlAppend') . '/claroline/inc/swf/player_flv.swf';
            $skinUrl = get_conf('urlAppend') . '/claroline/inc/swf/player_flv.jpg';

            $params[] = 'flv='.$fileUrl;
            $params[] = 'fake='.time();
            $params[] = 'showstop=1';
            $params[] = 'skin=' . $skinUrl;
            $params[] = 'margin=10';
            $params[] = 'showvolume=1';
            $params[] = 'loadingcolor=0';
            $params[] = 'bgcolor1=ffffff';
            $params[] = 'bgcolor2=cccccc';
            $params[] = 'buttoncolor=999999';
            $params[] = 'buttonovercolor=0';
            $params[] = 'slidercolor1=cccccc';
            $params[] = 'slidercolor2=aaaaaa';
            $params[] = 'sliderovercolor=666666';
            $params[] = 'playercolor=eeeeee';
            // for IE, to prevent a display bug (player is shown but is very small)
            $params[] = 'width=320';
            $params[] = 'height=240';

            $returnedString .=
                '<object type="application/x-shockwave-flash" data="'.$playerUrl.'?'.implode('&amp;',$params).'" width="320" height="240">' . "\n"
                .'<param name="movie" value="'.$playerUrl.'?'.implode('&amp;',$params).'" />' . "\n"
                //.'<param name="FlashVars" value="'.implode('&amp;',$params).'" />' . "\n"
                .'<param name="wmode" value="transparent" />' . "\n"
                .'</object>' . "\n";
        break;

        //-- mp3 sound
        case 'mp3' :
            // more infos about mp3 player : http://resources.neolao.com/flash/components/player_mp3
            $playerUrl = get_conf('urlAppend') . '/claroline/inc/swf/player_mp3.swf';

            $params[] = 'mp3='.$fileUrl;
            $params[] = 'fake='.time();
            $params[] = 'showstop=1';
            $params[] = 'showinfo=1';
            $params[] = 'loadingcolor=0';
            $params[] = 'bgcolor1=eeeeee';
            $params[] = 'bgcolor2=eeeeee';
            $params[] = 'buttoncolor=999999';
            $params[] = 'buttonovercolor=0';
            $params[] = 'slidercolor1=cccccc';
            $params[] = 'slidercolor2=999999';
            $params[] = 'sliderovercolor=666666';
            $params[] = 'textcolor=0';

            $returnedString .=
                '<object type="application/x-shockwave-flash" data="'.$playerUrl.'" width="200" height="20">' . "\n"
                .'<param name="movie" value="'.$playerUrl.'" />' . "\n"
                .'<param name="FlashVars" value="'.implode('&amp;',$params).'" />' . "\n"
                .'<param name="wmode" value="transparent" />' . "\n"
                .'</object>' . "\n"
                .'<br />' . "\n"
                .'<small>' . "\n"
                .'<a href="'.$fileUrl.'" target="_blank">'.get_lang('Download file').'</a>' . "\n"
                .'</small>'."\n\n";
        break;

        //-- not implemented media player
        default :
            $returnedString .= '<a href="'.$fileUrl.'" target="_blank">'.get_lang('Download file').'</a>'."\n";
        break;

    }
    $returnedString .= '</p>'."\n";

    return $returnedString;
}

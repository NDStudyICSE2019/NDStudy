<?php  // $Id: icon.lib.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * Icon library
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE
 * @package     KERNEL
 */

/**
 * Returns the (system) path to the current iconset
 */
function get_current_iconset_path()
{
    return get_path('imgRepositorySys');
}

/**
 * Returns the (web) url to the current iconset
 */
function get_current_iconset_url()
{
     return get_path('imgRepositoryWeb');
}

/**
 * Returns the url of the given icon, replaced by get_icon_url()
 * @deprecated
 * @since v1.9
 * @see get_icon_url()
 */
 
function get_icon( $fileName )
{
    return get_icon_url( $fileName );
}


/**
 * Returns the url of the given icon
 *
 * @param string $fileName file name with or without extension
 * @param string $moduleLabel label of the module (optional)
 * @return string icon url
 *         mixed null if icon not found
 */
function get_icon_url( $fileName, $moduleLabel = null )
{
    $fileInfo = pathinfo( $fileName );
    
    $currentModuleLabel = get_current_module_label();
    
    $imgPath = array();
    
    // Search kernel first for performance !
    
    // claroline theme iconset
    $imgPath[get_current_iconset_path()] = get_current_iconset_url();
    // claroline web/img <--- is now the default location find using get_current_iconset_url
    //$imgPath[get_path( 'rootSys' ) . 'web/img/'] = get_path('url') . '/web/img/';
    
    if ( !empty($moduleLabel) )
    {
        // module img directory
        $imgPath[get_module_path($moduleLabel).'/img/'] = get_module_url($moduleLabel).'/img/';
        // module root directory
        $imgPath[get_module_path($moduleLabel).'/'] = get_module_url($moduleLabel).'/';
    }
    
    if ( !empty( $currentModuleLabel ) )
    {
        // module img directory
        $imgPath[get_module_path($currentModuleLabel).'/img/'] = get_module_url($currentModuleLabel).'/img/';
        // module root directory
        $imgPath[get_module_path($currentModuleLabel).'/'] = get_module_url($currentModuleLabel).'/';
    }
    
    // img directory in working directory
    $imgPath['./img/'] = './img/';
    // working directory
    $imgPath['./'] = './';
    
    if ( !empty( $fileInfo['extension'] ) )
    {
        $img = array( $fileName );
    }
    else
    {
        $img = array(
            $fileName . '.png',
            $fileName . '.gif'
        );
    }
    
    foreach ( $imgPath as $tryPath => $tryUrl )
    {
        foreach ( $img as $tryImg )
        {
            if ( claro_debug_mode() ) pushClaroMessage("Try ".$tryPath.$tryImg, 'debug');
            
            if ( file_exists( $tryPath.$tryImg ) )
            {
                if ( claro_debug_mode() ) pushClaroMessage("Using ".$tryPath.$tryImg, 'debug');
                
                return $tryUrl.$tryImg.'?'.filemtime($tryPath.$tryImg);
            }
        }
    }
    
    if ( claro_debug_mode() ) pushClaroMessage("Icon $fileName not found",'error');
    
    // WORKAROUND : avoid double submission if missing image !!!!
    return 'image_not_found.png';
}

/**
 * Includes an icon in html code
 * @param string fileName file name with or without extension
 * @param string toolTip tooltip for the image (optional, default none)
 * @param string alternate alt text for the image (optional, default fileName)
 * @return string html code for the image
 */
function claro_html_icon( $fileName, $toolTip = null, $alternate = null )
{
    $alt = $alternate
        ? ' alt="' . $alternate . '"'
        : ' alt="' . claro_htmlspecialchars( $fileName ) . '"'
        ;
        
    $title = $toolTip
        ? ' title="' . claro_htmlspecialchars( $toolTip ) .'"'
        : ''
        ;
        
    if ( false !== ( $iconUrl = get_icon_url( $fileName ) ) )
    {
        return '<img src="' . $iconUrl .'"'
            . $alt . $title
            . ' />'
            ;
    }
    else
    {
        return false;
    }
}

/**
 * Add a claroCmd button with icon to HTML output
 * @param string targetUrl url of the target page
 * @param string iconName name of the icon with or without file extension
 *      (optional, default none)
 * @param string buttonText text of the button
 *      (optional, default none)
 * @param string toolTip tooltip of the button
 *      (optional, default none)
 */
function claro_html_icon_button( $targetUrl, $iconName = '', $buttonText = '', $toolTip = '' )
{
    return '<a class="claroCmd"' . "\n"
        . ' href="'.$targetUrl.'"'
        . ( $toolTip ? "\n" . ' title="'.claro_htmlspecialchars($toolTip).'"' : '' )
        . '>' . "\n"
        . ( $iconName ? claro_html_icon( $iconName ) . "\n" : '' )
        . ( $buttonText ? claro_htmlspecialchars($buttonText) . "\n" : '' )
        . '</a>'
        ;
}

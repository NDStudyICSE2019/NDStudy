<?php // $Id: helpers.lib.php 14320 2012-11-09 10:36:57Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Helper functions and classes.
 *
 * @version     $Revision: 14320 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

FromKernel::uses ( 'core/url.lib' );

/**
 * Create an html attribute list from an associative array attribute=>value
 * @param   array $attributes
 * @return  string
 */
function make_attribute_list( $attributes )
{
    $attribList = '';
    
    if ( is_array( $attributes ) && !empty( $attributes ) )
    {
        foreach ( $attributes as $attrib => $value )
        {
            $attribList .= ' ' . $attrib . '="'
                . claro_htmlspecialchars($value) . '"'
                ;
        }
    }
    
    return $attribList;
}
 
/**
 * Create an html link to the given url with the given text and attributes
 * @param   string text
 * @param   string url
 * @param   array attributes (optional)
 * @return  string
 */
function link_to ( $text, $url, $attributes = null )
{
    $url = htmlspecialchars_decode( $url );
    
    $link = '<a href="'
        . claro_htmlspecialchars( $url ) . '"'
        . make_attribute_list( $attributes )
        . '>' . claro_htmlspecialchars( $text ) . '</a>'
        ;
        
    return $link;
}

/**
 * Create an html link to the given url inside claroline with the given
 * text and attributes
 * @param   string text
 * @param   string url inside claroline
 * @param   array context (cid, gid)
 * @param   array attributes (optional)
 * @return  string
 */
function link_to_claro ( $text, $url = null, $context = null, $attributes = null )
{
    if ( empty ( $url ) )
    {
        $url = get_path( 'url' ) . '/index.php';
    }
    
    $urlObj = new Url( $url );
    
    if ( $context )
    {
        $urlObj->relayContext($context);
    }
    else
    {
        $urlObj->relayCurrentContext();
    }
    
    $url = $urlObj->toUrl();
    
    return link_to ( $text, $url, $attributes );
}

/**
 * Create an html link to the given course or course tool
 * text and attributes
 * @param   string text
 * @param   string courseId
 * @param   array attributes (optional)
 * @return  string
 */
function link_to_course ( $text, $courseId, $attributes = null )
{
    $url = get_path('url') . '/claroline/course/index.php?cid='.$courseId;
    $urlObj = new Url( $url );
    
    $url = $urlObj->toUrl();
    
    return link_to ( $text, $url, $attributes );
}

/**
 * Create an html link to the given course or course tool
 * text and attributes
 * @param   string text
 * @param   string toolLabel
 * @param   array context (cid, gid)
 * @param   array attributes (optional)
 * @return  string
 */
function link_to_tool ( $text, $toolLabel = null, $context = null, $attributes = null )
{
    $url = get_module_entry_url( $toolLabel );
    
    return link_to_claro ( $text, $url, $context, $attributes );
}

/**
 * Include the rendering of the given dock
 * @param string $dockName dock name
 * @param boolean $useList use <li> in rendering
 * @since 1.10
 * @return string rendering
 */
function include_dock( $dockName, $useList = false )
{
    $dock = new ClaroDock( $dockName );
    
    if ( $useList )
    {
        $dock->mustUseList();
    }
    
    echo $dock->render();
}

/**
 * Include a template file
 * @param   string $template name of the template
 */
function include_template( $template )
{
    $template = secure_file_path( $template );
    
    $customTemplatePath = get_path('rootSys') . '/platform/templates/'.$template;
    $defaultTemplatePath = get_path('includePath') . '/templates/'.$template;
    
    if ( file_exists( $customTemplatePath ) )
    {
        include $customTemplatePath;
    }
    elseif ( file_exists( $defaultTemplatePath ) )
    {
        include $defaultTemplatePath;
    }
    else
    {
        throw new Exception("Template not found {$template} "
            . "at custom location {$customTemplatePath} "
            . "or default location {$defaultTemplatePath} !");
    }
}

/**
 * Include a textzone file
 * @param   string $textzone name of the textzone
 * @param   string $defaultContent content displayed if textzone cannot be found or doesn't exist
 */
function include_textzone( $textzone, $defaultContent = null )
{
    $textzone = secure_file_path( $textzone );
    // find correct path where the file is
    // FIXME : move ALL textzones to the same location !
    if( file_exists( get_path('rootSys') . './platform/textzone/'.$textzone) )
    {
        $textzonePath = get_path('rootSys') . './platform/textzone/'.$textzone;
    }
    elseif( file_exists( get_path('rootSys') . './'.$textzone) )
    {
        $textzonePath = get_path('rootSys') . './'.$textzone;
    }
    else
    {
        $textzonePath = null;
    }
    
    // textzone content
    if ( !is_null( $textzonePath ) )
    {
        include $textzonePath;
    }
    else
    {
        if( !is_null( $defaultContent) )
        {
            echo $defaultContent;
        }
        
        if( claro_is_platform_admin() )
        {
            // help tip for administrator
            echo '<p>'
            .    get_lang('blockTextZoneHelp', array('%textZoneFile' => $textzone))
            .    '</p>';
        }
    }
    
    // edit link
    if( claro_is_platform_admin() )
    {
        echo '<p>' . "\n"
        .    '<a href="'.get_path('rootAdminWeb').'managing/editFile.php?cmd=rqEdit&amp;file='.$textzone.'">' . "\n"
        .    '<img src="'.get_icon_url('edit').'" alt="" />' . get_lang('Edit text zone') . "\n"
        .    '</a>' . "\n"
        .    '</p>' . "\n";
    }
}

/**
 * Include the link to a given css
 * @param name of the css without the complete path
 * @param css media
 * @return string
 */
function link_to_css( $css, $media = 'all' )
{
    if( file_exists(get_path('clarolineRepositorySys') . '../platform/css/' . $css) )
    {
        $date = filemtime(get_path('clarolineRepositorySys') . '../platform/css/' . $css);
        
        return '<link rel="stylesheet" type="text/css" href="'
            . get_path('clarolineRepositoryWeb') . '../platform/css/' . $css . "?{$date}"
            . '" media="'.$media.'" />'
            ;
    }
    elseif( file_exists(get_path('rootSys') . 'web/css/' . $css) )
    {
        $date = filemtime(get_path('rootSys') . 'web/css/' . $css);
        
        return '<link rel="stylesheet" type="text/css" href="'
            . get_path( 'url' ) . '/web/css/' . $css . "?{$date}"
            . '" media="'.$media.'" />'
            ;
    }
    
    return '';
}


/**
 * @param int $gid 
 * @param string $courseId 
 * @param boolean $active if set to true, only actvated tool will be considered for display
 */
function get_group_tool_menu( $gid = null, $courseId = null, $active = true )
{
    $toolList = array();
    
    if ( is_null( $gid ) )
    {
        $gid = claro_get_current_group_id();
    }
    
    if ( is_null( $courseId ) )
    {
        $courseId = claro_get_current_course_id();
    }
    
    require_once dirname(__FILE__) . '/../group.lib.inc.php';
    
    $groupToolList = get_group_tool_list( $courseId, $active );

    // group space links

    /* $toolList[] =
    claro_html_cmd_link(
        claro_htmlspecialchars(Url::Contextualize( get_module_url('CLGRP').'/group_space.php' ))
        , '<img src="' . get_icon_url('group') . '" alt="" />&nbsp;'
        . get_lang('Group area')
    ); */

    $courseGroupData= claro_get_main_group_properties( $courseId );

    foreach ( $groupToolList as $groupTool )
    {
        if ( is_tool_activated_in_groups( $courseId, $groupTool['label'] )
            && ( isset( $courseGroupData['tools'][$groupTool['label']] )
                && $courseGroupData['tools'][$groupTool['label']]
            )
        )
        {
            $toolList[] = claro_html_cmd_link(
                claro_htmlspecialchars(Url::Contextualize(
                    get_module_url($groupTool['label'])
                    . '/' . $groupTool['url'] ))
                , '<img src="' . get_module_url($groupTool['label']) . '/' . ($groupTool['icon']) . '" alt="" />'
                    . '&nbsp;'
                    . claro_get_tool_name ($groupTool['label'])
                , array('class' => $groupTool['visibility'] ? 'visible':'invisible')
            );
        }
    }
    
    if ( count( $toolList ) )
    {
        return claro_html_menu_horizontal( $toolList );
    }
    else
    {
        return '';
    }
}

/**
 * Get url of a module help page
 * @param string $block name of the help block to display
 * @param string $module module label or 'platform'
 * @return string 
 */
function get_help_page_url( $block, $module = 'platform' )
{
    $helpUrl = new Url( get_path('url').'/claroline/help/index.php' );
    
    if ( $module )
    {
        $helpUrl->addParam( 'module', $module );
    }
    
    $helpUrl->addParam( 'block', $block );
    
    return $helpUrl->toUrl();
}

/**
 * get the url of the homepage of a course site
 * @param string $courseId course sysCode
 * @return type string
 */
function claro_get_course_homepage_url( $courseId )
{
    return get_path('url')
        .'/claroline/course/index.php?cid='.$courseId;
}

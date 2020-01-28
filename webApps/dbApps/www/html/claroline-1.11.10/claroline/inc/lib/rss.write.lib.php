<?php // $Id: rss.write.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

/**
 * CLAROLINE
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLRSS
 * @since       1.9
 * @author      Claro Team <cvs@claroline.net>
 * @see         http://www.stervinou.com/projets/rss/
 * @see         http://feedvalidator.org/
 * @see         http://rss.scripting.com/
 */

define('RSS_FILE_EXT', 'xml');

include_once claro_get_conf_repository() . 'CLKCACHE.conf.php';
include_once claro_get_conf_repository() . 'rss.conf.php';

require_once dirname(__FILE__) . '/thirdparty/feedcreator.class.php';

function build_rss($context)
{
    if (is_array($context) && count($context) > 0)
    {

        $rss = new UniversalFeedCreator();

        if (array_key_exists(CLARO_CONTEXT_COURSE,$context))
        {
            // $rssFilePath .= $context[CLARO_CONTEXT_COURSE] . '.';

            $_course = claro_get_course_data($context[CLARO_CONTEXT_COURSE]);

            $rss->title = '[' . get_conf('siteName') . '] '.$_course['officialCode'];
            $rss->description = $_course['name'];
            $rss->editor = $_course['titular'] == '' ? get_conf('administrator_name') : $_course['titular'];
            $rss->editorEmail = $_course['email'] == '' ? get_conf('administrator_email') : $_course['email'];
            $rss->link = get_path('rootWeb') .  get_path('coursesRepositoryAppend') . claro_get_course_path();
            $rss->generator = 'Feedcreator';

            if (array_key_exists(CLARO_CONTEXT_GROUP,$context))
            {
                // $rssFilePath .= 'g'.$context[CLARO_CONTEXT_GROUP] . '.';
                $rss->title .= '[' . get_lang('Group') . $context[CLARO_CONTEXT_GROUP] . ']';
                $rss->description .= get_lang('Group') . $context[CLARO_CONTEXT_GROUP];
            }
        }
        else
        {
            $rss->title = '[' . get_conf('siteName') . '] '.$_course['officialCode'];
            $rss->description = $_course['name'];
            $rss->editor = get_conf('administrator_name');
            $rss->editorEmail = get_conf('administrator_email');
            $rss->link = get_path('rootWeb');
        }

        $rss->language = get_locale('iso639_1_code');
        $rss->docs = 'http://blogs.law.harvard.edu/tech/rss';
        $rss->pubDate = date("r",time());

        $toolLabelList = rss_get_tool_compatible_list();

        //var_dump($toolLabelList);
        $rssItems = array();

        foreach ( $toolLabelList as $toolLabel )
        {
            /*var_dump(is_tool_activated_in_course(
                get_tool_id_from_module_label( $toolLabel ),
                $context[CLARO_CONTEXT_COURSE]
            ));*/

            if ( is_tool_activated_in_course(
                get_tool_id_from_module_label( $toolLabel ),
                $context[CLARO_CONTEXT_COURSE]
            ) )
            {
                if ( ! is_module_installed_in_course($toolLabel,$context[CLARO_CONTEXT_COURSE]) )
                {
                    install_module_in_course( $toolLabel,$context[CLARO_CONTEXT_COURSE] );
                }

                $rssToolLibPath = get_module_path($toolLabel) . '/connector/rss.write.cnr.php';
                $rssToolFuncName =  $toolLabel . '_write_rss';

                if ( file_exists( $rssToolLibPath ) )
                {
                    include_once $rssToolLibPath;

                    if (function_exists($rssToolFuncName))
                    {
                        $rssItems = array_merge( $rssItems, call_user_func($rssToolFuncName, $context ) );
                    }
                }
            }
        }

        $sortDate = array();

        foreach ( $rssItems as $key => $rssItem )
        {
            $sortDate[$key] = $rssItem['pubDate'];
        }

        // die(var_export($sortDate, true));

        array_multisort( $sortDate, SORT_DESC, $rssItems );

        foreach ( $rssItems as $rssItem )
        {
            $item = new FeedItem();
            $item->title = claro_utf8_encode( $rssItem['title'], get_conf('charset') );
            $item->description = claro_utf8_encode( $rssItem['description'] );
            $item->category = $rssItem['category'];
            $item->guid = $rssItem['guid'];
            $item->link = $rssItem['link'];
            $item->date = $rssItem['pubDate'];

            $rss->addItem( $item );
        }

        return $rss->outputFeed("RSS2.0");

    }

    return false;

}


/**
 * Build the list of claro label of tool having a rss creator.
 *
 * @return array of claro_label
 *
 * This function use 2 level of cache.
 * - memory Cache to compute only one time the list by script execution
 * - if enabled : use cache lite
 */
function rss_get_tool_compatible_list()
{
    static $rssToolList = null;

    if ( is_null( $rssToolList ) )
    {
        $rssToolList = array();

        $toolList = $GLOBALS['_courseToolList'];

        foreach ( $toolList as $tool )
        {
            $toolLabel = trim($tool['label'],'_');

            $rssToolLibPath = get_module_path($toolLabel) . '/connector/rss.write.cnr.php';

            $rssToolFuncName =  $toolLabel . '_write_rss';

            if ( file_exists($rssToolLibPath)
            )
            {
                require_once $rssToolLibPath;

                if (function_exists($rssToolFuncName))
                {
                    $rssToolList[] = $toolLabel;
                }
            }

        }

    } // if is_null $rssToolList -> if not use static

    return $rssToolList;
}

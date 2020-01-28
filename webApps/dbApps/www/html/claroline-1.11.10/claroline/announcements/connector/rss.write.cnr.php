<?php // $Id: rss.write.cnr.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLANN
 * @subpackage  CLRSS
 * @author      Claro Team <cvs@claroline.net>
 */

function CLANN_write_rss($context)
{
    if (is_array($context) && count($context)>0)
    {
        $courseId = (array_key_exists(CLARO_CONTEXT_COURSE,$context)) ? $context[CLARO_CONTEXT_COURSE] : claro_get_current_course_id();
    }

    require_once dirname(__FILE__) . '/../lib/announcement.lib.php';

    $toolNameList = claro_get_tool_name_list();
    $announcementList = announcement_get_item_list($context, 'DESC');

    $rssList = array();
    foreach ($announcementList as $announcementItem)
    {
        if('SHOW' == $announcementItem['visibility'])
        {
            $rssList[] = array( 'title'       => trim($announcementItem['title'])
            ,                   'category'    => trim($toolNameList['CLANN'])
            ,                   'guid'        => get_path('rootWeb') .'claroline/' . 'announcements/announcements.php?cidReq=' . $courseId . '&l#ann'.$announcementItem['id']
            ,                   'link'        => get_path('rootWeb') .'claroline/' . 'announcements/announcements.php?cidReq=' . $courseId . '&l#ann'.$announcementItem['id']
            ,                   'description' => trim(str_replace('<!-- content: html -->','',$announcementItem['content']))
            ,                   'pubDate'     => date('r', stripslashes(strtotime($announcementItem['time'])))
            ,                   'dc:date'     => date('c', stripslashes(strtotime($announcementItem['time'])))
          //,                   'author'      => $_course['email']
            );
        }
    }
    return $rssList;
}

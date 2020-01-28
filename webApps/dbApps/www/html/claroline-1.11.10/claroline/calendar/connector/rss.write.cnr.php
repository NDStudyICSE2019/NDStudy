<?php // $Id: rss.write.cnr.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 13708 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLCAL
 * @package     CLRSS
 * @author      Claro Team <cvs@claroline.net>
 */

function CLCAL_write_rss($context)
{

    if (is_array($context) && count($context)>0)
    {
        $courseId = (array_key_exists(CLARO_CONTEXT_COURSE,$context)) ? $context[CLARO_CONTEXT_COURSE] : claro_get_current_course_id();
    }

    require_once dirname(__FILE__) . '/../lib/agenda.lib.php';
    $eventList    = agenda_get_item_list($context, 'ASC');
    $toolNameList = claro_get_tool_name_list();

    $itemRssList = array();
    foreach ($eventList as $item)
    {
        if('SHOW' == $item['visibility'] )
        {
            $item['timestamp'] = strtotime($item['day'] . ' ' . $item['hour'] );
            $item['pubDate'] = date('r', $item['timestamp']);
            $item['dc:date'] = date('c', $item['timestamp']);

            //prepare values
            //c ISO 8601 date (added in PHP 5) 2004-02-12T15:19:21+00:00
            $item['dc:date'] = ('c' == $item['dc:date'])?date('Y-m-d\TH:i:sO', $item['timestamp']):$item['dc:date'];
            $item['content'] = ((isset($item['speakers'])) ? (get_lang('Speakers').': '.$item['speakers'].'<br/>') : (''))
                             . trim(str_replace('<!-- content: html -->','',$item['content']));

            $itemRssList[] = array(
                'title'       => $item['title'],
                'category'    => trim($toolNameList['CLCAL']),
                'guid'        => get_path('rootWeb') .'claroline/' . 'calendar/agenda.php?cidReq=' . $courseId . '&amp;l#item' . $item['id'],
                'link'        => get_path('rootWeb') .'claroline/' . 'calendar/agenda.php?cidReq=' . $courseId . '&amp;l#item' . $item['id'],
                'description' => $item['content'],
                'pubDate'     => $item['pubDate'],
                'dc:date'     => $item['dc:date'],
            #   'author' => $_course['email'],
            );
        }
    }

    return $itemRssList;
}

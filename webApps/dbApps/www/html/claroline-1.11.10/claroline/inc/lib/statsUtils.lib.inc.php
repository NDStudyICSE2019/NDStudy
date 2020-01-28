<?php // $Id: statsUtils.lib.inc.php 14314 2012-11-07 09:09:19Z zefredz $

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * @version     1.9 $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <piraux@claroline.net>
 * @todo
 */

/**
 * Display a standardblock of
 *
 * @param $header string title of block
 * @param $content string content of the block
 * @param $footer string some additionnal infos (optionnal)
 * @return string html code of the full block
 */
function renderStatBlock($header,$content,$footer = '')
{
    $html = '<div class="statBlock">' . "\n"
    .     ' <h3 class="blockHeader">' . "\n"
    .     $header
    .     ' </h3>' . "\n"
    .     ' <div class="blockContent">' . "\n"
    .     $content
    .     ' </div>' . "\n"
    .     ' <div class="blockFooter">' . "\n"
    .     $footer
    .     ' </div>' . "\n"
    .     '</div>' . "\n";

    return $html;
}

/**
 * Return an assoc array.  Keys are the hours, values are
 * the number of time this hours was found.
 * key 'total' return the sum of all number of time hours
 * appear
 *
 * @param string sql query
 *
 * @return array hours
 */
function hoursTab($sql)
{
    $query = claro_sql_query( $sql );

    $hours_array['total'] = 0;
    $last_hours = -1;

    while( $row = @mysql_fetch_row( $query ) )
    {
        $date_array = getdate($row[0]);

        if($date_array['hours'] == $last_hours )
        {
            $hours_array[$date_array['hours']]++;
        }
        else
        {
            $hours_array[$date_array['hours']] = 1;
            $last_hours = $date_array['hours'];
        }

        $hours_array['total']++;
    }

    return $hours_array;
}

/**
 * Return an assoc array.  Keys are the days, values are
 * the number of time this hours was found.
 * key 'total' return the sum of all number of time days
 * appear
 *
 * @param string sql query
 *
 * @return days_array
 *
 */
function daysTab($sql)
{

    $langMonthNames = get_lang_month_name_list('short');

    $query = claro_sql_query( $sql );

    $days_array['total'] = 0;
    $last_day = -1;
    while( $row = @mysql_fetch_row( $query ) )
    {
        $date_array = getdate($row[0]);
        $display_date = $date_array['mday'] . ' '
        .               $langMonthNames[$date_array['mon']-1] . ' '
        .               $date_array['year']
        ;

        if ($date_array['mday'] == $last_day)
        {
            $days_array[$display_date]++;
        }
        else
        {
            $days_array[$display_date] = 1;
            $last_day = $display_date;
        }
        $days_array['total']++;
    }

    return $days_array;
}

/**
 * Return an assoc array.  Keys are the days, values are
 * the number of time this hours was found.
 * key 'total' return the sum of all number of time days
 * appear
 *
 * @param string sql query
 *
 * @return array month
 *
 */
function monthTab($sql)
{

    $langMonthNames = get_lang_month_name_list('long');

    // init tab with all month
    for($i = 0;$i < 12; $i++)
    {
        $month_array[$langMonthNames[$i]] = 0;

    }
    // and with total
    $month_array['total'] = 0;

    $query = claro_sql_query( $sql );
    while( $row = @mysql_fetch_row( $query ) )
    {
        $date_array = getdate($row[0]);
        $month_array[$langMonthNames[$date_array['mon']-1]]++;
        $month_array['total']++;
    }
    return $month_array;
}

/**
 * Display a 4 column array
 * Columns are : hour of day, graph, number of hits and %
 * First line are titles
 * next are informations
 * Last is total number of hits
 *
 * @param period_array : an array provided by hoursTab($sql) or daysTab($sql)
 * @param periodTitle : title of the first column, type of period
 * @param linkOnPeriod :
 *
 * @return
 *
 */
function makeHitsTable($period_array,$periodTitle,$linkOnPeriod = "???")
{
    $out = '';
    
    $out .= '<table class="claroTable emphaseLine" width="100%" cellpadding="0" cellspacing="1" align="center">' . "\n";
    // titles
    $out .= '<tr class="headerX">' . "\n"
    .    '<th width="15%">' . $periodTitle . '</th>' . "\n"
    .    '<th width="60%">&nbsp;</th>' . "\n"
    .    '<th width="10%">' . get_lang('Hits') . '</th>' . "\n"
    .    '<th width="15%"> % </th>' . "\n"
    .    '</tr>' . "\n\n"
    .    '<tbody>' . "\n\n"
    ;
    $factor = 4;
    $maxSize = $factor * 100; //pixels
    while(list($periodPiece,$cpt) = each($period_array))
    {
        if($periodPiece !== 'total')
        {
            if($period_array['total'] == 0 ) $pourcent = 0;
            else                             $pourcent = round(100 * $cpt / $period_array['total']);

            $out .= '<tr>' . "\n"
                .'<td align="center" width="15%">'.$periodPiece.'</td>' . "\n"
                .'<td width="60%" align="center">'.claro_html_progress_bar($pourcent, 4).'</td>' . "\n"
                .'<td align="center" width="10%">'.$cpt.'</td>' . "\n"
                .'<td align="center" width="15%">'.$pourcent.' %</td>' . "\n"
                .'</tr>' . "\n\n";
        }
    }

    // footer
    $out .= '</tbody>' . "\n\n"
          .'<tfoot>' . "\n"
          .'<tr>' . "\n"
          .'<td width="15%" align="center">'.get_lang('Total').'</td>' . "\n"
          .'<td align="right" width="60%">&nbsp;</td>' . "\n"
          .'<td align="center" width="10%">'.$period_array['total'].'</td>' . "\n"
          .'<td width="15%">&nbsp;</td>' . "\n"
          .'</tr>' . "\n"
          .'</tfoot>' . "\n\n"
          .'</table>' . "\n\n";
          
    return $out;
}

/**
 * Display a 2 column tab from an array
 * this tab has no title
 *
 * @param results : a 2 columns array
 * @param leftTitle : string, title of the left column
 * @param rightTitle : string, title of the ... right column
 *
 * @return
 */
function buildTab2Col($sql, $title = "")
{
    $results = claro_sql_query_fetch_all($sql);
    $out = '<table class="claroTable" cellpadding="2" cellspacing="1" align="center">' . "\n"
    .    '<tr class="headerX">' . "\n"
    .    '<th colspan="2">' . claro_htmlspecialchars($title) .' (' . get_lang('%x rows', array('%x' => count($results))). ') </th>' . "\n"
    .    '</tr>' . "\n\n"
    .    '<tbody>' . "\n\n"
    ;
    if( !empty($results) && is_array($results) )
    {
        foreach( $results as $result )
        {
            $keys = array_keys($result);
            $out .= '<tr>' . "\n"
            .    '<td>' . $result[$keys[0]] . '</td>' . "\n"
            .    '<td align="right">' . $result[$keys[1]] . '</td>' . "\n"
            .    '</tr>' . "\n\n"
            ;
        }

    }
    else
    {
        $out .= '<tr>' . "\n"
        .    '<td colspan="2"><center>'.get_lang('No result').'</center></td>' . "\n"
        .    '</tr>' . "\n\n"
        ;
    }
    $out .= '</tbody>' . "\n"
    .    '</table>' . "\n\n"
    ;
    
    return $out;
}
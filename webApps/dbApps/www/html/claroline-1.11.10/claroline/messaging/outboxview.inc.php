<?php // $Id: outboxview.inc.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 )
{
    die( 'The file ' . basename(__FILE__) . ' cannot be accessed directly, use include instead' );
}

/**
 * CLAROLINE
 *
 * View of the outbox.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Christophe Mertens <thetotof@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     internal_messaging
 */

// -------------------- selector form ----------------
$javascriptSearchBox = '
    <script type="text/javascript">
        $(document).ready(function(){
            $(\'#SelectorReadStatus\').hide();
            $(\'#searchStrategyBox\').hide();
            $(\'#toSimpleSearch\').hide();
            
            $(\'#toAdvancedSearch\').click(function(){
                $(\'#SelectorReadStatus\').show();
                $(\'#searchStrategyBox\').show();
                
                $(\'#toAdvancedSearch\').hide();
                $(\'#toSimpleSearch\').show();
            });
            
            $(\'#toSimpleSearch\').click(function(){
                $(\'#SelectorReadStatus\').hide();
                $(\'#searchStrategyBox\').hide();
                
                $(\'#toSimpleSearch\').hide();
                $(\'#toAdvancedSearch\').show();
            });
        });
    </script>';
$claroline->display->header->addHtmlHeader($javascriptSearchBox);

$arg_search = makeArgLink($link_arg,array('SelectorReadStatus','SelectorName','SelectorSubject'));
$linkSearch = $linkPage."?".$arg_search;

$searchForm = '<form action="'.$linkSearch.'" method="post">'."\n";
$searchForm .= '<input type="text" name="search" value="';

if (isset($link_arg['search']))
{
    $searchForm .= $link_arg['search'];
}

$searchForm .= '" class="inputSearch" />'."\n";

$searchForm .= '<input type="submit" value="'.get_lang("Search").'" />'."\n"
            . '<span id="toAdvancedSearch">[<a href="#">'.get_lang('Advanced').'</a>]</span>'
            . '<span id="toSimpleSearch">[<a href="#">'.get_lang('Simple').'</a>]</span>'
            . '<br />' . "\n"
            . '<span id="searchStrategyBox">' . "\n"
            . '<input type="checkbox" name="searchStrategy" id="searchStrategy" value="'.get_lang('Match the exact expression').'"'
            ;
if (isset($link_arg['searchStrategy']) && $link_arg['searchStrategy'] == 1)
{
    $searchForm .= ' checked="checked"';
}
$searchForm .= ' /><label for="searchStrategy">'.get_lang('Exact expression').'</label>' . "\n"
. '</span>' . "\n"
. '</form>'."\n";

$dialogbox = new DialogBox();
$dialogbox->form($searchForm);
$content .= $dialogbox->render();


//----------------------end selector form -----------------

$arg_sort = makeArgLink($link_arg,array('fieldOrder','order'));

if ($arg_sort == "")
{
    $linkSort = $linkPage."?";
}
else
{
    $linkSort = $linkPage."?".$arg_sort."&amp;";
}
    
$content .= '<table class="claroTable emphaseLine" width="100%">'."\n"
          . '<thead>'
          . '<tr> '."\n"
          . '<th>'.get_lang("Subject").'</th>'."\n"
          . '<th>'.get_lang("Recipient").'</th> '."\n"
          . '<th><a href="'.$linkSort.'fieldOrder=date&amp;order='.$nextOrder.'">'.get_lang("Date").'</a></th>'."\n"
          . '</tr>'."\n"
          . '</thead>'."\n";

if ($box->getNumberOfMessage() == 0)
{
    $content .= '<tr><td colspan="3">'.get_lang('Empty').'</td></tr>'."\n\n";
}
else
{
    foreach ($box as $key => $message)
    {
        $recipientList = $message->getRecipientList();
        
        $content .= '<tr';
        if ($message->isPlatformMessage())
        {
            $content .= ' class="platformMessage"';
        }
        $content .= '><td>';

        if ($message->isPlatformMessage())
        {
            $content .= '<img src="' . get_icon_url('important') . '" alt="" />';
        }
        
        if (!is_null($message->getCourseCode()))
        {
            $courseData = claro_get_course_data($message->getCourseCode());
            if ($courseData)
            {
                $content .= '<span class="im_context">'
                .   '[' . $courseData['officialCode'];
                
                if (!is_null($message->getToolsLabel()))
                {
                    $md = get_module_data($message->getToolsLabel());
                    $content .= ' - '.get_lang($md['moduleName']);
                }
                
                $content .= ']</span> ';
            }
        }
        
        $content .= ' <a href="readmessage.php?messageId='.$message->getId().'&amp;type=sent&amp;userId='.$currentUserId.'">';
        $content .=  claro_htmlspecialchars($message->getSubject()).'</a></td>'."\n"
                    .'<td>';
                    
        if ( $recipientList['sentTo'] == 'toUser' )
        {
            $content .= claro_htmlspecialchars($recipientList['userList'][0]['firstName'])." ".claro_htmlspecialchars($recipientList['userList'][0]['lastName']);
            
            if ( count( $recipientList['userList'] ) > 1 )
            {
                $content .=  ", ".claro_htmlspecialchars($recipientList['userList'][1]['firstName'])." ".claro_htmlspecialchars($recipientList['userList'][1]['lastName']);
            }
            
            if ( count( $recipientList['userList'] ) > 2 )
            {
                $content .= ",...";
            }
        }
        elseif ($recipientList['sentTo'] == 'toCourse')
        {
            $content .= get_lang('Course: ')." ". $message->getCourseCode();
        }
        elseif ($recipientList['sentTo'] == 'toGroup')
        {
            $groupInfo = claro_get_group_data(array(CLARO_CONTEXT_COURSE => $message->getCourseCode(),
                                                    CLARO_CONTEXT_GROUP => $message->getGroupId()));
            $courseInfo = claro_get_course_data($message->getCourseCode());
            if (!$groupInfo)
            {
                $content .= get_lang('Course').' : '.get_lang('unknown'). "; " .get_lang('Group').' : '.get_lang('unknown');
            }
            else
            {
                $content .= get_lang('Course').' : ' . $courseInfo['officialCode'] . "; " .get_lang('Group').' : '. $groupInfo['name'];
            }
        }
        elseif ($message->isPlatformMessage())
        {
             $content .= get_lang('All users of the platform');
        }
        else
        {
            $content .= get_lang('Unknown recipient');
        }
        
        $content .=  '</td>'
                    .'<td>'.claro_html_localised_date(get_locale('dateTimeFormatLong'),strtotime($message->getSendTime())).'</td>'."\n"
                    ;
        $content .=  '</tr>'."\n\n";
    }
}

$content .= '</table>'."\n";

// prepare the link to change of page
// prepare the link to change of page
if ($box->getNumberOfPage()>1)
{
    // number of page to display in the page before and after thecurrent page
    $nbPageToDisplayBeforeAndAfterCurrentPage = 1;
    
    $arg_paging = makeArgLink($link_arg,array('page'));
    
    if ($arg_paging == "")
    {
        $linkPaging = $linkPage."?page=";
    }
    else
    {
        $linkPaging = $linkPage."?".$arg_paging."&amp;page=";
    }
    
    if (!isset($link_arg['page']))
    {
        $page=1;
    }
    else
    {
        $page = $link_arg['page'];
    }
    
    $content .= getPager($linkPaging,$page,$box->getNumberOfPage());
}
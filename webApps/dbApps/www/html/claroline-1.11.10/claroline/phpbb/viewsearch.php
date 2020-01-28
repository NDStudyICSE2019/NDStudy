<?php // $Id: viewsearch.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @copyright   (c) 2001 The phpBB Group
 * @author      Claroline Team <info@claroline.net>
 * @author      FUNDP - WebCampus <webcampus@fundp.ac.be>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLFRM
 */

$tlabelReq = 'CLFRM';

require '../inc/claro_init_global.inc.php';
require_once get_path('incRepositorySys') . '/lib/forum.lib.php';
require_once get_path('incRepositorySys') . '/lib/group.lib.inc.php';
require_once get_path('incRepositorySys') . '/lib/user.lib.php';

$last_visit        = claro_get_current_user_data('lastLogin');
$is_groupPrivate   = claro_get_current_group_properties_data('private');
$is_allowedToEdit  = claro_is_allowed_to_edit();

if (  !claro_is_in_a_course() || ! claro_is_course_allowed() ) claro_disp_auth_form(true);

if ( isset($_REQUEST['searchUser']) )
{
    $sqlClauseString = ' p.poster_id = '. (int) $_REQUEST['searchUser'];
}
elseif ( isset($_REQUEST['searchPattern']) )
{
    $searchPatternString = trim($_REQUEST['searchPattern']);

    if ($searchPatternString != '')
    {
        $searchPatternList = explode(' ', $searchPatternString);
        $sqlClauseList = '';

        foreach($searchPatternList as $thisSearchPattern)
        {
            $thisSearchPattern = str_replace('_', '\\_', $thisSearchPattern);
            $thisSearchPattern = str_replace('%', '\\%', $thisSearchPattern);
            $thisSearchPattern = str_replace('?', '_' , $thisSearchPattern);
            $thisSearchPattern = str_replace('*', '%' , $thisSearchPattern);

            $sqlClauseList[] =
            "   pt.post_text  LIKE '%".claro_sql_escape($thisSearchPattern)."%'
             OR p.nom           LIKE '%".claro_sql_escape($thisSearchPattern)."%'
             OR p.prenom        LIKE '%".claro_sql_escape($thisSearchPattern)."%'
             OR t.topic_title   LIKE '%".claro_sql_escape($thisSearchPattern)."%'";
        }

        $sqlClauseString = implode("\n OR \n", $sqlClauseList);
    }
    else
    {
        $sqlClauseString = null;
    }
}
else
{
    $sqlClauseString = null;
}

if ( $sqlClauseString )
{
        $tbl_cdb_names  = claro_sql_get_course_tbl();
        $tbl_posts_text = $tbl_cdb_names['bb_posts_text'];
        $tbl_posts      = $tbl_cdb_names['bb_posts'     ];
        $tbl_topics     = $tbl_cdb_names['bb_topics'    ];
        $tbl_forums     = $tbl_cdb_names['bb_forums'    ];

        $sql = "SELECT pt.post_id,
                       pt.post_text,
                       p.nom         AS lastname,
                       p.prenom      AS firstname,
                       p.`poster_id`,
                       p.post_time,
                       t.topic_id,
                       t.topic_title,
                       f.forum_id,
                       f.forum_name,
                       f.group_id
               FROM  `" . $tbl_posts_text . "` AS pt,
                     `" . $tbl_posts . "`      AS p,
                     `" . $tbl_topics . "`     AS t,
                     `" . $tbl_forums . "`     AS f
               WHERE ( ". $sqlClauseString . ")
                 AND pt.post_id = p.post_id
                 AND p.topic_id = t.topic_id
                 AND t.forum_id = f.forum_id
               ORDER BY p.post_time DESC, t.topic_id";

        $searchResultList = claro_sql_query_fetch_all($sql);

        $userGroupList  = get_user_group_list(claro_get_current_user_id());
        $userGroupList  = array_keys($userGroupList);
        $tutorGroupList = get_tutor_group_list(claro_get_current_user_id());
}
else
{
    $searchResultList = array();
}

$pagetype= 'viewsearch';

ClaroBreadCrumbs::getInstance()->prepend( get_lang('Forums'), 'index.php' );
CssLoader::getInstance()->load( 'clfrm', 'screen');
$noPHP_SELF       = true;

$out = '';

$out .= claro_html_tool_title(get_lang('Forums'),
                           $is_allowedToEdit ? get_help_page_url('blockForumsHelp','CLFRM') : false);

$out .= claro_html_menu_horizontal(disp_forum_toolbar($pagetype, null))
.    disp_forum_breadcrumb($pagetype, null, null, null)

.    '<h4>' . get_lang('Search result') . ' : ' . (isset($_REQUEST['searchPattern']) ?  claro_htmlspecialchars($_REQUEST['searchPattern']) : '') . '</h4>' . "\n";

if (count($searchResultList) < 1 )
{
    $out .= '<p>' . get_lang('No result') . '</p>';
}
else foreach ( $searchResultList as $thisPost )
{
    // PREVENT USER TO CONSULT POST FROM A GROUP THEY ARE NOT ALLOWED
    if (    ! is_null($thisPost['group_id'])
        &&  $is_groupPrivate
        && ! (    in_array($thisPost['group_id'], $userGroupList )
               || in_array($thisPost['group_id'], $tutorGroupList)
               || claro_is_course_manager()
             )
       )
    {
       continue;
    }
    else
    {
        // notify if is new message
        $post_time = datetime_to_timestamp($thisPost['post_time']);

        if($post_time < $last_visit) $class = ' class="item"';
        else                         $class = ' class="item hot"';
        
        // get user picture
        $userData = user_get_properties( $thisPost['poster_id'] );

        $picturePath = user_get_picture_path( $userData );

        if ( $picturePath && file_exists( $picturePath ) )
        {
            $pictureUrl = user_get_picture_url( $userData );
        }
        else
        {
            $pictureUrl = null;
        }
        
        $out .= '<div id="post'. $thisPost['post_id'] .'" class="threadPost">'
        .    '<div class="threadPostInfo">'
        .    ( !is_null($pictureUrl) ?'<div class="threadPosterPicture"><img src="' . $pictureUrl . '" alt=" " /></div>':'' ) . "\n"
        .    '<b>' . $thisPost['firstname'] . ' ' . $thisPost['lastname'] . '</b> '
        .    '<br />'
        .    '<small>' . claro_html_localised_date(get_locale('dateTimeFormatLong'), $post_time) . '</small>' . "\n"
        ;

        
        $out .= '  </div>' . "\n"

        .    '<div class="threadPostContent">' . "\n"
        .    '<img src="' . get_icon_url('topic') . '" alt="" />'
        .    '<a href="' . claro_htmlspecialchars( Url::Contextualize(get_module_url('CLFRM') . '/viewtopic.php?topic='.$thisPost['topic_id'] )) . '">'
        .    claro_htmlspecialchars( $thisPost['topic_title'] )
        .    '</a>' . "\n"
        .    '<span class="threadPostIcon '.$class.'"><img src="' . get_icon_url( 'post' ) . '" alt="" /></span><br />' . "\n"
        .    claro_parse_user_text($thisPost['post_text']) . "\n";

        $out .= '</div>' . "\n"
        .    '<div class="spacer"></div>' . "\n\n"
        .    '</div>' . "\n"
        ;
    } // end else if ( ! is_null($thisPost['group_id'])

} // end for each

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

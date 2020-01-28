<?php

// $Id: export.php 14229 2012-08-06 08:22:10Z zefredz $

/**
 * CLAROLINE
 *
 * Script export topic/forum for forum tool.
 *
 * @version     $Revision: 14229 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @copyright   (c) 2001 The phpBB Group
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Dimitri Rambout <dim@claroline.net>
 * @package     CLFRM
 */
$tlabelReq = 'CLFRM';

require '../inc/claro_init_global.inc.php';

if ( !claro_is_in_a_course () || !claro_is_course_allowed () )
    claro_disp_auth_form ( true );

if ( !claro_is_allowed_to_edit () )
{
    claro_die ( get_lang ( 'Not allowed' ) );
}

claro_set_display_mode_available ( true );

/* -----------------------------------------------------------------
  Library
  ----------------------------------------------------------------- */

include_once get_path ( 'incRepositorySys' ) . '/lib/forum.lib.php';
include_once get_path ( 'incRepositorySys' ) . '/lib/user.lib.php';
include_once get_module_path ( $tlabelReq ) . '/lib/export.class.php';
include_once get_module_path ( $tlabelReq ) . '/lib/export.pdf.class.php';
include_once get_module_path ( $tlabelReq ) . '/lib/export.html.class.php';
/* -----------------------------------------------------------------
  Initialise variables
  ----------------------------------------------------------------- */

$dialogBox = new DialogBox();
$out = '';

if ( isset ( $_REQUEST[ 'topic' ] ) )
    $topicId = (int) $_REQUEST[ 'topic' ];
else
    $topicId = null;

if ( isset ( $_REQUEST[ 'forum' ] ) )
    $forumId = (int) $_REQUEST[ 'forum' ];
else
    $forumId = null;

if ( isset ( $_REQUEST[ 'type' ] ) )
    $type = strtoupper ( trim ( addslashes ( $_REQUEST[ 'type' ] ) ) );
else
    $type = null;

ClaroBreadCrumbs::getInstance ()->prepend ( get_lang ( 'Forums' ), 'index.php' );

$acceptedTypes = array ( 'HTML', 'PDF' );

if ( !in_array ( $type, $acceptedTypes ) )
{
    $dialogBox->error ( get_lang ( 'The export\'s type is not supported for the moment.' ) );
}
else
{
    switch ( $type )
    {
        case 'PDF' :
        {
            if ( !is_null ( $topicId ) )
            {
                $export = new exportPDF ( $topicId, 'screen' );

                if ( !$export->export () )
                {
                    $dialogBox->error ( get_lang ( 'Unable to export your topic in PDF format.' ) );
                }
                break;
            }
            elseif ( !is_null ( $forumId ) )
            {
                # Get all topic from the forum
                $tbl_cdb_names = claro_sql_get_course_tbl ();
                $tbl_topics = $tbl_cdb_names[ 'bb_topics' ];

                // Get topics list

                $sql = "SELECT    t.`topic_id`
                FROM      `" . $tbl_topics . "` t
                WHERE     `forum_id` = '" . (int) $forumId . "'
                ORDER BY t.`topic_id`";

                $topicsList = claro_sql_query_fetch_all ( $sql );

                $forumSettingList = get_forum_settings ( $forumId );

                foreach ( $topicsList as $topic )
                {
                    $export = new exportPDF ( $topic[ 'topic_id' ], 'file' );

                    if ( !$export->export () )
                    {
                        $dialogBox->error ( get_lang ( 'Unable to export your topic in PDF format.' ) );
                        break;
                    }
                }

                include_once get_path ( 'incRepositorySys' ) . "/lib/thirdparty/pclzip/pclzip.lib.php";

                $filename = replace_dangerous_char ( str_replace ( ' ', '_', $forumSettingList[ 'forum_name' ] ) ) . '.zip';

                $path = get_conf ( 'rootSys' ) . get_conf ( 'tmpPathSys' ) .
                    '/forum_export/';

                $zipFile = new PclZip ( $filename );
                $list = $zipFile->create ( $path, PCLZIP_OPT_REMOVE_PATH, $path );

                if ( !$list )
                {
                    $dialogBox->error ( get_lang ( 'Unable to create the archive' ) );
                    break;
                }

                claro_delete_file ( $path );

                header ( 'Content-Description: File Transfer' );
                header ( 'Content-Type: application/force-download' );
                header ( 'Content-Length: ' . filesize ( $filename ) );
                header ( 'Content-Disposition: attachment; filename=' . basename ( $filename ) );

                readfile ( $filename );

                claro_delete_file ( $filename );

                exit ( 0 );
            }
        }
        break;
        case 'HTML' :
        {
            if ( !is_null ( $topicId ) )
            {
                $export = new exportHTML ( $topicId, 'screen' );

                if ( !$export->export () )
                {
                    $dialogBox->error ( get_lang ( 'Unable to export your topic in HTML format.' ) );
                }
                break;
            }
            elseif ( !is_null ( $forumId ) )
            {
                # Get all topic from the forum
                $tbl_cdb_names = claro_sql_get_course_tbl ();
                $tbl_topics = $tbl_cdb_names[ 'bb_topics' ];

                // Get topics list

                $sql = "SELECT    t.`topic_id`
                FROM      `" . $tbl_topics . "` t
                WHERE     `forum_id` = '" . (int) $forumId . "'
                ORDER BY t.`topic_id`";

                $topicsList = claro_sql_query_fetch_all ( $sql );

                $forumSettingList = get_forum_settings ( $forumId );

                foreach ( $topicsList as $topic )
                {
                    $export = new exportHTML ( $topic[ 'topic_id' ], 'file' );

                    if ( !$export->export () )
                    {
                        $dialogBox->error ( get_lang ( 'Unable to export your topic in HTML format.' ) );
                        break;
                    }
                }

                include_once get_path ( 'incRepositorySys' ) . "/lib/thirdparty/pclzip/pclzip.lib.php";

                $filename = replace_dangerous_char ( str_replace ( ' ', '_', $forumSettingList[ 'forum_name' ] ) ) . '.zip';

                $path = get_conf ( 'rootSys' ) . get_conf ( 'tmpPathSys' ) .
                    '/forum_export/';

                $zipFile = new PclZip ( $filename );
                $list = $zipFile->create ( $path, PCLZIP_OPT_REMOVE_PATH, $path );

                if ( !$list )
                {
                    $dialogBox->error ( get_lang ( 'Unable to create the archive' ) );
                    break;
                }

                claro_delete_file ( $path );

                header ( 'Content-Description: File Transfer' );
                header ( 'Content-Type: application/force-download' );
                header ( 'Content-Length: ' . filesize ( $filename ) );
                header ( 'Content-Disposition: attachment; filename=' . basename ( $filename ) );

                readfile ( $filename );

                claro_delete_file ( $filename );

                exit ( 0 );
            }
        }
        break;
    }
}

$out .= $dialogBox->render ();

$claroline->display->body->appendContent ( $out );

echo $claroline->display->render ();

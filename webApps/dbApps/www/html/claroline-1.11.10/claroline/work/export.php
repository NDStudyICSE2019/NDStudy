<?php // $Id: export.php 14490 2013-07-03 13:02:00Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Script handling the download of assignments
 * As from 1.9.6 replaces $cmd = 'exDownload' in both work.php and work_list.php
 * As from 1.9.6 uses pclzip instead of zip.lib
 *
 * @version     $Revision: 14490 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      FUNDP - WebCampus <webcampus@fundp.ac.be>
 * @author      Jean-Roch Meurisse <jmeuriss@fundp.ac.be>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     CLWORK
 * @since       1.9.6
 */

$tlabelReq = 'CLWRK';

//load Claroline kernel
require_once dirname( __FILE__ ) . '/../../claroline/inc/claro_init_global.inc.php';

if ( !claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form( true );

if( !claro_is_allowed_to_edit() )
{
  claro_die( get_lang( 'Not allowed' ) );
}

//load required libs
require_once get_module_path( $tlabelReq ) . '/lib/assignment.class.php';
require_once get_path( 'incRepositorySys' ) . '/lib/course_utils.lib.php';
require_once get_path( 'incRepositorySys' ) . '/lib/fileManage.lib.php';
require_once get_path( 'incRepositorySys' ) . '/lib/file/garbagecollector.lib.php';
require_once get_path( 'incRepositorySys' ) . '/lib/thirdparty/pclzip/pclzip.lib.php';
require_once dirname(__FILE__).'/lib/score.lib.php';

//init general purpose vars
$out ='';

$dialogBox = new DialogBox();

$downloadMode = isset( $_REQUEST['downloadMode'] ) && is_string( $_REQUEST['downloadMode'] ) ? $_REQUEST['downloadMode'] : 'all';
$downloadScore = isset( $_REQUEST['downloadScore'] ) && $_REQUEST['downloadScore'] == 'yes' ? true : false;
$assignmentId = isset( $_REQUEST['assigId'] ) && is_numeric( $_REQUEST['assigId'] ) ? $_REQUEST['assigId'] : 0;
$downloadOnlyCurrentMembersSubmissions = isset( $_REQUEST['downloadOnlyCurrentMembers'] ) && $_REQUEST['downloadOnlyCurrentMembers'] == 'yes' ? true : false;

if( $assignmentId )
{
    $assignment = new Assignment();
    $assignment->load( $assignmentId );
}

if( claro_is_platform_admin() || get_conf( 'allow_download_all_submissions' ) )
{
    $courseTbl = claro_sql_get_course_tbl();
    $submissionTbl = $courseTbl['wrk_submission'];
    
    $mainTbl = claro_sql_get_main_tbl();
    $courseUserTbl = $mainTbl['rel_course_user'];
    
    if( $downloadMode == 'from' )
    {
        if( isset($_REQUEST['hour']) && is_numeric($_REQUEST['hour']) )       $hour = (int) $_REQUEST['hour'];
        else                                                                  $hour = 0;
        if( isset($_REQUEST['minute']) && is_numeric($_REQUEST['minute']) ) $minute = (int) $_REQUEST['minute'];
        else                                                                  $minute = 0;

        if( isset($_REQUEST['month']) && is_numeric($_REQUEST['month']) )   $month = (int) $_REQUEST['month'];
        else                                                                  $month = 0;
        if( isset($_REQUEST['day']) && is_numeric($_REQUEST['day']) )       $day = (int) $_REQUEST['day'];
        else                                                                  $day = 0;
        if( isset($_REQUEST['year']) && is_numeric($_REQUEST['year']) )       $year = (int) $_REQUEST['year'];
        else                                                                  $year = 0;

        $unixRequestDate = mktime( $hour, $minute, '00', $month, $day, $year );

        if( $unixRequestDate >= time() )
        {
            $dialogBox->error( get_lang( 'Warning : chosen date is in the future' ) );
        }

        $downloadRequestDate = date( 'Y-m-d G:i:s', $unixRequestDate );
        
        

        $wanted = '_' . replace_dangerous_char( get_lang( 'From' ) ) . '_' . date( 'Y_m_d', $unixRequestDate ) . '_'
                . replace_dangerous_char( get_lang( 'to' ) ) . '_' . date( 'Y_m_d' )
        ;
        $sqlDateCondition = " AND s.`last_edit_date` >= '" . $downloadRequestDate . "' ";
    }
    else
    {
        $wanted = '';

        $sqlDateCondition = '';
    }
    
    if ( $downloadOnlyCurrentMembersSubmissions )
    {
        $userRestrictions = "JOIN `{$courseUserTbl}` AS cu On cu.user_id = s.user_id AND cu.code_cours = '".claro_get_current_course_id()."'" ;
    }
    else
    {
        $userRestrictions = "";
    }
    
    //load_module_config('CLDOC');
    
    $tmpFolderPath = get_conf('clwrk_customTmpPath','');
    
    if( $assignmentId == 0 )
    {
        $assignmentRestriction = '';
        
        if ( !empty($tmpFolderPath) )
        {
            $zipPath = $tmpFolderPath . '/' . claro_get_current_course_id() . '/work/tmp';
        }
        else
        {
            $zipPath = get_path( 'coursesRepositorySys' ) . claro_get_course_path(claro_get_current_course_id()) . '/work/tmp';
        }
        
        $zipName = claro_get_current_course_id() . '_' . replace_dangerous_char( get_lang( 'Assignments' ) ) . $wanted . '.zip';
    }
    else
    {
        $assignmentRestriction = " AND s.`assignment_id` = " . (int)$assignmentId;
        
        if ( !empty($tmpFolderPath) )
        {
            $zipPath = $tmpFolderPath . '/' . claro_get_current_course_id() . '/work/assig_' . (int)$assignmentId . '/' . 'tmp';
        }
        else
        {
            $zipPath = get_path( 'coursesRepositorySys' ) . claro_get_course_path(claro_get_current_course_id()) . '/work/tmp';
        }
        
        $zipName = replace_dangerous_char(claro_get_course_name(claro_get_current_course_id())) . '_' . replace_dangerous_char( $assignment->getTitle(), 'strict' ) . $wanted . '.zip';
    }
    
    
    if ( !empty($tmpFolderPath) )
    {   
        $downloadArchiveFolderPath = $tmpFolderPath . '/' . claro_get_current_course_id() . '/work';
    }
    else
    {
        $downloadArchiveFolderPath = get_path('coursesRepositorySys') . claro_get_course_path(claro_get_current_course_id()) . '/tmp/zip';
    }

    if ( !is_dir( $downloadArchiveFolderPath ) )
    {
        mkdir( $downloadArchiveFolderPath, CLARO_FILE_PERMISSIONS, true );
    }

    $downloadArchiveFilePath = $downloadArchiveFolderPath . '/' . $zipName;

    $sql = "SELECT s.`id`,
                   s.`assignment_id`,
                   s.`authors`,
                   s.`submitted_text`,
                   s.`submitted_doc_path`,
                   s.`title`,
                   s.`creation_date`,
                   s.`last_edit_date`
              FROM `" . $submissionTbl . "` AS s
              {$userRestrictions}
             WHERE s.`parent_id` IS NULL "
                   . $assignmentRestriction
                   . $sqlDateCondition . "
          ORDER BY s.`authors`,
                   s.`creation_date`";

    if( !is_dir( $zipPath ) )
    {
        mkdir( $zipPath, CLARO_FILE_PERMISSIONS, true );
    }
    
    $results = claro_sql_query_fetch_all( $sql );

    if( is_array( $results ) && !empty( $results ) )
    {
        $previousAuthors = '';
        $i = 1;
        
        foreach ( $results as $row => $result )
        {
            //create assignment directory if necessary
            if( $assignmentId == 0 )
            {
                if( !is_dir( $zipPath . '/' . get_lang( 'Assignment' ) . '_' . $result['assignment_id'] . '/' ) )
                {
                    mkdir( $zipPath . '/' . get_lang( 'Assignment' ) . '_' . $result['assignment_id'] . '/', CLARO_FILE_PERMISSIONS, true );
                }

                $assigDir = '/' . get_lang( 'Assignment' ) . '_' . $result['assignment_id'] . '/';
            }
            else
            {
                $assigDir = '';
            }
            
            $assignmentPath = get_path( 'coursesRepositorySys' ) . claro_get_course_path(claro_get_current_course_id()) . '/work/assig_' . (int)$result['assignment_id'] . '/';
            
            //  count author's submissions for the name of directory
            if( $result['authors'] != $previousAuthors )
            {
                $i = 1;
                $previousAuthors = $result['authors'];
            }
            else
            {
                $i++;
            }

            $authorsDir = replace_dangerous_char( $result['authors'] ) . '/';
            
            if( !is_dir( $zipPath . $assigDir . '/' . $authorsDir ) )
            {
                mkdir( $zipPath . $assigDir . '/' . $authorsDir, CLARO_FILE_PERMISSIONS, true );
            }
            
            if ( $downloadScore && ! ( isset($currAssigId) && $currAssigId == $result['assignment_id'] )  )
            {
                $course = new Claro_Course(  claro_get_current_course_id () );
                $course->load();

                $assignment = new Assignment();
                $assignment->load( $result['assignment_id']  );
                $currAssigId = $result['assignment_id'] ;

                $scoreList = new CLWRK_AssignementScoreList( $assignment );
                
                if ( ! $downloadOnlyCurrentMembersSubmissions )
                {
                    $scoreList->setOptAllUsers();
                }
                
                $scoreListIterator = $scoreList->getScoreList();

                $scoreListRenderer = new CLWRK_ScoreListRenderer( $course, $assignment, $scoreListIterator );

                file_put_contents( $zipPath . $assigDir . '/scores.html', $scoreListRenderer->render() );
            }
            
            
            
            $submissionPrefix = $assigDir . $authorsDir . replace_dangerous_char( get_lang( 'Submission' ) ) . '_' . $i . '_';

            // attached file
            if( !empty( $result['submitted_doc_path'] ) )
            {
                if( file_exists( $assignmentPath . $result['submitted_doc_path'] ) )
                {
                    copy( $assignmentPath . $result['submitted_doc_path'], $zipPath . '/' . $submissionPrefix . $result['submitted_doc_path'] );
                }
            }

            // description file
            $txtFileName = replace_dangerous_char( get_lang( 'Descriptions' ) ) . '.html';

            $htmlContent = '<html><head></head><body>' . "\n"
            .     get_lang( 'Title' ) . ' : ' . $result['title'] . '<br />' . "\n"
            .     get_lang( 'First submission date' ) . ' : ' . $result['creation_date']. '<br />' . "\n"
            .     get_lang( 'Last edit date' ) . ' : ' . $result['last_edit_date'] . '<br />' . "\n"
            ;

            if( !empty( $result['submitted_doc_path'] ) )
            {
                $htmlContent .= get_lang( 'Attached file' ) . ' : ' . $submissionPrefix . $result['submitted_doc_path']. '<br />' . "\n";
            }

            $htmlContent .= '<div>' . "\n"
            .     '<h3>' . get_lang( 'Description' ) . '</h3>' . "\n"
            .     $result['submitted_text']
            .     '</div>' . "\n"
            .     '</body></html>';
            
            file_put_contents( $zipPath . '/' . $submissionPrefix . $txtFileName, $htmlContent );
        }

        $zipFile = new PclZip( $downloadArchiveFilePath );
        $created = $zipFile->create( $zipPath, PCLZIP_OPT_REMOVE_PATH, $zipPath );
        
        if ( !$created )
        {
            $dialogBox->error( get_lang( 'Unable to create the archive' ) );
        }
        else
        {
            claro_delete_file( $zipPath );
            
            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: application/force-download' );
            header( 'Content-Length: ' . filesize( $downloadArchiveFilePath ) );
            header( 'Content-Disposition: attachment; filename=' . str_replace( ',', '', replace_dangerous_char( $zipName ) ) );
            
            readfile( $downloadArchiveFilePath );
            
            claro_delete_file( $downloadArchiveFilePath );
            
            if ( !empty($tmpFolderPath) )
            {
                $gc = new ClaroGarbageCollector( $tmpFolderPath, 3600 );
                $gc->run();
            }
            
            exit();
        }
    }
    else
    {
        $dialogBox->error( get_lang( 'There is no submission available for download with these settings.' ) );
    }
}

$out .= $dialogBox->render();

ClaroBreadCrumbs::getInstance()->prepend( get_lang( 'Assignments' ), 'work.php' );

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

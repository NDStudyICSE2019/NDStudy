<?php

// $Id: exercise.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */
$tlabelReq = 'CLQWZ';

require '../inc/claro_init_global.inc.php';

if ( !claro_is_in_a_course () || !claro_is_course_allowed () )
    claro_disp_auth_form ( true );

claro_set_display_mode_available ( true );

$is_allowedToEdit = claro_is_allowed_to_edit ();
$is_allowedToTrack = claro_is_allowed_to_edit () && get_conf ( 'is_trackingEnabled' );

// tool libraries
include_once './lib/exercise.class.php';
include_once './lib/exercise.lib.php';

// claroline libraries
include_once get_path ( 'incRepositorySys' ) . '/lib/pager.lib.php';

/*
 * DB tables definition
 */

$tbl_cdb_names = get_module_course_tbl ( array ( 'qwz_exercise', 'qwz_question', 'qwz_rel_exercise_question' ), claro_get_current_course_id () );
$tbl_quiz_exercise = $tbl_cdb_names[ 'qwz_exercise' ];

$tbl_cdb_names = claro_sql_get_course_tbl ();
$tbl_lp_module = $tbl_cdb_names[ 'lp_module' ];
$tbl_lp_asset = $tbl_cdb_names[ 'lp_asset' ];

// learning path
// new module CLLP
$inLP = (claro_called_from () == 'CLLP') ? true : false;

$_SESSION[ 'inPathMode' ] = false;

// init request vars
if ( isset ( $_REQUEST[ 'cmd' ] ) )
    $cmd = $_REQUEST[ 'cmd' ];
else
    $cmd = null;

if ( isset ( $_REQUEST[ 'exId' ] ) && is_numeric ( $_REQUEST[ 'exId' ] ) )
    $exId = (int) $_REQUEST[ 'exId' ];
else
    $exId = null;

// init other vars
$maxFilledSpace = 100000000;
$courseDir = get_path ( 'coursesRepositorySys' ) . claro_get_current_course_data ( 'path' );

$dialogBox = new DialogBox();

if ( $is_allowedToEdit && !is_null ( $cmd ) )
{
    //-- import
    if ( $cmd == 'exImport' )
    {
        require_once get_path ( 'incRepositorySys' ) . '/lib/fileManage.lib.php';
        require_once get_path ( 'incRepositorySys' ) . '/lib/fileDisplay.lib.php';
        require_once get_path ( 'incRepositorySys' ) . '/lib/fileUpload.lib.php';
        require_once get_path ( 'incRepositorySys' ) . '/lib/file.lib.php';

        require_once './export/exercise_import.inc.php';
        require_once './lib/question.class.php';
        require_once './export/qti2/qti2_classes.php';
        require_once get_path ( 'incRepositorySys' ) . '/lib/backlog.class.php';

        if ( !isset ( $_FILES[ 'uploadedExercise' ][ 'name' ] ) )
        {
            $dialogBox->error ( get_lang ( 'Error : no file uploaded' ) );
        }
        else
        {
            $backlog = new Backlog();
            $importedExId = import_exercise ( $_FILES[ 'uploadedExercise' ][ 'name' ], $backlog );

            if ( $importedExId )
            {
                $dialogBox->success ( '<strong>' . get_lang ( 'Import done' ) . '</strong>' );
            }
            else
            {
                $dialogBox->error ( '<strong>' . get_lang ( 'Import failed' ) . '</strong>' );
                $cmd = 'rqImport';
            }
            $dialogBox->info ( $backlog->output () );
        }
    }

    if ( $cmd == 'rqImport' )
    {
        require_once get_path ( 'incRepositorySys' ) . '/lib/fileDisplay.lib.php';
        require_once get_path ( 'incRepositorySys' ) . '/lib/fileUpload.lib.php';

        $dialogBox->form ( "\n"
            . '<strong>' . get_lang ( 'Import exercise' ) . '</strong><br />' . "\n"
            . get_lang ( 'Imported exercises must be an ims-qti zip file.' ) . '<br />' . "\n"
            . '<form enctype="multipart/form-data" action="./exercise.php" method="post">' . "\n"
            . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '">' . "\n"
            . '<input name="cmd" type="hidden" value="exImport" />' . "\n"
            . claro_form_relay_context () . "\n"
            . '<input name="uploadedExercise" type="file" /><br />' . "\n"
            . '<small>' . get_lang ( 'Max file size' ) . ' : ' . format_file_size ( get_max_upload_size ( $maxFilledSpace, $courseDir ) ) . '</small>' . "\n"
            . '<p>' . "\n"
            . '<input value="' . get_lang ( 'Import exercise' ) . '" type="submit" /> ' . "\n"
            . claro_html_button ( Url::Contextualize ( './exercise.php' ), get_lang ( 'Cancel' ) )
            . '</p>' . "\n"
            . '</form>' );
    }

    //-- export
    if ( $cmd == 'rqExport' && $exId )
    {

        $exercise = new Exercise();

        if ( !$exercise->load ( $exId ) )
        {
            $dialogBox->error ( get_lang ( 'Unable to load the exercise' ) );
        }
        else
        {
            $dialogBoxContent = "\n"
                . '<strong>' . get_lang ( 'Export exercise' ) . '</strong><br />' . "\n"
                . get_lang ( 'Select the type for your export :' ) . '<br />' . "\n"
                . '<ul>' . "\n"
            ;

            if ( get_conf ( 'enableExerciseExportQTI' ) )
            {
                $dialogBoxContent .= '<li>' . "\n"
                    . '<img src="' . get_icon_url ( 'export' ) . '" alt="' . get_lang ( 'Export in IMS QTI' ) . '" /> ' . "\n"
                    . '<a href="' . claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?cmd=exExport&exId=' . $exId ) ) . '">' . get_lang ( 'Export in IMS QTI' ) . '</a>' . "\n"
                    . '</li>' . "\n"
                ;

                if ( $exercise->getShuffle () )
                {
                    $dialogBoxContent .= '<li>' . "\n"
                        . '<img src="' . get_icon_url ( 'export' ) . '" alt="' . get_lang ( 'Export in IMS QTI (Shuffle)' ) . '" /> ' . "\n"
                        . '<a href="' . claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?cmd=exExport&exId=' . $exId . '&shuffle=1' ) ) . '">' . get_lang ( 'Export in IMS QTI (Shuffle)' ) . '</a>' . "\n"
                        . '</li>' . "\n"
                    ;
                }
            }

            $dialogBoxContent .= '<li>' . "\n"
                . '<img src="' . get_icon_url ( 'mime/pdf' ) . '" alt="' . get_lang ( 'Export to PDF' ) . '" /> ' . "\n"
                . '<a href="' . claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?cmd=exExportPDF&exId=' . $exId ) ) . '">' . get_lang ( 'Export to PDF' ) . '</a>' . "\n"
                . '</li>' . "\n"
            ;

            if ( $exercise->getShuffle () )
            {
                $dialogBoxContent .= '<li>' . "\n"
                    . '<img src="' . get_icon_url ( 'mime/pdf' ) . '" alt="' . get_lang ( 'Export to PDF (Shuffle)' ) . '" /> ' . "\n"
                    . '<a href="' . claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?cmd=exExportPDF&exId=' . $exId . '&shuffle=1' ) ) . '">' . get_lang ( 'Export to PDF (Shuffle)' ) . '</a>' . "\n"
                    . '</li>' . "\n"
                ;
            }

            $dialogBoxContent .= '<li>' . "\n"
                . '<img src="' . get_icon_url ( 'print' ) . '" alt="' . get_lang ( 'Printable version' ) . '" /> ' . "\n"
                . '<a rel="external" href="' . claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?cmd=printPreview&exId=' . $exId ) ) . '">' . get_lang ( 'Printable version' ) . '</a>' . "\n"
                . '</li>' . "\n"
            ;

            if ( $exercise->getShuffle () )
            {
                $dialogBoxContent .= '<li>' . "\n"
                    . '<img src="' . get_icon_url ( 'print' ) . '" alt="' . get_lang ( 'Printable version (Shuffle)' ) . '" /> ' . "\n"
                    . '<a rel="external" href="' . claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?cmd=printPreview&exId=' . $exId . '&shuffle=1' ) ) . '">' . get_lang ( 'Printable version (Shuffle)' ) . '</a>' . "\n"
                    . '</li>' . "\n"
                ;
            }

            $dialogBoxContent .= '</ul>' . "\n"
            ;

            $dialogBox->question ( $dialogBoxContent );
        }
    }

    if ( $cmd == 'exExport' && get_conf ( 'enableExerciseExportQTI' ) && $exId )
    {
        include_once './lib/question.class.php';

        require_once './export/qti2/qti2_export.php';
        require_once get_path ( 'incRepositorySys' ) . '/lib/fileManage.lib.php';
        require_once get_path ( 'incRepositorySys' ) . '/lib/file.lib.php';

        //find exercise informations

        $exercise = new Exercise();
        $exercise->load ( $exId );
        if ( $exercise->getShuffle () && isset ( $_REQUEST[ 'shuffle' ] ) && $_REQUEST[ 'shuffle' ] == 1 )
        {
            $questionList = $exercise->getRandomQuestionList ();
        }
        else
        {
            $questionList = $exercise->getQuestionList ();
        }

        $filePathList = array ( );

        //prepare xml file of each question
        foreach ( $questionList as $question )
        {
            $quId = $question[ 'id' ];
            $questionObj = new Qti2Question();
            $questionObj->load ( $quId );

            // contruction of XML flow
            $xml = $questionObj->export ();
            // remove trailing slash
            if ( substr ( $questionObj->questionDirSys, -1 ) == '/' )
            {
                $questionObj->questionDirSys = substr ( $questionObj->questionDirSys, 0, -1 );
            }

            //save question xml file
            if ( !file_exists ( $questionObj->questionDirSys ) )
            {
                claro_mkdir ( $questionObj->questionDirSys, CLARO_FILE_PERMISSIONS );
            }

            if ( $fp = @fopen ( $questionObj->questionDirSys . "/question_" . $quRank . ".xml", 'w' ) )
            {
                fwrite ( $fp, $xml );
                fclose ( $fp );
            }
            else
            {
                // interrupt process
            }

            // list of dirs to add in archive
            $filePathList[ ] = $questionObj->questionDirSys;
        }

        if ( !empty ( $filePathList ) )
        {
            require_once get_path ( 'incRepositorySys' ) . '/lib/thirdparty/pclzip/pclzip.lib.php';

            // build and send the zip
            // TODO use $courseDir ?

            if ( sendZip ( $exercise->title, $filePathList, get_conf ( 'coursesRepositorySys' ) . claro_get_current_course_data ( 'path' ) . '/exercise/' ) )
            {
                exit ();
            }
            else
            {
                $dialogBox->error ( get_lang ( "Unable to create zip file" ) );
            }
        }
    }

    //-- export pdf
    if ( $cmd == 'printPreview' && $exId )
    {
        require_once( './lib/question.class.php' );

        $exercise = new Exercise();
        $exercise->load ( $exId );
        if ( $exercise->getShuffle () && isset ( $_REQUEST[ 'shuffle' ] ) && $_REQUEST[ 'shuffle' ] == 1 )
        {
            $questionList = $exercise->getRandomQuestionList ();
        }
        else
        {
            $questionList = $exercise->getQuestionList ();
        }

        foreach ( $questionList as $_id => $question )
        {
            $questionObj = new Question();
            $questionObj->setExerciseId ( $exId );

            if ( $questionObj->load ( $question[ 'id' ] ) )
            {
                $questionList[ $_id ][ 'description' ] = $questionObj->getDescription ();
                $questionList[ $_id ][ 'attachment' ] = $questionObj->getAttachment ();
                if ( !empty ( $questionList[ $_id ][ 'attachment' ] ) )
                {
                    $questionList[ $_id ][ 'attachmentURL' ] = get_conf ( 'rootWeb' ) . 'courses/' . claro_get_current_course_id () . '/exercise/question_' . $questionObj->getId () . '/' . $questionObj->getAttachment ();
                }

                switch ( $questionObj->getType () )
                {
                    case 'MCUA' :
                    case 'MCMA' :
                        {
                            $questionList[ $_id ][ 'answers' ] = $questionObj->answer->answerList;
                        }
                        break;
                    case 'TF' :
                        {
                            $questionList[ $_id ][ 'answers' ][ 0 ][ 'answer' ] = get_lang ( 'True' );
                            $questionList[ $_id ][ 'answers' ][ 0 ][ 'feedback' ] = $questionObj->answer->trueFeedback;
                            $questionList[ $_id ][ 'answers' ][ 1 ][ 'answer' ] = get_lang ( 'False' );
                            $questionList[ $_id ][ 'answers' ][ 1 ][ 'feedback' ] = $questionObj->answer->falseFeedback;
                        }
                        break;
                    case 'FIB' :
                        {
                            $questionList[ $_id ][ 'answerText' ] = $questionObj->answer->answerDecode ( $questionObj->answer->answerText );
                            $questionList[ $_id ][ 'answerList' ] = $questionObj->answer->answerList;

                            foreach ( $questionList[ $_id ][ 'answerList' ] as $i => $answer )
                            {
                                $questionList[ $_id ][ 'answerList' ][ $i ] = $questionObj->answer->answerDecode ( $questionObj->answer->addslashesEncodedBrackets ( $answer ) );
                            }
                            $questionList[ $_id ][ 'answerType' ] = $questionObj->answer->type;
                        }
                        break;
                    case 'MATCHING' :
                        {
                            $questionList[ $_id ][ 'leftList' ] = $questionObj->answer->leftList;
                            $questionList[ $_id ][ 'rightList' ] = $questionObj->answer->rightList;
                        }
                        break;
                }

                $questionList[ $_id ][ 'type' ] = $questionObj->getType ();
            }
        }

        Claroline::getDisplay ()->frameMode ();

        Claroline::getDisplay ()->header->SetTitle ( $exercise->getTitle () );

        Claroline::getDisplay ()->body->appendContent (
            '<header><h1 class="exerciseTitle">' . $exercise->getTitle () . '</h1>' );

        Claroline::getDisplay ()->body->appendContent (
            '<blockquote class="exerciseDescription">' . $exercise->getDescription () . '</blockquote></header>' );

        $i = 1;

        Claroline::getDisplay ()->body->appendContent ( '<article>' . "\n" );

        foreach ( $questionList as $question )
        {
            $htmlcontent = '<section class="question">' . "\n";
            $htmlcontent .=
                '<header>' . "\n" . '<h1 class="questionTitle">' . get_lang ( 'Question' ) . ' ' . $i
                . '&nbsp;-&nbsp;'
                . claro_htmlspecialchars ( strip_tags ( $question[ 'title' ] ) ) . '</h1>' . "\n"
            ;

            // Question description
            if ( trim ( claro_htmlspecialchars ( $question[ 'description' ] ) ) )
            {
                $htmlcontent .= '<blockquote class="questionDescription">' . "\n"
                    . claro_parse_user_text ( $question[ 'description' ] ) . '</blockquote>' . "\n"
                ;
            }
            // Attachment
            if ( !empty ( $question[ 'attachment' ] ) )
            {
                $extensionsList = array ( 'jpg', 'jpeg', 'bmp', 'gif', 'png' );

                $ext = strtolower ( get_file_extension ( $question[ 'attachment' ] ) );

                if ( in_array ( $ext, $extensionsList ) )
                {
                    $htmlcontent .= '<div class="questionAttachment">' . "\n"
                        . '<img src="' . $question[ 'attachmentURL' ] . '" />' . "\n"
                        . '</div>' . "\n"
                    ;
                }
            }

            $htmlcontent .= '</header>' . "\n" . '<table>' . "\n";

            switch ( $question[ 'type' ] )
            {
                case 'MCMA' :
                    {
                        foreach ( $question[ 'answers' ] as $answer )
                        {

                            $htmlcontent .= '<tr>' . "\n"
                                . '<td style="text-align: center; width: 3em;">[&nbsp;&nbsp;&nbsp;]</td>' . "\n"
                                . '<td>' . claro_parse_user_text ( $answer[ 'answer' ] ) . '</td>' . "\n"
                                . '</tr>' . "\n"
                            ;
                        }
                    }
                    break;
                case 'MCUA' :
                case 'TF' :
                    {

                        foreach ( $question[ 'answers' ] as $answer )
                        {

                            $htmlcontent .= '<tr>' . "\n"
                                . '<td style="text-align: center; width: 3em;">(&nbsp;&nbsp;&nbsp;)</td>' . "\n"
                                . '<td>' . claro_parse_user_text ( $answer[ 'answer' ] ) . '</td>' . "\n"
                                . '</tr>' . "\n"
                            ;
                        }
                    }
                    break;
                case 'FIB' :
                    {
                        $answerCount = count ( $question[ 'answerList' ] );
                        $replacementList = array ( );
                        switch ( $question[ 'answerType' ] )
                        {
                            case 1 :
                                {
                                    for ( $j = 0; $j < $answerCount; $j++ )
                                    {
                                        $replacementList[ ] = str_replace ( '$', '\$', ' [                  ] ' );
                                    }
                                }
                                break;
                            default :
                                {
                                    $answers = '';

                                    foreach ( $question[ 'answerList' ] as $answer )
                                    {
                                        if ( $answers )
                                        {
                                            $answers .= "/";
                                        }
                                        $answers .= $answer;
                                    }

                                    for ( $j = 0; $j < $answerCount; $j++ )
                                    {
                                        $replacementList[ ] = str_replace ( '$', '\$', ' [ ' . $answers . ' ] ' );
                                    }
                                }
                        }


                        $blankList = array ( );
                        foreach ( $question[ 'answerList' ] as $answer )
                        {
                            // filter slashes as they are modifiers in preg expressions
                            $blankList[ ] = '/\[' . preg_quote ( $answer, '/' ) . '\]/';
                        }

                        $displayedAnswer = preg_replace ( $blankList, $replacementList, claro_parse_user_text ( $question[ 'answerText' ] ), 1 );

                        $htmlcontent .= '<tr>' . "\n"
                            . '<td colspan="2">' . $displayedAnswer . '</td>' . "\n"
                            . '</tr>' . "\n"
                        ;
                    }
                    break;
                case 'MATCHING' :
                    {
                        foreach ( $question[ 'leftList' ] as $ql )
                        {
                            $ql[ 'answer' ] .= ' [';
                            $_qr = '';
                            foreach ( $question[ 'rightList' ] as $qr )
                            {
                                if ( $_qr )
                                {
                                    $_qr .= ' , ';
                                }
                                $_qr .= $qr[ 'answer' ];
                            }
                            $ql[ 'answer' ] .= $_qr;
                            $ql[ 'answer' ] .= '] ';
                            $htmlcontent .= '<tr>' . "\n"
                                . '<td colspan="2">' . claro_htmlspecialchars ( strip_tags ( $ql[ 'answer' ] ) ) . '</td>' . "\n"
                                . '</tr>' . "\n"
                            ;
                        }
                    }
                    break;
            }

            $htmlcontent .= "</table>\n";
            $htmlcontent .= "</section>\n";

            Claroline::getDisplay ()->body->appendContent ( $htmlcontent );

            $i++;
        }

        Claroline::getDisplay ()->body->appendContent ( '</article>' . "\n" );

        //Close and output PDF document
        echo Claroline::getDisplay ()->render ();

        exit ();
    }

    //-- export pdf
    if ( $cmd == 'exExportPDF' && $exId )
    {
        require_once( './lib/question.class.php' );

        $exercise = new Exercise();
        $exercise->load ( $exId );
        if ( $exercise->getShuffle () && isset ( $_REQUEST[ 'shuffle' ] ) && $_REQUEST[ 'shuffle' ] == 1 )
        {
            $questionList = $exercise->getRandomQuestionList ();
        }
        else
        {
            $questionList = $exercise->getQuestionList ();
        }

        foreach ( $questionList as $_id => $question )
        {
            $questionObj = new Question();
            $questionObj->setExerciseId ( $exId );

            if ( $questionObj->load ( $question[ 'id' ] ) )
            {
                $questionList[ $_id ][ 'description' ] = $questionObj->getDescription ();
                $questionList[ $_id ][ 'attachment' ] = $questionObj->getAttachment ();
                if ( !empty ( $questionList[ $_id ][ 'attachment' ] ) )
                {
                    $questionList[ $_id ][ 'attachmentURL' ] = get_conf ( 'rootWeb' ) . 'courses/' . claro_get_current_course_id () . '/exercise/question_' . $questionObj->getId () . '/' . $questionObj->getAttachment ();
                }

                switch ( $questionObj->getType () )
                {
                    case 'MCUA' :
                    case 'MCMA' :
                        {
                            $questionList[ $_id ][ 'answers' ] = $questionObj->answer->answerList;
                        }
                        break;
                    case 'TF' :
                        {
                            $questionList[ $_id ][ 'answers' ][ 0 ][ 'answer' ] = get_lang ( 'True' );
                            $questionList[ $_id ][ 'answers' ][ 0 ][ 'feedback' ] = $questionObj->answer->trueFeedback;
                            $questionList[ $_id ][ 'answers' ][ 1 ][ 'answer' ] = get_lang ( 'False' );
                            $questionList[ $_id ][ 'answers' ][ 1 ][ 'feedback' ] = $questionObj->answer->falseFeedback;
                        }
                        break;
                    case 'FIB' :
                        {
                            $questionList[ $_id ][ 'answerText' ] = $questionObj->answer->answerDecode ( $questionObj->answer->answerText );
                            $questionList[ $_id ][ 'answerList' ] = $questionObj->answer->answerList;

                            foreach ( $questionList[ $_id ][ 'answerList' ] as $i => $answer )
                            {
                                $questionList[ $_id ][ 'answerList' ][ $i ] = $questionObj->answer->answerDecode ( $questionObj->answer->addslashesEncodedBrackets ( $answer ) );
                            }
                            $questionList[ $_id ][ 'answerType' ] = $questionObj->answer->type;
                        }
                        break;
                    case 'MATCHING' :
                        {
                            $questionList[ $_id ][ 'leftList' ] = $questionObj->answer->leftList;
                            $questionList[ $_id ][ 'rightList' ] = $questionObj->answer->rightList;
                        }
                        break;
                }

                $questionList[ $_id ][ 'type' ] = $questionObj->getType ();
            }
        }

        require_once( get_path ( 'incRepositorySys' ) . '/lib/thirdparty/tcpdf/tcpdf.php' );

        // create new PDF document
        $pdf = new TCPDF ( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

        $pdf->SetTitle ( claro_utf8_encode ( $exercise->getTitle () ) );
        $pdf->SetSubject ( claro_utf8_encode ( $exercise->getTitle () ) );

        //set margins
        $pdf->SetMargins ( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
        $pdf->SetHeaderMargin ( PDF_MARGIN_HEADER );
        $pdf->SetFooterMargin ( PDF_MARGIN_FOOTER );

        //set auto page breaks
        $pdf->SetAutoPageBreak ( TRUE, PDF_MARGIN_BOTTOM );

        //set image scale factor
        $pdf->setImageScale ( PDF_IMAGE_SCALE_RATIO );

        $pdf->setPrintHeader ( false );

        // add a page
        $pdf->AddPage ();

        $htmlcontent = '<div style="font-size: xx-large; font-weight: bold;">' . claro_htmlspecialchars ( $exercise->getTitle () ) . '<div>' . "\n";

        $pdf->writeHTML ( claro_utf8_encode ( $htmlcontent, get_conf ( 'charset' ) ), true, 0, true, 0 );

        //change Img URL
        $exercise->setDescription ( claro_utf8_encode ( change_img_url_for_pdf ( $exercise->getDescription () ), get_conf ( 'charset' ) ) );

        //End change Img URL
        $htmlcontent = '<div style="font-size: normal; font-weight: normal;">' . $exercise->getDescription () . '</div><br /><br />' . "\n"
        ;
        $pdf->writeHTML ( claro_utf8_encode ( $htmlcontent, get_conf ( 'charset' ) ), true, 0, true, 0 );

        $i = 1;
        foreach ( $questionList as $question )
        {
            $htmlcontent = '<p><table cellspacing="4">' . "\n"
                . '<tbody>' . "\n"
                . '<tr>' . "\n"
                . '<th colspan="2" style="text-align: center; font-weight: bold; color: #693; background-color: #DEEECE;">' . get_lang ( 'Question' ) . ' ' . $i . '</th>' . "\n"
                . '</tr>' . "\n"
                // Question title
                . '<tr>' . "\n"
                . '<td colspan="2">' . claro_htmlspecialchars ( strip_tags ( $question[ 'title' ] ) ) . '</td>' . "\n"
                . '</tr>' . "\n"
            ;
            // Question description
            if ( trim ( claro_htmlspecialchars ( $question[ 'description' ] ) ) )
            {
                $htmlcontent .= '<tr>' . "\n"
                    . '<td colspan="2" style="font-size: x-small; font-style: italic;">' . change_img_url_for_pdf ( claro_parse_user_text ( $question[ 'description' ] ) ) . '</td>' . "\n"
                    . '</tr>' . "\n"
                ;
            }
            // Attachment
            if ( !empty ( $question[ 'attachment' ] ) )
            {
                $extensionsList = array ( 'jpg', 'jpeg', 'bmp', 'gif', 'png' );

                $ext = strtolower ( get_file_extension ( $question[ 'attachment' ] ) );

                if ( in_array ( $ext, $extensionsList ) )
                {
                    $htmlcontent .= '<tr>' . "\n"
                        . '<td colspan="2"><img src="' . $question[ 'attachmentURL' ] . '" /></td>' . "\n"
                        . '</tr>' . "\n"
                    ;
                }
            }


            switch ( $question[ 'type' ] )
            {
                case 'MCMA' :
                    {
                        foreach ( $question[ 'answers' ] as $answer )
                        {

                            $htmlcontent .= '<tr>' . "\n"
                                . '<td style="background-color: #EEE; text-align: center; width: 30px;">[   ]</td>' . "\n"
                                . '<td style="background-color: #EEE; width: 475px;">' . change_img_url_for_pdf ( claro_parse_user_text ( $answer[ 'answer' ] ) ) . '</td>' . "\n"
                                . '</tr>'
                            ;
                        }
                    }
                    break;
                case 'MCUA' :
                case 'TF' :
                    {
                        foreach ( $question[ 'answers' ] as $answer )
                        {

                            $htmlcontent .= '<tr>' . "\n"
                                . '<td style="background-color: #EEE; text-align: center; width: 30px;">O</td>' . "\n"
                                . '<td style="background-color: #EEE; width: 475px;">' . change_img_url_for_pdf ( claro_parse_user_text ( $answer[ 'answer' ] ) ) . '</td>' . "\n"
                                . '</tr>'
                            ;
                        }
                    }
                    break;
                case 'FIB' :
                    {
                        $answerCount = count ( $question[ 'answerList' ] );
                        $replacementList = array ( );
                        switch ( $question[ 'answerType' ] )
                        {
                            case 1 :
                                {
                                    for ( $j = 0; $j < $answerCount; $j++ )
                                    {
                                        $replacementList[ ] = str_replace ( '$', '\$', ' [                  ] ' );
                                    }
                                }
                                break;
                            default :
                                {
                                    $answers = '';

                                    foreach ( $question[ 'answerList' ] as $answer )
                                    {
                                        if ( $answers )
                                        {
                                            $answers .= "/";
                                        }
                                        $answers .= $answer;
                                    }

                                    for ( $j = 0; $j < $answerCount; $j++ )
                                    {
                                        $replacementList[ ] = str_replace ( '$', '\$', ' [ ' . $answers . ' ] ' );
                                    }
                                }
                        }


                        $blankList = array ( );
                        foreach ( $question[ 'answerList' ] as $answer )
                        {
                            // filter slashes as they are modifiers in preg expressions
                            $blankList[ ] = '/\[' . preg_quote ( $answer, '/' ) . '\]/';
                        }

                        $displayedAnswer = preg_replace ( $blankList, $replacementList, claro_parse_user_text ( $question[ 'answerText' ] ), 1 );

                        $htmlcontent .= '<tr>' . "\n"
                            . '<td colspan="2" style="background-color: #EEE;">' . $displayedAnswer . '</td>' . "\n"
                            . '</tr>' . "\n"
                        ;
                    }
                    break;
                case 'MATCHING' :
                    {
                        foreach ( $question[ 'leftList' ] as $ql )
                        {
                            $ql[ 'answer' ] .= ' [';
                            $_qr = '';
                            foreach ( $question[ 'rightList' ] as $qr )
                            {
                                if ( $_qr )
                                {
                                    $_qr .= ' , ';
                                }
                                $_qr .= $qr[ 'answer' ];
                            }
                            $ql[ 'answer' ] .= $_qr;
                            $ql[ 'answer' ] .= '] ';
                            $htmlcontent .= '<tr>' . "\n"
                                . '<td colspan="2" style="background-color: #EEE;">' . claro_htmlspecialchars ( strip_tags ( $ql[ 'answer' ] ) ) . '</td>' . "\n"
                                . '</tr>' . "\n"
                            ;
                        }
                    }
                    break;
            }

            $htmlcontent .= '</tbody>' . "\n"
                . '</table></p>' . "\n"
            ;

            $pdf->writeHTML ( claro_utf8_encode ( $htmlcontent, get_conf ( 'charset' ) ), true, 0, true, 0 );

            $i++;
        }

        //Close and output PDF document
        $pdf->Output ( 'exercise' . $exercise->getId () . '.pdf', 'D' );

        exit ();
    }
    //-- delete
    if ( $cmd == 'exDel' && $exId )
    {
        $exercise = new Exercise();
        $exercise->load ( $exId );

        $exercise->delete ();

        //notify manager that the exercise is deleted

        $eventNotifier->notifyCourseEvent ( "exercise_deleted", claro_get_current_course_id (), claro_get_current_tool_id (), $exId, claro_get_current_group_id (), "0" );
    }

    //-- change visibility
    if ( $cmd == 'exMkVis' && $exId )
    {
        Exercise::updateExerciseVisibility ( $exId, 'VISIBLE' );
        $eventNotifier->notifyCourseEvent ( "exercise_visible", claro_get_current_course_id (), claro_get_current_tool_id (), $exId, claro_get_current_group_id (), "0" );
        $eventNotifier->notifyCourseEvent ( "exercise_updated", claro_get_current_course_id (), claro_get_current_tool_id (), $exId, claro_get_current_group_id (), "0" );
    }

    if ( $cmd == 'exMkInvis' && $exId )
    {
        Exercise::updateExerciseVisibility ( $exId, 'INVISIBLE' );
        $eventNotifier->notifyCourseEvent ( "exercise_invisible", claro_get_current_course_id (), claro_get_current_tool_id (), $exId, claro_get_current_group_id (), "0" );
        $eventNotifier->notifyCourseEvent ( "exercise_updated", claro_get_current_course_id (), claro_get_current_tool_id (), $exId, claro_get_current_group_id (), "0" );
    }
}

// Save question list
if ( $cmd == 'exSaveQwz' )
{
    if ( is_null ( $exId ) )
    {
        $dialogBox->error ( get_lang ( 'Error : unable to save the questions list' ) );
    }
    else
    {
        $exercise = new Exercise();
        if ( !$exercise->load ( $exId ) )
        {
            $dialogBox->error ( get_lang ( 'Error: unable to load exercise' ) );
        }
        elseif ( isset ( $_SESSION[ 'lastRandomQuestionList' ] ) )
        {

            if ( !$exercise->saveRandomQuestionList ( $_SESSION[ '_user' ][ 'userId' ], $exercise->getId (), @unserialize ( $_SESSION[ 'lastRandomQuestionList' ] ) ) )
            {
                $dialogBox->error ( get_lang ( 'Error: unable to save this questions list' ) );
            }
            else
            {
                $dialogBox->success ( get_lang ( 'The list of questions has been saved' ) );
            }
            unset ( $_SESSION[ 'lastRandomQuestionList' ] );
        }
        else
        {
            $dialogBox->error ( get_lang ( 'Error: no questions list in memory' ) );
        }
    }
}
/*
 * Get list
 */
// pager initialisation
if ( !isset ( $_REQUEST[ 'offset' ] ) )
    $offset = 0;
else
    $offset = $_REQUEST[ 'offset' ];

// prepare query
if ( $is_allowedToEdit )
{
    // we need to check if exercise is used as a module in a learning path
    // to display a more complete confirm message for delete aciton
    $sql = "SELECT E.`id`, E.`title`, E.`visibility`, M.`module_id`
              FROM `" . $tbl_quiz_exercise . "` AS E
             LEFT JOIN `" . $tbl_lp_asset . "` AS A
             ON (A.`path` = E.`id` OR A.`path` IS NULL)
             LEFT JOIN `" . $tbl_lp_module . "` AS M
             ON A.`module_id` = M.`module_id`
                 AND M.`contentType` = 'EXERCISE'
             ORDER BY `id`";
}
// only for students
else
{
    if ( claro_is_user_authenticated () )
    {
        $sql = "SELECT `id`, `title`
              FROM `" . $tbl_quiz_exercise . "`
              WHERE `visibility` = 'VISIBLE'
              ORDER BY `id`";
    }
    else // anonymous user
    {
        $sql = "SELECT `id`, `title`
              FROM `" . $tbl_quiz_exercise . "`
              WHERE `visibility` = 'VISIBLE'
                AND `anonymousAttempts` = 'ALLOWED'
              ORDER BY `id`";
    }
}

$myPager = new claro_sql_pager ( $sql, $offset, get_conf ( 'exercisesPerPage', 25 ) );
$exerciseList = $myPager->get_result_list ();


// Display
$nameTools = get_lang ( 'Exercises' );
$noQUERY_STRING = true;
$helpUrl = $is_allowedToEdit ? get_help_page_url ( 'blockExercisesHelp', 'CLQWZ' ) : null;

$out = '';

if ( !$inLP )
{
    // Command list
    $cmdList = array ( );
    $advancedCmdList = array ( );

    if ( $is_allowedToEdit )
    {
        $cmdList[ ] = array (
            'img' => 'quiz_new',
            'name' => get_lang ( 'New exercise' ),
            'url' => claro_htmlspecialchars ( Url::Contextualize ( 'admin/edit_exercise.php?cmd=rqEdit' ) )
        );

        $advancedCmdList[ ] = array (
            'img' => 'question_pool',
            'name' => get_lang ( 'Question pool' ),
            'url' => claro_htmlspecialchars ( Url::Contextualize ( 'admin/question_pool.php' ) )
        );

        $advancedCmdList[ ] = array (
            'img' => 'question_pool',
            'name' => get_lang ( 'Question categories' ),
            'url' => claro_htmlspecialchars ( Url::Contextualize ( 'admin/question_category.php' ) )
        );

        $advancedCmdList[ ] = array (
            'img' => 'import',
            'name' => get_lang ( 'Import exercise' ),
            'url' => claro_htmlspecialchars ( Url::Contextualize ( 'exercise.php?cmd=rqImport' ) )
        );
    }

    if ( get_conf ( 'is_trackingEnabled' ) && claro_is_user_authenticated () )
    {
        $cmdList[ ] = array (
            'img' => 'statistics',
            'name' => get_lang ( 'My results' ),
            'url' => claro_htmlspecialchars ( Url::Contextualize ( '../tracking/userReport.php?userId=' . claro_get_current_user_id () ) )
        );
    }

    $out .= claro_html_tool_title ( $nameTools, $helpUrl, $cmdList, $advancedCmdList );
    $out .= $dialogBox->render ();

    //-- pager
    $out .= $myPager->disp_pager_tool_bar ( $_SERVER[ 'PHP_SELF' ] );

    //-- list

    $display = new ModuleTemplate( 'CLQWZ' , 'exercise_list.tpl.php' );
    $display->assign( 'exerciseList', $exerciseList );
    $display->assign( 'is_allowedToTrack', $is_allowedToTrack );
    $display->assign( 'is_allowedToEdit', $is_allowedToEdit );
    $display->assign( 'notifier', $claro_notifier );

    $out .= $display->render();
}
else
{
    $out .= claro_html_tool_title ( $nameTools, $helpUrl );
    $out .= $dialogBox->render ();
}

//-- pager
$out .= $myPager->disp_pager_tool_bar ( $_SERVER[ 'PHP_SELF' ] );

$claroline->display->body->appendContent ( $out );

echo $claroline->display->render ();

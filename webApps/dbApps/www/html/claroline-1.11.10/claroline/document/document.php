<?php // $Id: document.php 14548 2013-09-19 08:58:33Z jrm_ $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14548 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLDOC
 * @author      Hugues Peeters <hugues@claroline.net>
 * @author      Claro Team <cvs@claroline.net>
 */

/**
 *
 * DESCRIPTION:
 *
 * This PHP script allow user to manage files and directories on a remote http server.
 *  The user can : - navigate trough files and directories.
 *                 - upload a file
 *                 - rename, delete, copy a file or a directory
 *
 *  The script is organised in four sections.
 *
 *  * 1st section execute the command called by the user
 *                Note: somme commands of this section is organised in two step.
 *                The script lines always begin by the second step,
 *                so it allows to return more easily to the first step.
 *
 * * 2nd section define the directory to display
 *
 * * 3rd section read files and directories from the directory defined in part 2
 *
 *  * 4th section display all of that on a HTML page
 */

/* Programmer's documentation :
 *
 * Action variable : $_REQUEST['cmd']
 *
 * Available actions
 * - exUpload       : upload a file
 * - rqUpload       : display upload dialog
 * - submitImage    : upload image within html documents
 * - exMkHtml       : create html file
 * - exEditHtml     : edit html file
 * - exMkUrl        : create a link
 * - rqMkUrl        : display link creation dialog
 * - exMv           : move a file or directory
 * - rqMv           : display move dialog
 * - exRm           : delete a file or directory
 * - exEdit         : edit file or directory properties
 * - rqEdit         : display file properties dialog
 * - exMkDir        : create a directory
 * - rqMkDir        : display directory creation dialog
 * - exChVis        : change file or directory visibility
 * - rqSearch       : display search dialog
 * - exDownload     : download directory contents
 * - exSearch       : execute search
 */

/*= = = = = = = = = = = = = = = = =
       CLAROLINE MAIN
  = = = = = = = = = = = = = = = = = = = =*/

$tlabelReq = 'CLDOC';
require '../inc/claro_init_global.inc.php';

if ( ! claro_is_in_a_course() || ! claro_is_course_allowed()) claro_disp_auth_form(true);
$_course = claro_get_current_course_data();

/*
 * Library for images
 */

require_once get_path('incRepositorySys') . '/lib/image.lib.php';
require_once get_path('incRepositorySys') . '/lib/pager.lib.php';

/*
 * Library for file management and display
 */

require_once get_path('incRepositorySys') . '/lib/fileDisplay.lib.php';
require_once get_path('incRepositorySys')  . '/lib/fileManage.lib.php';
require_once get_path('incRepositorySys')  . '/lib/file.lib.php';
require_once get_path('incRepositorySys')  . '/lib/file/garbagecollector.lib.php';
require_once get_path('incRepositorySys')  . '/lib/url.lib.php';

/*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                     FILEMANAGER BASIC VARIABLES DEFINITION
  = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =*/

$baseServDir = get_path('coursesRepositorySys');
$baseServUrl = get_path('url') . '/';

$dialogBox = new DialogBox();

/*
 * The following variables depends on the use context
 * The document tool can be used at course or group level
 * (one document area for each group)
 */

if (claro_is_in_a_group() && claro_is_group_allowed())
{
    $_group = claro_get_current_group_data();

    $groupContext      = true;
    $courseContext     = false;

    $maxFilledSpace    = get_conf('maxFilledSpace_for_groups');
    $courseDir         = claro_get_course_path() . '/group/' . claro_get_current_group_data('directory');

    $is_allowedToEdit  = claro_is_group_member() ||  claro_is_group_tutor()|| claro_is_course_manager();
    $is_allowedToUnzip =  false;

    if ( ! claro_is_group_allowed() )
    {
      die('<center>You are not allowed to see this group\'s documents!!!</center>');
    }
}
else
{
    $groupContext     = false;
    $courseContext    = true;

    $courseDir   = claro_get_course_path().'/document';

    // initialise view mode tool
    claro_set_display_mode_available(true);

    $is_allowedToEdit  = claro_is_allowed_to_edit();
    $is_allowedToUnzip = claro_is_allowed_to_edit();
    $maxFilledSpace    = get_conf('maxFilledSpace_for_course');

    // table names for learning path (needed to check integrity)

    /*
     * DB tables definition
     */

    $tbl_cdb_names = claro_sql_get_course_tbl();

    $tbl_lp_learnPath            = $tbl_cdb_names['lp_learnPath'           ];
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
    $tbl_lp_module               = $tbl_cdb_names['lp_module'              ];
    $tbl_lp_asset                = $tbl_cdb_names['lp_asset'               ];

    $TABLELEARNPATH            = $tbl_lp_learnPath;
    $TABLELEARNPATHMODULE      = $tbl_lp_rel_learnPath_module;
    $TABLEUSERMODULEPROGRESS   = $tbl_lp_user_module_progress;
    $TABLEMODULE               = $tbl_lp_module;
    $TABLEASSET                = $tbl_lp_asset;

    $dbTable = $tbl_cdb_names['document'];
}

$baseWorkDir = $baseServDir.$courseDir;

if($is_allowedToEdit) // for teacher only
{
    require_once get_path('incRepositorySys') . '/lib/fileUpload.lib.php';

    if (isset($_REQUEST['uncompress']) && $_REQUEST['uncompress'] == 1)
    {
        require_once get_path('incRepositorySys') . '/lib/thirdparty/pclzip/pclzip.lib.php';
    }
}

// XSS protection
$cwd = isset( $_REQUEST['cwd'] ) ? strip_tags($_REQUEST['cwd']) : null;

// clean information submited by the user from antislash

if ( isset($_REQUEST['cmd']) ) $cmd = strip_tags($_REQUEST['cmd']);
else                           $cmd = null;

if ( isset($_REQUEST['docView']) ) $docView = strip_tags($_REQUEST['docView']);
else                               $docView = 'files';

if ( isset($_REQUEST['file']) /*&& is_download_url_encoded($_REQUEST['file']) */ )
{
    $_REQUEST['file'] = strip_tags(download_url_decode( $_REQUEST['file'] ));
}

/* > > > > > > MAIN SECTION  < < < < < < <*/

if ( $is_allowedToEdit ) // Document edition are reserved to certain people
{
    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                                  UPLOAD FILE
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */


    /*
     * check the request method in place of a variable from POST
     * because if the file size exceed the maximum file upload
     * size set in php.ini, all variables from POST are cleared !
     */

    if ('exUpload' == $cmd)
    {
        if( ! isset( $_FILES['userFile'] ) )
        {
            $dialogBox->error( get_lang('No file uploaded') );
        }
        else
        {
            if (   isset($_REQUEST['uncompress'])
                && $_REQUEST['uncompress'] == 1
                && $is_allowedToUnzip
                && preg_match('/.zip$/i',$_FILES['userFile']['name']))
            {
                $unzip = 'unzip';
            }
            else
            {
                $unzip = '';
            }

            if ( isset( $_REQUEST['comment'] ) && trim($_REQUEST['comment']) != '') // insert additional comment
            {
                $comment = trim($_REQUEST['comment']);
            }
            else
            {
                $comment = '';
            }

            $cwd = secure_file_path( $cwd );

            $uploadedFileName = treat_uploaded_file($_FILES['userFile'], $baseWorkDir,
                                    $cwd, $maxFilledSpace, $unzip);

            $uploadedFileNameList = array();

            if ($uploadedFileName !== false)
            {
                if (isset($_REQUEST['uncompress']) && $_REQUEST['uncompress'] == 1  && $unzip=='unzip')
                {
                    $dialogBox->success( get_lang('Zip file uploaded and uncompressed') );

                    foreach ( $uploadedFileName as $uploadedFile )
                    {
                        $uploadedFileNameList[] = $cwd . '/' . $uploadedFile['stored_filename'];
                    }
                }
                else
                {
                    $dialogBox->success( get_lang('The upload is finished') );
                    $uploadedFileNameList[] = $cwd . '/' . $uploadedFileName;
                }

                if ( !empty($comment) )
                {
                    $cur_dir = $cwd;

                    // add comment to each file
                    foreach ( $uploadedFileNameList as $fileName )
                    {
                        $fileName = secure_file_path($fileName);

                        if ( dirname($fileName) != $cwd )
                        {
                            // put a comment on the folder
                            update_db_info('update', dirname($fileName),
                                            array('comment' => $comment ) );
                            $cur_dir = dirname($fileName);
                        }

                        // put a comment on the file
                        update_db_info('update', $fileName,
                                        array('comment' => $comment ) );
                    }
                }
            }
            else
            {
                $dialogBox->error( claro_failure::get_last_failure() );
            }

            //notify that a new document has been uploaded
            
            if( is_array( $uploadedFileName ) )
            {
                if ( get_conf( 'cldoc_notifyAllFilesWhenUncompressingArchives', false ) )
                {
                    foreach ( $uploadedFileName as $uploadedFile )
                    {
                        $eventNotifier->notifyCourseEvent('document_file_added'
                                                 , claro_get_current_course_id()
                                                 , claro_get_current_tool_id()                                             
                                                 , $cwd . '/' . $uploadedFile['stored_filename']
                                                 , claro_get_current_group_id()
                                                 , '0');
                    }
                }
                else
                {
                    $eventNotifier->notifyCourseEvent('document_file_modified'
                                             , claro_get_current_course_id()
                                             , claro_get_current_tool_id()                                             
                                             , array( 'old_uri' => $cwd,'new_uri' => $cwd )
                                             , claro_get_current_group_id()
                                             , '0');
                }
            }
            else
            {
                $eventNotifier->notifyCourseEvent('document_file_added'
                                             , claro_get_current_course_id()
                                             , claro_get_current_tool_id()                                             
                                             , $cwd . '/' . $uploadedFileName
                                             , claro_get_current_group_id()
                                             , '0');
            }

            /*--------------------------------------------------------------------
               IN CASE OF HTML FILE, LOOKS FOR IMAGE NEEDING TO BE UPLOADED TOO
              --------------------------------------------------------------------*/

            if ( preg_match('/.htm$/i',$_FILES['userFile']['name'])
                || preg_match('/.html$/i',$_FILES['userFile']['name']) )
            {
                $imgFilePath = search_img_from_html($baseWorkDir . $cwd . '/' . $uploadedFileName);

                /*
                 * Generate Form for image upload
                 */

                if ( sizeof($imgFilePath) > 0)
                {
                    $dialogBox->warning( get_lang("Missing images detected") );
                    $form = '<form method="post" action="' . claro_htmlspecialchars($_SERVER['PHP_SELF']) . '" '
                    . 'enctype="multipart/form-data">' . "\n"
                    . claro_form_relay_context()
                    . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />'
                    . '<input type="hidden" name="cmd" value="submitImage" />' . "\n"
                    . '<input type="hidden" name="relatedFile" '
                    . ' value="' . $cwd . '/' . $uploadedFileName . '" />' . "\n"
                    . '<table border="0">' . "\n"
                    ;

                    foreach($imgFilePath as $thisImgKey => $thisImgFilePath )
                    {
                        $form .= '<tr>' . "\n"
                        . '<td>' . "\n"
                        . '<label for="' . $thisImgKey . '">' . basename($thisImgFilePath) . ' : </label>' . "\n"
                        . '</td>' . "\n"
                        . '<td>'
                        . '<input type="file"  id="' . $thisImgKey . '" name="imgFile[]" />' . "\n"
                        . '<input type="hidden" name="imgFilePath[]"  value="' . $thisImgFilePath . '" />'
                        . '</td>' . "\n"
                        . '</tr>' . "\n"
                        ;
                    }

                    $form .= '<tr>' . "\n"
                    . '<td>&nbsp;</td>' . "\n"
                    . '<td>' . "\n"
                    . '<input type="submit" name="submitImage" value="' . get_lang("Ok") . '" />&nbsp;' . "\n"
                    . claro_html_button(claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                    . '?cmd=exChDir&file=' . base64_encode($cwd) ) ), get_lang("Cancel") )
                    . '</td>' . "\n"
                    . '</tr>' . "\n\n"
                    . '</table>' . "\n"
                    . '</form>' . "\n"
                    ;
                    
                    $dialogBox->form( $form );
                }                            // end if ($imgFileNb > 0)
            }                                // end if (strrchr($fileName) == "htm"
        }                                    // end if is_uploaded_file
    }                                        // end if ($cmd == 'exUpload')

    if ($cmd == 'rqUpload')
    {
        /*
         * Prepare dialog box display
         */

        $spaceAlreadyOccupied = dir_total_space($baseWorkDir);
        $remainingDiskSpace = $maxFilledSpace - $spaceAlreadyOccupied;
        $maxUploadSize = get_max_upload_size( $maxFilledSpace,$baseWorkDir );

        if ( $remainingDiskSpace < 0 )
        {
            // Disk quota exceeded

            $remainingDiskSpace = 0;

            $adminEmailUrl = '<a href="mailto:'.get_conf('administrator_email').'">'
                . get_lang('Platform administrator') . '</a>';
            $dialogBox->error('<p>' . get_lang( 'Disk quota exceeded, please contact the %administrator',
                    array ( '%administrator' => $adminEmailUrl ) ) . '<br />' . "\n"
            . '<small>' . get_lang('Maximum disk space : %size',array('%size'=>format_file_size($maxFilledSpace))) . '</small><br />' . "\n"
            . '<small>' . get_lang('Disk space occupied : %size',array('%size'=>format_file_size($spaceAlreadyOccupied))) . '</small><br />' . "\n"
            . '<small>' . get_lang('Disk space available : %size',array('%size'=>format_file_size($remainingDiskSpace))) . '</small>'
            . '</p>')
            ;
        }
        else
        {
            /*
             * Technical note: 'cmd=exUpload' is added into the 'action'
             * attributes of the form, rather than simply put in a post
             * hidden input. That way, this parameter is concatenated with
             * the URL, and it guarantees than it will be received by the
             * server. The reason of this trick, is because, sometimes,
             * when file upload fails, no form data are received at all by
             * the server. For example when the size of the sent file is so
             * huge that its reception exceeds the max execution time
             * allowed for the script. When no 'cmd' argument are sent it is
             * impossible to manage this error gracefully. That's why,
             * exceptionally, we pass 'cmd' in the 'action' attribute of
             * the form.
             */

            $dialogBox->title( get_lang('Upload file') );
            
            $agreementText = claro_text_zone::get_content('textzone_upload_file_disclaimer');
            
            if ( !empty( $agreementText ) )
            {
                $dialogBox->info( $agreementText );
            }


            $form = '<form action="' . claro_htmlspecialchars($_SERVER['PHP_SELF']) . '" method="post" enctype="multipart/form-data">'
            . '<fieldset>'
            . claro_form_relay_context()
            . '<input type="hidden" name="claroFormId" value="' . uniqid('') . '" />' . "\n"
            . '<input type="hidden" name="cmd" value="exUpload" />' . "\n"
            . '<input type="hidden" name="cwd" value="' . claro_htmlspecialchars($cwd) . '" />' . "\n"
            . '<dl>'
            // upload file
            . '<dt><label for="userFile">' . get_lang('File') . '&nbsp;<span class="required">*</span></label>' . '</dt>' . "\n"
            . '<dd>'
            . '<input type="file" id="userFile" name="userFile" />' . "\n"
            // size and space infos
            . '<p class="notice">' . get_lang("Max file size") .' : ' . format_file_size( $maxUploadSize ) . '</p>' . "\n"
            . '</dd>'
            . '<dt>' . get_lang("Disk space available") . '</dt>'
            . '<dd>'
            . claro_html_progress_bar( $spaceAlreadyOccupied / $maxFilledSpace * 100 , 1)
            . ' <span class="notice">' . format_file_size($remainingDiskSpace) . '</span>'
            . '</dd>' . "\n";
            
            if ($is_allowedToUnzip)
            {
                // uncompress
                $form .= '<dt>' . "\n"
                . '<label for="uncompress"><img src="' . get_icon_url('mime/package-x-generic') . '" alt="" /> '
                . get_lang('uncompress zipped (.zip) file on the server').'</label>' . "\n"
                . '</dt>'
                . '<dd>'
                . '<input type="checkbox" id="uncompress" name="uncompress" value="1" />'
                . '</dd>' . "\n";
            }

            if ($courseContext)
            {
                if (!isset($oldComment)) $oldComment = "";
                // comment
                $form .= '<dt>' . "\n"
                . '<label for="comment">'.get_lang('Comment').'</label>'
                . '</dt>' . "\n"
                . '<dd>'
                . '<textarea rows=2 cols=50 id="comment" name="comment">' . claro_htmlspecialchars($oldComment) . '</textarea>' . "\n"
                . '</dd>' . "\n";
            }
            
            $form .= '</dl>'
            . '</fieldset>'
            . '<p><span class="required">*</span>&nbsp;'.get_lang('Denotes required fields') . '</p>' . "\n"
            . '<input type="submit" value="' . get_lang('Ok') . '" />&nbsp; '
            . claro_html_button(claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']. '?cmd=exChDir&file='. base64_encode($cwd))), get_lang('Cancel'))
            . '</form>';
            
            $dialogBox->form( $form );
        }
    }


    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                           UPLOAD RELATED IMAGE FILES
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    if ('submitImage' == $cmd )
    {

        $uploadImgFileNb = sizeof($_FILES['imgFile']);

        if ($uploadImgFileNb > 0)
        {
            // Try to create  a directory to store the image files
            $_REQUEST['relatedFile'] = secure_file_path( $_REQUEST['relatedFile']);

            $imgDirectory = $_REQUEST['relatedFile'].'_files';
            $imgDirectory = create_unexisting_directory($baseWorkDir.$imgDirectory);

            // set the makeInvisible command param appearing later in the script
            $mkInvisibl = str_replace($baseWorkDir, '', $imgDirectory);

            // move the uploaded image files into the corresponding image directory

            // Try to create  a directory to store the image files
            $newImgPathList = move_uploaded_file_collection_into_directory($_FILES['imgFile'], $imgDirectory);

            if ( !empty( $newImgPathList ) )
            {
                $newImgPathList = array_map('rawurlencode', $newImgPathList);
                // rawurlencode() does too much. We don't need to replace '/' by '%2F'
                $newImgPathList = str_replace('%2F', '/', $newImgPathList);

                replace_img_path_in_html_file($_REQUEST['imgFilePath'],
                                          $newImgPathList,
                                          $baseWorkDir.$_REQUEST['relatedFile']);

            }
        }                                            // end if ($uploadImgFileNb > 0)
    }                                        // end if ($submitImage)


    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                             CREATE DOCUMENT
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    /*------------------------------------------------------------------------
                            CREATE DOCUMENT : STEP 2
      ------------------------------------------------------------------------*/

    if ('exMkHtml' == $cmd)
    {
        $fileName = replace_dangerous_char(trim($_REQUEST['fileName']));
        $cwd = secure_file_path( $cwd);

        if (! empty($fileName) )
        {
            if ( ! in_array( strtolower (get_file_extension($_REQUEST['fileName']) ),
                           array('html', 'htm') ) )
            {
                $fileName = $fileName.'.html';
            }

            $cwd = secure_file_path( $cwd);

            $htmlContent = claro_parse_user_text( $_REQUEST['htmlContent'] );

            $template = new PhpTemplate( get_path('incRepositorySys') . '/templates/document_create.tpl.php' );
            $template->assign('content', $htmlContent);
            
            $htmlContent = $template->render();
            
            create_file($baseWorkDir.$cwd.'/'.$fileName,
                        $htmlContent);
            
            $eventNotifier->notifyCourseEvent('document_htmlfile_created',claro_get_current_course_id(), claro_get_current_tool_id(), $cwd.'/'.$fileName, claro_get_current_group_id(), "0");
            $dialogBox->success( get_lang('File created') );
        }
        else
        {
            $dialogBox->error( get_lang('File name is missing') );

            if (!empty($_REQUEST['htmlContent']))
            {
                $dialogBox->info( '<a href="'.claro_htmlspecialchars(Url::Contextualize('rqmkhtml.php?cmd=rqMkHtml&amp;cwd='.rawurlencode($cwd)
                . '&amp;htmlContent='.rawurlencode($_REQUEST['htmlContent']))).'">' . get_lang('Back to the editor'). '</a>' );
            }
        }
    }


    /*------------------------------------------------------------------------
                            CREATE DOCUMENT : STEP 1
      ------------------------------------------------------------------------*/

      // see rqmkhtml.php ...

    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                             EDIT DOCUMENT CONTENT
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    // TODO use the same code as exMkHml
    if ('exEditHtml' == $cmd)
    {
        $_REQUEST['file'] = secure_file_path( $_REQUEST['file']);
        $fp = fopen($baseWorkDir.$_REQUEST['file'], 'w');

        if ($fp)
        {
            $htmlContent = claro_parse_user_text( $_REQUEST['htmlContent'] );
            
            $template = new PhpTemplate(get_path('incRepositorySys') . '/templates/document_create.tpl.php' );
            $template->assign('content', $htmlContent);
            
            $htmlContent = $template->render();

            if ( fwrite($fp, $htmlContent) )
            {
                $eventNotifier->notifyCourseEvent('document_htmlfile_edited',claro_get_current_course_id(), claro_get_current_tool_id(), $_REQUEST['file'], claro_get_current_group_id(), "0");
                $dialogBox->success( get_lang('File content modified') );
            }

        }
    }


    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                                   CREATE URL
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    /*
     * The code begins with STEP 2
     * so it allows to return to STEP 1 if STEP 2 unsucceeds
     */

    /*------------------------------------------------------------------------
                              CREATE URL : STEP 2
    --------------------------------------------------------------------------*/

    if ( 'exMkUrl' == $cmd )
    {
        $fileName = replace_dangerous_char(trim($_REQUEST['fileName']));
        $url = trim($_REQUEST['url']);

        $cwd = secure_file_path( $cwd);

        if ( ! empty($fileName) && ! empty($url) )
        {
            // check for "http://", if the user forgot "http://" or "ftp://" or ...
            // the link will not be correct
            if( !preg_match( '/:\/\//',$url ) )
            {
                // add "http://" as default protocol for url
                $url = "http://".$url;
            }
            
            $linkFileExt = '.url';
            create_link_file( $baseWorkDir.$cwd.'/'.$fileName.$linkFileExt,
                              $url);

            if (   isset($_REQUEST['comment'])
                && trim($_REQUEST['comment']) != ''
                && $courseContext                     )
            {
                update_db_info('update', $cwd.'/'.$fileName.$linkFileExt,
                                array('comment' => trim($_REQUEST['comment']) ) );
            }
        }
        else
        {
            $dialogBox->error( get_lang("File Name or URL is missing.") );
            $cmd        = 'rqMkUrl';
        }
    }

    /*------------------------------------------------------------------------
                              CREATE URL : STEP 1
    --------------------------------------------------------------------------*/

    if ('rqMkUrl' == $cmd )
    {
        $dialogBox->title( get_lang('Create hyperlink') );
        $form = '<form action="'.claro_htmlspecialchars($_SERVER['PHP_SELF']).'" method="post">' . "\n"
        . claro_form_relay_context()
        . '<input type="hidden" name="cmd" value="exMkUrl" />' . "\n"
        . '<input type="hidden" name="cwd" value="'. claro_htmlspecialchars($cwd).'" />' . "\n"
        . '<label for="fileName">' . get_lang('Name'). '</label>&nbsp;<span class="required">*</span><br />' . "\n"
        . '<input type="text" id="fileName" name="fileName" /><br />' . "\n"
        . '<label for="url">'. get_lang('URL'). '</label>&nbsp;<span class="required">*</span><br />' . "\n"
        . '<input type="text" id="url" name="url" value="" />' . "\n"
        . '<br />' . "\n";

        if ($courseContext)
        {
            $form .= '<p><label for="comment">' . get_lang('Comment') . '</label>' . '<br />' . "\n"
            . '<textarea rows="2" cols="50" id="comment" name="comment"></textarea>' . "\n"
            . '</p>' . "\n";
        }

        $form .= '<input type="submit" value="'.get_lang('Ok') . '" />&nbsp; '
        . claro_html_button(claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']
        . '?cmd=exChDir&file='.base64_encode($cwd))), get_lang('Cancel'))
        . '</form>' . "\n";
        
        $dialogBox->form( $form );

    }

    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                             MOVE FILE OR DIRECTORY
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */


    /*------------------------------------------------------------------------
                        MOVE FILE OR DIRECTORY : STEP 2
    --------------------------------------------------------------------------*/

    if ('exMv' == $cmd )
    {
        $_REQUEST['file'       ] = secure_file_path( $_REQUEST['file'       ]);
        $_REQUEST['destination'] = secure_file_path( $_REQUEST['destination']);

        if ( claro_move_file($baseWorkDir.$_REQUEST['file'],$baseWorkDir.$_REQUEST['destination']) )
        {
            if ($courseContext)
            {
                update_db_info( 'update', $_REQUEST['file'],
                                array('path' => $_REQUEST['destination'].'/'.basename($_REQUEST['file'])) );
                update_Doc_Path_in_Assets("update",$_REQUEST['file'],
                                                   $_REQUEST['destination'].'/'.basename($_REQUEST['file']));
            }
            $ressource['old_uri'] = $_REQUEST['file'];
            $ressource['new_uri'] = $_REQUEST['destination'].'/'.basename($_REQUEST['file']);
            $eventNotifier->notifyCourseEvent('document_moved', claro_get_current_course_id(), claro_get_current_tool_id(), $ressource, claro_get_current_group_id(), '0');

            $dialogBox->success( get_lang('Element moved') );
        }
        else
        {
            $dialogBox->error( get_lang('File cannot be moved there') );

            if ( claro_failure::get_last_failure() == 'FILE EXISTS' )
            {
                $dialogBox->error( 'A file with the same name already exists.' );
            }
            elseif (claro_failure::get_last_failure() == 'MOVE INSIDE ITSELF')
            {
                $dialogBox->error( 'You can not move an element inside itself.' );
            }

            /* return to step 1 */

            $cmd = 'rqMv';
            unset ($_REQUEST['destination']);
        }
    }

    /*------------------------------------------------------------------------
                        MOVE FILE OR DIRECTORY : STEP 1
    --------------------------------------------------------------------------*/

    if ('rqMv' == $cmd )
    {
        $dialogBox->form( form_dir_list($_REQUEST['file'], $baseWorkDir) );
    }

    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                            DELETE FILE OR DIRECTORY
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    if ('exRm' == $cmd )
    {
        $file = secure_file_path( $_REQUEST['file']);
        
        $checkFile = trim($file, '/' );
        $checkFile = trim( $checkFile );
        
        if ( empty( $checkFile ) )
        {
            $dialogBox->error(get_lang('Cannot delete : missing file or directory name'));
        }
        elseif ( !empty($checkFile) && claro_delete_file($baseWorkDir.$file))
        {
            if ($courseContext)
            {
                update_db_info('delete', $file);
                update_Doc_Path_in_Assets('delete', $file, '');
            }

            //notify that a document has been deleted

            $eventNotifier->notifyCourseEvent("document_file_deleted",claro_get_current_course_id(), claro_get_current_tool_id(), $_REQUEST['file'], claro_get_current_group_id(), "0");

            $dialogBox->success( get_lang("Document deleted") );
        }
    }

    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                                      EDIT
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    /*
     * The code begin with STEP 2
     * so it allows to return to STEP 1
     * if STEP 2 unsucceds
     */

    /*------------------------------------------------------------------------
                                 EDIT : STEP 2
      ------------------------------------------------------------------------*/

    if ('exEdit' == $cmd )
    {
        if ( isset($_REQUEST['url']))
        {
            $url = trim ($_REQUEST['url']);

            if ( ! empty($url) )
            {
                /* First check for the presence of a protocol in the url
                 * If the user forget "http://" or "ftp://" or whatever,
                 * the link won't work.
                 * In this case, add "http://" as default url protocol
                 */

                if( ! preg_match( '/:\/\//',$url ) ) $url = 'http://'.$url;

                // else $url = $url ...

                create_link_file( $baseWorkDir.$_REQUEST['file'],
                                  $url);
            }

        }

        $directoryName = dirname($_REQUEST['file']);

        if ( $directoryName == '/' || $directoryName == '\\' )
        {
            // When the dir is root, PHP dirname leaves a '\' for windows or a '/' for Unix
            $directoryName = '';
        }

        $_REQUEST['newName'] = secure_file_path( trim($_REQUEST['newName']));


        if ( ! empty($_REQUEST['newName']) )
        {
            $newPath = $directoryName . '/' . $_REQUEST['newName'];
        }
        else
        {
            $newPath = secure_file_path( $_REQUEST['file'] );
        }

        $oldPath = secure_file_path( $_REQUEST['file'] );

        $newPath = claro_rename_file( $baseWorkDir.$oldPath, $baseWorkDir.$newPath );

        if ( $newPath )
        {
            $newPath = substr($newPath, strlen($baseWorkDir) );
            $dialogBox->success( get_lang('Element renamed') );

            if ($courseContext)
            {
                $newComment = trim($_REQUEST['newComment']); // remove spaces

                update_db_info('update', $_REQUEST['file'],
                                array( 'path'    => $newPath,
                                       'comment' => $newComment ) );

                update_Doc_Path_in_Assets('update', $_REQUEST['file'], $newPath);

                if ( ! empty($newComment) ) $dialogBox->success( get_lang('Comment modified') );
            }

            $ressource['old_uri'] = str_replace('..', '', $_REQUEST['file']);
            $ressource['new_uri'] = $newPath;
            $eventNotifier->notifyCourseEvent('document_file_modified',claro_get_current_course_id(), claro_get_current_tool_id(), $ressource , claro_get_current_group_id(), "0");
        }
        else
        {
            $dialogBox->error( get_lang('A file with this name already exists.') );

            /* return to step 1 */

            $cmd = 'rqEdit';
        }
    }

    /*------------------------------------------------------------------------
                                 EDIT : STEP 1
    -------------------------------------------------------------------------*/

    if ('rqEdit' == $cmd )
    {
        $fileName = basename($_REQUEST['file']);

        $dialogBox->title( get_lang('Edit <i>%filename</i>', array ('%filename' => claro_htmlspecialchars($fileName) ) ) );
        $form = '<form action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] ) . '" method="post">'
        . claro_form_relay_context()
        . '<input type="hidden" name="cmd" value="exEdit" />' . "\n"
        . '<input type="hidden" name="file" value="' . base64_encode( $_REQUEST['file'] ) . '" />' . "\n"
        . '<p>'
        . '<label for="newName">'
        . get_lang('Name')
        . '</label>&nbsp;<span class="required">*</span>' . "\n"
        . '<br />' . "\n"
        . '<input type="text" id="newName" name="newName" value="' . claro_htmlspecialchars($fileName) . '" />' . "\n"
        . '</p>' . "\n"
        ;

        if ('url' == get_file_extension($baseWorkDir.$_REQUEST['file']) )
        {
            if( file_exists($baseWorkDir.$_REQUEST['file']) )
            {
                $url = get_link_file_url($baseWorkDir.$_REQUEST['file']);
            }
            else
            {
                $url = '';
            }

            $form .= '<p>' . "\n"
            . '<label for="url">' . get_lang('URL') . "\n"
            . '</label>&nbsp;<span class="required">*</span>' . "\n"
            . '<br />' . "\n"
            . '<input type="text" id="url" name="url" value="' . claro_htmlspecialchars($url) . '" />' . "\n"
            . '</p>' . "\n"
            ;
        }

        if ($courseContext)
        {
            /* Search the old comment */
            $sql = "SELECT comment
                    FROM `".$dbTable."`
                    WHERE path = \"". claro_sql_escape($_REQUEST['file']) ."\"";

            $result = claro_sql_query ($sql);

            while( $row = mysql_fetch_array($result, MYSQL_ASSOC) ) $oldComment = $row['comment'];

            //list($oldComment) = claro_sql_query_fetch_all($sql);

            if (!isset($oldComment)) $oldComment = "";

            $form .= '<p><label for="newComment">' . get_lang('Comment') . '</label>'
                          . '<br />' . "\n"
                          . '<textarea rows="2" cols="50" name="newComment" id="newComment">'
                          . claro_htmlspecialchars($oldComment)
                          . '</textarea>'
                          . '</p>' . "\n";
        }

        /*
         * Add the possibility to edit on line the content of file
         * if it is an html file
         */
            
        if ( in_array( strtolower (get_file_extension($_REQUEST['file']) ),
                       array('html', 'htm') ) )
        {

            $form .= '<p><a href="'.claro_htmlspecialchars(Url::Contextualize('rqmkhtml.php?cmd=rqEditHtml&amp;file='. download_url_encode($_REQUEST['file']))) .'">'
                          .get_lang('Edit file content') . '</a></p>';
        }

        $form .= '<span class="required">*</span>&nbsp;'.get_lang('Denotes required fields') . '<br />' . "\n"
        . '<input type="submit" value="'.get_lang('Ok').'" />&nbsp; '
                      .claro_html_button(claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']. '?cmd=exChDir&file='.base64_encode(claro_dirname($_REQUEST['file'])))), get_lang('Cancel'))
                     .'</form>' . "\n";
                     
        $dialogBox->form( $form );

    } // end if cmd == rqEdit

    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                                CREATE DIRECTORY
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    /*
     * The code begin with STEP 2
     * so it allows to return to STEP 1
     * if STEP 2 unsucceds
     */

    /*------------------------------------------------------------------------
                                     STEP 2
      ------------------------------------------------------------------------*/

    if ('exMkDir' == $cmd )
    {
        $newDirName = replace_dangerous_char(trim($_REQUEST['newName']));

        $cwd = secure_file_path( $cwd);

        if( check_name_exist($baseWorkDir.$cwd.'/'.$newDirName) )
        {
            $dialogBox->error( get_lang('A file with this name already exists.') );
            $cmd = 'rqMkDir';
        }
        else
        {
            claro_mkdir($baseWorkDir.$cwd.'/'.$newDirName, CLARO_FILE_PERMISSIONS);

            $comment = isset($_REQUEST['comment'])?trim($_REQUEST['comment']):'';

            if ( !empty($comment) && $courseContext)
            {
                update_db_info('update', $cwd.'/'.$newDirName,
                                array('comment' => $comment) );
            }

            $dialogBox->success( get_lang("Directory created") );
            $eventNotifier->notifyCourseEvent("document_file_added",claro_get_current_course_id(), claro_get_current_tool_id(), $cwd.'/'.$newDirName, claro_get_current_group_id(), "0");
        }
    }


    /*------------------------------------------------------------------------
                                     STEP 1
      ------------------------------------------------------------------------*/

    if ('rqMkDir' == $cmd )
    {
        $dialogBox->title( get_lang('Create directory') );
        $form = '<form action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] ) . '" method="post">' . "\n"
        . claro_form_relay_context()
        . '<input type="hidden" name="cmd" value="exMkDir" />' . "\n"
        . '<input type="hidden" name="cwd" value="'. claro_htmlspecialchars($cwd).'" />' . "\n"
        // directory name
        . '<label for="newName">' . get_lang('Directory name').'</label>&nbsp;<span class="required">*</span><br />' . "\n"
        . '<input type="text" id="newName" name="newName" />' . "\n"
        . '<br />' . "\n" ;

        if ( $courseContext )
        {
            $form .= '<p>' . "\n"
            // comment
            . '<label for="comment">' . get_lang('Comment') . '</label><br />' . "\n"
            . '<textarea rows="5" cols="50" id="comment" name="comment"></textarea>' . "\n"
            . '</p>' . "\n";
        }

        $form .= '<span class="required">*</span>&nbsp;'.get_lang('Denotes required fields') . '<br />' . "\n"
        . '<input type="submit" value="'.get_lang('Ok').'" />&nbsp; '
        . claro_html_button(claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']. '?cmd=exChDir&file='.base64_encode($cwd))), get_lang('Cancel'))
        . '</form>' . "\n";

        $dialogBox->form( $form );
    }

    /*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                              VISIBILITY COMMANDS
      = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

    if ('exChVis'  == $cmd && $courseContext)
    {
        $_REQUEST['file'] = secure_file_path( $_REQUEST['file']);

        update_db_info('update', $_REQUEST['file'], array('visibility' => $_REQUEST['vis']) );

        //notify claroline that visibility changed

        if ($_REQUEST['vis'] == 'v')
        {
            $eventNotifier->notifyCourseEvent("document_visible",claro_get_current_course_id(), claro_get_current_tool_id(), $_REQUEST['file'], claro_get_current_group_id(), "0");
        }
        else
        {
            $eventNotifier->notifyCourseEvent("document_invisible",claro_get_current_course_id(), claro_get_current_tool_id(), $_REQUEST['file'], claro_get_current_group_id(), "0");
        }
    }
} // END is Allowed to Edit

if ('rqSearch' == $cmd )
{
    $searchMsg = !empty($cwd) ? '<br />' . get_lang('Search in %currentDirectory', array('%currentDirectory'=>claro_htmlspecialchars($cwd)) ) : '' ;
    $dialogBox->form( '<form action="' . claro_htmlspecialchars( $_SERVER['PHP_SELF'] ) . '" method="post">' . "\n"
                    . claro_form_relay_context()
                    . '<input type="hidden" name="cmd" value="exSearch" />' . "\n"
                    . '<input type="text" id="searchPattern" name="searchPattern" class="inputSearch" />' . "\n"
                    . '<input type="hidden" name="cwd" value="' . claro_htmlspecialchars($cwd) . '" />' . "\n"
                    . '<input type="submit" value="' . get_lang('Search' ) . '" />&nbsp;'
                    . claro_html_button(claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF']. '?cmd=exChDir&file='. base64_encode($cwd))), get_lang("Cancel"))
                    . $searchMsg
                    . '</form>' . "\n"
    );
}

if ('exDownload' == $cmd )
{
    if ( ( claro_is_user_authenticated() 
            && ( claro_is_allowed_to_edit() || get_conf('cldoc_allowNonManagersToDownloadFolder', true) ) ) 
        || ( get_conf('cldoc_allowNonManagersToDownloadFolder', true) 
            && get_conf( 'cldoc_allowAnonymousToDownloadFolder', true ) )
    )
    {
        /*
         * PREPARE THE FILE COLLECTION
         */
    
        if ( isset( $_REQUEST['file'] ) )
        {
            $requestDownloadPath = $baseWorkDir
                                 . secure_file_path( $_REQUEST['file']);
            $searchDownloadPattern = '';
        }
        elseif( isset($_REQUEST['searchPattern']) )
        {
                $requestDownloadPath   = $baseWorkDir;
                $searchDownloadPattern = $_REQUEST['searchPattern'];
        }
    
        if (! $is_allowedToEdit && $courseContext)
        {
            // Build an exclude file list to prevent simple user
            // to see document contained in "invisible" directories
            $searchExcludeList = getInvisibleDocumentList($baseWorkDir);
        }
        else
        {
            $searchExcludeList = array();
        }
    
        $filePathList = claro_search_file(search_string_to_pcre($searchDownloadPattern),
                                          $requestDownloadPath,
                                          true,
                                          'FILE',
                                          $searchExcludeList);
    
        /*
         * BUILD THE ZIP ARCHIVE
         */
    
        require_once get_path('incRepositorySys') . '/lib/thirdparty/pclzip/pclzip.lib.php';
    
        // Build archive in tmp course folder
        
        $downloadArchivePath = get_conf('cldoc_customTmpPath', '');
        
        if ( empty($downloadArchivePath) )
        {          
            $downloadArchivePath = get_path('coursesRepositorySys') . claro_get_course_path() . '/tmp/zip';
            $downloadArchiveFile = $downloadArchivePath . '/' . uniqid('') . '.zip';
        }
        else
        {
            $downloadArchiveFile = rtrim( $downloadArchivePath, '/' )
                . '/' . claro_get_current_course_id() 
                . '_CLDOC_' . uniqid('') . '.zip';
        }
    
        if ( ! is_dir( $downloadArchivePath ) )
        {
            mkdir( $downloadArchivePath, CLARO_FILE_PERMISSIONS, true );
        }
    
        $downloadArchiveName = get_conf('siteName');
    
        if (claro_is_in_a_course())
        {
            $downloadArchiveName .= '.' . $_course['officialCode'];
        }
    
        if (claro_is_in_a_group())
        {
            $downloadArchiveName .= '.' . claro_get_current_group_data('name');
        }
    
        if (isset($_REQUEST['file']))
        {
            $bnFile = basename($_REQUEST['file']);
            if (empty($bnFile)) $downloadArchiveName .= '.complete';
            else                $downloadArchiveName .= '.' . $bnFile;
        }
    
        if (isset($_REQUEST['searchPattern']))
        {
            $downloadArchiveName .= '.' . get_lang('Search') . '.' . $_REQUEST['searchPattern'];
        }
    
        $downloadArchiveName .= '.zip';
        $downloadArchiveName = str_replace('/', '', $downloadArchiveName);
    
        if ( $downloadArchiveName == '.zip')
        {
            $downloadArchiveName = get_lang('Documents and Links') . '.zip';
        }
    
        $downloadArchive     = new PclZip($downloadArchiveFile);
    
        $downloadArchive->add($filePathList,
                              PCLZIP_OPT_REMOVE_PATH,
                              $requestDownloadPath);
    
        if ( file_exists($downloadArchiveFile) )
        {
            /*
             * SEND THE ZIP ARCHIVE FOR DOWNLOAD
             */
    
            claro_send_file( $downloadArchiveFile, $downloadArchiveName );
            unlink($downloadArchiveFile);
            exit();
        }
        else
        {
            $dialogBox->error( get_lang('Unable to create zip file') );
        }
    }
    else
    {
        $dialogBox->error( get_lang('Not allowed') );
    }
}


/*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                            DEFINE CURRENT DIRECTORY
  = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

if (in_array($cmd, array('rqMv', 'exRm', 'rqEdit', 'exEdit', 'exEditHtml',
                         'exChVis', 'rqComment', 'exComment', 'submitImage')))
{
    $curDirPath = claro_dirname(isset($_REQUEST['file']) ? $_REQUEST['file'] : $_REQUEST['relatedFile']);
}
elseif (in_array($cmd, array('rqMkDir', 'exMkDir', 'rqUpload', 'exUpload',
                             'rqMkUrl', 'exMkUrl', 'reqMkHtml', 'exMkHtml', 'rqSearch')))
{
    $curDirPath = $cwd;
}
elseif ($cmd == 'exChDir')
{
        $curDirPath = $_REQUEST['file'];
}
elseif ($cmd == 'exMv')
{
    $curDirPath = $_REQUEST['destination'];
}
elseif ($docView == 'image' || $docView == 'thumbnails' )
{
    $curDirPath = $cwd;
}
else
{
    $curDirPath = '';
}

if ($curDirPath == '/' || $curDirPath == '\\' || strstr($curDirPath, '..'))
{
    $curDirPath = ''; // manage the root directory problem

    /*
     * The strstr($curDirPath, '..') prevent malicious users to go to the root directory
     */
}

if ( !file_exists($baseWorkDir.'/'.$curDirPath) || ! is_dir($baseWorkDir.'/'.$curDirPath) )
{
    $dialogBox->error(get_lang("The requested folder %dir does not exists", 
        array('%dir' => claro_htmlspecialchars($baseWorkDir.'/'.$curDirPath))));
    $curDirPath = ''; // back to root directory
}

$curDirName = basename($curDirPath);
$parentDir  = dirname($curDirPath);

if ($parentDir == '/' || $parentDir == '\\')
{
    $parentDir = ''; // manage the root directory problem
}

/*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                         READ CURRENT DIRECTORY CONTENT
  = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

/*----------------------------------------------------------------------------
                     LOAD FILES AND DIRECTORIES INTO ARRAYS
  ----------------------------------------------------------------------------*/

// $resultFileList = array();

if ($cmd == 'exSearch')
{
    if (! $is_allowedToEdit && $courseContext)
    {
        // Build an exclude file list to prevent simple user
        // to see document contained in "invisible" directories
        $searchExcludeList = getInvisibleDocumentList($baseWorkDir);
    }
    else
    {
      $searchExcludeList = array();
    }

    $cwd = secure_file_path( $cwd);


    $searchPattern    = $_REQUEST['searchPattern'];
    $searchPatternSql = $_REQUEST['searchPattern'];

    $searchPatternSql = str_replace('_', '\\_', $searchPatternSql);
    $searchPatternSql = str_replace('%', '\\%', $searchPatternSql);
    $searchPatternSql = str_replace('?', '_' , $searchPatternSql);
    $searchPatternSql = str_replace('*', '%' , $searchPatternSql);

    $searchRecursive = true;
    $searchBasePath  = $baseWorkDir.$cwd;
}
else
{
    $searchPattern   = '';
    $searchRecursive = false;
    $searchBasePath  = $baseWorkDir.$curDirPath;
    $searchExcludeList = array();
}

$searchBasePath = secure_file_path( $searchBasePath);

if (false === ($filePathList = claro_search_file( search_string_to_pcre($searchPattern),$searchBasePath,$searchRecursive,'ALL',$searchExcludeList)))
{
    switch (claro_failure::get_last_failure())
    {
        case 'BASE_DIR_DONT_EXIST' :
            pushClaroMessage($searchBasePath . ' : call to an unexisting directory in groups');
        break;
        default :
            pushClaroMessage('Search failed');
        break;
    }
    // TODO claro_search_file would return an empty array when failed
    $filePathList=array();
}

for ($i =0; $i < count($filePathList); $i++ )
{
    $filePathList[$i] = str_replace($baseWorkDir, '', $filePathList[$i]);
}

if ($cmd == 'exSearch' && $courseContext)
{
    $sql = "SELECT path FROM `".$dbTable."`
            WHERE comment LIKE '%".claro_sql_escape($searchPatternSql)."%'";

    $dbSearchResult = claro_sql_query_fetch_all_cols($sql);

    if (! $is_allowedToEdit)
    {
        for ($i = 0; $i < count($searchExcludeList) ; $i++)
        {
            for ($j = 0; $j < count($dbSearchResult['path']) ; $j++)
            {
                if (preg_match('|^'.$searchExcludeList[$i].'|', $dbSearchResult['path'][$j]) )
                {
                    unset($dbSearchResult['path'][$j]);
                }
            }
        }
    }

    $filePathList = array_unique( array_merge($filePathList, $dbSearchResult['path']) );
}

$fileList = array();

if ( count($filePathList) > 0 )
{
    /*--------------------------------------------------------------------------
                 SEARCHING FILES & DIRECTORIES INFOS ON THE DB
      ------------------------------------------------------------------------*/

    /*
     * Search infos in the DB about the current directory the user is in
     */

    if ($courseContext)
    {
        $sql = "SELECT `path`, `visibility`, `comment`
                FROM `".$dbTable."`
                WHERE path IN ('".implode("', '", array_map('claro_sql_escape', $filePathList) )."')";

        $xtraAttributeList = claro_sql_query_fetch_all_cols($sql);
    }
    else
    {
        $xtraAttributeList = array('path' => array(), 'visibility'=> array(), 'comment' => array() );
    }


    define('A_DIRECTORY', 1);
    define('A_FILE',      2);

    foreach($filePathList as $thisFile)
    {
        $fileAttributeList['path'] = $thisFile;

        if( is_dir($baseWorkDir.$thisFile) )
        {
            $fileAttributeList['type'] = A_DIRECTORY;
            $fileAttributeList['size'] = false;
            $fileAttributeList['date'] = false;
        }
        elseif( is_file($baseWorkDir.$thisFile) )
        {
            $fileAttributeList['type'] = A_FILE;
            $fileAttributeList['size'] = claro_get_file_size($baseWorkDir.$thisFile);
            $fileAttributeList['date'] = filemtime($baseWorkDir.$thisFile);
        }

        $xtraAttributeKey = array_search($thisFile, $xtraAttributeList['path']);

        if ($xtraAttributeKey !== false)
        {
            $fileAttributeList['comment'   ] = $xtraAttributeList['comment'   ][$xtraAttributeKey];
            $fileAttributeList['visibility'] = $xtraAttributeList['visibility'][$xtraAttributeKey];

            unset( $xtraAttributeList['path'][$xtraAttributeKey] );
        }
        else
        {
            $fileAttributeList['comment'   ] = null;
            $fileAttributeList['visibility'] = null;
        }

        $fileList[] = $fileAttributeList;
    } // end foreach $filePathList

    /*------------------------------------------------------------------------
                              CHECK BASE INTEGRITY
      ------------------------------------------------------------------------*/

    if ( count($xtraAttributeList['path']) > 0 )
    {
        $sql = "DELETE FROM `".$dbTable."`
                WHERE `path` IN ( \"".implode("\" , \"" , $xtraAttributeList['path'])."\" )";

        claro_sql_query($sql);

        $sql = "DELETE FROM `".$dbTable."`
                WHERE comment LIKE '' AND visibility LIKE 'v'";

        claro_sql_query($sql);
        /* The second query clean the DB 'in case of' empty records (no comment an visibility=v)
           These kind of records should'nt be there, but we never know... */

    }    // end if sizeof($attribute['path']) > 0

} // end if count ($filePathList) > 0


$defaultSortkeyList = array('type', 'path', 'date', 'size', 'visibility');
$fileLister = new claro_array_pager($fileList, 0, 1000);
foreach ($defaultSortkeyList as $thisSortkey) $fileLister->add_sort_key($thisSortkey, SORT_ASC);
if ( isset($_GET['sort']) ) $fileLister->set_sort_key($_GET['sort'], $_GET['dir']);

$sortUrlList = $fileLister->get_sort_url_list( Url::Contextualize($_SERVER['PHP_SELF'] . '?cmd=exChDir&file='.download_url_encode($curDirPath)) );

$fileList = $fileLister->get_result_list();


      /* > > > > > > END: COMMON TO TEACHERS AND STUDENTS < < < < < < <*/


/*= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
                                    DISPLAY
  = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = */

if ( $docView == 'image' )
{
    $noQUERY_STRING = true;
    $claroBodyOnload[] = "CLDOC.zoomOut();";
}

$nameTools = get_lang("Documents and Links");

// Used for the breadcrumb when one need to add a parameter after the filename
$_SERVER['QUERY_STRING'] = '';



// Display (3 view modes: image, thumbnails or files)
JavascriptLanguage::getInstance()->addLangVar('Are you sure to delete %name ?');
JavascriptLanguage::getInstance()->addLangVar('Click to zoom out');
JavascriptLanguage::getInstance()->addLangVar('Click to zoom in');

JavascriptLoader::getInstance()->load('documents');

$out = '';

$dspCurDirName = claro_htmlspecialchars($curDirName);
$dspCurDirPath = claro_htmlspecialchars($curDirPath);
$cmdCurDirPath = rawurlencode($curDirPath);
$cmdParentDir  = rawurlencode($parentDir);

// Define toot title and subtitle
$titleElement['mainTitle'] = get_lang("Documents and Links");

if ( claro_is_in_a_group() && claro_is_group_allowed())
{
    $titleElement['supraTitle'] = claro_get_current_group_data('name');
}

// Get image list from file list
if( ($docView == 'image' || $docView == 'thumbnails') && isset($fileList) )
{
    $imageList = get_image_list($fileList, $is_allowedToEdit);
}

// Command list
$cmdList = array();

/*
 * if the $curDirName is empty, we're in the root point
 * and we can't go to a parent dir
 */
if ($curDirName || $cmd == 'exSearch')
{
    $cmdList[] = array(
        'img' => 'parent',
        'name' => get_lang('Up'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=exChDir&file='.download_url_encode($parentDir)))
    );
}

$cmdList[] = array(
    'img' => 'search',
    'name' => get_lang('Search'),
    'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqSearch&cwd='.$cmdCurDirPath ))
);

if ( trim($searchPattern) != '')
    $downloadArgument = 'searchPattern='.rawurlencode($searchPattern);
else
    $downloadArgument = 'file='. download_url_encode($curDirPath);

if ( ( claro_is_user_authenticated() 
        && ( claro_is_allowed_to_edit() || get_conf('cldoc_allowNonManagersToDownloadFolder', true) ) ) 
    || ( get_conf('cldoc_allowNonManagersToDownloadFolder', true) 
        && get_conf( 'cldoc_allowAnonymousToDownloadFolder', true ) )
)
{
    if( isset($fileList) && count($fileList) > 0 )
    {
        // Download current folder
        $cmdList[] = array(
            'img' => 'save',
            'name' => get_lang('Download current directory'),
            'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=exDownload&'.$downloadArgument))
        );
    }
}


if ($is_allowedToEdit)
{
    // Create directory, document, hyperlink or upload file
    $cmdList[] = array(
        'img' => 'upload',
        'name' => get_lang('Upload file'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqUpload&cwd='.$cmdCurDirPath))
    );
    
    $cmdList[] = array(
        'img' => 'folder',
        'name' => get_lang('Create directory'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqMkDir&cwd='.$cmdCurDirPath))
    );
    
    $cmdList[] = array(
        'img' => 'link',
        'name' => get_lang('Create hyperlink'),
        'url' => claro_htmlspecialchars(Url::Contextualize($_SERVER['PHP_SELF'].'?cmd=rqMkUrl&cwd='.$cmdCurDirPath))
    );
    
    $cmdList[] = array(
        'img' => 'html',
        'name' => get_lang('Create Document'),
        'url' => claro_htmlspecialchars(Url::Contextualize('rqmkhtml.php?cmd=rqMkHtml&cwd='.$cmdCurDirPath))
    );
}

$helpUrl = $is_allowedToEdit ? get_help_page_url('blockDocumentsHelp','CLDOC') : null;

// Display title
$out .= claro_html_tool_title($titleElement, $helpUrl, $cmdList ); //, 3);

// Display dialog box
$out .= $dialogBox->render();

// Define colspan
$is_allowedToEdit ? $colspan = 7 : $colspan = 3;



/*----------------------------------------------------------------
                VIEW IMAGES ($docView == 'image')
----------------------------------------------------------------*/
if ($docView == 'image' && isset($imageList) && count($imageList) > 0)
{
    $colspan = 3;
    
    // Get requested image name
    if( isset( $_REQUEST['file'] ) && ! isset( $_REQUEST['viewMode'] ) )
    {
        $file = $_REQUEST['file'];
        $fileName = basename( $_REQUEST['file'] );
    }
    else
    {
        $file = $fileList['path'][$imageList[0]];
        $fileName = basename( $file );
    }
    
    $searchCmdUrl = "";
    
    if( isset( $_REQUEST['searchPattern'] ) )
    {
        $searchCmdUrl = "&amp;cmd=exSearch&amp;searchPattern=" . rawurlencode( $_REQUEST['searchPattern'] );
    }
    
    // Get requested image key in fileList
    $imgKey = image_search( $file, $fileList );
    
    $current = get_current_index($imageList, $imgKey);
    
    $offset = "&amp;offset=" . $current;
    
    // Compute absolute path to requested image
    $doc_url = claro_get_file_download_url( $file );
    
    // View Mode Bar
    if ($cmd == 'exSearch')
    {
        $curDirLine = get_lang('Search result');
    }
    elseif ($curDirName)
    {
           $curDirLine = '<img src="' . get_icon_url('opendir') . '" alt="" />' . "\n"
           . $dspCurDirName . "\n"
           ;
    }
    else
    {
        $curDirLine = '&nbsp;';
    }
    
    $docViewToolbar[] = '<a class="claroCmd" href="'
         . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
            . '?docView=files&cmd=exChDir&file='
            . base64_encode($curDirPath) . $searchCmdUrl ))
         . '">'
         . '<img src="' . get_icon_url('document') . '" alt="" /> '
         . get_lang('File list')
         . '</a>';
    
    $docViewToolbar[] = '<a class="claroCmd" href="'
         . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
            . '?docView=thumbnails&cwd='
            . rawurlencode($curDirPath) . $searchCmdUrl ))
         . '">'
         . '<img src="' . get_icon_url('image') . '" alt="" /> '
         . get_lang('Thumbnails').'</a>';
    
    // Image description table
    $out .= '<table class="claroTable" width="100%">' . "\n" ;
    $out .= '<!-- current dir name line -->' . "\n"
            .'<tr>' . "\n"
            .'<th class="superHeader" colspan="' . $colspan . '" align="left">' . "\n"
            .'<div style="float: right;">'. claro_html_menu_horizontal($docViewToolbar) . '</div>'
            .$curDirLine
            .'</th>' . "\n"
            .'</tr>' . "\n";
    
    
    // Tool bar
    // create image title
    $imgTitle = claro_htmlspecialchars($fileName);
    
    // create image style
    $titleStyle ='title';
    
    // if image invisible set style to invisible
    if ( isset( $fileList['visibility'] ) &&  $fileList['visibility'][$imgKey] == 'i')
    {
        $titleStyle = 'title invisible';
    } // if invisible
    
    $out .= '<tr class="toolbar" valign="top">' . "\n";
    
    // Display link to previous image
    $out .= display_link_to_previous_image($imageList, $fileList, $current);
    
    // Display title of current image
    $out .= '<th class="' . $titleStyle . '">' ."\n"
          . $imgTitle
          . '</th>' . "\n";
    
    // Display link to previous image
    $out .= display_link_to_next_image($imageList, $fileList, $current)
          . '</tr>' . "\n"
          . '</table>' . "\n";
    
    // Display comment about  requested image
    if ( isset ( $fileList['comment'] ) && $fileList['comment'][$imgKey])
    {
        $out .= '<hr />' . "\n"
              . '<blockquote>' . $fileList['comment'][$imgKey] . '</blockquote>' . "\n";
    }
    else
    {
        $out .= '<!-- empty -->' . "\n";
    }
    
    
    // Display current image
    
    // System path
    $imgPath = get_path('coursesRepositorySys') . $courseDir
        . $file
        ;
    
    // Get image info
    list($width, $height, $type, $attr ) = getimagesize($imgPath);
    
    // Get color depth ! used to get both mime-type and color depth working together
    $depth = get_image_color_depth( $imgPath );
    
    if( version_compare(phpversion(), '4.3.0', '>') )
    {
        $mime_type = image_type_to_mime_type($type);
    }
    else
    {
        $mime_type = get_lang('No mime-type available');
    }
    
    // Display image
    $out .= '<p style="text-align: center;">'
          . '<a href="#"><img id="mainImage" src="' . claro_htmlspecialchars(Url::Contextualize($doc_url)) . '" alt="' . $fileName . '" /></a>'
          . '</p>' . "\n"
          . '<p style="text-align: center;">'
          . '<a href="' . claro_htmlspecialchars(Url::Contextualize($doc_url)) . '">' . get_lang('Direct link to image') . '</a>'
          . '</p>' . "\n";
    
    // Display image info (title, size, color depth, mime-type)
    $out .= '<br />'
          . '<small>[ Info : ' . $imgTitle
          . ' - ' . $width . 'x' . $height
          . ' - ' .format_file_size($fileList[$imgKey]['size'])
          . ' - ' . $depth . 'bits'
          . ' - ' . $mime_type
          . ' ]</small>' . "\n";
}

/*----------------------------------------------------------------
                VIEW THUMBNAILS ($docView == 'thumbnails')
----------------------------------------------------------------*/
elseif ($docView == 'thumbnails' ) // thumbnails mode
{
    // intialize page number
     $page = 1; // if not set, set to first page
    
     if( isset( $_REQUEST['page'] ) )
    {
        $page = $_REQUEST['page'];
    }
    
    if( isset( $_REQUEST['offset'] ) )
    {
          $page = get_page_number($_REQUEST['offset']);
    }
    
    $searchCmdUrl = "";
    
    if( isset( $_REQUEST['searchPattern'] ) )
    {
        $searchCmdUrl = '&amp;cmd=exSearch&amp;searchPattern=' . rawurlencode( $_REQUEST['searchPattern'] );
    }
    
    // compute column width
     $colWidth = round(100 / get_conf('numberOfCols', 3));
    
    // display table
    $out .= "\n" . '<table class="claroTable" width="100%">' . "\n";
    
    // View Mode Bar
    
    if ($cmd == 'exSearch')
    {
        $curDirLine = get_lang('Search result');
    }
    elseif ($curDirName)
    {
        $curDirLine = '<img src="' . get_icon_url('opendir') . '" alt="" />' . "\n"
        . $dspCurDirName."\n";
    }
    else
    {
        $curDirLine = '&nbsp;';
    }
    
    $docViewToolbar[] = '<a class="claroCmd" href="'
         . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
            . '?docView=files&cmd=exChDir&amp;file='
            . base64_encode($curDirPath) . $searchCmdUrl ))
         . '">'
         . '<img src="' . get_icon_url('document') . '" alt="" /> ' . "\n"
         . get_lang('File list') . '</a>';
         
    $docViewToolbar[] = '<span class="claroCmdDisabled">'
        . '<img src="' . get_icon_url('image') . '" alt="" /> ' . "\n"
        . get_lang('Thumbnails').'</span>'
        ;
    
    $colspan = get_conf( 'numberOfCols', 3 );
    
    $out .= '<!-- current dir name line -->' . "\n"
            .'<tr>' . "\n"
            .'<th class="superHeader" colspan="' . $colspan . '" align="left">' . "\n"
            .'<div style="float: right;">'. claro_html_menu_horizontal($docViewToolbar) . '</div>'
            .$curDirLine
            .'</th>' . "\n"
            .'</tr>' . "\n";
    
    // Toolbar
    $out .= '<tr class="toolbar">' . "\n"
          . '<th class="prev" colspan="1" style="width: ' . $colWidth . '%;">' . "\n";
    
    if( !isset($imageList) || count($imageList) == 0)
    {
        $colspan = get_conf( 'numberOfCols', 3 );
        
        $out .= '<!-- current dir name line -->' . "\n"
              .'<tr>' . "\n"
              .'<td colspan="' . $colspan . '" align="left">' . "\n"
              . get_lang('No image to display')
              .'</td>' . "\n"
              .'</tr>' . "\n";
    }
    else
    {
        if(has_previous_page($imageList, $page))
        {
            // link to previous page
              $out .= '<a href="'
                   . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                        . '?docView=thumbnails&cwd=' . rawurlencode($curDirPath)
                        . '&page=' . ($page - 1) . $searchCmdUrl ))
                   . '">&lt;&lt;&nbsp;&nbsp;page&nbsp;'
                   . ($page - 1) . '</a>' . "\n";
        }
        else
        {
            $out .= '<!-- empty -->';
        }
        
        $out .= '</th>' . "\n"
              . '<th class="title" colspan="' . (get_conf( 'numberOfCols', 3) - 2) . '">' . "\n"
              . '<p align="center">' . get_lang('Page') . '&nbsp;' . $page . '</p>'
              . '</th>' . "\n"
              . '<th class="next" colspan="1" style="width: ' . $colWidth . '%;">' . "\n";
        
        if(has_next_page($imageList, $page))
        {
            // link to next page
            $out .= '<a href="'
                  . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
                        . '?docView=thumbnails&cwd=' . rawurlencode($curDirPath)
                        . '&page=' . ($page + 1) . $searchCmdUrl ))
                  . '">'. get_lang('Page') .'&nbsp;'
                  . ($page + 1) . '&nbsp;&nbsp;&gt;&gt;</a>' . "\n";
        }
        else
        {
            $out .= '<!-- empty -->';
        }
        
        $out .= '</th>' . "\n"
              . '</tr>' . "\n"
              . display_thumbnails($imageList, $fileList, $page
                , get_conf('thumbnailWidth'), $colWidth
                , get_conf('numberOfCols'), get_conf('numberOfRows') );
    }
    
    $out .= '</table>' . "\n";
}

/*----------------------------------------------------------------
                VIEW CURRENT DIRECTORY LINE ($docView == 'files')
----------------------------------------------------------------*/
else
{
    $searchCmdUrl = '';
    
    if( isset( $_REQUEST['searchPattern'] ) )
    {
        $searchCmdUrl = '&amp;cmd=exSearch&amp;searchPattern=' . rawurlencode( $_REQUEST['searchPattern'] );
    }
    
    $out .= claro_html_document_breadcrumb($curDirPath)
          .'<table class="claroTable emphaseLine" width="100%">'
          . '<thead>'
          . "\n";
    
    // CURRENT DIRECTORY LINE
    if ($cmd == 'exSearch')
    {
        $curDirLine = get_lang('Search result');
    }
    elseif ($curDirName)
    {
        $curDirLine = '<img src="' . get_icon_url('opendir') . '" alt="" />' . "\n"
            .$dspCurDirName."\n";
    }
    else
    {
        $curDirLine = '&nbsp;';
    }
    
    $docViewToolbar[] = '<span class="claroCmdDisabled">'
        . '<img src="' . get_icon_url('document') . '" alt="" />' . "\n"
        . get_lang('File list')
        . '</span>';
    
    $docViewToolbar[] = '<a class="claroCmd" href="'
         . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF']
         . '?docView=thumbnails&cwd='. $curDirPath . $searchCmdUrl ))
         .'">'
         . '<img src="' . get_icon_url('image') . '" alt="" /> ' . "\n"
         . get_lang('Thumbnails').'</a>';
    
    $out .= '<!-- current dir name line -->' . "\n"
          .'<tr>' . "\n"
          .'<th class="superHeader" colspan="' . $colspan . '" align="left">' . "\n"
          .'<div style="float: right;">'. claro_html_menu_horizontal($docViewToolbar).'</div>'
          .$curDirLine
          .'</th>' . "\n"
          .'</tr>' . "\n"
          . '<tr align="center" valign="top">' . "\n";
    
    # Patch to avoid E_NOTICE when no files in directory empty
    # FIXME find a more elegant way to solve the problem
    if ( count( $sortUrlList ) > 0 )
    {
        $out .= '<th><a href="'.claro_htmlspecialchars(Url::Contextualize($sortUrlList['path'])).'">'.get_lang('Name').'</a></th>' . "\n"
              . '<th><a href="'.claro_htmlspecialchars(Url::Contextualize($sortUrlList['size'])).'">'.get_lang('Size').'</a></th>' . "\n"
              . '<th><a href="'.claro_htmlspecialchars(Url::Contextualize($sortUrlList['date'])).'">'.get_lang('Last modification date').'</a></th>' . "\n";
    }
    else
    {
        $out .= '<th>'.get_lang('Name').'</th>' . "\n"
              . '<th>'.get_lang('Size').'</th>' . "\n"
              . '<th>'.get_lang('Date').'</th>' . "\n";
    }
    
    if ($is_allowedToEdit)
    {
        $out .=  '<th>'.get_lang('Modify').'</th>' . "\n"
              . '<th>'.get_lang('Delete').'</th>' . "\n"
              . '<th>'.get_lang('Move').'</th>' . "\n";
        
        if ($courseContext)
        {
            $out .= '<th>'.get_lang('Visibility').'</th>' . "\n";
        }
        elseif ($groupContext)
        {
            $out .= '<th>'.get_lang('Publish').'</th>' . "\n";
        }
    }
    
    $out .= '</tr>' . "\n"
          . '</thead>'
          .'<tbody>';
    
    /*------------------------------------------------------------------------
                               DISPLAY FILE LIST
      ------------------------------------------------------------------------*/
    
    // Find the recent documents with the notification system
    if (claro_is_user_authenticated())
    {
        $date = $claro_notifier->get_notification_date(claro_get_current_user_id());
    }
    
    if (!empty($fileList))
    {
        foreach($fileList as $thisFile )
        {
            // Note. We've switched from 'each' to 'foreach', as 'each' seems to
            // poses problems on PHP 4.1, when the array contains only
            // a single element
            
            $dspFileName = claro_htmlspecialchars( basename($thisFile['path']) );
            $cmdFileName = download_url_encode($thisFile['path']);
            
            if ( $thisFile['visibility'] == 'i')
            {
                if ($is_allowedToEdit)
                {
                    $style='invisible ';
                }
                else
                {
                    continue; // skip the display of this file
                }
            }
            else
            {
                $style='';
            }
            
            //modify style if the file is recently added since last login
            if (claro_is_user_authenticated()
                && $claro_notifier->is_a_notified_document(claro_get_current_course_id(), $date, claro_get_current_user_id(), claro_get_current_group_id(), claro_get_current_tool_id(), $thisFile))
            {
                $classItem=' hot';
            }
            else // otherwise just display its name normally
            {
                $classItem='';
            }
            
            if ($thisFile['type'] == A_FILE)
            {
                $image       = choose_image($thisFile['path']);
                $size        = format_file_size($thisFile['size']);
                $date        = format_date($thisFile['date']);
                
                $urlFileName = claro_htmlspecialchars( claro_get_file_download_url( $thisFile['path'] ) );
                
                //$urlFileName = "goto/?doc_url=".rawurlencode($cmdFileName);
                //format_url($baseServUrl.$courseDir.$curDirPath."/".$fileName));
                
                $target = ( get_conf('openNewWindowForDoc') ? 'target="_blank"' : '');
            }
            elseif ($thisFile['type'] == A_DIRECTORY)
            {
                $image       = 'folder';
                $size        = '&nbsp;';
                $date        = '&nbsp;';
                $urlFileName = claro_htmlspecialchars(Url::Contextualize(
                    $_SERVER['PHP_SELF'].'?cmd=exChDir&amp;file='
                    .$cmdFileName ));
                
                $target = '';
            }
            
            $out .= '<tr align="center">' . "\n"
                .'<td align="left">';
            
            if( is_image( $thisFile['path'] ) )
            {
                $out .= '<a class="'.$style.' item'.$classItem.'" href="'
                    . claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] .
                    '?docView=image&amp;file=' . download_url_encode($thisFile['path']) . '&amp;cwd='
                    . $curDirPath . $searchCmdUrl ))
                    .'">';
            }
            else
            {
                    $out .= '<a class="'.$style.' item'.$classItem.'" href="'.$urlFileName.'" '.$target.' >';
            } // end if is_image
            
            $out .= '<img src="' . get_icon_url($image) . '" alt="" /> '.$dspFileName.'</a>'
                  . '</td>' . "\n"
                  .'<td><small>'.$size.'</small></td>' . "\n"
                  .'<td><small>'.$date.'</small></td>' . "\n";
            
            /* NB : Before tracking implementation the url above was simply
             * "<a href=\"",$urlFileName,"\"",$style,">"
             */
            
            if($is_allowedToEdit)
            {
                /* EDIT COMMAND */
                $out .= '<td>'
                      .'<a href="'.claro_htmlspecialchars(Url::Contextualize(
                        $_SERVER['PHP_SELF'].'?cmd=rqEdit&amp;file='.$cmdFileName ))
                      .'">'
                      .'<img src="' . get_icon_url('edit') . '" alt="'.get_lang('Modify').'" />'
                      .'</a>'
                      .'</td>' . "\n";
                
                /* DELETE COMMAND */
                $out .= '<td>'
                      .'<a href="' . claro_htmlspecialchars(Url::Contextualize(
                        $_SERVER['PHP_SELF'] . '?cmd=exRm&amp;file=' . $cmdFileName ))
                      . '" '
                      .'onclick="return CLDOC.confirmation(\''.clean_str_for_javascript($dspFileName).'\');">'
                      .'<img src="' . get_icon_url('delete') . '" alt="'.get_lang('Delete').'" />'
                      .'</a>'
                      .'</td>' . "\n";
                
                /* MOVE COMMAND */
                $out .= '<td>'
                      .'<a href="' . claro_htmlspecialchars(Url::Contextualize(
                        $_SERVER['PHP_SELF'] . '?cmd=rqMv&amp;file=' . $cmdFileName ))
                      . '">'
                      . '<img src="' . get_icon_url('move') . '" alt="'.get_lang('Move').'" />'
                      . '</a>'
                      . '</td>' . "\n"
                      . '<td>';
                
                if ($groupContext)
                {
                    /* PUBLISH COMMAND */
                    if ($thisFile['type'] == A_FILE)
                    {
                        $out .= '<a href="'
                            .claro_htmlspecialchars(Url::Contextualize( get_module_url('CLWRK').'/work.php?'
                            .'submitGroupWorkUrl='.urlencode($thisFile['path']) ))
                            . '">'
                            .'<small>'.get_lang('Publish').'</small>'
                            .'</a>';
                    }
                    // else noop
                }
                elseif($courseContext)
                {
                    /* VISIBILITY COMMAND */
                    if ($thisFile['visibility'] == "i")
                    {
                        $out .= '<a href="'
                              . claro_htmlspecialchars(Url::Contextualize(
                                $_SERVER['PHP_SELF'] . '?cmd=exChVis&amp;file=' . $cmdFileName . '&amp;vis=v'))
                              . '">'
                              . '<img src="' . get_icon_url('invisible') . '" alt="'.get_lang('Make visible').'" />'
                              . '</a>';
                    }
                    else
                    {
                        $out .= '<a href="'
                              . claro_htmlspecialchars(Url::Contextualize(
                                $_SERVER['PHP_SELF'] . '?cmd=exChVis&amp;file=' . $cmdFileName . '&amp;vis=i'))
                              . '">'
                              . '<img src="' . get_icon_url('visible') . '" alt="'.get_lang('Make invisible').'" />'
                              . '</a>';
                    }
                }
                
                $out .= '</td>' . "\n";
            } // end if($is_allowedToEdit)
            
            $out .= '</tr>' . "\n";
            
            /* COMMENTS */
            
            if ( $thisFile['comment'] != '' )
            {
                $thisFile['comment'] = claro_htmlspecialchars($thisFile['comment']);
                $thisFile['comment'] = claro_parse_user_text($thisFile['comment']);
                
                $out .= '<tr align="left">' . "\n"
                      . '<td colspan="' . $colspan . '">'
                      . '<div class="comment">'
                      . $thisFile['comment']
                      . '</div>'
                      . '</td>' . "\n"
                      . '</tr>' . "\n";
            }
        }               // end each ($fileList)
        
    }                   // end if ( $fileList)
    else
    {
        $out .= '<tr align="left">' . "\n"
              . '<td colspan="' . $colspan . '">'
              . '<div class="comment">'
              . get_lang('Nothing to display')
              . '</div>'
              . '</td>' . "\n"
              . '</tr>';
    }
    
    $out .= '</tbody>' . "\n"
          . '</table>' . "\n";
} // END ELSE VIEW IMAGE

$claroline->display->body->appendContent($out);

echo $claroline->display->render();

// call the garbage collector to remove temporary files

$tmpZipPath = get_conf('cldoc_customTmpPath', '');

if ( empty($tmpZipPath) )
{
    $tmpZipPath = get_path('coursesRepositorySys') . claro_get_course_path() . '/tmp/zip';
}

$gc = new ClaroGarbageCollector( $tmpZipPath, 3600 );
$gc->run();

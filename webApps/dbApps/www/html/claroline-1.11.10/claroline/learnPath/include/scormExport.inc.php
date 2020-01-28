<?php // $Id: scormExport.inc.php 14345 2012-12-12 15:40:02Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 14345 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Amand Tihon <amand.tihon@alrj.org>
 * @package     CLLNP
 * @subpackage  navigation
 * @since       1.8
 */

/*
    How SCORM export should be done

    1. Get (flat) list of LP's content
    2. Create export directory structure, with base files (dtd/xsd, javascript, ...)
    3. Find if any SCOs are used in the LP
    4. If it's the case, copy the whole SCORM content into destination directory
    5. Rebuild imsmanifest.xml from the list we got in 1.
        - *EVERY* document must be present in the LP. If an HTML document includes an image,
          it must be declared in the LP, but marked as "invisible".
        - If a module is "visible", add it both as an <item> and as a <resource>,
          otherwise, add it only as a <resource>.
        - The rebuild must take into acount that modules are ordered in a tree, not a flat list.

    Current limitations :
    - Dependencies between resources are not taken into account.
    - No multi-page exercises

    This file is currently supposed to be included by learningPathList.php, in order to inherit some
    of its global variables, like some tables' names.

*/


function getIdCounter()
{
    global $idCounter;

    if( !isset($idCounter) || $idCounter < 0 )
    {
        $idCounter = 0;
    }
    else
    {
        $idCounter++;
    }

    return $idCounter;
}


if ( !class_exists('ScormExport') )
{
    include_once get_path('incRepositorySys') . "/lib/fileUpload.lib.php";
    include_once get_path('incRepositorySys') . "/lib/thirdparty/pclzip/pclzip.lib.php";

    require_once dirname(__FILE__).'/../../exercise/lib/exercise.class.php';
    require_once dirname(__FILE__).'/../../exercise/lib/exercise.lib.php';
    require_once dirname(__FILE__).'/../../exercise/export/scorm/scorm_classes.php';

    include_once get_path('incRepositorySys') . '/lib/htmlxtra.lib.php';
    include_once get_path('incRepositorySys') . '/lib/form.lib.php';
    

    /**
     * Exports a Learning Path to a SCORM package.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    class ScormExport
    {
        public $id;
        public $name;
        public $comment;
        public $resourceMap;
        public $itemTree;
        public $fromScorm;
        public $destDir;
        public $srcDirScorm;
        public $srcDirDocument;
        public $srcDirExercise;

        public $manifest_itemTree;
        public $scormURL;
        public $mp3Found;

        public $error;

        /**
         * Constructor
         *
         * @param $learnPathId The ID of the learning path to export
         * @author Amand Tihon <amand@alrj.org>
         */
        public function __construct($learnPathId)
        {
            /* Default values */
            $this->id = (int)$learnPathId;
            $this->fromScorm = false;
            $this->mp3Found = false;
            $this->resourceMap = array();
            $this->itemTree = array();
            $this->error = array();
        }


        /**
         * Returns the error
         *
         * @author Amand Tihon <amand@alrj.org>
         */
        public function getError()
        {
            return $this->error;
        }

        /**
         * Fetch info from the database
         *
         * @return False on error, true otherwise.
         * @author Amand Tihon <amand@alrj.org>
         */
        public function fetch()
        {
            global $TABLELEARNPATH, $TABLELEARNPATHMODULE, $TABLEMODULE, $TABLEASSET;

            /* Get general infos about the learning path */
            $sql = 'SELECT `name`, `comment`
                    FROM `'.$TABLELEARNPATH.'`
                    WHERE `learnPath_id` = '. $this->id;

            $result = claro_sql_query($sql);
            if ( empty($result) )
            {
                $this->error[] = get_lang('Learning Path not found');
                return false;
            }

            $list = mysql_fetch_array($result, MYSQL_ASSOC);
            if ( empty($list) )
            {
                $this->error[] = get_lang('Learning Path not found');
                return false;
            }

            $this->name = $list['name'];
            $this->comment = $list['comment'];

            /* Build various directories' names */

            // Replace ',' too, because pclzip doesn't support it.
            $this->destDir = get_path('coursesRepositorySys') . claro_get_course_path() . '/temp/'
                . str_replace(',', '_', replace_dangerous_char($this->name));
            $this->srcDirDocument = get_path('coursesRepositorySys') . claro_get_course_path() . '/document';
            $this->srcDirExercise  = get_path('coursesRepositorySys') . claro_get_course_path() . '/exercise';
            $this->srcDirScorm    = get_path('coursesRepositorySys') . claro_get_course_path() . '/scormPackages/path_'.$this->id;

            /* Now, get the complete list of modules, etc... */
            $sql = 'SELECT  LPM.`learnPath_module_id` ID, LPM.`lock`, LPM.`visibility`, LPM.`rank`,
                            LPM.`parent`, LPM.`raw_to_pass`, LPM.`specificComment` itemComment,
                            M.`name`, M.`contentType`, M.`comment` resourceComment, A.`path`
                    FROM `'.$TABLELEARNPATHMODULE.'` AS LPM
                    LEFT JOIN `'.$TABLEMODULE.'` AS M
                           ON LPM.`module_id` = M.`module_id`
                    LEFT JOIN `'.$TABLEASSET.'` AS A
                           ON M.`startAsset_id` = A.`asset_id`
                    WHERE LPM.`learnPath_id` = '. $this->id.'
                    ORDER BY LPM.`parent`, LPM.`rank`
                   ';

            $result = claro_sql_query($sql);
            
            if ( empty($result) )
            {
                $this->error = get_lang('Learning Path is empty');
                return false;
            }

            while ($module = mysql_fetch_array($result, MYSQL_ASSOC))
            {
                // Check for SCORM content. If at least one module is SCORM, we need to export the existing SCORM package
                if ( $module['contentType'] == 'SCORM' )       $this->fromScorm = true;

                // If it is an exercise, create a filename for it.
                if ( $module['contentType'] == 'EXERCISE' )    $module['fileName'] = 'quiz_' . $module['path'] . '.html';

                // Only for clarity :
                $id = $module['ID'];
                $parent = $module['parent'];

                // Add to the flat resource map
                $this->resourceMap[$id] = $module;

                // Build Item tree, only keeping visible modules
                if ( $module['visibility'] == 'SHOW' ) {
                    if ( ! $parent )
                    {
                        // parent is 0, item is at root
                        $this->itemTree[$id] = &$this->resourceMap[$id];
                    }
                    else
                    {
                        /* item has a parent. Add it to the list of its children.
                           Note that references are used, not copies. */
                        $this->resourceMap[$parent]['children'][] = &$this->resourceMap[$id];
                    }
                }
            }

            return true;
        }


        /**
        * Exports an exercise as a SCO.
        * This method is intended to be called from the prepare method.
        *
        *@note There's a lot of nearly cut-and-paste from exercise.lib.php here
        *      because of some little differences...
        *      Perhaps something that could be refactorised ?
        *
        * @see prepare
        * @param $quizId The quiz
        * @param $raw_to_pass The needed score to attain
        * @return False on error, True if everything went well.
        * @author  Amand Tihon <amand@alrj.org>
        */
        public function prepareQuiz($quizId, $raw_to_pass=50)
        {
            global $claro_stylesheet;

            // those two variables are needed by display_attached_file()
            global $attachedFilePathWeb;
            global $attachedFilePathSys;
            $attachedFilePathWeb = 'Exercises';
            $attachedFilePathSys = $this->destDir . '/Exercises';

            // read the exercise
            $quiz = new Exercise();
            if (! $quiz->load($quizId))
            {
                $this->error[] = get_lang('Unable to load the exercise');
                return false;
            }

    // Generate standard page header
            $pageHeader = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
    <title>'.$quiz->getTitle().'</title>
    <meta http-equiv="expires" content="Tue, 05 DEC 2000 07:00:00 GMT">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Content-Type" content="text/HTML; charset='.get_locale('charset').'"  />

    <link rel="stylesheet" type="text/css" href="' . get_conf('claro_stylesheet') . '/main.css" media="screen, projection, tv" />
    <script language="javascript" type="text/javascript" src="jquery.js"></script>
    <script language="javascript" type="text/javascript" src="claroline.js"></script>
    <script language="javascript" type="text/javascript" src="claroline.ui.js"></script>

    <script language="javascript" type="text/javascript" src="APIWrapper.js"></script>
    <script language="javascript" type="text/javascript" src="scores.js"></script>
    ' . "\n";


            $pageBody = '<body onload="loadPage()">
        <div id="claroBody"><form id="quiz">
        <table width="100%" border="0" cellpadding="1" cellspacing="0" class="claroTable">' . "\n";




            // Get the question list
            $questionList = $quiz->getQuestionList();
            $questionCount = count($questionList);

            // Keep track of raw scores (ponderation) for each question
            $questionPonderationList = array();

            // Keep track of correct texts for fill-in type questions
            // TODO La variable $fillAnswerList n'appara�t qu'une fois
            $fillAnswerList = array();

            // Display each question
            $questionCount = 0;
            foreach( $questionList as $question )
            {

                // Update question number
                $questionCount++;

                // read the question, abort on error
                $scormQuestion = new ScormQuestion();
                if (!$scormQuestion->load($question['id']))
                {
                    $this->error[] = get_lang('Unable to load exercise\'s question');
                    return false;
                }
                $questionPonderationList[] = $scormQuestion->getGrade();

                $pageBody .=
                    '<tr class="headerX">' . "\n"
                .    '<th>'.get_lang('Question').' '.$questionCount.'</th>' . "\n"
                .    '</tr>' . "\n";

                $pageBody .=
                    '<tr>' . "\n" . '<td>' . "\n"
                .    $scormQuestion->export() . "\n"
                .    '</td>' . "\n" . '</tr>' . "\n";
/*
                if( !empty($scormQuestion->getAttachment()) )
                {
                    // copy the attached file
                    if ( !claro_copy_file($this->srcDirExercise . '/' . $attachedFile, $this->destDir . '/Exercises') )
                    {
                        $this->error[] = get_lang('Unable to copy file : %filename', array ( '%filename' => $attachedFile  ));
                        return false;
                    }

                    // Ok, if it was an mp3, we need to copy the flash mp3-player too.
                    $extension=substr(strrchr($attachedFile, '.'), 1);
                    if ( $extension == 'mp3')   $this->mp3Found = true;

                    $pageBody .= '<tr><td colspan="2">' . display_attached_file($attachedFile) . '</td></tr>' . "\n";
                }
*/
                /*
                 * Display the possible answers
                 */

                // End of the question

            } // foreach($questionList as $questionId)

            // No more questions, add the button.
            $pageEnd = '
                <tr>
                    <td align="center"><br /><input type="button" value="' . get_lang('Ok') . '" onclick="calcScore()" /></td>
                </tr>
                </table>
                </form>
                </div></body></html>' . "\n";

            /* Generate the javascript that'll calculate the score
             * We have the following variables to help us :
             * $idCounter : number of elements to check. their id are "scorm_XY"
             * $raw_to_pass : score (on 100) needed to pass the quiz
             * $fillAnswerList : a list of arrays (text, score) indexed on <input>'s names
             *
             */
            $pageHeader .= '
    <script type="text/javascript" language="javascript">
        var raw_to_pass = ' . $raw_to_pass . ';
        var weighting = ' . array_sum($questionPonderationList) . ';
        var rawScore;
        var scoreCommited = false;
        var showScore = true;
        var fillAnswerList = new Array();' . "\n";

            // This is the actual code present in every exported exercise.
            // use claro_html_entity_decode in output to prevent double encoding errors with some languages...
            $pageHeader .= '

        function calcScore()
        {
            if( !scoreCommited )
            {
                rawScore = CalculateRawScore(document, ' . getIdCounter() . ', fillAnswerList);
                var score = Math.max(Math.round(rawScore * 100 / weighting), 0);
                var oldScore = doLMSGetValue("cmi.core.score.raw");

                doLMSSetValue("cmi.core.score.max", weighting);
                doLMSSetValue("cmi.core.score.min", 0);

                computeTime();

                if (score > oldScore) // Update only if score is better than the previous time.
                {
                    doLMSSetValue("cmi.core.score.raw", rawScore);
                }

                var mode = doLMSGetValue( "cmi.core.lesson_mode" );
                if ( mode != "review"  &&  mode != "browse" )
                {
                    var oldStatus = doLMSGetValue( "cmi.core.lesson_status" )
                    if (score >= raw_to_pass)
                    {
                        doLMSSetValue("cmi.core.lesson_status", "passed");
                    }
                    else if (oldStatus != "passed" ) // If passed once, never mark it as failed.
                    {
                        doLMSSetValue("cmi.core.lesson_status", "failed");
                    }
                }

                doLMSCommit();
                doLMSFinish();
                scoreCommited = true;
                if(showScore) alert(\''.clean_str_for_javascript(claro_html_entity_decode(get_lang('Score'))).' :\n\' + rawScore + \'/\' + weighting );
            }
        }
    
    </script>
    ';
    
            // Construct the HTML file and save it.
            $filename = "quiz_" . $quizId . ".html";
            
            $pageContent = $pageHeader
                         . $pageBody
                         . $pageEnd;
            
            if (! $f = fopen($this->destDir . '/' . $filename, 'w') )
            {
                $this->error[] = get_lang('Unable to create file : ') . $filename;
                return false;
            }
            fwrite($f, $pageContent);
            fclose($f);
            
            // Went well.
            return True;
        }
        
        /**
         * Prepare the temporary destination directory that'll be zipped and exported.
         * Existing SCORM, documents, as well as required or helper javascript files and XML schemas
         * are copied into the directory.
         * No manifest created yet.
         *
         * @return False on error, true otherwise.
         * @see createManifest
         * @author Amand Tihon <amand@alrj.org>
         */
        public function prepare()
        {
            global $claro_stylesheet;
            
            // (re)create fresh directory
            claro_delete_file($this->destDir);
            
            if ( !claro_mkdir($this->destDir, CLARO_FILE_PERMISSIONS , true))
            {
                $this->error[] = get_lang('Unable to create directory : ') . $this->destDir;
                return false;
            }
            
            // Copy SCORM package, if needed
            if ($this->fromScorm && file_exists( $this->srcDirScorm ) )
            {
                // Copy the scorm directory as OrigScorm/
                if (
                       !claro_copy_file($this->srcDirScorm,  $this->destDir)
                    || !claro_rename_file($this->destDir.'/path_'.$this->id, $this->destDir.'/OrigScorm')  )
                {
                    $this->error[] = get_lang('Error copying existing SCORM content');
                    return false;
                }
                else
                {
                    return true;
                }
            }
            
            
            
            // Check css to use
            if( file_exists( get_path( 'clarolineRepositorySys' ) . '../platform/css/' . $claro_stylesheet ) )
            {
                $claro_stylesheet_path = get_path( 'clarolineRepositorySys' ) . '../platform/css/' . $claro_stylesheet;
            }
            elseif( file_exists( get_path( 'clarolineRepositorySys' ) . '../web/css/' . $claro_stylesheet ) )
            {
                $claro_stylesheet_path = get_path( 'clarolineRepositorySys' ) . '../web/css/' . $claro_stylesheet;
            }
            
            // Copy usual files (.css, .js, .xsd, etc)
            if (
                   !claro_copy_file( $claro_stylesheet_path, $this->destDir)
                || !claro_copy_file(dirname(__FILE__).'/../export/APIWrapper.js', $this->destDir)
                || !claro_copy_file(dirname(__FILE__).'/../export/scores.js', $this->destDir)
                || !claro_copy_file(dirname(__FILE__).'/../export/ims_xml.xsd', $this->destDir)
                || !claro_copy_file(dirname(__FILE__).'/../export/imscp_rootv1p1p2.xsd', $this->destDir)
                || !claro_copy_file(dirname(__FILE__).'/../export/imsmd_rootv1p2p1.xsd', $this->destDir)
                || !claro_copy_file(dirname(__FILE__).'/../export/adlcp_rootv1p2.xsd', $this->destDir)
                || !claro_copy_file(get_path('clarolineRepositorySys') . '../web/js/jquery.js', $this->destDir)
                || !claro_copy_file(get_path('clarolineRepositorySys') . '../web/js/claroline.js', $this->destDir)
                || !claro_copy_file(get_path('clarolineRepositorySys') . '../web/js/claroline.ui.js', $this->destDir)
               )
            {
                $this->error[] = get_lang('Error when copying needed SCORM files');
                return false;
            }
            
            
            // Copy SCORM package, if needed
            if ($this->fromScorm && file_exists( $this->srcDirScorm ) )
            {
                // Copy the scorm directory as OrigScorm/
                if (
                       !claro_copy_file($this->srcDirScorm,  $this->destDir)
                    || !claro_rename_file($this->destDir.'/path_'.$this->id, $this->destDir.'/OrigScorm')  )
                {
                    $this->error[] = get_lang('Error copying existing SCORM content');
                    return false;
                }
            }
            
            // Create destination directory for "pure" documents
            claro_mkdir($this->destDir.'/Documents');
            
            // And for exercises
            claro_mkdir($this->destDir.'/Exercises');
            
            // Copy documents into the created directory
            foreach($this->resourceMap as $module)
            {
                if ( $module['contentType'] == 'DOCUMENT' )
                {
                    if ( dirname($module['path']) != '/' )
                    {
                        $destinationDir = $this->destDir . '/Documents' . dirname($module['path']) ;
                    }
                    else
                    {
                        $destinationDir = $this->destDir . '/Documents';
                    }
                    if ( ! is_dir($destinationDir) )
                    {
                        claro_mkdir($destinationDir, CLARO_FILE_PERMISSIONS, true);
                    }
                    
                    if ( !empty( $module['path'] ) ) claro_copy_file($this->srcDirDocument . $module['path'], $destinationDir);
                    
                    // TODO : If it's an html document, parse it and add the embed object (img, ...)
                }
                elseif ( $module['contentType'] == 'EXERCISE' )
                {
                    if ( !$this->prepareQuiz($module['path'], $module['raw_to_pass']))    return false;
                }
            }
            
            // Did we find an mp3 ?
            if ( $this->mp3Found)
            {
                if ( !claro_copy_file(get_module_path('CLQWZ') .'/claroPlayer.swf', $this->destDir) )
                {
                    $this->error[] = get_lang('Unable to copy file : %filename', array ( '%filename' => get_module_path('CLQWZ') . '/claroPlayer.swf') );
                    
                    // This is *NOT* a fatal error.
                    // Do *NOT* return false.
                }
            }


            return true;
        }

        /**
         * Create the frame file that'll hold the document. This frame is supposed to
         * set the SCO's status
         * @param $filename string: the name of the file to create, absolute.
         * @param $targetPath string: The actual document path, relative to the scorm
         * @return False on error, true otherwise.
         * @author Amand Tihon <amand@alrj.org>
         */
        public function createFrameFile($fileName, $targetPath)
        {

            if ( !($f = fopen($fileName, 'w')) )
            {
                $this->error[] = get_lang('Unable to create frame file');
                return false;
            }

            fwrite($f, '<html><head>
    <script src="APIWrapper.js" type="text/javascript" language="JavaScript"></script>
    <title>Default Title</title>
    </head>
    <frameset border="0" rows="100%,*" onload="immediateComplete()">
    <frame src="' . str_ireplace('%2F','/',rawurlencode($targetPath)) . '" scrolling="auto">
    <frame src="SCOFunctions.js">
    </frameset>
    </html>');
            fclose($f);

            return true;
        }

        /**
         * Create a simple <metadata>
         *
         *
         * @param $title The resource title
         * @param $description The resource description
         * @return A string containing the metadata block.
         * @author Amand Tihon <amand@alrj.org>
         */
        public function makeMetaData($title, $description)
        {
            if ( empty($title) and empty($description) ) return '<metadata />';

            $out = '<metadata>
    <imsmd:lom>
        <imsmd:general>';

            if (!empty($title))
            {
            $out .= '
            <imsmd:title>
                <imsmd:langstring><![CDATA[' . claro_htmlspecialchars($title) . ']]></imsmd:langstring>
            </imsmd:title>';
            }

            if (!empty($description))
            {
            $out .= '
            <imsmd:description>
                <imsmd:langstring><![CDATA[' . claro_htmlspecialchars($description) . ']]></imsmd:langstring>
            </imsmd:description>';
            }

            $out .= '
        </imsmd:general>
    </imsmd:lom>
    </metadata>';

            return $out;
        }

        /**
         * Recursive function to deal with the tree representation of the items
         *
         * @param $itemlist the subtree to build
         * @param $depth indentation level. Is it really useful ?
         * @return the (sub-)tree representation
         * @author Amand Tihon <amand@alrj.org>
         */
        public function createItemList($itemlist, $depth=0)
        {
            $out = "";
            $ident = "";
            
            for ($i=0; $i<$depth; $i++) $ident .= "    ";
            
            foreach ( $itemlist as $item )
            {
                $out .= $ident . '<item identifier="I_'.$item['ID'].'" isvisible="true" ';
                if ( $item['contentType'] != 'LABEL' )
                {
                    $out .= 'identifierref="R_' . $item['ID'] . '" ';
                }
                $out .= '>' . "\n";
                $out .= $ident . '    <title>'.claro_htmlspecialchars($item['name']).'</title>' . "\n";

                // Check if previous was blocking
                if (!empty($this->blocking) && ($item['contentType'] != 'LABEL'))
                {
                    $out .= '        <adlcp:prerequisites type="aicc_script"><![CDATA[I_'.$this->blocking.']]></adlcp:prerequisites>'."\n";
                }

                // Add metadata, except for LABELS
                if ( $item['contentType'] != 'LABEL' )
                {
                    $out .= $this->makeMetaData($item['name'], $item['itemComment']) . "\n";
                }

                if ( ! isset($item['children']) )
                {
                    // change only if we do not recurse.
                    $this->blocking = ($item['lock'] == 'CLOSE') ? $item['ID'] : '';
                }
                else
                {
                    $out .= $this->createItemList($item['children'], $depth+1);
                }
                $out .= $ident . '</item>' . "\n";
            }
            return $out;
        }

        /**
         * Create the imsmanifest.xml file.
         *
         * @return False on error, true otherwise.
         * @author Amand Tihon <amand@alrj.org>
         */
        public function createManifest()
        {
            if ( $this->fromScorm )
            {
                return true;
            }
            
            // Start creating sections for items and resources
            $this->blocking = "";

            // First the items...
            $manifest_itemTree = '<organizations default="A1"><organization identifier="A1">' . "\n"
                . '<title><![CDATA[' . claro_htmlspecialchars($this->name) . ']]></title>' . "\n"
                . '<description><![CDATA[' . claro_htmlspecialchars($this->comment) . ']]></description>' . "\n"
                . $this->createItemList($this->itemTree)
                . '</organization></organizations>' . "\n";
            $manifest_itemTree = str_replace("\r\n","\n", $manifest_itemTree);
            $manifest_itemTree = str_replace("\r","\n", $manifest_itemTree);
            // ...Then the resources
            
            $manifest_resources = "<resources>\n";
            
            foreach ( $this->resourceMap as $module )
            {
                if ( $module['contentType'] == 'LABEL' ) continue;

                switch ( $module['contentType'] )
                {
                    case 'DOCUMENT':
                        $framefile = $this->destDir . '/frame_for_' . $module['ID'] . '.html';
                        $targetfile = 'Documents'.$module['path'];

                        // Create an html file with a frame for the document.
                        if ( !$this->createFrameFile($framefile, 'Documents'.$module['path'])) return false;

                        // Add the resource to the manifest
                        $manifest_resources .= '<resource identifier="R_' . $module['ID'] . '" type="webcontent"  adlcp:scormType="sco" '
                            . ' href="' . basename($framefile) . '">' . "\n"
                            . '  <file href="' . urlencode(basename($framefile)) . '" />' . "\n"
                            . '  <file href="' . str_ireplace('%2F','/', rawurlencode($targetfile)) . '" />' . "\n"
                            . $this->makeMetaData($module['name'], $module['resourceComment'])
                            . "</resource>\n";
                        break;

                    case 'EXERCISE':
                        $targetfile = $module['fileName'];

                        // Add the resource to the manifest
                        $manifest_resources .= '<resource identifier="R_' . $module['ID'] . '" type="webcontent"  adlcp:scormType="sco" '
                            . ' href="' . $targetfile . '" >' . "\n"
                            . '  <file href="' . $targetfile . '" />' . "\n"
                            . $this->makeMetaData($module['name'], $module['resourceComment'])
                            . "</resource>\n";
                        break;


                    case 'SCORM'   :
                        // Add the resource to the manifest
                        // TODO $path is unused
                        $path = 'OrigScorm';
                        $manifest_resources .= '<resource identifier="R_' . $module['ID'] . '" type="webcontent"  adlcp:scormType="sco" '
                            . ' href="OrigScorm' . $module['path'] . '">' . "\n"
                            . '  <file href="OrigScorm' . $module['path'] . '" />' . "\n"
                            . $this->makeMetaData($module['name'], $module['resourceComment'])
                            . "</resource>\n";
                        break;

                    default        : break;
                }

            }
            $manifest_resources .= '</resources>' . "\n";
            $manifest_resources = str_replace("\r\n","\n", $manifest_resources);
            $manifest_resources = str_replace("\r","\n", $manifest_resources);

            $manifestPath = $this->destDir . '/imsmanifest.xml';
            if ( ! $f = fopen($manifestPath, 'w') )
            {
                $this->error[] = get_lang('Unable to create the SCORM manifest (imsmanifest.xml)');
                return false;
            }

            // Prepare Metadata
            $metadata = $this->makeMetaData($this->name, $this->comment);
            $metadata = str_replace("\r\n","\n", $metadata);
            $metadata = str_replace("\r","\n", $metadata);
            // Write header
            fwrite($f, '<?xml version="1.0" encoding="' . get_locale('charset') . '" ?>
    <manifest identifier="SingleCourseManifest" version="1.1"
                xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2"
                xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:imsmd="http://www.imsglobal.org/xsd/imsmd_rootv1p2p1"
                xsi:schemaLocation="http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd
                http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd
                http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd">' . "\n");
            fwrite($f, $metadata);
            fwrite($f, $manifest_itemTree);
            fwrite($f, $manifest_resources);
            fwrite($f, "</manifest>\n");
            fclose($f);

            return true;
        }


        /**
         * Create the final zip file.
         *
         * @return False on error, True otherwise.
         * @author Amand Tihon <amand@alrj.org>
         */
        public function zip()
        {

            $list = 1;
            $zipFile = new PclZip($this->destDir . '.zip');
            
            if ( $this->fromScorm )
            {
                $exportFrom = $this->destDir . '/OrigScorm';
            }
            else
            {
                $exportFrom = $this->destDir;
            }
            
            $list = $zipFile->create( $exportFrom, PCLZIP_OPT_REMOVE_PATH, $exportFrom );

            if ( !$list )
            {
                $this->error[] = get_lang('Unable to create the SCORM archive');
                return false;
            }

            // Temporary directory can be deleted, now that the zip is made.
            claro_delete_file($this->destDir);

            return true;

        }

        /**
         * Send the .zip file to the browser.
         *
         * @return Does NOT return !
         * @author Amand Tihon <amand@alrj.org>
         */
        public function send()
        {
            $filename = $this->destDir . '.zip';
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            header('Content-Length: ' . filesize($filename));
            header('Content-Disposition: attachment; filename=' . basename($filename));
            readfile($filename);

            exit(0);
        }

        /**
         * Helper method : take care of everything
         *
         * @return False on error. Does NOT return on success.
         * @author Amand Tihon <amand@alrj.org>
         */
        public function export()
        {
            if ( !$this->fetch() ) return false;
            if ( !$this->prepare() ) return false;
            if ( !$this->createManifest() ) return false;
            if ( !$this->zip() ) return false;
            $this->send();

            return True;
        }

    }
} // !class_exists(ScormExport)


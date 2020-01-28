<?php // $Id: exercise_import.inc.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 * @version 1.8
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package ADMIN
 * @since 1.8
 *
 * @author claro team <cvs@claroline.net>
 */

/**
 * @return the path of the temporary directory where the exercise was uploaded and unzipped
 */

function get_and_unzip_uploaded_exercise($baseWorkDir, $uploadDir)
{
    //Check if the file is valid (not to big and exists)

    if( !isset($_FILES['uploadedExercise'])
    || !is_uploaded_file($_FILES['uploadedExercise']['tmp_name']))
    {
        // upload failed
        return false;
    }

    //1- Unzip folder in a new repository in claroline/module
    //include_once realpath(dirname(__FILE__) . '/../../inc/lib/pclzip/') . '/pclzip.lib.php';
    require_once get_path('incRepositorySys') . '/lib/thirdparty/pclzip/pclzip.lib.php';


    if ( preg_match('/.zip$/i', $_FILES['uploadedExercise']['name'])
        && treat_uploaded_file($_FILES['uploadedExercise'],$baseWorkDir, $uploadDir, get_conf('maxFilledSpaceForExercise' , 10000000),'unzip',true))
    {
        if (!function_exists('gzopen'))
        {
            claro_delete_file($uploadDir);
            return false;
        }
        // upload successfull
        return true;
    }
    else
    {
        claro_delete_file($uploadDir);
        return false;
    }

}
/**
 * Main function to import an exercise.
 *
 * @param string $file path of the file
 * @param array $backlog_message
 *
 * @return the id of the created exercise or false if the operation failed
 */
function import_exercise($file, &$backlog)
{
    global $exercise_info;
    global $element_pile;
    global $non_HTML_tag_to_avoid;
    global $record_item_body;
    // used to specify the question directory where files could be found in relation in any question
    global $questionTempDir;

    // get required table names

    $tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise', 'qwz_question' ), claro_get_current_course_id() );
    $tbl_quiz_exercise = $tbl_cdb_names['qwz_exercise'];
    $tbl_quiz_question = $tbl_cdb_names['qwz_question'];

    // paths
    $baseWorkDir = get_path('rootSys') . get_conf('tmpPathSys') . 'upload/';
    // create temp dir for upload
    if( !file_exists($baseWorkDir) ) claro_mkdir($baseWorkDir, CLARO_FILE_PERMISSIONS);

    $uploadDir = claro_mkdir_tmp($baseWorkDir); // this function should return the dir name and not the full path ...
    $uploadPath = str_replace($baseWorkDir,'',$uploadDir);

    // set some default values for the new exercise
    $exercise_info   = array();
    $exercise_info['name'] = preg_replace('/.zip$/i','' ,$file);
    $exercise_info['description'] = '';
    $exercise_info['question'] = array();

    // create parser and array to retrieve info from manifest

    $element_pile = array();  //pile to known the depth in which we are
    $module_info = array();   //array to store the info we need

    // if file is not a .zip, then we cancel all

    if ( !preg_match('/.zip$/i', $_FILES['uploadedExercise']['name']))
    {
        $backlog->failure(get_lang('You must upload a zip file'));
        return false;
    }

    //unzip the uploaded file in a tmp directory

    if( !get_and_unzip_uploaded_exercise($baseWorkDir,$uploadPath) )
    {
        $backlog->failure(get_lang('Upload failed'));
        return false;
    }

    // find the different manifests for each question and parse them.

    $exerciseHandle = opendir($uploadDir);

    $question_number = 0;
    $file_found = false;

    // parse every subdirectory to search xml question files

    while( false !== ($file = readdir($exerciseHandle)) )
    {
        if( is_dir($uploadDir.'/'.$file) && $file != "." && $file != ".." )
        {
            //find each manifest for each question repository found

            $questionHandle = opendir($uploadDir.'/'.$file);

            while (false !== ($questionFile = readdir($questionHandle)))
            {
                if (preg_match('/.xml$/i' ,$questionFile))
                {
                    list( $parsingBacklog, $success ) = parse_file($uploadDir, $file, $questionFile);
                    $backlog->append($parsingBacklog);
                    $file_found = true;
                }
            }
        }
        elseif( preg_match('/.xml$/i' ,$file) )
        {
            list( $parsingBacklog, $success ) = parse_file($uploadDir, '', $file);
            $backlog->append($parsingBacklog);
            $file_found = true;
        } // else ignore file
    }

    if( !$file_found )
    {
        $backlog->failure(get_lang('No XML file found in the zip'));
        return false;
    }

    //---------------------
    //add exercise in tool
    //---------------------

    //1.create exercise

    $exercise = new Exercise();

    $exercise->setTitle($exercise_info['name']);
    $exercise->setDescription($exercise_info['description']);

    if ($exercise->validate())
    {
        $exercise_id = $exercise->save();
    }
    else
    {
        $backlog->failure(get_lang('There is an error in exercise data of imported file.'));
        return false;
    }
    
    ksort( $exercise_info['question'] , SORT_NUMERIC );
    
    //For each question found...
    foreach($exercise_info['question'] as $key => $question_array)
    {
        //2.create question
        $question = new Qti2Question();
        $question->import($question_array);


        if( $question->validate() )
        {
            // I need to save the question after the answer because I need question id in answers
            $question_id = $question->save();

            //3.create answers
            $question->setAnswer();
            $question->answer->import($question_array);

            if( $question->answer->validate() )
            {
                $question->setGrade($question->answer->getGrade());
                $question->save(); // save computed grade

                $question->answer->save();

                $exercise->addQuestion($question_id);
            }
            else
            {
                $backlog->failure(get_lang('Invalid answer') . ' : ' . $key);
            }
        }
        else
        {
            $backlog->failure(get_lang('Invalid question') . ' : ' . $key);
        }
    }

    // delete the temp dir where the exercise was unzipped
    claro_delete_file($uploadDir);

    return $exercise_id;
}

/**
 * parse an xml file to find info
 *
 * @param string $exercisePath
 * @param string $file dirname of question in zip file
 * @param string $questionFile name of xml file in zip file
 * @return array( backlog, boolean )
 */

function parse_file($exercisePath, $file, $questionFile)
{
    global $exercise_info;
    global $element_pile;
    global $non_HTML_tag_to_avoid;
    global $record_item_body;
    global $questionTempDir;

    $questionTempDir = $exercisePath.'/'.$file.'/';
    $questionFilePath = $questionTempDir.$questionFile;

    $backlog = new Backlog;

    if (!($fp = @fopen($questionFilePath, 'r')))
    {
        $backlog->failure(get_lang("Error opening question's XML file"));
        return array($backlog,false);
    }
    else
    {
        $data = fread($fp, filesize( $questionFilePath));
    }

    //parse XML question file

    $record_item_body = false;

    $non_HTML_tag_to_avoid = array(
    "SIMPLECHOICE",
    "CHOICEINTERACTION",
    "INLINECHOICEINTERACTION",
    "INLINECHOICE",
    "SIMPLEMATCHSET",
    "SIMPLEASSOCIABLECHOICE",
    "TEXTENTRYINTERACTION",
    "FEEDBACKINLINE",
    "MATCHINTERACTION",
    "BR",
    "OBJECT",
    "PROMPT"
    );

    $inside_non_HTML_tag_to_avoid = 0;

    //this array to detect tag not supported by claroline import in the xml file to warn the user.
    $non_supported_content_in_question = array(
    "GAPMATCHINTERACTION",
    "EXTENDEDTEXTINTERACTION",
    "HOTTEXTINTERACTION",
    "HOTSPOTINTERACTION",
    "SELECTPOINTINTERACTION",
    "GRAPHICORDERINTERACTION",
    "GRAPHICASSOCIATIONINTERACTION",
    "GRAPHICGAPMATCHINTERACTION",
    "POSITIONOBJECTINTERACTION",
    "SLIDERINTERACTION",
    "DRAWINGINTERACTION",
    "UPLOADINTERACTION",
    "RESPONSECONDITION",
    "RESPONSEIF"
    );
    $question_format_supported = true;

    $xml_parser = xml_parser_create();
    xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE,false);
    xml_set_element_handler($xml_parser, 'startElement', 'endElement');
    xml_set_character_data_handler($xml_parser, 'elementData');

    if( !xml_parse($xml_parser, $data, feof($fp)) )
    {
        // if reading of the xml file in not successfull :
        $backlog->failure(get_lang('Error reading XML file') . '(' . $questionFile . ':' . xml_get_current_line_number($xml_parser) . ': ' . xml_error_string(xml_get_error_code($xml_parser)) . ')');
        return array($backlog,false);
    }

    //close file

    fclose($fp);

    if( !$question_format_supported )
    {
        $backlog->failure(get_lang('Unknown question format in file %file', array ('%file' => $questionFile) ) );
        return array($backlog,false);
    }

    return array($backlog,true);
}


/**
 * Function used by the SAX xml parser when the parser meets a opening tag
 *
 * @param unknown_type $parser xml parser created with "xml_parser_create()"
 * @param unknown_type $name name of the element
 * @param unknown_type $attributes
 */

function startElement($parser, $name, $attributes)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $current_answer_id;
    global $current_match_set;
    global $currentAssociableChoice;
    global $current_question_item_body;
    global $prompt;
    global $record_item_body;
    global $non_HTML_tag_to_avoid;
    /* inside_non_HTML_tag_to_avoid is a hack to avoid adding of content of html tags contained by non html tags to avoid */
    global $inside_non_HTML_tag_to_avoid;
    global $current_inlinechoice_id;
    global $cardinality;
    global $questionTempDir;

    foreach( $attributes as $key => $value)
    {
        $attributes[$key] = claro_utf8_decode($value);
    }

    array_push($element_pile,$name);
    $current_element = end($element_pile);
    if (sizeof($element_pile)>=2) $parent_element = $element_pile[sizeof($element_pile)-2]; else $parent_element = "";

    if ($record_item_body)
    {
        if( !in_array($current_element,$non_HTML_tag_to_avoid) )
        {
            if( $inside_non_HTML_tag_to_avoid == 0 )
            {
                $current_question_item_body .= "<".$name;

                foreach ($attributes as $attribute_name => $attribute_value)
                {
                    $current_question_item_body .= " ".$attribute_name."=\"".$attribute_value."\"";
                }
                $current_question_item_body .= ">";
            }
        }
        else
        {
            $inside_non_HTML_tag_to_avoid++;

            //in case of FIB question, we replace the IMS-QTI tag b y the correct answer between "[" "]",
            //we first save with claroline tags ,then when the answer will be parsed, the claroline tags will be replaced

            if ($current_element == 'INLINECHOICEINTERACTION')
            {
                  $current_question_item_body .= "**claroline_start**".$attributes['RESPONSEIDENTIFIER']."**claroline_end**";
            }

            if ($current_element == 'TEXTENTRYINTERACTION')
            {
                $correct_answer_value = $exercise_info['question'][$current_question_ident]['correct_answers'][$attributes['RESPONSEIDENTIFIER']];

                $current_question_item_body .= "[".$correct_answer_value."]";

            }

            if ($current_element == 'BR')
            {
                $current_question_item_body .= "<br />";
            }
        }
    }

    switch ($current_element)
    {
        case 'PROMPT' :
        {
            $prompt = '';
        }
        break;

        case 'ASSESSMENTITEM' :
        {
            //retrieve current question

            $current_question_ident = (int) substr( $attributes['IDENTIFIER'] , 4 );
            $exercise_info['question'][$current_question_ident] = array();
            $exercise_info['question'][$current_question_ident]['answer'] = array();
            $exercise_info['question'][$current_question_ident]['correct_answers'] = array();
            $exercise_info['question'][$current_question_ident]['title'] = $attributes['TITLE'];
            $exercise_info['question'][$current_question_ident]['tempdir'] = $questionTempDir;
        }
        break;

        case 'SECTION' :
        {
             //retrieve exercise name

            $exercise_info['name'] = $attributes['TITLE'];

        }
        break;

        case 'RESPONSEDECLARATION' :
        {
             //retrieve question type

            if( $attributes['CARDINALITY'] == "multiple" )
            {
                $exercise_info['question'][$current_question_ident]['type'] = 'MCMA'; // will be overload if FIB
                $cardinality = 'multiple';
            }

            if( $attributes['CARDINALITY'] == "single" )
            {
                $exercise_info['question'][$current_question_ident]['type'] = 'MCUA'; // will be overload if FIB
                $cardinality = 'single';
            }

            //needed for FIB
            $current_answer_id = $attributes['IDENTIFIER'];

        }
        break;

        case 'INLINECHOICEINTERACTION' :
        {
            $exercise_info['question'][$current_question_ident]['type'] = 'FIB';
            $exercise_info['question'][$current_question_ident]['subtype'] = 'LISTBOX_FILL';
            $current_answer_id = $attributes['RESPONSEIDENTIFIER'];

        }
        break;

        case 'INLINECHOICE' :
        {
            $current_inlinechoice_id = $attributes['IDENTIFIER'];
        }
        break;

        case 'TEXTENTRYINTERACTION' :
        {
            $exercise_info['question'][$current_question_ident]['type'] = 'FIB';
            $exercise_info['question'][$current_question_ident]['subtype'] = 'TEXTFIELD_FILL';
        }
        break;

        case 'MATCHINTERACTION' :
        {
            //retrieve question type

            $exercise_info['question'][$current_question_ident]['type'] = 'MATCHING';
        }
        break;

        case 'SIMPLEMATCHSET' :
        {
            if (!isset($current_match_set))
            {
                $current_match_set = 1;
            }
            else
            {
                $current_match_set++;
            }
            $exercise_info['question'][$current_question_ident]['answer'][$current_match_set] = array();
        }
        break;

        case 'SIMPLEASSOCIABLECHOICE' :
        {
            $currentAssociableChoice = $attributes['IDENTIFIER'];
        }
        break;

        //retrieve answers id for MCUA and MCMA questions

        case 'SIMPLECHOICE':
        {
            $current_answer_id = $attributes['IDENTIFIER'];
            if (!isset($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]))
            {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id] = array();
            }
        }
        break;

        case 'MAPENTRY':
        {
            if ($parent_element == "MAPPING")
            {
                $answer_id = $attributes['MAPKEY'];

                if( !isset($exercise_info['question'][$current_question_ident]['weighting']) )
                {
                    $exercise_info['question'][$current_question_ident]['weighting'] = array();
                }
                $exercise_info['question'][$current_question_ident]['weighting'][$answer_id] = $attributes['MAPPEDVALUE'];
            }
        }
        break;

        case 'MAPPING':
        {
            if (isset($attributes['DEFAULTVALUE']))
            {
                $exercise_info['question'][$current_question_ident]['default_weighting'] = $attributes['DEFAULTVALUE'];
            }
        }

        case 'ITEMBODY':
        {
            $record_item_body = true;
            $current_question_item_body = '';
            $prompt = '';
        }
        break;

        case 'OBJECT' :
        {
            $exercise_info['question'][$current_question_ident]['attached_file_url'] =  $attributes['DATA'];
        }
        break;
    }
}

/**
 * Function used by the SAX xml parser when the parser meets a closing tag
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $name name of the element
 */

function endElement($parser,$name)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $record_item_body;
    global $current_question_item_body;
    global $prompt;
    global $non_HTML_tag_to_avoid;
    global $inside_non_HTML_tag_to_avoid;
    global $cardinality;

    $current_element = end($element_pile);

    switch ($name)
    {
        case 'ITEMBODY':
            {
                if ($exercise_info['question'][$current_question_ident]['type'] == 'FIB')
                {
                    $exercise_info['question'][$current_question_ident]['response_text'] = $current_question_item_body;
                    $exercise_info['question'][$current_question_ident]['statement'] = $prompt;
                }
                else
                {
                    $exercise_info['question'][$current_question_ident]['statement'] = $current_question_item_body;
                    $exercise_info['question'][$current_question_ident]['statement'] .= '<p><i>' . $prompt . '</i></p>';
                }

                $record_item_body = false;
            }
        break;
    }

    if( $record_item_body )
    {
        if( !in_array($current_element,$non_HTML_tag_to_avoid) && $inside_non_HTML_tag_to_avoid == 0 )
        {
            $current_question_item_body .= "</".$name.">";
        }
        elseif( $inside_non_HTML_tag_to_avoid > 0 )
        {
            $inside_non_HTML_tag_to_avoid--;
        }
    }

    array_pop($element_pile);
}

function elementData($parser,$data)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $current_answer_id;
    global $current_match_set;
    global $currentAssociableChoice;
    global $current_question_item_body;
    global $prompt;
    global $record_item_body;
    global $non_HTML_tag_to_avoid;
    global $inside_non_HTML_tag_to_avoid;
    global $current_inlinechoice_id;
    global $cardinality;

    $data = claro_utf8_decode($data);

    $current_element = end($element_pile);
    if (sizeof($element_pile) >= 2) $parent_element = $element_pile[sizeof($element_pile)-2]; else $parent_element = "";


    if( $record_item_body && $inside_non_HTML_tag_to_avoid == 0 )
    {
        if( !in_array($current_element,$non_HTML_tag_to_avoid)  )
        {
            $current_question_item_body .= $data;
        }
    }

    switch ($current_element)
    {
        case 'PROMPT' :
        {
            $prompt .= $data;
        }
        break;

        case 'SIMPLECHOICE':
        {
            if (!isset($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value']))
            {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'] = trim($data);
            }
            else
            {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'] .= ' '.trim($data);
            }
        }
        break;

        case 'FEEDBACKINLINE' :
        {
            if (!isset($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback']))
            {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback'] = trim($data);
            }
            else
            {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback'] .= ' '.trim($data);
            }
        }
        break;

        case 'SIMPLEASSOCIABLECHOICE' :
        {
            $exercise_info['question'][$current_question_ident]['answer'][$current_match_set][$currentAssociableChoice] = trim($data);
        }
        break;

        case 'VALUE':
        {
            if ($parent_element == "CORRECTRESPONSE")
            {
                if( $cardinality == "single" )
                {
                    $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id] = $data;
                }
                else
                {
                    $exercise_info['question'][$current_question_ident]['correct_answers'][] = $data;
                }
            }
        }
        break;

        case 'INLINECHOICE' :
        {
            // if this is the right answer, then we must replace the claroline tags in the FIB text bye the answer between "[" and "]" :

            $answer_identifier = $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id];

            if ($current_inlinechoice_id == $answer_identifier)
            {
                $current_question_item_body = str_replace("**claroline_start**".$current_answer_id."**claroline_end**", "[".$data."]", $current_question_item_body);
            }
            else // save wrong answers in an array
            {
                if(!isset($exercise_info['question'][$current_question_ident]['wrong_answers']))
                {
                    $exercise_info['question'][$current_question_ident]['wrong_answers'] = array();
                }
                $exercise_info['question'][$current_question_ident]['wrong_answers'][] = $data;
            }
        }
        break;
    }
}

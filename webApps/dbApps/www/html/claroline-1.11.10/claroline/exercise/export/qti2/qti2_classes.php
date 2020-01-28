<?php // $Id: qti2_classes.php 14314 2012-11-07 09:09:19Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 14314 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */
$path = dirname(__FILE__);
include_once $path . '/../../lib/answer_multiplechoice.class.php';
include_once $path . '/../../lib/answer_truefalse.class.php';
include_once $path . '/../../lib/answer_fib.class.php';
include_once $path . '/../../lib/answer_matching.class.php';

include_once get_path('incRepositorySys') . '/lib/xml.lib.php';

class Qti2Question extends Question
{
    static private $_rank = 1;
    
    private $rank;
    
    /**
     * Constructor
     */
    public function __construct( $course_id = null )
    {
        $this->rank = self::$_rank++;
        parent::__construct( $course_id );
    }
    
    /**
     * get question rank
     * @return int $rank
     */
    public function getRank()
    {
        return $this->rank;
    }
    
    /**
     * set question rank
     * @param int $rank
     * @return boolean
     */
    public function setRank( $rank )
    {
        return $this->rank = (int) $rank;
    }
    
    /**
     * Include the correct answer class and create answer
     */
    public function setAnswer()
    {
        switch($this->type)
        {
            case 'MCUA' :
                $this->answer = new Qti2AnswerMultipleChoice($this->id, false);
                break;
            case 'MCMA' :
                $this->answer = new Qti2AnswerMultipleChoice($this->id, true);
                break;
            case 'TF' :
                $this->answer = new Qti2AnswerTrueFalse($this->id);
                break;
            case 'FIB' :
                $this->answer = new Qti2AnswerFillInBlanks($this->id);
                break;
            case 'MATCHING' :
                $this->answer = new Qti2AnswerMatching($this->id);
                break;
            default :
                $this->answer = null;
                break;
        }

        return true;
    }
    
    public function export($standalone = true)
    {
        $ims = new ImsAssessmentItem($this);

        return $ims->export($standalone);
    }
    /**
     * allow to import the question
     *
     * @param questionArray is an array that must contain all the information needed to build the question
     */

    public function import($questionInfo)
    {
        if( is_array($questionInfo) )
        {
            if( isset($questionInfo['title']) )       $this->setTitle($questionInfo['title']);
            if( isset($questionInfo['statement']) )   $this->setDescription($questionInfo['statement']."\n".'<!-- content: imsqti -->');
            $this->setType($questionInfo['type']);

            if( !empty($questionInfo['attached_file_url']) )
            {
                $this->importAttachment($questionInfo['tempdir'].$questionInfo['attached_file_url']);
            }
        }
        else
        {
            return false;
        }
    }

    public function importAttachment($importedFilePath)
    {
        // copy file in a tmp directory known by object,
        // attached file will be copied to its final destination when saving question
        $dir = $this->tmpQuestionDirSys;
        $filename = basename($importedFilePath);

        if( !is_dir( $dir ) )
        {
            // create it
            if( !claro_mkdir($dir, CLARO_FILE_PERMISSIONS) )
            {
                claro_failure::set_failure('cannot_create_tmp_dir');
                return false;
            }
        }

        if( claro_move_file($importedFilePath, $dir.$filename) )
        {
            $this->attachment = $filename;
            return true;
        }
        else
        {
            return false;
        }
    }
}

class Qti2AnswerMultipleChoice extends answerMultipleChoice
{
    /**
     * Return the XML flow for the possible answers.
     *
     */
    public function qti2ExportResponses($questionIdent, $questionStatment)
    {
        $out = "\n" . '    <![CDATA[' . $questionStatment . ']]>' . "\n";
        $out .= '    <choiceInteraction responseIdentifier="' . $questionIdent . '" >' . "\n";

        foreach ($this->answerList as $current_answer)
        {
            $out .= '      <simpleChoice identifier="answer_' . $current_answer['id'] . '" fixed="false"><![CDATA[' . $current_answer['answer'] . ']]>';
            if (isset($current_answer['comment']) && $current_answer['comment'] != '')
            {
                $out .= '<feedbackInline identifier="answer_' . $current_answer['id'] . '"><![CDATA[' . $current_answer['comment'] . ']]></feedbackInline>';
            }
            $out .= '</simpleChoice>'. "\n";
        }

        $out .= '    </choiceInteraction>'. "\n";

        return $out;
    }

    /**
     * Return the XML flow of answer ResponsesDeclaration
     *
     */
    public function qti2ExportResponsesDeclaration($questionIdent)
    {

        if ($this->multipleAnswer == 'MCMA')  $cardinality = 'multiple'; else $cardinality = 'single';

        $out = '  <responseDeclaration identifier="' . $questionIdent . '" cardinality="' . $cardinality . '" baseType="identifier">' . "\n";

        //Match the correct answers

        $out .= '    <correctResponse>'. "\n";

        foreach($this->answerList as $current_answer)
        {
            if ($current_answer['correct'])
            {
                $out .= '      <value>answer_'. $current_answer['id'] .'</value>'. "\n";
            }
        }
        $out .= '    </correctResponse>'. "\n";

        //Add the grading

        $out .= '    <mapping>'. "\n";

        foreach($this->answerList as $current_answer)
        {
            if (isset($current_answer['grade']))
            {
                $out .= '      <mapEntry mapKey="answer_'. $current_answer['id'] .'" mappedValue="'.$current_answer['grade'].'" />'. "\n";
            }
        }
        $out .= '    </mapping>'. "\n";

        $out .= '  </responseDeclaration>'. "\n";

        return $out;
    }

    /**
     * allow to import the answers, feedbacks, and grades of a question
     * @param questionArray is an array that must contain all the information needed to build the question

     */

    public function import($questionArray)
    {
        $answerArray = $questionArray['answer'];

        $this->answerList = array(); //re-initialize answer object content

        foreach ($answerArray as $key => $answer)
        {
            if (!isset($answer['feedback'])) $answer['feedback'] = "";

            if (!isset($questionArray['weighting'][$key]))
            {
                if (isset($questionArray['default_weighting']))
                {
                    $grade = castToFloat($questionArray['default_weighting']);
                }
                else
                {
                    $grade = 0;
                }
            }
            else
            {
                $grade = castToFloat($questionArray['weighting'][$key]);
            }

            if (in_array($key,$questionArray['correct_answers'])) $is_correct = 1; else $is_correct = 0;

            $addedAnswer = array(
                            'answer' => $answer['value'],
                            'correct' => $is_correct,
                            'grade' => $grade,
                            'comment' => $answer['feedback'],
                            );

            $this->answerList[] = $addedAnswer;
        }
    }
}

class Qti2AnswerTrueFalse extends AnswerTrueFalse
{
    /**
     * Return the XML flow for the possible answers.
     *
     */
    public function qti2ExportResponses($questionIdent, $questionStatment)
    {
        $out = "\n" . '    <![CDATA[' . $questionStatment . ']]>'. "\n";
        $out .= '    <choiceInteraction responseIdentifier="' . $questionIdent . '" >' . "\n";

        //set true answer

        $out .= '      <simpleChoice identifier="answer_true" fixed="false"><![CDATA[' . get_lang('True') . ']]>' . "\n";
        if (isset($this->trueFeedback) && $this->trueFeedback != '')
        {
            $out .= '<feedbackInline identifier="answer_true"><![CDATA[' . $this->trueFeedback . ']]></feedbackInline>'. "\n";
        }
        $out .= '</simpleChoice>'. "\n";

        //set false answer

        $out .= '      <simpleChoice identifier="answer_false" fixed="false"><![CDATA[' . get_lang('False') . ']]>' . "\n";
        if (isset($this->falseFeedback) && $this->falseFeedback != '')
        {
            $out .= '<feedbackInline identifier="answer_false"><![CDATA[' . $this->falseFeedback . ']]></feedbackInline>'. "\n";
        }
        $out .= '</simpleChoice>'. "\n";


        $out .= '    </choiceInteraction>'. "\n";
        return $out;
    }

    public function qti2ExportResponsesDeclaration($questionIdent)
    {
        $out = '  <responseDeclaration identifier="' . $questionIdent . '" cardinality="single" baseType="identifier">' . "\n";

        //Match the correct answers

        $out .= '    <correctResponse>'. "\n";


        if ($this->correctAnswer=='TRUE')
        {
            $out .= '      <value>answer_true</value>'. "\n";
        }
        else
        {
            $out .= '      <value>answer_false</value>'. "\n";
        }

        $out .= '    </correctResponse>'. "\n";

        //Add the grading

        $out .= '    <mapping>'. "\n";

        if (isset($this->trueGrade))
        {
            $out .= '      <mapEntry mapKey="answer_true" mappedValue="'.$this->trueGrade.'" />'. "\n";
        }

        if (isset($this->falseGrade))
        {
            $out .= '      <mapEntry mapKey="answer_false" mappedValue="'.$this->falseGrade.'" />'. "\n";
        }

        $out .= '    </mapping>'. "\n";

        $out .= '  </responseDeclaration>'. "\n";

        return $out;

    }
}



class Qti2AnswerFillInBlanks extends answerFillInBlanks
{
    /**
     * Export the text with missing words.
     *
     *
     */
    public function qti2ExportResponses($questionIdent, $questionStatment)
    {
        $out = '';

        $out .= '<prompt><![CDATA[' . $questionStatment . ']]></prompt>'. "\n";

        switch ($this->type)
        {
            case TEXTFIELD_FILL :
            {
                $text = $this->answerText;

                foreach ($this->answerList as $key=>$answer)
                {
                    $text = str_replace('['.$answer.']','<textEntryInteraction responseIdentifier="fill_'.$key.'" expectedLength="'.strlen($answer).'"/>', $text);
                }
                $out .= $text;
            }
            break;

            case LISTBOX_FILL :
            {
                $replacementList = array();

                foreach ($this->answerList as $answerKey => $answer)
                {
                    //build inlinechoice list

                    $inlineChoiceList = '';

                    //1-start interaction tag

                    $inlineChoiceList .= '<inlineChoiceInteraction responseIdentifier="fill_'.$answerKey.'" >'. "\n";

                    //2- add wrong answer array

                    foreach ($this->wrongAnswerList as $choiceKey => $wrongAnswer)
                    {
                        $inlineChoiceList .= '  <inlineChoice identifier="choice_w_'.$answerKey.'_'.$choiceKey.'"><![CDATA['.$wrongAnswer.']]></inlineChoice>'. "\n";
                    }

                    //3- add correct answers array
                    foreach ($this->answerList as $choiceKey => $correctAnswer)
                    {
                        $inlineChoiceList .= '  <inlineChoice identifier="choice_c_'.$answerKey.'_'.$choiceKey.'"><![CDATA['.$correctAnswer.']]></inlineChoice>'. "\n";
                    }

                    //4- finish interaction tag

                    $inlineChoiceList .= '</inlineChoiceInteraction>';

                    $replacementList['['.$answer.']'] =  $inlineChoiceList;
                }

                $out .= strtr($this->answerText, $replacementList);
            }
            break;
        }

        return $out;

    }

    /**
     *
     */
    public function qti2ExportResponsesDeclaration($questionIdent)
    {

        $out = '';

        foreach ($this->answerList as $answerKey=>$answer)
        {
            $out .= '  <responseDeclaration identifier="fill_' . $answerKey . '" cardinality="single" baseType="identifier">' . "\n";
            $out .= '    <correctResponse>'. "\n";

            if ($this->type == TEXTFIELD_FILL)
            {
                $out .= '      <value><![CDATA['.$answer.']]></value>'. "\n";
            }
            else
            {
                //find correct answer key to apply in manifest and output it

                foreach ($this->answerList as $choiceKey=>$correctAnswer)
                {
                    if ($correctAnswer==$answer)
                    {
                        $out .= '      <value>choice_c_'.$answerKey.'_'.$choiceKey.'</value>'. "\n";
                    }
                }
            }

            $out .= '    </correctResponse>'. "\n";

            if (isset($this->gradeList[$answerKey]))
            {
                $out .= '    <mapping>'. "\n";
                $out .= '      <mapEntry mapKey="'.claro_htmlspecialchars($answer).'" mappedValue="'.$this->gradeList[$answerKey].'"/>'. "\n";
                $out .= '    </mapping>'. "\n";
            }

            $out .= '  </responseDeclaration>'. "\n";
        }

       return $out;
    }

    /**
     * allow to import the answers, feedbacks, and grades of a question
     *
     * @param questionArray is an array that must contain all the information needed to build the question

     */

    public function import($questionArray)
    {
        // $questionArray['answer'] should be empty for this question type
        $this->answerText = $questionArray['response_text'];

        if ($questionArray['subtype'] == "TEXTFIELD_FILL")
        {
            $this->type = TEXTFIELD_FILL;
        }
        if ($questionArray['subtype'] == "LISTBOX_FILL")
        {
            $this->wrongAnswerList = $questionArray['wrong_answers'];
            $this->type = LISTBOX_FILL;
        }

        //build correct_answsers array
        if( isset($questionArray['weighting']) && is_array($questionArray['weighting']) )
        {
            $this->gradeList = array();
            foreach( $questionArray['weighting'] as $key => $value )
            {
                $this->gradeList[$key] = castToFloat($value);
            }
        }
    }
}

class Qti2AnswerMatching extends answerMatching
{
    /**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     */
    public function qti2ExportResponses($questionIdent, $questionStatment)
    {
        $maxAssociation = max(count($this->leftList), count($this->rightList));

        $out = "";

        $out .= '<matchInteraction responseIdentifier="' . $questionIdent . '" maxAssociations="'. $maxAssociation .'">'. "\n";
        $out .= '<prompt><![CDATA[' . $questionStatment . ']]></prompt>'. "\n";

        //add left column

        $out .= '  <simpleMatchSet>'. "\n";

        foreach ($this->leftList as $leftKey=>$leftElement)
        {
            $out .= '    <simpleAssociableChoice identifier="left_'.$leftKey.'" ><![CDATA['. $leftElement['answer'] .']]></simpleAssociableChoice>'. "\n";
        }

        $out .= '  </simpleMatchSet>'. "\n";

        //add right column

        $out .= '  <simpleMatchSet>'. "\n";

        $i = 0;

        foreach($this->rightList as $rightKey=>$rightElement)
        {
            $out .= '    <simpleAssociableChoice identifier="right_'.$i.'" ><![CDATA['. $rightElement['answer'] .']]></simpleAssociableChoice>'. "\n";
            $i++;
        }

        $out .= '  </simpleMatchSet>'. "\n";

        $out .= '</matchInteraction>'. "\n";

        return $out;
    }

    /**
     *
     */
    public function qti2ExportResponsesDeclaration($questionIdent)
    {
        $out =  '  <responseDeclaration identifier="' . $questionIdent . '" cardinality="multiple" baseType="identifier">' . "\n";
        $out .= '    <correctResponse>' . "\n";

        $gradeArray = array();

        foreach ($this->leftList as $leftKey=>$leftElement)
        {
            $i=0;
            foreach ($this->rightList as $rightKey=>$rightElement)
            {
                if( ($leftElement['match'] == $rightElement['code']))
                {
                    $out .= '      <value>left_' . $leftKey . ' right_'.$i.'</value>'. "\n";

                    $gradeArray['left_' . $leftKey . ' right_'.$i] = $leftElement['grade'];
                }
                $i++;
            }
        }
        $out .= '    </correctResponse>'. "\n";
        $out .= '    <mapping>' . "\n";
        foreach ($gradeArray as $gradeKey=>$grade)
        {
            $out .= '          <mapEntry mapKey="'.$gradeKey.'" mappedValue="'.$grade.'"/>' . "\n";
        }
        $out .= '    </mapping>' . "\n";
        $out .= '  </responseDeclaration>'. "\n";

        return $out;
    }

    /**
     * allow to import the answers, feedbacks, and grades of a question
     *
     * @param questionArray is an array that must contain all the information needed to build the question

     */

    public function import($questionArray)
    {
        $answerArray = $questionArray['answer'];

        //This tick to remove examples in the answers!!!!
        $this->leftList = array();
        $this->rightList = array();

        //find right and left column
        $right_column = array_pop($answerArray);
        $left_column  = array_pop($answerArray);

        //1- build answers

        foreach ($right_column as $right_key => $right_element)
        {
            $code = $this->addRight($right_element);

            foreach ($left_column as $left_key => $left_element)
            {
                $matched_pattern = $left_key." ".$right_key;
                $matched_pattern_inverted = $right_key." ".$left_key;

                if (in_array($matched_pattern, $questionArray['correct_answers']) || in_array($matched_pattern_inverted, $questionArray['correct_answers']))
                {
                    if (isset($questionArray['weighting'][$matched_pattern]))
                    {
                        $grade = castToFloat($questionArray['weighting'][$matched_pattern]);
                    }
                    else
                    {
                        $grade = 0;
                    }
                    $this->addLeft($left_element, $code, $grade);
                }
            }
        }

        $this->save();
    }
}

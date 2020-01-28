<?php

// $Id: answer_fib.class.php 14441 2013-05-02 06:51:52Z ldumorti $

/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 14441 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */
define ( 'TEXTFIELD_FILL', 1 );
define ( 'LISTBOX_FILL', 2 );

class answerFillInBlanks
{

    /**
     * @var $id id of answer, -1 if answer doesn't exist already
     */
    public $id;

    /**
     * @var $id id of question, -1 if answer doesn't exist already
     */
    public $questionId;

    /**
     * @var $answer complete answer text with blanks
     */
    public $answerText;

    /**
     * @var $answerList list of text within blanks
     */
    public $answerList;

    /**
     * @var $gradeList array list scores of each blank
     */
    public $gradeList;

    /**
     * @var $wrongAnswerList array list of incorrect answers to be added in drop down list
     */
    public $wrongAnswerList;

    /**
     * @var $type fill type of form (text field, drop down list box)
     */
    public $type;

    /**
     * @var $step step in edition form
     */
    public $step;

    //----- Others

    /**
     * @var $response response sent by user and stored in object for easiest use
     * use extractResponseFromRequest to set it
     */
    public $response;

    /**
     * @var $errorList is used to store error that comes on form post
     */
    public $errorList;

    /**
     * @var $tblAnswer
     */
    protected $tblAnswer;

    /**
     * constructor
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @param $questionId integer question that use this answer
     * @param $course_id to use the class when not in course context
     * @return string
     */
    public function __construct ( $questionId, $course_id = null )
    {
        $this->questionId = (int) $questionId;

        $this->id = -1;

        // directly fill with an example
        $this->answerText = get_lang ( '&#91;British people&#93; live in &#91;United Kingdom&#93;.' );

        $this->answerList = array ( );
        $this->gradeList = array ( );
        $this->wrongAnswerList = array ( );

        $this->type = TEXTFIELD_FILL;

        $this->step = 1;

        $this->response = array ( );

        $this->errorList = array ( );

        $tbl_cdb_names = get_module_course_tbl ( array ( 'qwz_answer_fib' ), $course_id );
        $this->tblAnswer = $tbl_cdb_names[ 'qwz_answer_fib' ];
    }

    /**
     * load answers in object
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function load ()
    {
        $sql = "SELECT
                    `id`,
                    `answer`,
                    `gradeList`,
                    `wrongAnswerList`,
                    `type`
            FROM `" . $this->tblAnswer . "`
            WHERE `questionId` = " . (int) $this->questionId;

        $data = claro_sql_query_get_single_row ( $sql );

        if ( !empty ( $data ) )
        {
            $this->id = (int) $data[ 'id' ];
            $this->answerText = $data[ 'answer' ];
            if ( !empty ( $data[ 'gradeList' ] ) )
                $this->gradeList = explode ( ',', $data[ 'gradeList' ] );
            if ( !empty ( $data[ 'wrongAnswerList' ] ) )
                $this->wrongAnswerList = explode ( ',', $data[ 'wrongAnswerList' ] );
            $this->type = $data[ 'type' ];

            $this->setAnswerList ();

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * save object in db
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function save ()
    {
        $sqlGradeList = implode ( ',', $this->gradeList );
        $sqlWrongAnswerList = implode ( ',', $this->wrongAnswerList );

        if ( $this->id == -1 )
        {
            // insert
            $sql = "INSERT INTO `" . $this->tblAnswer . "`
                    SET `questionId` = " . (int) $this->questionId . ",
                        `answer` = '" . claro_sql_escape ( $this->answerText ) . "',
                        `gradeList` = '" . claro_sql_escape ( $sqlGradeList ) . "',
                        `wrongAnswerList` = '" . claro_sql_escape ( $sqlWrongAnswerList ) . "',
                        `type` = " . (int) $this->type;

            // execute the creation query and get id of inserted assignment
            $insertedId = claro_sql_query_insert_id ( $sql );

            if ( $insertedId )
            {
                $this->id = (int) $insertedId;

                return $this->id;
            }
            else
            {
                return false;
            }
        }
        else
        {
            // update
            $sql = "UPDATE `" . $this->tblAnswer . "`
                    SET `answer` = '" . claro_sql_escape ( $this->answerText ) . "',
                        `gradeList` = '" . claro_sql_escape ( $sqlGradeList ) . "',
                        `wrongAnswerList` = '" . claro_sql_escape ( $sqlWrongAnswerList ) . "',
                        `type` = " . (int) $this->type . "
                    WHERE `id` = " . (int) $this->id;

            // execute and return main query
            if ( claro_sql_query ( $sql ) )
            {
                return $this->id;
            }
            else
            {
                return false;
            }
        }
    }

    /**
     * delete answers from db
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function delete ()
    {
        if ( $this->id != -1 )
        {
            // delete question from all exercises
            $sql = "DELETE FROM `" . $this->tblAnswer . "`
                    WHERE `id` = " . (int) $this->id;

            if ( !claro_sql_query ( $sql ) )
                return false;

            $this->id = -1;
        }

        return true;
    }

    /**
     * clone the object
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function duplicate ( $duplicatedQuestionId )
    {
        $duplicated = new answerFillInBlanks ( $duplicatedQuestionId );

        $duplicated->answerText = $this->answerText;
        $duplicated->answerList = $this->answerList;
        $duplicated->gradeList = $this->gradeList;
        $duplicated->wrongAnswerList = $this->wrongAnswerList;
        $duplicated->type = $this->type;

        $duplicated->save ();

        return $duplicated;
    }

    /**
     * check if the object content is valide (use before using save method)
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function validate ()
    {
        // answer cannot be empty
        if ( $this->answerText == '' )
        {
            $this->errorList[ ] = get_lang ( 'Please type the text' );
            $this->step = 1;
            return false;
        }

        // answer must contain at least one blank
        $regex = '/\[.*\]/';
        if ( !preg_match ( $regex, $this->answerText ) )
        {
            $this->errorList[ ] = get_lang ( 'Please define at least one blank with brackets %mask', array ( '%mask' => '&#91;...&#93;' ) );
            $this->step = 1;
            return false;
        }

        return true;
    }

    /**
     * handle the form, get data of request and put in the object, handle commands if required
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean true if form can be checked and saved, false
     */
    public function handleForm ()
    {
        // for multipage form handling
        if ( isset ( $_REQUEST[ 'step' ] ) )
            $this->step = (int) abs ( $_REQUEST[ 'step' ] );
        else
            $this->step = 1;

        // for answer
        if ( isset ( $_REQUEST[ 'answer' ] ) )
            $this->answerText = trim ( $this->answerEncode ( $_REQUEST[ 'answer' ] ) );
        else
            $this->answerText = '';
        // and update answerList
        $this->setAnswerList ();

        if ( isset ( $_REQUEST[ 'type' ] ) )
            $this->type = $_REQUEST[ 'type' ];
        // else keep the value in object (default or loaded)

        if ( isset ( $_REQUEST[ 'wrongAnswerList' ] ) )
        {
            $encoded = $this->wrongAnswerEncode ( $_REQUEST[ 'wrongAnswerList' ] );
            // remove empty lines
            $encodedList = explode ( "\n", $encoded );
            // remove duplicated entries
            $this->wrongAnswerList = array_unique ( $encodedList );
        }
        else
        {
            $this->wrongAnswerList = '';
        }

        if ( isset ( $_REQUEST[ 'grade' ] ) && is_array ( $_REQUEST[ 'grade' ] ) )
        {
            // reinit gradeList
            $this->gradeList = array ( );
            // all values must be positive
            foreach ( $_REQUEST[ 'grade' ] as $grade )
            {
                $this->gradeList[ ] = abs ( castToFloat ( $grade ) );
            }
        }
        // else keep the value in object (default or loaded)
        //-- cmd
        if ( isset ( $_REQUEST[ 'cmdBack' ] ) )
        {
            if ( $this->validate () )
            {
                if ( $this->step > 1 )
                    $this->step--;
            }
            return false;
        }

        if ( isset ( $_REQUEST[ 'cmdNext' ] ) )
        {
            if ( $this->validate () )
            {
                $this->step++;
            }
            return false;
        }


        // no special command
        return true;
    }

    /**
     * provide the list of error that validate found
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return array list of errors
     */
    public function getErrorList ()
    {
        return $this->errorList;
    }

    /**
     * display the answers as a form part for display in quizz submission page
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string html code for display of answer
     */
    public function getAnswerHtml ()
    {
        if ( $this->id == -1 )
        {
            $html = "\n" . '<p>' . get_lang ( 'There is no answer for the moment' ) . '</p>' . "\n\n";
        }
        else
        {
            $answerCount = count ( $this->answerList );

            // build replacement
            $replacementList = array ( );

            if ( $this->type == LISTBOX_FILL )
            {
                // build the list shown in list box
                // prepare option list using good and wrong answers
                $allAnswerList = array_merge ( $this->answerList, $this->wrongAnswerList );

                // alphabetical sort of the list
                natcasesort ( $allAnswerList );

                $optionList[ '' ] = '';

                foreach ( $allAnswerList as $answer )
                {
                    $optionListValue = $this->answerDecode ( $answer );
                    $optionList[ $optionListValue ] = $optionListValue;
                }

                for ( $i = 0; $i < $answerCount; $i++ )
                {
                    if ( isset ( $this->response[ $i ] ) && array_key_exists ( $this->response[ $i ], $optionList ) )
                    {
                        $selected = $this->response[ $i ];
                    }
                    else
                    {
                        $selected = ''; // default is the empty element
                    }

                    $replacementList[ ] = str_replace ( '$', '\$', claro_html_form_select ( 'a_' . $this->questionId . '_' . $i, $optionList, $selected ) );
                }
            }
            else
            {
                for ( $i = 0; $i < $answerCount; $i++ )
                {
                    if ( isset ( $this->response[ $i ] ) )
                        $value = $this->response[ $i ];
                    else
                        $value = '';

                    $replacementList[ ] = str_replace ( '$', '\$', ' <input type="text" name="a_' . $this->questionId . '_' . $i . '" size="10" value="' . $value . '" /> ' );
                }
            }

            // get all enclosed answers
            $blankList = array ( );
            foreach ( $this->answerList as $answer )
            {
                // filter slashes as they are modifiers in preg expressions
                $blankList[ ] = '/\[' . preg_quote ( $this->answerDecode ( $this->addslashesEncodedBrackets ( $answer ) ), '/' ) . '\]/';
            }

            // apply replacement on answer, require limit parameter to replace only the first occurrence in case we
            // have several times the same word in a blank.

            $displayedAnswer = preg_replace ( $blankList, $replacementList, claro_parse_user_text ( $this->answerDecode ( $this->answerText ) ), 1 );

            $html =
                '<table width="100%">' . "\n\n"
                . '<tr>' . "\n"
                . '<td>' . "\n"
                . $displayedAnswer . "\n"
                . '</td>' . "\n"
                . '</tr>' . "\n\n"
                . '</table>' . "\n"
                . '<p><small>' . get_lang ( 'Fill in blanks' ) . '</small></p>' . "\n";
        }

        return $html;
    }

    /**
     * display the input hidden field depending on what was submitted in exercise submit form
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string html code for display of hidden sent data
     */
    public function getHiddenAnswerHtml ()
    {
        $html = "\n" . '<!-- ' . $this->questionId . ' -->' . "\n";

        while ( list($key, $response) = each ( $this->response ) )
        {
            $html .= '<input type="hidden" name="a_' . $this->questionId . '_' . $key . '" value="' . $response . '" />' . "\n";
        }
        $html .= "\n" . '<!-- ' . $this->questionId . '(end) -->' . "\n";
        return $html;
    }

    /**
     * display the input hidden field depending on what was submitted in exercise submit form
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string html code for display of feedback for this answer
     */
    public function getAnswerFeedbackHtml ()
    {
        $html =
            '<table width="100%">' . "\n\n"
            . '<tr height:style="font-weight:italic;font-size:small;">' . "\n"
            . '<td valign="top">' . get_lang ( 'Answer' ) . '</td>' . "\n"
            . '</tr>' . "\n\n";

        // get all enclosed answers
        $blankList = array ( );
        foreach ( $this->answerList as $answer )
        {
            // filter slashes as they are modifiers in preg expressions
            $blankList[ ] = '/\[' . preg_quote ( $this->answerDecode ( $this->addslashesEncodedBrackets ( $answer ) ), '/' ) . '\]/';
        }
        $answerCount = count ( $blankList );

        // build replacement
        $replacementList = array ( );

        for ( $i = 0; $i < $answerCount; $i++ )
        {
        	if ( empty ( $this->response[ $i ] ) )
            {
                // no response for this blank
                $userAnswer = '&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            elseif ( $this->isResponseCorrect ( $this->response[ $i ], $this->answerDecode ( $this->answerList[ $i ] ) ) )
            {
                // user answer is ok
                $userAnswer = claro_htmlspecialchars ( $this->answerDecode ( $this->response[ $i ] ) );
            }
            else
            {
                // incorrect response
                $userAnswer = '<span class="error"><s>' . claro_htmlspecialchars ( $this->answerDecode ( $this->response[ $i ] ) ) . '</s></span>';
            }

            //
            $correctAnswer = claro_htmlspecialchars ( $this->answerDecode ( $this->answerList[ $i ] ) );          

            $replacementList[ ] = str_replace ( '$', '\$', '[' . $userAnswer . ' / <span class="correct"><b>' . $correctAnswer . '</b></span>]' . "\n" );
        }

        // apply replacement on answer
        // use preg_replace instead of str_replace because if there is several blanks
        // with same correct value using str_replace will replace each occurence by the 1st one he found
        $displayedAnswer = preg_replace ( $blankList, $replacementList, claro_parse_user_text ( $this->answerDecode ( $this->answerText ) ), 1 );

        $html =
            '<table width="100%">' . "\n\n"
            . '<tr height:style="font-weight:italic;font-size:small;">' . "\n"
            . '<td valign="top">' . get_lang ( 'Answer' ) . '</td>' . "\n"
            . '</tr>' . "\n\n"
            . '<tr>' . "\n"
            . '<td>' . "\n"
            . $displayedAnswer . "\n"
            . '</td>' . "\n"
            . '</tr>' . "\n\n"
            . '</table>' . "\n"
            . '<p><small>' . get_lang ( 'Fill in blanks' ) . '</small></p>' . "\n";

        return $html;
    }

    /**
     * display the form to edit answers
     *
     * @param $exId exercise id, required to get stay in the exercise context if required after posting the form
     * @param $askDuplicate display or not the form elements allowing to choose if the question must be duplicated or modified in all exercises
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string html code for display of answer edition form
     */
    public function getFormHtml ( $exId = null, $askDuplicate )
    {
        $html =
            '<form method="post" action="./edit_answers.php?exId=' . $exId . '&amp;quId=' . $this->questionId . '">' . "\n"
            . '<input type="hidden" name="cmd" value="exEdit" />' . "\n"
            . '<input type="hidden" name="step" value="' . $this->step . '" />' . "\n"
            . claro_form_relay_context () . "\n";

        if ( $this->step > 1 )
        {
            $html .=
                // populate hidden fields for other steps
                '<input type="hidden" name="answer" value="' . claro_htmlspecialchars ( $this->answerText ) . '" />' . "\n"
                . '<input type="hidden" name="type" value="' . claro_htmlspecialchars ( $this->type ) . '" />' . "\n"
                . '<input type="hidden" name="wrongAnswerList" value="' . claro_htmlspecialchars ( implode ( "\n", $this->wrongAnswerList ) ) . '" />' . "\n\n";

            if ( !empty ( $exId ) && $askDuplicate )
            {
                if ( isset ( $_REQUEST[ 'duplicate' ] ) )
                {
                    $html .= '<input type="hidden" name="duplicate" value="' . claro_htmlspecialchars ( $_REQUEST[ 'duplicate' ] ) . '" />' . "\n";
                }
            }

            $html .= '<p>' . get_lang ( 'Please give a weighting to each blank' ) . '&nbsp;:</p>' . "\n"
                . '<table border="0" cellpadding="5" width="500">' . "\n";

            $i = 0;
            foreach ( $this->answerList as $correctAnswer )
            {
                $value = isset ( $this->gradeList[ $i ] ) ? $this->gradeList[ $i ] : '0';

                $html .=
                    '<tr>' . "\n"
                    . '<td width="50%">' . $correctAnswer . '</td>' . "\n"
                    . '<td width="50%">'
                    . '<input type="text" name="grade[' . $i . ']" size="5" value="' . $value . '" />'
                    . '</td>' . "\n"
                    . '</tr>' . "\n\n"
                ;

                $i++;
            }


            $html .=
                '</table>' . "\n\n"
                . '<input type="submit" name="cmdBack" value="&lt; ' . get_lang ( 'Back' ) . '" />&nbsp;&nbsp;'
                . '<input type="submit" name="cmdOk" value="' . get_lang ( 'Ok' ) . '" />&nbsp;&nbsp;'
                . claro_html_button ( './edit_question.php?exId=' . $exId . '&amp;quId=' . $this->questionId, get_lang ( "Cancel" ) );
        }
        else
        {
            // populate fields of other steps
            $i = 0;
            foreach ( $this->gradeList as $grade )
            {
                $html .= '<input type="hidden" name="grade[' . $i . ']" value="' . $grade . '" />' . "\n";
                $i++;
            }

            if ( !empty ( $exId ) && $askDuplicate )
            {
                $html .= '<p>' . html_ask_duplicate () . '</p>' . "\n";
            }

            // answer
            $text = $this->addslashesEncodedBrackets ( $this->answerText );
            $text = $this->answerDecode ( $text );

            $html .= '<p>' . get_lang ( 'Please type your text below, use brackets %mask to define one or more blanks', array ( '%mask' => '&#91;...&#93;' ) ) . ' :</p>' . "\n"
                . claro_html_textarea_editor ( 'answer', $text ) . "\n"

                // fill type
                . '<p>' . get_lang ( 'Fill type' ) . '&nbsp;:</p>' . "\n"
                . '<p>' . "\n"
                . '<input type="radio" name="type" id="textFill" value="' . TEXTFIELD_FILL . '"'
                . ( $this->type == TEXTFIELD_FILL ? 'checked="checked"' : '')
                . ' /><label for="textFill">' . get_lang ( 'Fill text field' ) . '</label><br />' . "\n"
                . '<input type="radio" name="type" id="listboxFill" value="' . LISTBOX_FILL . '"'
                . ( $this->type == LISTBOX_FILL ? 'checked="checked"' : '')
                . ' /><label for="listboxFill">' . get_lang ( 'Select in drop down list' ) . '</label><br />' . "\n"
                . '</p>' . "\n"

                // wrong answers list
                . '<p>' . get_lang ( 'Add wrong answers for drop down lists <small>(Optionnal. One wrong answer by line.)</small>' ) . '</p>'
                . '<textarea name="wrongAnswerList" cols="30" rows="5">' . claro_htmlspecialchars ( $this->answerDecode ( implode ( "\n", $this->wrongAnswerList ) ) ) . '</textarea>' . "\n"
                . '<p>' . "\n"
                . '<input type="submit" name="cmdNext" value="' . get_lang ( 'Next' ) . ' &gt;" />&nbsp;&nbsp;'
                . claro_html_button ( Url::Contextualize ( './edit_question.php?exId=' . $exId . '&amp;quId=' . $this->questionId ), get_lang ( "Cancel" ) )
                . '</p>' . "\n";
        }


        $html .= '</form>';

        return $html;
    }

    /**
     * get all blank in answer text and set the value of answer list
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return array array containing
     */
    public function setAnswerList ()
    {
        $matches = array ( );

        $regex = '/\[([^]]*)\]/';

        preg_match_all ( $regex, $this->answerText, $matches );

        $this->answerList = array ( );

        if ( is_array ( $matches[ 1 ] ) && !empty ( $matches[ 1 ] ) )
        {
            $this->answerList = $matches[ 1 ];
        }

        return true;
    }

    /**
     * encode the answer : replace forbidden and escaped chars by their html entities
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string encoded answer
     */
    public function answerEncode ( $answer )
    {
        $charsToReplace = array ( '\[', '\]', '<', '>' );
        $replacingChars = array ( '&#91;', '&#93;', '&lt;', '&gt;' );

        return str_replace ( $charsToReplace, $replacingChars, trim ( $answer ) );
    }

    /**
     * decode the answer : replace some claro_htmlentities by forbidden and escaped chars for proper display
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string decoded answer
     */
    public function answerDecode ( $answer )
    {
        $charsToReplace = array ( '&#91;', '&#93;', '&lt;', '&gt;', '&#44;' );
        $replacingChars = array ( '[', ']', '<', '>', ',' );

        return str_replace ( $charsToReplace, $replacingChars, trim ( $answer ) );
    }

    /**
     * encode the wrong answers list : replace forbidden and escaped chars by their html entities
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string encoded wrong answers list
     */
    public function wrongAnswerEncode ( $wrongAnswer )
    {
        $charsToReplace = array ( ',', '<', '>' );
        $replacingChars = array ( '&#44;', '&lt;', '&gt;' );

        return str_replace ( $charsToReplace, $replacingChars, trim ( $wrongAnswer ) );
    }

    /**
     * add slahes before encoded [ (&#91;) and ] (&#93;)
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return string text with backslashes before encoded brackets
     */
    public function addslashesEncodedBrackets ( $text )
    {
        $charsToReplace = array ( '&#91;', '&#93;' );
        $replacingChars = array ( '\\&#91;', '\\&#93;' );

        return str_replace ( $charsToReplace, $replacingChars, trim ( $text ) );
    }

    /**
     * read response from request grade it, write grade in object, return grade
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return float question grade
     * @desc return score of checked answer or 0 if nothing was checked
     */
    public function gradeResponse ()
    {
        $grade = 0;

        $answerCount = count ( $this->answerList );

        for ( $i = 0; $i < $answerCount; $i++ )
        {
            if ( $this->isResponseCorrect ( $this->response[ $i ], $this->answerDecode ( $this->answerList[ $i ] ) ) )
            {
                $grade += $this->gradeList[ $i ];
            }
        }
        return $grade;
    }

    /**
     * get response of user via $_REQUEST and store it in object
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function extractResponseFromRequest ()
    {
        $this->response = array ( );

        $answerCount = count ( $this->answerList );

        for ( $i = 0; $i < $answerCount; $i++ )
        {
            if ( isset ( $_REQUEST[ 'a_' . $this->questionId . '_' . $i ] ) )
            {
                $this->response[ $i ] = $_REQUEST[ 'a_' . $this->questionId . '_' . $i ];
            }
            else
            {
                $this->response[ $i ] = '';
            }
        }

        return true;
    }

    /**
     * compute grade of question from answer
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return float question grade
     */
    public function getGrade ()
    {
        $gradeSum = 0;

        foreach ( $this->gradeList as $grade )
        {
            $gradeSum += $grade;
        }
        return $gradeSum;
    }

    /**
     * compare two string to check if given response is
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return float question grade
     */
    public function isResponseCorrect ( $response, $correctAnswer )
    {
        if ( $this->type == LISTBOX_FILL )
        {
            // case sensitive check when select box are used
            return (bool) ($response == $correctAnswer);
        }
        else
        {
            // case insensitive check when text box are used
            return (bool) (strtolower ( $response ) == strtolower ( $correctAnswer ));
        }
    }

    /**
     * return a array with values needed for tracking
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return array
     */
    public function getTrackingValues ()
    {
        return $this->response;
    }

}

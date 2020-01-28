<?php

// $Id: answer_multiplechoice.class.php 14314 2012-11-07 09:09:19Z zefredz $

/**
 * CLAROLINE
 *
 * @version $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author Claro Team <cvs@claroline.net>
 */
class answerMultipleChoice
{

    /**
     * @var $id id of question, -1 if answer doesn't exist already
     */
    public $questionId;

    /**
     * @var $answerList array with list of proposal
     *      $answerList[]['id'] // int
     *         $answerList[]['answer'] // text
     *         $answerList[]['correct'] // boolean
     *         $answerList[]['grade'] // float
     *         $answerList[]['comment'] // text
     */
    public $answerList;

    /**
     * @var $multipleAnswer boolean true if multiple answer
     */
    public $multipleAnswer;

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
     * @param $multipleAnswer boolean true if several answer can be checked by user
     * @param $course_id to use the class when not in course context
     * @return string
     */
    public function __construct ( $questionId, $multipleAnswer = false, $course_id = null )
    {
        $this->questionId = (int) $questionId;

        $this->multipleAnswer = (bool) $multipleAnswer;

        $this->answerList = array ( );
        // add 2 empty answers as minimum requested number of answers
        $this->addAnswer ();
        $this->addAnswer ();

        $this->response = array ( );
        $this->errorList = array ( );

        $tbl_cdb_names = get_module_course_tbl ( array ( 'qwz_answer_multiple_choice' ), $course_id );
        $this->tblAnswer = $tbl_cdb_names[ 'qwz_answer_multiple_choice' ];
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
                    `correct`,
                    `grade`,
                    `comment`
            FROM `" . $this->tblAnswer . "`
            WHERE `questionId` = " . (int) $this->questionId . "
            ORDER BY `id`";

        $data = claro_sql_query_fetch_all ( $sql );

        if ( !empty ( $data ) )
        {
            $this->answerList = $data;
            if ( count ( $data ) == 1 )
            {
                // it is not a normal comportment but we need at least 2 answers !
                $this->addAnswer ();
            }

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
        // we need at least 2 answers
        if ( count ( $this->answerList ) < 2 )
            return false;

        $sql = "DELETE FROM `" . $this->tblAnswer . "`
                WHERE `questionId` = " . (int) $this->questionId;

        if ( claro_sql_query ( $sql ) == false )
            return false;

        // inserts new answers into data base
        $sql = "INSERT INTO `" . $this->tblAnswer . "` (`questionId`,`answer`,`correct`,`grade`,`comment`)
                VALUES ";

        foreach ( $this->answerList as $anAnswer )
        {
            $sql .= "(" . (int) $this->questionId . ",
                    '" . claro_sql_escape ( $anAnswer[ 'answer' ] ) . "',
                    " . (int) $anAnswer[ 'correct' ] . ",
                    '" . claro_sql_escape ( $anAnswer[ 'grade' ] ) . "',
                    '" . claro_sql_escape ( $anAnswer[ 'comment' ] ) . "'),";
        }

        $sql = substr ( $sql, 0, -1 ); // remove trailing ,

        return claro_sql_query ( $sql );
    }

    /**
     * delete answers from db
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function delete ()
    {
        $sql = "DELETE FROM `" . $this->tblAnswer . "`
                WHERE `questionId` = " . (int) $this->questionId;

        return claro_sql_query ( $sql );
    }

    /**
     * clone the object
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return object duplicated object
     */
    public function duplicate ( $duplicatedQuestionId )
    {
        $duplicated = new answerMultipleChoice ( $duplicatedQuestionId );

        $duplicated->multipleAnswer = $this->multipleAnswer;
        $duplicated->answerList = $this->answerList;
        // we could remove the ids in this array but it is not required, they are ignored during save

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
        // must have at least a correct answer
        $hasGoodAnswer = false;
        // must have text in answer
        foreach ( $this->answerList as $answer )
        {
            if ( $answer[ 'correct' ] == 1 )
            {
                $hasGoodAnswer = true;
            }

            if ( trim ( $answer[ 'answer' ] ) == '' )
            {
                $this->errorList[ ] = get_lang ( 'Please give the answers to the question' );
                return false;
            }
        }

        if ( !$hasGoodAnswer )
        {
            $this->errorList[ ] = get_lang ( 'Please choose a good answer' );
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
        $this->answerList = array ( );

        // set form value in object
        for ( $i = 0; $i < $_REQUEST[ 'answerCount' ]; $i++ )
        {
            $answerNumber = $i + 1;

            //-- answer text
            $answer = 'answer_' . $answerNumber;
            if ( isset ( $_REQUEST[ $answer ] ) )
                $this->answerList[ $i ][ 'answer' ] = $_REQUEST[ $answer ];
            else
                $this->answerList[ $i ][ 'answer' ] = '';

            //-- correct answer
            $correct = 'correct_' . $answerNumber;
            if ( $this->multipleAnswer )
            {
                if ( isset ( $_REQUEST[ $correct ] ) )
                    $this->answerList[ $i ][ 'correct' ] = 1;
                else
                    $this->answerList[ $i ][ 'correct' ] = 0;
            }
            else
            {
                if ( isset ( $_REQUEST[ 'correct' ] ) && $_REQUEST[ 'correct' ] == $correct )
                {
                    $this->answerList[ $i ][ 'correct' ] = 1;
                }
                else
                {
                    $this->answerList[ $i ][ 'correct' ] = 0;
                }
            }

            //-- feedbacks
            $comment = 'comment_' . $answerNumber;
            if ( isset ( $_REQUEST[ $comment ] ) )
                $this->answerList[ $i ][ 'comment' ] = $_REQUEST[ $comment ];
            else
                $this->answerList[ $i ][ 'comment' ] = '';

            //-- grade
            $grade = 'grade_' . $answerNumber;
            if ( isset ( $_REQUEST[ $grade ] ) )
            {
                if ( $this->answerList[ $i ][ 'correct' ] == 1 )
                {
                    // correct answer must have positive answer
                    $this->answerList[ $i ][ 'grade' ] = abs ( castToFloat ( $_REQUEST[ $grade ] ) );
                }
                else
                {
                    if ( $this->multipleAnswer )
                    {
                        // if multiple answer score must be negative
                        $this->answerList[ $i ][ 'grade' ] = 0 - abs ( castToFloat ( $_REQUEST[ $grade ] ) );
                    }
                    else
                    {
                        // if single answer score can be positive
                        $this->answerList[ $i ][ 'grade' ] = castToFloat ( $_REQUEST[ $grade ] );
                    }
                }
            }
            else
            {
                $this->answerList[ $i ][ 'grade' ] = 0;
            }
        }

        //-- cmd
        if ( isset ( $_REQUEST[ 'cmdRemAnsw' ] ) )
        {
            $this->remAnswer ();
            return false;
        }

        if ( isset ( $_REQUEST[ 'cmdAddAnsw' ] ) )
        {
            $this->addAnswer ();
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
        if ( empty ( $this->answerList ) )
        {
            $html = "\n" . '<p>' . get_lang ( 'There is no answer for the moment' ) . '</p>' . "\n\n";
        }
        else
        {
            if ( $this->multipleAnswer )
            {
                $questionTypeLang = get_lang ( 'Multiple choice (Multiple answers)' );
            }
            else
            {
                $questionTypeLang = get_lang ( 'Multiple choice (Unique answer)' );
            }

            $html = '<table width="100%">' . "\n\n";

            foreach ( $this->answerList as $answer )
            {
                $isSelected = array_key_exists ( $answer[ 'id' ], $this->response );

                $html .=
                    '<tr>' . "\n"
                    . '<td align="center" width="5%">' . "\n";

                if ( $this->multipleAnswer )
                {
                    $html .=
                        '<input name="a_' . $this->questionId . '_' . $answer[ 'id' ] . '" id="a_' . $this->questionId . '_' . $answer[ 'id' ] . '" value="true" type="checkbox" class="checkbox" '
                        . ( $isSelected ? 'checked="checked"' : '' )
                        . '/>' . "\n";
                }
                else
                {
                    $html .=
                        '<input name="a_' . $this->questionId . '" id="a_' . $this->questionId . '_' . $answer[ 'id' ] . '" value="' . $answer[ 'id' ] . '" type="radio" '
                        . ( $isSelected ? 'checked="checked"' : '' )
                        . '/>' . "\n";
                }

                $html .=
                    '</td>' . "\n"
                    . '<td width="95%" class="labelizer">' . "\n"
                    //.    '<label for="a_'.$this->questionId.'_'.$answer['id'].'">' . claro_parse_user_text($answer['answer']) . '</label>' . "\n"
                    . '<div>'
                    . claro_parse_user_text ( $answer[ 'answer' ] )
                    . '</div>' . "\n"
                    . '</td>' . "\n"
                    . '</tr>' . "\n\n";
            }

            $html .=
                '</table>' . "\n"
                . '<p><small>' . $questionTypeLang . '</small></p>' . "\n";
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

        foreach ( $this->answerList as $answer )
        {
            if ( array_key_exists ( $answer[ 'id' ], $this->response ) )
            {
                if ( $this->multipleAnswer )
                {
                    $html .= '<input type="hidden" name="a_' . $this->questionId . '_' . $answer[ 'id' ] . '" value="true" />' . "\n";
                }
                else
                {
                    $html .= '<input type="hidden" name="a_' . $this->questionId . '" value="' . $answer[ 'id' ] . '" />' . "\n";
                    // only one response is required so get out of the loop
                    break;
                }
            }
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
        global $imgRepositoryWeb;

        if ( $this->multipleAnswer )
        {
            $questionTypeLang = get_lang ( 'Multiple choice (Multiple answers)' );
            $imgOnHtml = '<img src="' . get_icon_url ( 'checkbox_on' ) . '" alt="[X]" />';
            $imgOffHtml = '<img src="' . get_icon_url ( 'checkbox_off' ) . '" alt="[ ]" />';
        }
        else
        {
            $questionTypeLang = get_lang ( 'Multiple choice (Unique answer)' );
            $imgOnHtml = '<img src="' . get_icon_url ( 'radio_on' ) . '" alt="(X)" />';
            $imgOffHtml = '<img src="' . get_icon_url ( 'radio_off' ) . '" alt="( )" />';
        }

        $html =
            '<table width="100%">' . "\n\n"
            . '<tr style="font-style:italic;font-size:small;">' . "\n"
            . '<td align="center" valign="top" width="5%">' . get_lang ( 'Your choice' ) . '</td>' . "\n"
            . '<td align="center" valign="top" width="5%">' . get_lang ( 'Expected choice' ) . '</td>' . "\n"
            . '<td valign="top" width="45%">' . get_lang ( 'Answer' ) . '</td>' . "\n"
            . '<td valign="top" width="45%">' . get_lang ( 'Comment' ) . '</td>' . "\n"
            . '</tr>' . "\n\n";


        foreach ( $this->answerList as $answer )
        {
            $isSelected = array_key_exists ( $answer[ 'id' ], $this->response );

            $html .=
                '<tr>' . "\n"
                . '<td align="center" width="5%">'
                . ( $isSelected ? $imgOnHtml : $imgOffHtml )
                . '</td>' . "\n"
                . '<td align="center" width="5%">'
                . ( $answer[ 'correct' ] ? $imgOnHtml : $imgOffHtml )
                . '</td>' . "\n"
                . '<td width="45%">'
                . claro_parse_user_text ( $answer[ 'answer' ] )
                . '</td>' . "\n"
                . '<td width="45%">'
                . ( ( get_conf ( 'showAllFeedbacks' ) || ($isSelected || $answer[ 'correct' ])) ? claro_parse_user_text ( $answer[ 'comment' ] ) : '&nbsp;' )
                . '</td>' . "\n"
                . '</tr>' . "\n\n";
        }


        $html .=
            '</table>' . "\n"
            . '<p><small>' . $questionTypeLang . '</small></p>' . "\n";

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
            . '<input type="hidden" name="answerCount" value="' . count ( $this->answerList ) . '" />' . "\n"
            . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />' . "\n"
            . claro_form_relay_context () . "\n";

        if ( $this->multipleAnswer )
        {
            // warn course admin that if the user checks all answer he will have the sum of all wieghting values
            $html .= '<p><small>' . get_lang ( 'Use negative weighting for incorrect choices to penalize a user that check all answers.' ) . '</small></p>' . "\n";
        }

        if ( !empty ( $exId ) && $askDuplicate )
        {
            $html .= '<p>' . html_ask_duplicate () . '</p>' . "\n";
        }

        $html .= '<table class="claroTable" >' . "\n"
            . '<thead>'
            . '<tr>' . "\n"
            . '<th>' . get_lang ( 'Expected choice' ) . '</th>' . "\n"
            . '<th>' . get_lang ( 'Answer' ) . '</th>' . "\n"
            . '<th>' . get_lang ( 'Comment' ) . '</th>' . "\n"
            . '<th>' . get_lang ( 'Weighting' ) . '</th>' . "\n"
            . '</tr>' . "\n"
            . '</thead>' . "\n";

        $i = 1;
        foreach ( $this->answerList as $answer )
        {
            $html .=
                '<tr>' . "\n"
                . '<td valign="top" align="center">';

            if ( $this->multipleAnswer )
            {
                $html .=
                    '<input name="correct_' . $i . '" id="correct_' . $i . '" '
                    . ( $answer[ 'correct' ] ? 'checked="checked"' : '')
                    . ' type="checkbox" value="1" />';
            }
            else
            {
                $html .=
                    '<input name="correct" id="correct_' . $i . '" '
                    . ( $answer[ 'correct' ] ? 'checked="checked"' : '')
                    . ' type="radio" value="correct_' . $i . '" />';
            }

            $html .=
                '</td>' . "\n"
                . '<td valign="top">'
                . claro_html_textarea_editor ( 'answer_' . $i, $answer[ 'answer' ], 10, 25, '', 'simple' )
                . '</td>' . "\n"
                . '<td>'
                . claro_html_textarea_editor ( 'comment_' . $i, $answer[ 'comment' ], 10, 25, '', 'simple' )
                . '</td>' . "\n"
                . '<td valign="top">'
                . '<input name="grade_' . $i . '" size="5" value="' . claro_htmlspecialchars ( $answer[ 'grade' ] ) . '" type="text" />'
                . '</td>' . "\n"
                . '</tr>' . "\n\n"
            ;

            $i++;
        }

        $html .=
            '<tr>' . "\n"
            . '<td colspan="4" align="center">'
            . '<input type="submit" name="cmdOk" value="' . get_lang ( 'Ok' ) . '" />&nbsp;&nbsp;'
            . '<input type="submit" name="cmdRemAnsw" value="' . get_lang ( 'Rem. answ.' ) . '" />&nbsp;&nbsp;'
            . '<input type="submit" name="cmdAddAnsw" value="' . get_lang ( 'Add answ.' ) . '" />&nbsp;&nbsp;'
            . claro_html_button ( Url::Contextualize ( './edit_question.php?exId=' . $exId . '&amp;quId=' . $this->questionId ), get_lang ( "Cancel" ) )
            . '</td>' . "\n"
            . '</tr>' . "\n\n"
            . '</table>' . "\n\n"
            . '</form>' . "\n\n";

        return $html;
    }

    /**
     * add empty answer at end of answerList
     *
     * @return boolean result of operation
     */
    public function addAnswer ()
    {
        // id is mainly use for creation on new answer,
        // will be overwritten by the id in db
        $addedAnswer = array (
            'id' => 0,
            'answer' => '',
            'correct' => 0,
            'grade' => 0,
            'comment' => '',
        );

        $this->answerList[ ] = $addedAnswer;
    }

    /**
     * remove empty answer ad end of answerList
     *
     * @return boolean result of operation
     */
    public function remAnswer ()
    {
        if ( count ( $this->answerList ) > 2 )
        {
            $removedAnswer = array_pop ( $this->answerList );

            if ( !is_null ( $removedAnswer ) )
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
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

        foreach ( $this->answerList as $answer )
        {
            if ( array_key_exists ( $answer[ 'id' ], $this->response ) )
            {
                $grade += $answer[ 'grade' ];

                // if not multiple we only need one response so get out of the loop
                if ( !$this->multipleAnswer )
                    break;
            }
        }

        // avoid returning negative val
        return max ( 0, $grade );
    }

    /**
     * get response of user via $_REQUEST and store it in object
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function extractResponseFromRequest ()
    {
        if ( $this->multipleAnswer )
        {
            foreach ( $this->answerList as $answer )
            {
                if ( isset ( $_REQUEST[ 'a_' . $this->questionId . '_' . $answer[ 'id' ] ] ) )
                {
                    $this->response[ $answer[ 'id' ] ] = true;
                }
            }
        }
        else
        {
            if ( isset ( $_REQUEST[ 'a_' . $this->questionId ] ) )
            {
                $this->response[ $_REQUEST[ 'a_' . $this->questionId ] ] = true;
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
        $grade = 0;

        foreach ( $this->answerList as $answer )
        {
            if ( $answer[ 'correct' ] )
            {
                $grade += $answer[ 'grade' ];
            }
        }
        return $grade;
    }

    /**
     * return a array with values needed for tracking
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return array
     */
    public function getTrackingValues ()
    {
        $values = array ( );

        foreach ( $this->answerList as $answer )
        {
            if ( array_key_exists ( $answer[ 'id' ], $this->response ) )
            {
                $values[ ] = $answer[ 'answer' ];
            }
        }
        return $values;
    }

}
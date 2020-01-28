<?php

// $Id: answer_truefalse.class.php 14144 2012-05-07 09:01:42Z zefredz $

/**
 * CLAROLINE
 *
 * @version 1.11 $Revision: 14144 $
 *
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */
class answerTrueFalse
{

    //----- in DB
    /**
     * @var $id id of answer, -1 if answer doesn't exist already
     */
    public $id;

    /**
     * @var $id id of question, -1 if answer doesn't exist already
     */
    public $questionId;

    /**
     * @var $trueFeedback feedback if user check 'true'
     */
    public $trueFeedback;

    /**
     * @var $trueScore score if user check 'true'
     */
    public $trueGrade;

    /**
     * @var $falseFeedback feedback if user check 'false'
     */
    public $falseFeedback;

    /**
     * @var $falseScore score if user check 'false'
     */
    public $falseGrade;

    /**
     * @var $attachment attached file
     */
    public $correctAnswer;


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
        $this->trueFeedback = '';
        $this->trueGrade = 0;
        $this->falseFeedback = '';
        $this->falseGrade = 0;

        $this->correctAnswer = '';

        $this->response = '';
        $this->errorList = array ( );

        $tbl_cdb_names = get_module_course_tbl ( array ( 'qwz_answer_truefalse' ), $course_id );
        $this->tblAnswer = $tbl_cdb_names[ 'qwz_answer_truefalse' ];
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
                    `trueFeedback`,
                    `trueGrade`,
                    `falseFeedback`,
                    `falseGrade`,
                    `correctAnswer`
            FROM `" . $this->tblAnswer . "`
            WHERE `questionId` = " . (int) $this->questionId;

        $data = claro_sql_query_get_single_row ( $sql );

        if ( !empty ( $data ) )
        {
            $this->id = (int) $data[ 'id' ];
            $this->trueFeedback = $data[ 'trueFeedback' ];
            $this->trueGrade = $data[ 'trueGrade' ];
            $this->falseFeedback = $data[ 'falseFeedback' ];
            $this->falseGrade = $data[ 'falseGrade' ];
            $this->correctAnswer = $data[ 'correctAnswer' ];

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
        if ( $this->id == -1 )
        {
            // insert
            $sql = "INSERT INTO `" . $this->tblAnswer . "`
                    SET `questionId` = " . (int) $this->questionId . ",
                        `trueFeedback` = '" . claro_sql_escape ( $this->trueFeedback ) . "',
                        `trueGrade` = '" . claro_sql_escape ( $this->trueGrade ) . "',
                        `falseFeedback` = '" . claro_sql_escape ( $this->falseFeedback ) . "',
                        `falseGrade` = '" . claro_sql_escape ( $this->falseGrade ) . "',
                        `correctAnswer` = '" . claro_sql_escape ( $this->correctAnswer ) . "'";

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
                    SET `trueFeedback` = '" . claro_sql_escape ( $this->trueFeedback ) . "',
                        `trueGrade` = '" . claro_sql_escape ( $this->trueGrade ) . "',
                        `falseFeedback` = '" . claro_sql_escape ( $this->falseFeedback ) . "',
                        `falseGrade` = '" . claro_sql_escape ( $this->falseGrade ) . "',
                        `correctAnswer` = '" . claro_sql_escape ( $this->correctAnswer ) . "'
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
     * @return object duplicated object
     */
    public function duplicate ( $duplicatedQuestionId )
    {
        $duplicated = new answerTrueFalse ( $duplicatedQuestionId );

        $duplicated->trueFeedback = $this->trueFeedback;
        $duplicated->trueGrade = $this->trueGrade;
        $duplicated->falseFeedback = $this->falseFeedback;
        $duplicated->falseGrade = $this->falseGrade;
        $duplicated->correctAnswer = $this->correctAnswer;

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
        $acceptedValues = array ( 'TRUE', 'FALSE' );

        if ( !in_array ( $this->correctAnswer, $acceptedValues ) )
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
        //-- feedbacks
        if ( isset ( $_REQUEST[ 'trueFeedback' ] ) )
            $this->trueFeedback = $_REQUEST[ 'trueFeedback' ];
        if ( isset ( $_REQUEST[ 'falseFeedback' ] ) )
            $this->falseFeedback = $_REQUEST[ 'falseFeedback' ];

        //-- correct answer
        if ( isset ( $_REQUEST[ 'correctAnswer' ] ) )
        {
            if ( $_REQUEST[ 'correctAnswer' ] == 'true' )
            {
                $this->correctAnswer = 'TRUE';
            }
            elseif ( $_REQUEST[ 'correctAnswer' ] == 'false' )
            {
                $this->correctAnswer = 'FALSE';
            }
        }

        //-- grades
        $trueGrade = (isset ( $_REQUEST[ 'trueGrade' ] )) ? castToFloat ( $_REQUEST[ 'trueGrade' ] ) : 0;
        $falseGrade = (isset ( $_REQUEST[ 'falseGrade' ] )) ? castToFloat ( $_REQUEST[ 'falseGrade' ] ) : 0;

        if ( $this->correctAnswer == 'TRUE' )
        {
            $this->trueGrade = abs ( $trueGrade ); // good answer cannot have negative score
            $this->falseGrade = 0 - abs ( $falseGrade ); // bad answer cannot have positive score
        }
        elseif ( $this->correctAnswer == 'FALSE' )
        {
            $this->trueGrade = 0 - abs ( $trueGrade ); // good answer cannot have negative score
            $this->falseGrade = abs ( $falseGrade ); // bad answer cannot have positive score
        }
        else
        {
            $this->trueGrade = abs ( $trueGrade );
            $this->falseGrade = abs ( $falseGrade );
        }

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
            $html =
                '<table width="100%">' . "\n\n"
                . '<tr>' . "\n"
                . '<td align="center" width="5%">' . "\n"
                . '<input name="a_' . $this->questionId . '" id="a_' . $this->questionId . '_true" value="TRUE" type="radio" '
                . ($this->response == 'TRUE' ? 'checked="checked"' : '')
                . '/>' . "\n"
                . '</td>' . "\n"
                . '<td width="95%">' . "\n"
                . '<label for="a_' . $this->questionId . '_true">' . get_lang ( 'True' ) . '</label>' . "\n"
                . '</td>' . "\n"
                . '</tr>' . "\n\n"
                . '<tr>' . "\n"
                . '<td align="center" width="5%">' . "\n"
                . '<input name="a_' . $this->questionId . '" id="a_' . $this->questionId . '_false" value="FALSE" type="radio" '
                . ($this->response == 'FALSE' ? 'checked="checked"' : '')
                . '/>' . "\n"
                . '</td>' . "\n"
                . '<td width="95%">' . "\n"
                . '<label for="a_' . $this->questionId . '_false">' . get_lang ( 'False' ) . '</label>' . "\n"
                . '</td>' . "\n"
                . '</tr>' . "\n\n"
                . '</table>' . "\n"
                . '<p><small>' . get_lang ( 'True/False' ) . '</small></p>' . "\n";
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

        if ( !empty ( $this->response ) )
        {
            $html .= '<input type="hidden" name="a_' . $this->questionId . '" value="' . $this->response . '" />' . "\n";
        }

        $html .= '<!-- ' . $this->questionId . '(end) -->' . "\n";
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
        $imgOnHtml = '<img src="' . get_icon_url ( 'radio_on' ) . '" alt="(X)" />';
        $imgOffHtml = '<img src="' . get_icon_url ( 'radio_off' ) . '" alt="( )" />';

        $html =
            '<table width="100%">' . "\n\n"
            . '<tr style="font-style:italic;font-size:small;">' . "\n"
            . '<td align="center" valign="top" width="5%">' . get_lang ( 'Your choice' ) . '</td>' . "\n"
            . '<td align="center" valign="top" width="5%">' . get_lang ( 'Expected choice' ) . '</td>' . "\n"
            . '<td valign="top" width="45%">' . get_lang ( 'Answer' ) . '</td>' . "\n"
            . '<td valign="top" width="45%">' . get_lang ( 'Comment' ) . '</td>' . "\n"
            . '</tr>' . "\n\n"
            . '<tr>' . "\n"
            . '<td align="center" width="5%">'
            . ( $this->response == 'TRUE' ? $imgOnHtml : $imgOffHtml )
            . '</td>' . "\n"
            . '<td align="center" width="5%">'
            . ( $this->correctAnswer == 'TRUE' ? $imgOnHtml : $imgOffHtml )
            . '</td>' . "\n"
            . '<td width="45%">'
            . get_lang ( 'True' )
            . '</td>' . "\n"
            . '<td width="45%">'
            . claro_parse_user_text ( $this->trueFeedback )
            . '</td>' . "\n"
            . '</tr>' . "\n\n"
            . '<tr>' . "\n"
            . '<td align="center" width="5%">'
            . ( $this->response == 'FALSE' ? $imgOnHtml : $imgOffHtml )
            . '</td>' . "\n"
            . '<td align="center" width="5%">'
            . ( $this->correctAnswer == 'FALSE' ? $imgOnHtml : $imgOffHtml )
            . '</td>' . "\n"
            . '<td width="45%">'
            . get_lang ( 'False' )
            . '</td>' . "\n"
            . '<td width="45%">'
            . claro_parse_user_text ( $this->falseFeedback )
            . '</td>' . "\n"
            . '</tr>' . "\n\n"
            . '</table>' . "\n"
            . '<p><small>' . get_lang ( 'True/False' ) . '</small></p>' . "\n";

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
    public function getFormHtml ( $exId = null, $askDuplicate = false )
    {
        $html =
            '<form method="post" action="./edit_answers.php?exId=' . $exId . '&amp;quId=' . $this->questionId . '">' . "\n"
            . '<input type="hidden" name="cmd" value="exEdit" />' . "\n"
            . '<input type="hidden" name="claroFormId" value="' . uniqid ( '' ) . '" />' . "\n"
            . claro_form_relay_context () . "\n"
            . '<table class="claroTable">' . "\n";

        if ( !empty ( $exId ) && $askDuplicate )
        {
            $html .= html_ask_duplicate ();
        }

        $html .= '<thead>' . "\n"
            . '<tr>' . "\n"
            . '<th>' . get_lang ( 'Expected choice' ) . '</th>' . "\n"
            . '<th>' . get_lang ( 'Answer' ) . '</th>' . "\n"
            . '<th>' . get_lang ( 'Comment' ) . '</th>' . "\n"
            . '<th>' . get_lang ( 'Weighting' ) . '</th>' . "\n"
            . '</tr>' . "\n"
            . '</thead>' . "\n"
            . '<tr>' . "\n"
            . '<td valign="top" align="center">'
            . '<input name="correctAnswer" id="trueCorrect" '
            . ($this->correctAnswer == "TRUE" ? 'checked="checked"' : '')
            . 'type="radio" value="true" />'
            . '</td>' . "\n"
            . '<td valign="top"><label for="trueCorrect">' . get_lang ( 'True' ) . '</label></td>' . "\n"
            . '<td>'
            . claro_html_textarea_editor ( 'trueFeedback', $this->trueFeedback, 10, 25, '', 'simple' )
            . '</td>' . "\n"
            . '<td valign="top"><input name="trueGrade" size="5" value="' . $this->trueGrade . '" type="text" /></td>' . "\n"
            . '</tr>' . "\n\n"
            . '<tr>' . "\n"
            . '<td valign="top" align="center">'
            . '<input name="correctAnswer" id="falseCorrect" '
            . ($this->correctAnswer == "FALSE" ? 'checked="checked"' : '')
            . 'type="radio" value="false" />'
            . '</td>' . "\n"
            . '<td valign="top"><label for="falseCorrect">' . get_lang ( 'False' ) . '</label></td>' . "\n"
            . '<td>'
            . claro_html_textarea_editor ( 'falseFeedback', $this->falseFeedback, 10, 25, '', 'simple' )
            . '</td>' . "\n"
            . '<td valign="top"><input name="falseGrade" size="5" value="' . $this->falseGrade . '" type="text" /></td>' . "\n"
            . '</tr>' . "\n\n"
            . '<tr>' . "\n"
            . '<td colspan="4" align="center">'
            . '<input type="submit" name="cmdOk" value="' . get_lang ( 'Ok' ) . '" />&nbsp;&nbsp;'
            . claro_html_button ( Url::Contextualize ( './edit_question.php?exId=' . $exId . '&amp;quId=' . $this->questionId ), get_lang ( "Cancel" ) )
            . '</td>' . "\n"
            . '</tr>' . "\n\n"
            . '</table>' . "\n\n"
            . '</form>';

        return $html;
    }

    /**
     * compute and return grade obtained from $this->response
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return float question grade
     * @desc return score of checked answer or 0 if nothing was checked
     */
    public function gradeResponse ()
    {
        if ( $this->response == 'TRUE' )
        {
            return $this->trueGrade;
        }
        elseif ( $this->response == 'FALSE' )
        {
            return $this->falseGrade;
        }
        else
        {
            return 0;
        }
    }

    /**
     * get response of user via $_REQUEST and store it in object
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return boolean result of operation
     */
    public function extractResponseFromRequest ()
    {
        $acceptedValues = array ( 'TRUE', 'FALSE' );

        if ( isset ( $_REQUEST[ 'a_' . $this->questionId ] )
            && in_array ( strtoupper ( $_REQUEST[ 'a_' . $this->questionId ] ), $acceptedValues )
        )
        {
            $this->response = strtoupper ( $_REQUEST[ 'a_' . $this->questionId ] );
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * compute grade of question from answer
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @return float question grade
     */
    public function getGrade ()
    {
        if ( $this->correctAnswer == 'TRUE' )
        {
            return $this->trueGrade;
        }
        elseif ( $this->correctAnswer == 'FALSE' )
        {
            return $this->falseGrade;
        }

        return 0;
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

        $acceptedValues = array ( 'TRUE', 'FALSE' );

        if ( in_array ( $this->response, $acceptedValues ) )
        {
            $values[ ] = $this->response;
        }

        return $values;
    }

}

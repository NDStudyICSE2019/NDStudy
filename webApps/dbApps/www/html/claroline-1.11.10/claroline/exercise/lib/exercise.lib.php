<?php // $Id: exercise.lib.php 14386 2013-02-08 13:03:23Z kitan1982 $

if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 14386 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */
 
/**
 * Build a list of available exercises that wil be used by claro_html_form_select to show a filter list
 * @param $excludeId an exercise id that doesn't have to be shown in  filter list
 * @return array 2d array where keys are the exercise name and value is the exercise id
 * @author Sebastien Piraux <pir@cerdecam.be>
 */
function get_filter_list($excludeId = '')
{
    $tbl_cdb_names = get_module_course_tbl( array( 'qwz_exercise' ), claro_get_current_course_id() );
    $tbl_quiz_exercise = $tbl_cdb_names['qwz_exercise'];

    $filterList[get_lang('All exercises')] = 'all';
    $filterList[get_lang('Orphan questions')] = 'orphan';
    
    // get exercise list
    $sql = "SELECT `id`, `title`
              FROM `".$tbl_quiz_exercise."`
              ORDER BY `title`";
    $exerciseList = claro_sql_query_fetch_all($sql);
    
    if( is_array($exerciseList) && !empty($exerciseList) )
    {
        foreach( $exerciseList as $anExercise )
        {
            if( $excludeId != $anExercise['id'] )
            {
                $filterList[$anExercise['title']] = $anExercise['id'];
            }
        }
    }
    $questionCategoryList = getQuestionCategoryList();
    // category
    foreach ($questionCategoryList as $category)
    {
        $filterList[get_lang('Category').' '.$category['title']] = 'categoryId'.$category['id'];
    }
    return $filterList;
}

/**
 * build a array making the correspondance between question type and its name
 *
 * @return array array where key is the type and value is the corresponding translation
 * @author Sebastien Piraux <pir@cerdecam.be>
 */
function get_localized_question_type()
{
    $questionType['MCUA']         = get_lang('Multiple choice (Unique answer)');
    $questionType['MCMA']         = get_lang('Multiple choice (Multiple answers)');
    $questionType['TF']         = get_lang('True/False');
    $questionType['FIB']         = get_lang('Fill in blanks');
    $questionType['MATCHING']     = get_lang('Matching');
    
    return $questionType;
}

/**
 * return the number of exercises using question $quId
 *
 * @param $quId requested question id
 * @return number of exercises using question $quId
 * @author Sebastien Piraux <pir@cerdecam.be>
 */
function count_exercise_using_question($quId)
{
    $tbl_cdb_names = get_module_course_tbl( array( 'qwz_rel_exercise_question' ), claro_get_current_course_id() );
    $tbl_quiz_rel_exercise_question = $tbl_cdb_names['qwz_rel_exercise_question'];
    
    $sql = "SELECT COUNT(`exerciseId`)
            FROM `".$tbl_quiz_rel_exercise_question."`
            WHERE `questionId` = '".(int) $quId."'";
    
    $exerciseCount = claro_sql_query_get_single_value($sql);
    
    if( ! $exerciseCount )  return 0;
    else                    return $exerciseCount;
}

function set_learning_path_progression($totalResult,$totalGrade,$timeToCompleteExe,$_uid)
{
    $tbl_cdb_names = get_module_course_tbl( array( 'lp_rel_learnPath_module', 'lp_user_module_progress' ), claro_get_current_course_id() );
    $tbl_lp_rel_learnPath_module = $tbl_cdb_names['lp_rel_learnPath_module'];
    $tbl_lp_user_module_progress = $tbl_cdb_names['lp_user_module_progress'];
    
    
    // update raw in DB to keep the best one, so update only if new raw is better  AND if user NOT anonymous
    if( $_uid )
    {
        // exercices can have a negative score, but we don't accept that in LP
        // so if totalScore is negative use 0 as result
        $totalResult = max($totalResult, 0);

        if ( $totalGrade != 0 )
        {
            $newRaw = @round($totalResult/$totalGrade*100);
        }
        else
        {
            $newRaw = 0;
        }

        $scoreMin = 0;
        $scoreMax = $totalGrade;
        $scormSessionTime = seconds_to_scorm_time($timeToCompleteExe);
        
        // need learningPath_module_id and raw_to_pass value
        $sql = "SELECT LPM.`raw_to_pass`, LPM.`learnPath_module_id`, UMP.`total_time`, UMP.`raw`
                  FROM `".$tbl_lp_rel_learnPath_module."` AS LPM, `".$tbl_lp_user_module_progress."` AS UMP
                 WHERE LPM.`learnPath_id` = '".(int)$_SESSION['path_id']."'
                   AND LPM.`module_id` = '".(int)$_SESSION['module_id']."'
                   AND LPM.`learnPath_module_id` = UMP.`learnPath_module_id`
                   AND UMP.`user_id` = ".(int) $_uid;
                   
        $lastProgression = claro_sql_query_get_single_row($sql);

        if( $lastProgression )
        {
            // build sql query
            $sql = "UPDATE `".$tbl_lp_user_module_progress."` SET ";
            // if recorded score is more than the new score => update raw, credit and status

            if( $lastProgression['raw'] < $totalResult )
            {
                // update raw
                $sql .= "`raw` = ".$totalResult.",";
                // update credit and statut if needed ( score is better than raw_to_pass )
                if ( $newRaw >= $lastProgression['raw_to_pass'])
                {
                    $sql .= "    `credit` = 'CREDIT',
                                 `lesson_status` = 'PASSED',";
                }
                else // minimum raw to pass needed to get credit
                {
                    $sql .= "    `credit` = 'NO-CREDIT',
                                `lesson_status` = 'FAILED',";
                }
            }// else don't change raw, credit and lesson_status

            // default query statements
            $sql .= "    `scoreMin`         = " . (int)$scoreMin . ",
                        `scoreMax`         = " . (int)$scoreMax . ",
                        `total_time`    = '".addScormTime($lastProgression['total_time'], $scormSessionTime)."',
                        `session_time`    = '".$scormSessionTime."'
                     WHERE `learnPath_module_id` = ". (int)$lastProgression['learnPath_module_id']."
                       AND `user_id` = " . (int)$_uid . "";
            
            // Generate an event to notify that the exercise has been completed
            $learnPathEventArgs = array( 'userId' => (int)$_uid,
                                         'courseCode' => claro_get_current_course_id(),
                                         'scoreRaw' => (int)$totalResult,
                                         'scoreMin' => (int)$scoreMin,
                                         'scoreMax' => (int)$scoreMax,
                                         'sessionTime' => $scormSessionTime,
                                         'learnPathModuleId' => (int)$lastProgression['learnPath_module_id'],
                                         'type' => "update"
                                       );
            if ( $newRaw >= $lastProgression['raw_to_pass'] )
            {
                $learnPathEventArgs['status'] = "PASSED";
            }
            else
            {
                $learnPathEventArgs['status'] = "FAILED";
            }
            $learnPathEvent = new Event( 'lp_user_module_progress_modified', $learnPathEventArgs );
            EventManager::notify( $learnPathEvent );
    
            return claro_sql_query($sql);
        }
        else
        {
            return false;
        }
    }
}


/**
 * return html required to display the required form elements to ask the user if the question must be modified in
 * all exercises or only the current one
 *
 * @return string html code
 * @author Sebastien Piraux <pir@cerdecam.be>
 */
function html_ask_duplicate()
{
    $html = '<strong>' . get_lang('This question is used in several exercises.') . '</strong><br />' . "\n"
    .    '<input type="radio" name="duplicate" id="doNotDuplicate" value="false"';
    
    if( !isset($_REQUEST['duplicate']) || $_REQUEST['duplicate'] != 'true')
    {
        $html .= ' checked="checked" ';
    }
    
    $html .= '/>'
    .    '<label for="doNotDuplicate">' . get_lang('Modify it in all exercises') . '</label><br />' . "\n"
    .    '<input type="radio" name="duplicate" id="duplicate" value="true"';
    
    if( isset($_REQUEST['duplicate']) && $_REQUEST['duplicate'] == 'true')
    {
        $html .= ' checked="checked" ';
    }
    
    $html .= '/>'
    .    '<label for="duplicate">' . get_lang('Modify it only in this exercise') . '</label>' . "\n";
    
    return $html;
}

/**
 * cast $value to a float with max 2 decimals
 *
 * @param string string to cast
 * @return string html code
 * @author Sebastien Piraux <pir@cerdecam.be>
 */
function castToFloat($value)
{
    // use dot as decimal separator
    $value = (float) str_replace(',','.',$value);
    // round to max 2 decimals
    $value = round($value*100)/100;
    
    return $value;
}


/**
 * Record result of user when an exercice was done
 * @param exercise_id ( id in courseDb exercices table )
 * @param result ( score @ exercice )
 * @param weighting ( higher score )
 *
 * @return inserted id or false if the query cannot be done
 *
 * @author Sebastien Piraux <seb@claroline.net>
*/
function track_exercice($exercise_id, $score, $weighting, $time, $uid = "")
{
    // get table names
    $tblList = get_module_course_tbl( array( 'qwz_tracking' ), claro_get_current_course_id() );
    $tbl_qwz_tracking = $tblList['qwz_tracking'];

    if( $uid != "" )
    {
        $user_id = "'".(int) $uid."'";
    }
    else // anonymous
    {
        $user_id = "NULL";
    }

    $sql = "INSERT INTO `".$tbl_qwz_tracking."`
               SET `user_id` = ".$user_id.",
                   `exo_id` = '".(int) $exercise_id."',
                   `result` = '".(float) $score."',
                   `weighting` = '".(float) $weighting."',
                   `date` = FROM_UNIXTIME(".time()."),
                   `time` = '".(int) $time."'";


    return claro_sql_query_insert_id($sql);
}

/**
 * Record result of user when an exercice was done
 * @param exerciseTrackId id in qwz_tracking table
 * @param questionId id of the question
 * @param values array with user answers
 * @param questionResult result of this question
 *
 * @author Sebastien Piraux <seb@claroline.net>
*/
function track_exercise_details($exerciseTrackId, $questionId, $values, $questionResult)
{
    // get table names
    $tblList = get_module_course_tbl( array( 'qwz_tracking_questions', 'qwz_tracking_answers' ), claro_get_current_course_id() );
    $tbl_qwz_tracking_questions  = $tblList['qwz_tracking_questions'];
    $tbl_qwz_tracking_answers  = $tblList['qwz_tracking_answers'];

    // add the answer tracking informations
    $sql = "INSERT INTO `".$tbl_qwz_tracking_questions."`
               SET `exercise_track_id` = ".(int) $exerciseTrackId.",
                   `question_id` = '".(int) $questionId."',
                   `result` = '".(float) $questionResult."'";

    $details_id = claro_sql_query_insert_id($sql);

    // check if previous query succeed to add answers
    if( $details_id && is_array($values) )
    {
        // add, if needed, the different answers of the user
        // one line by answer
        // each entry of $values should be correctly formatted depending on the question type

        foreach( $values as $answer )
        {
            $sql = "INSERT INTO `".$tbl_qwz_tracking_answers."`
                       SET `details_id` =  ". (int)$details_id.",
                           `answer` = '".claro_sql_escape($answer)."'";

            claro_sql_query($sql);
        }
    }
    return 1;
}

function change_img_url_for_pdf( $str )
{
    $pattern = '/(.*?)<img (.*?)src=(\'|")(.*?)url=(.*?)=&(.*?)(\'|")(.*?)>(.*?)$/is';
    
    if( ! preg_match( $pattern, urldecode( $str ), $matches) )
    {
        return $str;
    }
    
    if( count($matches) != 10 )
    {
        return $str;
    }
    
    if( is_download_url_encoded( $matches[5] ) )
    {
      $matches[5] = download_url_decode( $matches[5] );
    }
    $matches[5] = get_conf('rootWeb') . 'courses/' . claro_get_current_course_id() . '/document' . $matches[5];
    $replace = $matches[1].'<img ' . $matches[2] . ' src="' . $matches[5] .'" ' . $matches[8] . '>' . $matches[9];
    
    return $replace;
}

function getQuestionCategoryList()
{
    $categoryList = array();
    $tbl_cdb_names = get_module_course_tbl( array('qwz_questions_categories' ),  claro_get_current_course_id()  );
    $tblQuestionCategories = $tbl_cdb_names['qwz_questions_categories'];
    $query = "SELECT `id`, `title` FROM `".$tblQuestionCategories."`
               ORDER BY `title`";
    if( claro_sql_query($query) )
    {
        return claro_sql_query_fetch_all_rows($query);
    }
    else
    {
         return $categoryList;
    }
}


function getCategoryTitle( $categoryId )
{
   	$tbl_cdb_names = get_module_course_tbl( array('qwz_questions_categories' ),  claro_get_current_course_id()  );
	$tblQuestionCategories = $tbl_cdb_names['qwz_questions_categories'];
	$sql = "SELECT `title` FROM  `".$tblQuestionCategories. "` WHERE `id`= '".(int)$categoryId."'";
	$data = claro_sql_query_get_single_row($sql);
    
    if( !empty($data) )
    {
    	// from query
        return $data['title'];
    }
    else
    {
    	return '';
    }
}

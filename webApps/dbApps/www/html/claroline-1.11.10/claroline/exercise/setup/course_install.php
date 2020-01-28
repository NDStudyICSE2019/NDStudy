<?php // $Id: course_install.php 13708 2011-10-19 10:46:34Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

if ( count( get_included_files() ) == 1 ) die( '---' );

############################## EXERCISES #######################################

$moduleWorkingDirectory = get_path('coursesRepositorySys') . $courseDirectory . '/exercise';

if ( ! claro_mkdir($moduleWorkingDirectory, CLARO_FILE_PERMISSIONS,true) )
{
    return claro_failure::set_failure(
            get_lang( 'Unable to create folder %folder'
                ,array( '%folder' => $moduleWorkingDirectory ) ) );
}

if ( get_conf('fill_course_example',true) )
{
    // Exercise
    $TABLEQWZEXERCISE   = $moduleCourseTblList['qwz_exercise'];
    $TABLEQWZQUESTION   = $moduleCourseTblList['qwz_question'];
    $TABLEQWZRELEXERCISEQUESTION = $moduleCourseTblList['qwz_rel_exercise_question'];
    $TABLEQWZANSWERMULTIPLECHOICE = $moduleCourseTblList['qwz_answer_multiple_choice'];
    
    // create question
    $questionId = claro_sql_query_insert_id("INSERT INTO `".$TABLEQWZQUESTION."` (`title`, `description`, `attachment`, `type`, `grade`)
        VALUES
        ('".claro_sql_escape(get_lang('sampleQuizQuestionTitle'))."', '".claro_sql_escape(get_lang('sampleQuizQuestionText'))."', '', 'MCMA', '10' )");

    claro_sql_query("INSERT INTO `".$TABLEQWZANSWERMULTIPLECHOICE."`(`questionId`,`answer`,`correct`,`grade`,`comment`)
        VALUES
        ('".$questionId."','".claro_sql_escape(get_lang('sampleQuizAnswer1'))."','0','-5','".claro_sql_escape(get_lang('sampleQuizAnswer1Comment'))."'),
        ('".$questionId."','".claro_sql_escape(get_lang('sampleQuizAnswer2'))."','0','-5','".claro_sql_escape(get_lang('sampleQuizAnswer2Comment'))."'),
        ('".$questionId."','".claro_sql_escape(get_lang('sampleQuizAnswer3'))."','1','5','".claro_sql_escape(get_lang('sampleQuizAnswer3Comment'))."'),
        ('".$questionId."','".claro_sql_escape(get_lang('sampleQuizAnswer4'))."','1','5','".claro_sql_escape(get_lang('sampleQuizAnswer4Comment'))."')");

    // create exercise
    $exerciseId = claro_sql_query_insert_id("INSERT INTO `".$TABLEQWZEXERCISE."` (`title`, `description`, `visibility`, `startDate`, `endDate`, `quizEndMessage`)
        VALUES
        ('".claro_sql_escape(get_lang('sampleQuizTitle'))."', '".claro_sql_escape(get_lang('sampleQuizDescription'))."', 'INVISIBLE', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), '' )");
    
    // put question in exercise
    claro_sql_query("INSERT INTO `".$TABLEQWZRELEXERCISEQUESTION."` VALUES ($exerciseId, $questionId, 1)");
}

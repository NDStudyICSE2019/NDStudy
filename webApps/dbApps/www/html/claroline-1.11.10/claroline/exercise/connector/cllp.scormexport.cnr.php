<?php // $Id: cllp.scormexport.cnr.php 14315 2012-11-08 14:51:17Z zefredz $

/**
 * CLAROLINE
 *
 * @version     0.1 $Revision: 14315 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLQWZ
 * @author      Dimitri Rambout
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

/**
 * Class needed to export the content of the module
 * 1) the method prepareFiles will copy all the needed files in the specied directory
 * 2) the method prepareManifestResource create a string like <resource></resource> with the correct
 * attribute based on the item
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 */
class CLQWZ_ScormExport extends PathScormExport
{
  /**
   * @var string Error returned by a method
   */
  private $error;
  /**
   * @var  string $scrDirDocument path to the documents
   */
  private $srcDirDocument;
  
  /**
   * Constructor
   *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
   */
  public function __construct()
  {
    $this->srcDirDocument = get_path('coursesRepositorySys') . claro_get_course_path() . '/document';
  }
  
  /**
   * Create files (quiz) needed in the export of this module
   *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
   * @param int $quizId id of the Quiz
   * @param object $item item of the path
   * @param string $destDir path when the files need to be copied
   * @param int $deepness deepness of the destinationd directory
   * @return boolean
   */
  public function prepareFiles( $quizId, &$item, $destDir, $deepness )
  {
    $completionThresold = $item->getCompletionThreshold();
    if( empty($completionThresold) )
    {
        $completionThresold = 50;
    }
    $quizId = (int) $quizId;
    
    $quiz = new Exercise();
    if( ! $quiz->load( $quizId ) )
    {
        $this->error[] = get_lang('Unable to load the exercise');
        return false;
    }
    
    $deep = '';
    if( $deepness )
    {
        for($i = $deepness; $i > 0; $i--)
        {
            $deep .=' ../';
        }
    }
    
    // Generate standard page header
    $pageHeader = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
    <title>'.$quiz->getTitle().'</title>
    <meta http-equiv="expires" content="Tue, 05 DEC 2000 07:00:00 GMT">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Content-Type" content="text/HTML; charset='.get_locale('charset').'"  />

    <link rel="stylesheet" type="text/css" href="' . $deep . get_conf('claro_stylesheet') . '/main.css" media="screen, projection, tv" />
    <script language="javascript" type="text/javascript" src="' . $deep . 'js/jquery.js"></script>
    <script language="javascript" type="text/javascript" src="' . $deep . 'js/claroline.js"></script>
    <script language="javascript" type="text/javascript" src="' . $deep . 'js/claroline.ui.js"></script>

    <script language="javascript" type="text/javascript" src="' . $deep . 'js/APIWrapper.js"></script>
    <script language="javascript" type="text/javascript" src="' . $deep . 'js/connector13.js"></script>
    <script language="javascript" type="text/javascript" src="' . $deep . 'js/scores.js"></script>
    </head>
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
    // TODO La variable $fillAnswerList n'apparaît qu'une fois
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

        $pageBody .= '<thead>' . "\n"
        .    '<tr>' . "\n"
        .    '<th>'.get_lang('Question').' '.$questionCount.'</th>' . "\n"
        .    '</tr>' . "\n"
        .    '</thead>' . "\n";

        $pageBody .=
          '<tr>' . "\n"
        . '<td>' . "\n"
        . $scormQuestion->export() . "\n"
        . '</td>' . "\n"
        . '</tr>' . "\n";
    }
    
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
        var raw_to_pass = ' . $completionThresold . ';
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
                var oldScore = doLMSGetValue("cmi.score.raw");
    
                doLMSSetValue("cmi.score.max", weighting);
                doLMSSetValue("cmi.score.min", 0);
    
                computeTime();
    
                if (score > oldScore) // Update only if score is better than the previous time.
                {
                    doLMSSetValue("cmi.raw", rawScore);
                }
                
                var oldStatus = doLMSGetValue( "cmi.completion_status" )
                if (score >= raw_to_pass)
                {
                    doLMSSetValue("cmi.completion_status", "completed");
                }
                else if (oldStatus != "completed" ) // If passed once, never mark it as failed.
                {
                    doLMSSetValue("cmi.completion_status", "failed");
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
    
    if (! $f = fopen($destDir . '/' . $filename, 'w') )
    {
        $this->error = get_lang('Unable to create file : ') . $filename;
        return false;
    }
    fwrite($f, $pageContent);
    fclose($f);
    
    
    return true;
  }
  
  /**
   * Create a resource for the manifest
   *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
   * @param array $item item's data
   * @param string $destDir
   * @param object $locator locator of the file
   */
  public function prepareManifestResources( &$item, $destDir, &$locator )
  {
    $resource = '<resource identifier="R_'. $item['id'] .'" type="webcontent"  adlcp:scormType="sco" href="'. $destDir .'quiz_'.$locator->getResourceId().'.html">
        <file href="'. $destDir .'quiz_'.$locator->getResourceId().'.html" />
    </resource>
    ';
    
    return $resource;
  }
  /**
   * Return the error
   *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
   * @return string $error
   */
  public function getError()
  {
    return $this->error;
  }
}

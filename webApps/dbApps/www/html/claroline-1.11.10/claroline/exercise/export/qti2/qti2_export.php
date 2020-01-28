<?php // $Id: qti2_export.php 13708 2011-10-19 10:46:34Z abourguignon $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * @version 1.8 $Revision: 13708 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 *
 */

require_once dirname(__FILE__) . '/qti2_classes.php';
/*--------------------------------------------------------
      Classes
  --------------------------------------------------------*/

/**
 * An IMS/QTI item. It corresponds to a single question.
 * This class allows export from Claroline to IMS/QTI2.0 XML format of a single question.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * @warning Attached files are NOT exported.
 */
class ImsAssessmentItem
{
    var $question;
    var $question_ident;
    var $answer;

    /**
     * Constructor.
     *
     * @param $question The Question object we want to export.
     */
     function ImsAssessmentItem($question)
     {
        $this->question = $question;
        $this->answer = $question->answer;
        $this->questionIdent = 'QST_' . (int) $question->getRank();
     }

     /**
      * Start the XML flow.
      *
      * This opens the <item> block, with correct attributes.
      *
      */
      function start_item()
      {
        return '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p0"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p0 imsqti_v2p0.xsd"
                    identifier="'.$this->questionIdent.'"
                    title="'.xmlentities($this->question->getTitle()).'">'."\n";
      }

      /**
       * End the XML flow, closing the </item> tag.
       *
       */
      function end_item()
      {
        return "</assessmentItem>\n";
      }

     /**
      * Start the itemBody
      *
      */
     function start_item_body()
     {
        return '  <itemBody>' . "\n";
     }

     /**
      * Add oject container for attached file
      *
      */
     function object_attached_file()
     {
        $attachment = $this->question->getAttachment();

        if( !empty($attachment) )
        {
            $mimeType = get_mime_on_ext($attachment);
            return '    <object type="'.$mimeType.'" data="'.xmlentities($attachment).'" />' . "\n";
        }
        return '';
     }
     /**
      * End the itemBody part.
      *
      */
     function end_item_body()
     {
        return "  </itemBody>\n";
     }

     /**
      * add the response processing template used.
      *
      */

      function add_response_processing()
      {
          return '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p0/rptemplates/map_response"/>' . "\n";
      }


     /**
      * Export the question as an IMS/QTI Item.
      *
      * This is a default behaviour, some classes may want to override this.
      *
      * @param $standalone: Boolean stating if it should be exported as a stand-alone question
      * @return A string, the XML flow for an Item.
      */
     function export($standalone = False)
     {

        $head = $foot = "";

        if( $standalone )
        {
            $head = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n";
        }

        $out = $head
               . $this->start_item()
                 .$this->answer->qti2ExportResponsesDeclaration($this->questionIdent)
                 . $this->start_item_body()
                   . $this->object_attached_file()
                   . $this->answer->qti2ExportResponses($this->questionIdent, $this->question->description)
                 . $this->end_item_body()
               . $this->add_response_processing()
               . $this->end_item()
             . $foot;

         return claro_utf8_encode($out);
     }
}


/*--------------------------------------------------------
      Functions
  --------------------------------------------------------*/

/**
 * Send a complete exercise in IMS/QTI format, from its ID
 *
 * @param int $exerciseId The exercise to exporte
 * @param boolean $standalone Wether it should include XML tag and DTD line.
 * @return The XML as a string, or an empty string if there's no exercise with given ID.
 */
function export_exercise($exerciseId, $standalone = true)
{
    $exercise = new Exercise();
    if (! $exercise->load($exerciseId))
    {
        return '';
    }
    $ims = new ImsSection($exercise);
    $xml = $ims->export($standalone);
    return $xml;
}

/**
 * Send a zip file for download,
 *
 * @param string name of the downloaded file (without extension)
 * @param
 *
 * @return boolean result of operation
 */
function sendZip($archiveName, $archiveContent, $removedPath)
{
    // TODO find a better solution for removedPath
    if( !is_array($archiveContent) || empty($archiveContent) )
    {
        return false;
    }

    $downloadPlace = get_path('rootSys') . get_conf('tmpPathSys');
    $downloadArchivePath = $downloadPlace.''.uniqid('').'.zip';
    $downloadArchiveName = empty($archiveName) ? 'archive' : $archiveName;
    $downloadArchiveName = str_replace(',', '_', replace_dangerous_char($downloadArchiveName));
    $downloadArchiveName = $downloadArchiveName . '.zip';

    $downloadArchive     = new PclZip($downloadArchivePath);

    $downloadArchive->add($archiveContent, PCLZIP_OPT_REMOVE_PATH, $removedPath);

    if( file_exists($downloadArchivePath) )
    {
        if( claro_send_file($downloadArchivePath, $downloadArchiveName) )
        {
            unlink($downloadArchivePath);
            return true;
        }
        else
        {
            unlink($downloadArchivePath);
            return false;
        }
    }
    else
    {
        return false;
    }
}

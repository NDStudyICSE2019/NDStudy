<?php

/**
 * CLAROLINE
 *
 * @version 0.1 $Revision: 13708 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @package CLDOC
 *
 * @author Dimitri Rambout
 *
 */

/**
 * Class needed to export the content of the module
 * 1) the method prepareFiles will copy all the needed files in the specied directory
 * 2) the method prepareManifestResource create a string like <resource></resource> with the correct
 * attribute based on the item
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 */

class CLDOC_ScormExport extends PathScormExport
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
   * Copy files needed in the export for this module
   *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
   * @param string $docId name of the document
   * @param object $item item of the path
   * @param string $destDir path when the files need to be copied
   * @param int $deepness deepness of the destinationd directory
   * @return boolean
   */
  public function prepareFiles( $docId, &$item, $destDir, $deepness )
  {
    $completionThresold = $item->getCompletionThreshold();
    if( empty($completionThresold) )
    {
        $completionThresold = 50;
    }
    
    $docPath = $this->srcDirDocument . '/' . $docId;
    
    if( ! file_exists( $docPath ) )
    {
        $this->error = get_lang( 'The file %file doesn\'t exist', array( '%file' => $docId ) );
        return false;
    }
    
    if( ! claro_copy_file( $docPath, $destDir ) )
    {
        $this->error = get_lang( 'Unable to copy file %file in temporary directory', array( '%file' => $docId ) );
        return false;
    }
    
    $frameName = 'frame_for_' . $item->getId() . '.html';
    
    if( ! parent::createFrameFile( $frameName, $docId, $item, $destDir, $deepness ) )
    {
        $this->error = get_lang( 'Unable to create frame for document %file.', array( '%file' => $docId ) );
        return false;
    }
    
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
    $resource = '<resource identifier="R_' . $item['id'] . '" type="webcontent" adlcp:scormType="sco" href="'. $destDir .'frame_for_'.$item['id'].'.html">
      <file href="'. $destDir .'frame_for_'.$item['id'].'.html" />
      <file href="'. $destDir .'sub_frame_for_'.$item['id'].'.html" />
      <file href="'. $destDir . $locator->getResourceId().'" />
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

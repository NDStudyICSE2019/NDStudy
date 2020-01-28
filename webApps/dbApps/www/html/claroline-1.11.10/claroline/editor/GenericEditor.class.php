<?php // $Id: GenericEditor.class.php 14642 2014-01-20 07:56:18Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );

/**
 * CLAROLINE
 *
 * @version     $Revision: 14642 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @see         http://www.claroline.net/wiki/config_def/
 * @package     EDITOR
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <pir@cerdecam.be>
 */
 
/**
 * Class to manage htmlarea overring simple textarea html
 * @package EDITOR
 */
class GenericEditor
{
    /**
     * @var $name content for attribute name and id of textarea
     */
    var $name;

    /**
     * @var $content content of textarea
     */
    var $content;
    
    /**
     * @var $rows number of lines of textarea
     */
    var $rows;

    /**
     * @var $cols number of cols of textarea
     */
    var $cols;

    /**
     * @var $optAttrib additionnal attributes that can be added to textarea
     */
    var $optAttrib;

    /**
     * @var $webPath path to access via the web to the directory of the editor
     */
    var $webPath;

    function GenericEditor( $name,$content,$rows,$cols,$optAttrib,$webPath )
    {
        $this->name = $name;
        $this->content = $content;
        $this->rows = $rows;
        $this->cols = $cols;
        $this->optAttrib = $optAttrib;
        $this->webPath = $webPath;
    }


    /**
     * Returns the html code needed to display an advanced (default) version of the editor
     * ! Needs to be overloaded by extending classes
     * $returnString .= $this->getTextArea();
     * @return string html code needed to display an advanced (default) version of the editor
       */
    function getAdvancedEditor()
    {
        return $this->getTextArea();
    }

    /**
     * Returns the html code needed to display a simple version of the editor
     * ! Needs to be overloaded by extending classes
     * @return string html code needed to display a simple version of the editor
       */
    function getSimpleEditor()
    {
        return $this->getTextArea();
    }
    
    /**
     * Returns the html code needed to display the default textarea
     *
     * @access private
     * @return string html code needed to display the default textarea
     */
    function getTextArea($class = '')
    {
        $textArea = "\n"
        .    '<textarea '
        .    'id="'.$this->name.'" '
        .    'name="'.$this->name.'" '
        .    'style="width:100%" ';

        if( !empty($class) ) $textArea .= 'class="'.$class.'" ';
                
        $textArea .= 'rows="'.$this->rows.'" '
        .    'cols="'.$this->cols.'" '
        .   $this->optAttrib.' >'
        .   claro_htmlspecialchars($this->content)
        .    '</textarea>'."\n";

        return $textArea;
    }
}

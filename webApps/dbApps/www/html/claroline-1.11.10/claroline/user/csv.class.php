<?php // $Id: csv.class.php 14092 2012-03-21 15:12:47Z zefredz $

/**
 * CLAROLINE
 *
 * @version     1.11 $Revision: 14092 $
 * @copyright   (c) 2001-2012, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLUSR
 * @author      Claro Team <cvs@claroline.net>
 *
 * @deprecated  This php class just manages CSV for users and should be
 *              renamed (at least); it's not a generic CSV class.
 *              Plese, use the generic CsvExporter class instead.  You'll find
 *              it there: claroline/inc/lib/csvexporter.class.php.
 */

FromKernel::Uses( 'password.lib' );

class Csv
{
    /**
     * @var $fieldSeparator field separator
     */
    protected $fieldSeparator;
    
    /**
     * @var $enclosedBy field enclosed by
     */
    protected $enclosedBy;
    
    /**
     * @var $fieldName
     */
    protected $fileName;
    
    /**
     * @var $csvContent array of rows;
     */
    protected $csvContent;
    
    /**
     * @var $firstLine
     */
    protected $firstLine;
    
    
    /**
     * Constructor.
     *
     * @param $fieldSeparator field separator
     * @param $enclosedBy fields encolsed by
     */
    public function __construct($fieldSeparator = ',', $enclosedBy = '"')
    {
        $this->fieldSeparator = $fieldSeparator;
        $this->enclosedBy = $enclosedBy;
        $this->csvContent = array();
    }
    
    
    /**
     * Load the content of a csv file in the class $csvContent var.
     *
     * @param $fileName name of the csv file
     * @return boolean
     */
    public function load( $fileName )
    {
        $this->fileName = $fileName;
        
        if( !is_file($this->fileName) )
        {
            return false;
        }
        
        if( !$handle = fopen($this->fileName, "r") )
        {
            return false;
        }
        
        $this->firstLine = fgets( $handle);
        
        rewind( $handle);
        
        $content = array();
        
        while( ( $row = fgetcsv( $handle, 0, $this->fieldSeparator, $this->enclosedBy) ) !== false)
        {
            $content[] = $row;
        }
        
        $this->setCSVContent( $content );
        
        return true;
    }
    
    
    public function getFirstLine()
    {
        return $this->firstLine;
    }
    
    
    /**
     * Set the content of csvContent.
     *
     * @param $content
     */
    public function setCSVContent( $content )
    {
        $this->csvContent = $content;
    }
    
    
    /**
     * Get the content of csvContent.
     *
     * @return $csvContent array of rows
     */
    public function getCSVContent()
    {
        return $this->csvContent;
    }
    
    
    /**
     * Alias for the getCSVcontent method.
     *
     * @deprecated
     */
    public function export()
    {
        return $this->getCSVcontent();
    }
}
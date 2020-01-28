<?php // $Id: csv.class.php 14406 2013-02-25 07:27:37Z zefredz $

FromKernel::uses('csvexporter.class');

/**
 * CLAROLINE
 *
 * CSV class.
 *
 * This class will be correctly implemented soon, within CsvExporter and
 * CsvImporter classes.
 * Meanwhile, it will just act as a patch solution.
 *
 * @version     Claroline 1.11 $Revision: 14406 $
 * @copyright   (c) 2001-2013, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     KERNEL
 * @author      Claro Team <cvs@claroline.net>
 */

class CsvRecordlistExporter // extends CsvExporter
{
    public $recordList = array();
    
    private $csvExporter;
    
    public function __construct($delimiter = ',', $quote = '"')
    {
        $this->csvExporter = new CsvExporter($delimiter, $quote);
    }
    
    /**
     * Export internal record list to csv
     * @return string
     */
    public function export()
    {
        return $this->csvExporter->export($this->recordList);
    }
}
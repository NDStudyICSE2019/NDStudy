<?php // $Id: courselist.lib.php 13685 2011-10-14 12:42:41Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLTRACK
 * @author      Claro Team <cvs@claroline.net>
 * @author      Sebastien Piraux <pir@cerdecam.be>
 */

/**
 * This class defines main methods used in the tracking renderers for
 * course related tracking data
 *
 * @abstract
 */
abstract class CourseTrackingRenderer
{
    private $courseId;
    
    public function __contruct($courseId){}
    
    public function render()
    {
        $html = '<div class="statBlock">' . "\n"
        .    ' <h3 class="blockHeader">' . "\n"
        .    $this->renderHeader()
        .    ' </h3>' . "\n"
        .    ' <div class="blockContent">' . "\n"
        .    $this->renderContent()
        .    ' </div>' . "\n"
        .    ' <div class="blockFooter">' . "\n"
        .    $this->renderFooter()
        .    ' </div>' . "\n"
        .    '</div>' . "\n";
        
        return $html;
    }
    
    
    /**
     * Render part of display (header) used in render class
     * @abstract
     */
    abstract protected function renderHeader();
    
    /**
     * Render part of display (content) used in render class
     * @abstract
     */
    abstract protected function renderContent();
    
    /**
     * Render part of display(footer) used in render class
     * @abstract
     */
    abstract protected function renderFooter();
}

/**
 * This class defines main methods used in the tracking renderers for
 * user related tracking data in course
 *
 * @abstract
 */
abstract class UserTrackingRenderer
{
    private $courseId;
    private $userId;
    
    public function __contruct($courseId, $userId){}
    
    public function render()
    {
        $html = '<div class="statBlock">' . "\n"
        .    ' <h3 class="blockHeader">' . "\n"
        .    $this->renderHeader()
        .    ' </h3>' . "\n"
        .    ' <div class="blockContent">' . "\n"
        .    $this->renderContent()
        .    ' </div>' . "\n"
        .    ' <div class="blockFooter">' . "\n"
        .    $this->renderFooter()
        .    ' </div>' . "\n"
        .    '</div>' . "\n";
        
        return $html;
    }
    
    /**
     * Render part of display (header) used in render class
     * @abstract
     */
    abstract protected function renderHeader();
    
    /**
     * Render part of display (header) used in render class
     * @abstract
     */
    abstract protected function renderContent();
    
    /**
     * Render part of display (header) used in render class
     * @abstract
     */
    abstract protected function renderFooter();
}

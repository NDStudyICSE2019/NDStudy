<?php // $Id: coursesearchbox.class.php 14576 2013-11-07 09:27:59Z zefredz $

/**
 * CLAROLINE
 *
 * Course search box Class.
 *
 * @version     $Revision: 14576 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 * @author      Antonin Bourguignon <antonin.bourguignon@claroline.net>
 * @since       1.11
 *
 * @todo        while we browse through platform's categories, the search box
 *              doesn't take the current category in account for its researches.
 * @todo        this class deserves to get splitted into 2 parts (at least), 
 *              including a view part with the render and view options get and 
 *              set methods.
 */

class CourseSearchBox implements Display
{
    /**
     * Where the script is executed
     *
     * @var string
     */
    protected $formAction;
    
    /**
     * The specified keyword(s)
     *
     * @var string
     */
    protected $keyword;
    
    /**
     * Course list
     *
     * @var array
     */
    protected $searchResults;
    
    /**
     * View options for the course tree to render
     *
     * @var CourseTreeViewOptions
     */
    protected $viewOptions;
    
    public function __construct($formAction)
    {
        $this->formAction   = $formAction;
        
        if (isset($_REQUEST['coursesearchbox_keyword']))
        {
            // Note: $keyword get secured later, in the SQL request
            $this->keyword = preg_replace( "/[^0-9\w _.]/", ' ', $_REQUEST['coursesearchbox_keyword'] );
        }
        else
        {
            $this->keyword = '';
        }
        
        $this->viewOptions = new CourseTreeViewOptions();
    }
    
    protected function fetchResults()
    {
        $this->searchResults = 
            CourseTreeNodeViewFactory::getSearchedCourseTreeView($this->keyword);
    }
    
    /**
     * @return CourseTreeViewOptions
     */
    public function getViewOptions()
    {
        return $this->viewOptions;
    }
    
    /**
     * @param CourseTreeViewOptions
     */
    public function setViewOptions($viewOptions)
    {
        $this->viewOptions = $viewOptions;
    }
    
    /**
     * @return CoreTemplate
     */
    public function getTemplate()
    {
        if (!empty($this->keyword))
        {
            $this->fetchResults();
            $this->searchResults->setViewOptions($this->viewOptions);
        }
        
        $template = new CoreTemplate('course_search_box.tpl.php');
        $template->assign('formAction', $this->formAction);
        $template->assign('courseTree', $this->searchResults);
        $template->assign('keyword', $this->keyword);
        
        return $template;
    }
    
    public function render()
    {
        return $this->getTemplate()->render();
    }
}

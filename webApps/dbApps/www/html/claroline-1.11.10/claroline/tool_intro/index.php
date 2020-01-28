<?php // $Id: index.php 14450 2013-05-15 12:02:23Z zefredz $

/**
 * CLAROLINE
 *
 * Manage tools' introductions.
 * Caution: this module uses two labels: CLINTRO and CLTI.
 *
 * @version     $Revision: 14450 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @package     CLINTRO
 * @author      Claro Team <cvs@claroline.net>
 * @since       1.9
 */


// Reset session variables
$cidReset = true; // course id
$gidReset = true; // group id
$tidReset = true; // tool id

// Load Claroline kernel
require_once dirname(__FILE__) . '/../inc/claro_init_global.inc.php';

// Build the breadcrumb
$nameTools = get_lang('Headlines');

// Initialisation of variables and used classes and libraries
require_once get_module_path('CLTI').'/lib/toolintroductioniterator.class.php';

$id                 = isset($_REQUEST['id'])  ? (int) $_REQUEST['id'] : 0;
$cmd                = (!empty($_REQUEST['cmd'])?($_REQUEST['cmd']):(null));
$isAllowedToEdit    = claro_is_allowed_to_edit();

set_current_module_label('CLINTRO');

// Init linker
FromKernel::uses('core/linker.lib');
ResourceLinker::init();

// Javascript confirm pop up declaration for header
JavascriptLanguage::getInstance()->addLangVar('Are you sure to delete %name ?');

JavascriptLoader::getInstance()->load('tool_intro');

// Instanciate dialog box
$dialogBox = new DialogBox();

$toolIntroForm = '';

if ( isset( $cmd ) && $isAllowedToEdit )
{
    // Set linker's params
    if ( $id )
    {
        $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
            array('id' => (int) $id));
    }
    else
    {
        $currentLocator = null;
    }
    
    // CRUD
    if ( $cmd == 'rqAdd' )
    {
        $toolIntro = new ToolIntro();
        $toolIntroForm = $toolIntro->renderForm();
    }
    
    elseif ( $cmd == 'rqEd' )
    {
        ResourceLinker::setCurrentLocator($currentLocator);
        
        $toolIntro = new ToolIntro($id);
        if($toolIntro->load())
        {
            $toolIntroForm = $toolIntro->renderForm();
        }
    }
    
    elseif ( $cmd == 'exAdd' )
    {
        $toolIntro = new ToolIntro();
        $toolIntro->handleForm();
        
        //TODO inputs validation
        
        // Manage ressources
        if ($toolIntro->save())
        {
            $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                array( 'id' => (int) $toolIntro->getId() ) );
            
            $resourceList =  isset($_REQUEST['resourceList'])
                ? $_REQUEST['resourceList']
                : array()
                ;
            
            ResourceLinker::updateLinkList( $currentLocator, $resourceList );
            
            $dialogBox->success( get_lang('Introduction added') );
            
            // Notify that the introsection has been created
            $claroline->notifier->notifyCourseEvent('introsection_created', claro_get_current_course_id(), claro_get_current_tool_id(), $toolIntro->getId(), claro_get_current_group_id(), '0');
        }
    }
    
    elseif ( $cmd == 'exEd' )
    {
        $toolIntro = new ToolIntro($id);
        
        $toolIntro->handleForm();
        
        //TODO inputs validation
        
        if ( $toolIntro->save() )
        {
            $currentLocator = ResourceLinker::$Navigator->getCurrentLocator(
                array( 'id' => (int) $toolIntro->getId() ) );
            
            $resourceList =  isset($_REQUEST['resourceList'])
                ? $_REQUEST['resourceList']
                : array()
                ;
            
            ResourceLinker::updateLinkList( $currentLocator, $resourceList );
            
            $dialogBox->success( get_lang('Introduction modified') );
            
            // Notify that the introsection has been modified
            $claroline->notifier->notifyCourseEvent('introsection_modified', claro_get_current_course_id(), claro_get_current_tool_id(), $toolIntro->getId(), claro_get_current_group_id(), '0');
        }
    }
    
    elseif ( $cmd == 'exDel' )
    {
        $toolIntro = new ToolIntro($id);
        
        if ($toolIntro->delete())
        {
            $dialogBox->success( get_lang('Introduction deleted') );
            
            //TODO linker_delete_resource('CLINTRO_');
        }
    }
    
    // Modify rank and visibility
    elseif ( $cmd == 'exMvUp' )
    {
        $toolIntro = new ToolIntro($id);
        
        if($toolIntro->load())
        {
            if ($toolIntro->moveUp())
            {
                $dialogBox->success( get_lang('Introduction moved up') );
            }
            else
            {
                $dialogBox->error( get_lang('This introduction can\'t be moved up') );
            }
        }
    }
    
    elseif ( $cmd == 'exMvDown' )
    {
        $toolIntro = new ToolIntro($id);
        
        if($toolIntro->load())
        {
            if ($toolIntro->moveDown())
            {
                $dialogBox->success( get_lang('Introduction moved down') );
            }
            else
            {
                $dialogBox->error( get_lang('This introduction can\'t be moved down') );
            }
        }
    }
    
    elseif ( $cmd == 'mkVisible' )
    {
        $toolIntro = new ToolIntro($id);
        
        if ($toolIntro->load())
        {
            $toolIntro->setVisibility('SHOW');
            
            if ($toolIntro->save())
            {
                $dialogBox->success( get_lang('Introduction\' visibility modified') );
            }
            else
            {
                $dialogBox->error( get_lang('This introduction\'s visibility can\'t be modified') );
            }
        }
    }
    
    elseif ( $cmd == 'mkInvisible' )
    {
        $toolIntro = new ToolIntro($id);
        
        if ($toolIntro->load())
        {
            $toolIntro->setVisibility('HIDE');
            
            if ($toolIntro->save())
            {
                $dialogBox->success( get_lang('Introduction\' visibility modified') );
            }
            else
            {
                $dialogBox->error( get_lang('This introduction\'s visibility can\'t be modified') );
            }
        }
    }
}



// Display
$cmdList = array();

if (claro_is_allowed_to_edit())
{
    $cmdList[] = array(
        'img' => 'headline_new',
        'name' => get_lang('New headline'),
        'url' => claro_htmlspecialchars(Url::Contextualize( $_SERVER['PHP_SELF'] .'?cmd=rqAdd'))
    );
}

$cmdList[] = array(
        'img' => 'coursehome',
        'name' => get_lang('Back to course homepage'),
        'url' => claro_htmlspecialchars( Url::Contextualize( 
            get_path('rootWeb') . 'claroline/course/index.php?cid='.  claro_get_current_course_id () ) )
);

$toolIntroIterator = new ToolIntroductionIterator(claro_get_current_course_id());

$toolIntroductionList = '';

if ( count( $toolIntroIterator ) > 0)
{
    foreach ($toolIntroIterator as $toolIntro)
    {
        $toolIntroductionList .= $toolIntro->render();
    }
}
else
{
    // If there's no item, display the form to add one
    $toolIntro = new ToolIntro();
    
    $dialogBox->info(get_lang('There\'s no headline for this course right now.  Use the form below to add a new one.'));
    
    $toolIntroForm = (empty($toolIntroForm) ? $toolIntro->renderForm() : $toolIntroForm);
}

Claroline::getDisplay()->body->appendContent(claro_html_tool_title(get_lang('Headlines'), null, $cmdList));
Claroline::getDisplay()->body->appendContent($dialogBox->render());

$output = '';
$output .= $toolIntroForm
         . $toolIntroductionList;

// Append output
Claroline::getDisplay()->body->appendContent($output);

// Render output
echo $claroline->display->render();

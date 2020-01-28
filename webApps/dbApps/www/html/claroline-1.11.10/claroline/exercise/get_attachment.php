<?php // $Id: get_attachment.php 14064 2012-03-19 15:10:37Z zefredz $

/**
 * CLAROLINE
 *
 * @version     $Revision: 14064 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 * @author      Claro Team <cvs@claroline.net>
 */

$tlabelReq = 'CLQWZ';

require '../inc/claro_init_global.inc.php';

if ( !claro_is_in_a_course() || !claro_is_course_allowed() ) claro_disp_auth_form(true);

$dialogBox = new DialogBox();
$is_allowedToEdit = claro_is_allowed_to_edit();

// tool libraries

include_once './lib/exercise.class.php';
include_once './lib/question.class.php';
include_once './lib/exercise.lib.php';

include_once get_path('incRepositorySys') . '/lib/file.lib.php';

// init request vars
if ( isset($_REQUEST['id']) ) $id = $_REQUEST['id'];
else                          $id = null;

$item_list = explode('_',$id);

if( isset($item_list['0']) ) $cmd = $item_list['0'];
else                         $cmd = null;

if( isset($item_list['1']) && is_numeric($item_list['1']) ) $quId = (int) $item_list['1'];
else                                                        $quId = null;

if( isset($item_list['2']) && is_numeric($item_list['2']) ) $exId = (int) $item_list['2'];
else                                                        $exId = null;

if ( $cmd == 'download' )
{
    // find exercise informations

    $exercise= new Exercise();

    if ( $exercise->load($exId) || $is_allowedToEdit )
    {
        if ( $exercise->getVisibility() == 'VISIBLE' || $is_allowedToEdit )
        {
            $question = new Question();
        
            if ( $question->load($quId) )
            {
                $attachmentFile = $question->getQuestionDirSys() . $question->getAttachment();

                if ( claro_send_file($attachmentFile) )
                {
                    die();
                }
                else
                {
                    $dialogBox->error( get_lang('Not found') );
                }
            }
            else
            {
                 $dialogBox->error( get_lang('Not found') );
            }
        }
        else
        {
            $dialogBox->error( get_lang('Not allowed') );
        }
    }
    else
    {
        $dialogBox->error( get_lang('Not found') );
    }
}

// Not Found 404

header('HTTP/1.1 404 Not Found');

$out = '';

$out .= $dialogBox->render();


$claroline->display->body->appendContent($out);

echo $claroline->display->render();

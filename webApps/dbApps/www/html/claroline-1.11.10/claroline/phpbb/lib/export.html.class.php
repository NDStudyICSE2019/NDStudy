<?php

// $Id: export.html.class.php 14229 2012-08-06 08:22:10Z zefredz $
/**
 * CLAROLINE
 *
 * Script export topic/forum in HTML
 *
 * @version 1.9 $Revision: 14229 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @copyright (C) 2001 The phpBB Group
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Dimitri Rambout <dim@claroline.net>
 *
 * @package CLFRM
 *
 */

class exportHTML extends export
{

    private function importCss ()
    {
        if ( file_exists ( get_path ( 'clarolineRepositorySys' ) . '../platform/css/' . get_conf ( 'claro_stylesheet' ) . '/main.css' ) )
        {
            $css = file_get_contents ( get_path ( 'clarolineRepositorySys' ) . '../platform/css/' . get_conf ( 'claro_stylesheet' ) . '/main.css' );
        }
        elseif ( file_exists ( get_path ( 'rootSys' ) . 'web/css/' . get_conf ( 'claro_stylesheet' ) . '/main.css' ) )
        {
            $css = file_get_contents ( get_path ( 'rootSys' ) . 'web/css/' . get_conf ( 'claro_stylesheet' ) . '/main.css' );
        }
        else
        {
            $css = '';
        }

        if ( file_exists ( get_module_path ( 'CLFRM' ) . '/css/clfrm.css' ) )
        {
            $css .= file_get_contents ( get_module_path ( 'CLFRM' ) . '/css/clfrm.css' );
        }
        else
        {
            $css .= '';
        }


        $regex = '~/\*(?s:.*?)\*/|^\s*//.*~m';

        $css = preg_replace ( $regex, '', $css );

        return $css;
    }

    public function export ()
    {
        $postsList = $this->loadTopic ( $this->getTopicId () );

        $topicInfo = get_topic_settings ( $this->getTopicId () );

        $css = $this->importCss ();

        $form = new PhpTemplate ( get_module_path ( 'CLFRM' ) . '/templates/forum_export.tpl.php' );

        $form->assign ( 'forum_id', $topicInfo[ 'forum_id' ] );
        $form->assign ( 'topic_id', $topicInfo[ 'topic_id' ] );
        $form->assign ( 'notification_bloc', false );
        $form->assign ( 'topic_subject', $topicInfo[ 'topic_title' ] );
        $form->assign ( 'postList', $postsList );
        $form->assign ( 'claro_notifier', false );
        $form->assign ( 'is_allowedToEdit', false );

        $form->assign ( 'date', null );

        $out = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' . "\n"
            . '<html>' . "\n"
            . '<head>' . "\n"
            . '<meta http-equiv="Content-Type" content="text/HTML; charset=' . get_conf ( 'charset' ) . '"  />' . "\n"
            . '<style type="text/css">' . $css . '</style>' . "\n"
            . '<title>' . $topicInfo[ 'topic_title' ] . '</title>' . "\n"
            . '</head>' . "\n"
            . '<body><div id="forumExport">' . "\n"
        ;

        $out .= $form->render ();

        $out .= '</div></body>' . "\n"
            . '</html>'
        ;

        $path = get_conf ( 'rootSys' ) . get_conf ( 'tmpPathSys' ) . '/forum_export/';
        $filename = $path . replace_dangerous_char ( str_replace ( ' ', '_', $topicInfo[ 'topic_title' ] ) . '_' . $topicInfo[ 'topic_id' ] ) . '.html';

        claro_mkdir ( $path );

        file_put_contents ( $filename, $out );

        switch ( $this->output )
        {
            case 'screen' :
                {
                    header ( 'Content-Description: File Transfer' );
                    header ( 'Content-Type: application/force-download' );
                    header ( 'Content-Length: ' . filesize ( $filename ) );
                    header ( 'Content-Disposition: attachment; filename=' . basename ( $filename ) );

                    readfile ( $filename );

                    claro_delete_file ( $filename );
                }
                break;
            case 'file' :
                {
                    
                }
                break;
        }

        return true;
    }

}
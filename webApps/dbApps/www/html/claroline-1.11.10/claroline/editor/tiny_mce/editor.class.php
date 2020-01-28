<?php // $Id: editor.class.php 14252 2012-08-23 05:48:22Z zefredz $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * Driver for tinyMCE wysiwyg editor ( http://tinymce.moxiecode.com/ )
 *
 * @version 1.8 $Revision: 14252 $
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/config_def/
 *
 * @package EDITOR
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Sebastien Piraux <pir@cerdecam.be>
 *
 */

/**
 * Add javascript var for mimetex URL
 */
Claroline::getInstance()->display->header->addInlineJavascript('var mimeTexURL = "' . get_conf('claro_texRendererUrl') .'"');


require_once dirname(__FILE__) . '/../GenericEditor.class.php';
/**
 * Class to manage htmlarea overring simple textarea html
 * @package EDITOR
 */
class editor extends GenericEditor
{
    /**
     * @var $_tag metadata comment added to identify editor
     */
    private $_tag;
    

    /**
     * constructor
     *
     * @author Sebastien Piraux <pir@cerdecam.be>
     * @param string $name content for attribute name and id of textarea
     * @param string $content content of the textarea
     * @param string $rows number of lines of textarea
     * @param string $cols number of cols of textarea
     * @param string $optAttrib additionnal attributes that can be added to textarea
     * @param string $webPath path to access via the web to the directory of the editor
     */
    public function editor( $name,$content,$rows,$cols,$optAttrib,$webPath )
    {
        parent::GenericEditor( $name,$content,$rows,$cols,$optAttrib,$webPath );

        $this->_tag = '<!-- content: html tiny_mce -->';

        $this->prepareContent();
    }


    /**
     * Returns the html code needed to display an advanced (default) version of the editor
     * @return string html code needed to display an advanced (default) version of the editor
       */
    public function getAdvancedEditor()
    {
        $html = '';
        $html .= $this->getDefaultJs();
        
        $html .= $this->getAdvancedJs();
        
        // add standard text area
        $html .= $this->getTextArea('advancedMCE');

        return  $html;
    }

    /**
     * Returns the html code needed to display a simple version of the editor
     * @return string html code needed to display a simple version of the editor
     */
    public function getSimpleEditor()
    {
        $html = '';
        $html .= $this->getDefaultJs();
        
        $html .= $this->getSimpleJs();
        
        // add standard text area
        $html .= $this->getTextArea('simpleMCE');

        return  $html;
    }
    
    public function getAdvancedJs()
    {
        // ok, it's not cool to use global for that but it has to be shared between instances
        // TODO find a cool way to do that
        global $_isAdvancedJsLoaded;
        
        $html = '';
        
        if(claro_is_allowed_to_edit())
        {
            $configFile = 'advanced';
        }
        else
        {
            $configFile = 'advanced_users';
        }
        
        if( !isset($_isAdvancedJsLoaded) )
        {
            /*if( get_conf('useTinyMCECompressor') )
            {
                $html .= '<script language="javascript" type="text/javascript" src="'.$this->webPath.'/'.$configFile.'_gzip.conf.js"></script>'."\n";
            }*/
            
            $html .= '<script language="javascript" type="text/javascript" src="'.$this->webPath.'/'.$configFile.'.conf.js"></script>'."\n";

            $_isAdvancedJsLoaded = true;
        }

        return $html;
    }
    
    public function getSimpleJs()
    {
        // ok, it's not cool to use global for that but it has to be shared between instances
        // TODO find a cool way to do that
        global $_isSimpleJsLoaded;

        $html = '';
        
        if( ! isset($_isSimpleJsLoaded) )
        {
            /*if( get_conf('useTinyMCECompressor') )
            {
                $html .= '<script language="javascript" type="text/javascript" src="'.$this->webPath.'/simple_gzip.conf.js"></script>'."\n";
            }*/
            
            $html .= '<script language="javascript" type="text/javascript" src="'.$this->webPath.'/simple.conf.js"></script>'."\n";
                
            $_isSimpleJsLoaded = true;
        }
        
        return $html;
    }
    
    public function getDefaultJs()
    {
        // ok, it's not cool to use global for that but it has to be shared between instances
        // TODO find a cool way to do that
        global $_isDefaultJsLoaded;
        
        $html = '';
        
        if( ! isset($_isDefaultJsLoaded) )
        {
            $html .= "\n";
            
            /*if( get_conf('useTinyMCECompressor') )
            {
                $html .= '<script language="javascript" type="text/javascript" src="'.$this->webPath.'/tiny_mce/tiny_mce_gzip.js"></script>'."\n";
            }
            else*/
            {
                $html .= '<script language="javascript" type="text/javascript" src="'.$this->webPath.'/tiny_mce/tiny_mce.js"></script>'."\n";
            }
            
            $html .= '<script language="javascript" type="text/javascript">'."\n"
            .    'var text_dir = "'.get_locale("text_dir").'";' . "\n"
            .    '</script>'."\n\n";

            $_isDefaultJsLoaded = true;
        }
        
        return $html;
    }
    
    
    /**
     * Introduce a comment stating that the content is html and edited with this editor
     *
     * @access private
     */
    private function prepareContent()
    {
        // remove old 'metadata' and add the good one
        $this->content = preg_replace('/<!-- content:[^(\-\->)]*-->/', '', $this->content) . $this->_tag;

        return true;
    }
}

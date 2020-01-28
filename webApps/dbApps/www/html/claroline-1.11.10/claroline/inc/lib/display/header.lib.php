<?php // $Id: header.lib.php 14453 2013-05-16 08:15:37Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Class used to configure and display the page header.
 *
 * @version     $Revision: 14453 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     display
 */

FromKernel::uses( 'core/loader.lib' );

class ClaroHeader extends CoreTemplate
{
    private static $instance = false;
    
    private $_htmlXtraHeaders;
    private $_httpXtraHeaders;
    private $_toolName;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('header.tpl.php');
        $this->_htmlXtraHeaders = array();
        $this->_httpXtraHeaders = array();
        $this->_toolName = '';
        
        if ( claro_debug_mode() )
        {
            $this->addInlineJavascript("var claro_debug_mode = true;");
        }
        
        require dirname(__FILE__) .'/../../installedVersion.inc.php';
        
        if ( $stable )
        { 
            if ( file_exists( dirname(__FILE__) .'/../../../../platform/currentVersion.inc.php' ) )
            {
                require dirname(__FILE__) .'/../../../../platform/currentVersion.inc.php';
            }
            else
            {
                $clarolineVersion = $new_version;
            }

            if ( $clarolineVersion != $new_version )
            {
                $claroVersion = "{$clarolineVersion}-{$new_version}";
            }
            else
            {
                $claroVersion = $new_version;
            }
        }
        else
        {
            $claroVersion = $new_version;
        }
        
        $this->assign( 'version', $claroVersion );
    }
    
    public static function getInstance()
    {
        if ( ! self::$instance )
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set the page title
     * @since Claroline 1.9.5
     * @param string $title
     */
    public function setTitle( $pageTitle )
    {
        $this->_toolName = $pageTitle;
    }

    /**
     * Set the page title
     * @deprecated since Claroline 1.9.5, use setTitle() instead
     * @param string $pageTitle
     */
    public function setToolName( $pageTitle )
    {
        $this->setToolName( $pageTitle );
    }
    
    /**
     * Add extra HTML header elements
     *
     * @param   string $header header to add
     */
    public function addHtmlHeader( $header )
    {
        $this->_htmlXtraHeaders[] = $header;
    }
    
    /**
     * Add inline javascript code to HTML head
     *
     * @param   string $script javascript code
     */
    public function addInlineJavascript( $script )
    {
        if ( false === strpos( $script, '<script' ) )
        {
            $script = "<script type=\"text/javascript\">\n{$script}\n</script>";
        }
        
        $this->addHtmlHeader( $script );
    }
    
    /**
     * Add inline css style to HTML head
     *
     * @param   string $style css style
     */
    public function addInlineStyle( $style )
    {
        if ( false === strpos( $style, '<style' ) )
        {
            $style = "<style type=\"text/css\">\n{$style}\n</style>";
        }
        
        $this->addHtmlHeader( $style );
    }

    /**
     * Add extra HTTP header elements
     *
     * @param   string $header HTTP header
     */
    public function addHttpHeader( $header )
    {
        $this->_httpXtraHeaders[] = $header;
    }
    
    /**
     * Send HTTP headers to the client
     */
    public function sendHttpHeaders()
    {
        if (! is_null(get_locale('charset')) )
        {
            header('Content-Type: text/html; charset='. get_locale('charset'));
        }

        if ( !empty($this->_httpXtraHeaders) )
        {
            foreach( $this->_httpXtraHeaders as $httpHeader )
            {
                header( $httpHeader );
            }
        }
    }
    
    /**
     * Retrieve variables used by the old header script for compatibility
     * with old scripts
     */
    private function _globalVarsCompat()
    {
        if ( isset( $GLOBALS['htmlHeadXtra'] ) && !empty($GLOBALS['htmlHeadXtra']) )
        {
            $this->_htmlXtraHeaders = array_merge($this->_htmlXtraHeaders, $GLOBALS['htmlHeadXtra'] );
        }
        
        if ( isset( $GLOBALS['httpHeadXtra'] ) && !empty($GLOBALS['httpHeadXtra']) )
        {
            $this->_httpXtraHeaders = array_merge($this->_httpXtraHeaders, $GLOBALS['httpHeadXtra'] );
        }
        
        if ( isset( $GLOBALS['nameTools'] ) && !empty($GLOBALS['nameTools']) )
        {
            $this->_nameTools = $GLOBALS['nameTools'];
        }
    }
    
    
    /**
     * Render the HTML page header
     * @return  string
     */
    public function render()
    {
        $this->_globalVarsCompat();
        
        $this->addInlineJavascript(JavascriptLanguage::getInstance()->buildJavascript());
        
        $titlePage = '';

        if(empty($this->_toolName) && !empty($this->_nameTools))
        {
            $titlePage .= $this->_nameTools . ' - ';
        }
        elseif (! empty($this->_toolName) )
        {
            $titlePage .= $this->_toolName  . ' - ';
        }

        if(claro_is_in_a_course() && claro_get_current_course_data('officialCode') != '')
        {
            $titlePage .= claro_get_current_course_data('officialCode') . ' - ';
        }

        $titlePage .= get_conf('siteName');
        
        $this->assign( 'pageTitle', $titlePage );
        
        if ( true === get_conf( 'warnSessionLost', true ) && claro_get_current_user_id() )
        {
            $this->assign( 'warnSessionLost',
"function claro_session_loss_countdown(sessionLifeTime){
    var chrono = setTimeout('claro_warn_of_session_loss()', sessionLifeTime * 1000);
}

function claro_warn_of_session_loss() {
    alert('" . clean_str_for_javascript (get_lang('WARNING ! You have just lost your session on the server.') . "\n"
             . get_lang('Copy any text you are currently writing and paste it outside the browser')) . "');
}
" );
        }
        else
        {
            $this->assign( 'warnSessionLost', '' );
        }
        
        $htmlXtraHeaders = '';
        
        if ( !empty( $this->_htmlXtraHeaders ) )
        {
            $htmlXtraHeaders .= implode ( "\n", $this->_htmlXtraHeaders );
        }

        $this->assign( 'htmlScriptDefinedHeaders', $htmlXtraHeaders );
        
        return parent::render() . "\n";
    }
}
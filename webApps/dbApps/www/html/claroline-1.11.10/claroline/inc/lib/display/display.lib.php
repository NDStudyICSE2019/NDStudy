<?php // $Id: display.lib.php 14482 2013-06-21 07:58:40Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Display library.
 *
 * @version     $Revision: 14482 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     display
 */

require_once dirname(__FILE__) . '/phptemplate.lib.php';
require_once dirname(__FILE__) . '/header.lib.php';
require_once dirname(__FILE__) . '/body.lib.php';
require_once dirname(__FILE__) . '/footer.lib.php';
require_once dirname(__FILE__) . '/dock.lib.php';
require_once dirname(__FILE__) . '/banner.lib.php';
require_once dirname(__FILE__) . '/dialogBox.lib.php';
require_once dirname(__FILE__) . '/tooltitle.lib.php';

/**
 * Popup helper
 *
 * @access public
 */
class PopupWindowHelper
{
    /**
     * generate window.close() html code
     *
     * @access public
     * @static
     * @return  string html code of the window.close() link
     */
    public static function windowClose()
    {
        return '<div class="linkCloseWindow"><a class="claroCmd" href="#" '
            . 'onclick="window.close()">'
            . get_lang('Close window')
            . '</a></div>'
            . "\n"
            ;
    }

    /**
     * Embed content between window.close() code
     *
     * @access  static
     * @static
     * @param   string content
     * @return  string embedded content
     */
    public static function popupEmbed( $content )
    {
        $out = PopupWindowHelper::windowClose()
            . $content
            . PopupWindowHelper::windowClose()
            ;

        return $out;
    }
}


/**
 * Interface for any class that offers a view.
 */
interface Display
{
    public function render();
}


/**
 * Claroline script embed class
 *
 * @access  public
 */
class ClaroPage implements Display
{
    public $header, $body, $banner, $footer;
    
    private $jsBodyOnload = array();
    private $bannerAtEnd = false;
    private $inPopup = false;

    public function __construct()
    {
        // HACK : force loading translation here to avoid having to rewrite the kernel !
        language::load_translation();
        
        $this->header = ClaroHeader::getInstance();
        $this->body = ClaroBody::getInstance();
        $this->banner = ClaroBanner::getInstance();
        $this->footer = ClaroFooter::getInstance();
    }

    /**
     * Set page content
     *
     * @access  public
     * @param   string content, page content
     */
    public function setContent( $content )
    {
        $this->body->setContent( $content );
    }
    
    public function addBodyOnload( $function )
    {
        $this->jsBodyOnload[] = $function;
    }

    public function brailleMode()
    {
        $this->bannerAtEnd = true;
    }

    public function popupMode()
    {
        $this->body->popupMode();
        $this->banner->hide();
        $this->footer->hide();
        $this->body->hideCourseTitleAndTools();
    }

    public function frameMode()
    {
        $this->banner->hide();
        $this->footer->hide();
        $this->body->hideCourseTitleAndTools();
    }
    
    private function _globalVarsCompat()
    {
        if ( isset( $GLOBALS['claroBodyOnload'] ) && !empty($GLOBALS['claroBodyOnload']) )
        {
            $this->jsBodyOnload = array_merge( $this->jsBodyOnload, $GLOBALS['claroBodyOnload'] );
        }
        
        // set shared display variables
        if( isset( $_REQUEST['inPopup'] )
                && 'true' == $_REQUEST['inPopup'] )
        {
            $this->popupMode();
        }
        
        if( isset( $_REQUEST['inFrame'] )
                && 'true' == $_REQUEST['inFrame'] )
        {
            $this->frameMode();
        }
        
        if( isset( $_REQUEST['embedded'] )
                && 'true' == $_REQUEST['embedded'] )
        {
            $this->frameMode();
        }
    
        if ( isset( $_REQUEST['hide_banner'] )
                && 'true' == $_REQUEST['hide_banner'] )
        {
            $this->banner->hide();
        }
    
        if( isset( $_REQUEST['hide_footer'] )
                && 'true' == $_REQUEST['hide_footer'] )
        {
            $this->footer->hide();
        }
    
        if( isset( $_REQUEST['hide_body'] )
                && 'true' == $_REQUEST['hide_body'] )
        {
            $this->body->hideClaroBody();
        }
    }

    // output methods
    /**
     * Generate and set output to client
     *
     * @access  public
     */
    public function render()
    {
        try
        {
            $this->_globalVarsCompat();
            
            $contents = '';
                
            if ( ! $this->bannerAtEnd )
            {
                $contents .= $this->banner->render() . "\n";
            }
    
            $contents .= $this->body->render();
            
            if ( $this->bannerAtEnd )
            {
                $contents .= $this->banner->render() . "\n";
            }
    
            $contents .= $this->footer->render() . "\n";
    
            if ( claro_debug_mode() )
            {
                $contents .= claro_disp_debug_banner();
            }
            
            $output = '';
            
            $output .= $this->header->render();
            
            if ( true === get_conf( 'warnSessionLost', true ) && claro_get_current_user_id() )
            {
                $this->jsBodyOnload[] = 'claro_session_loss_countdown(' . ini_get('session.gc_maxlifetime') . ');';
            }
            
            $output .= '<body dir="' . get_locale('text_dir') . '"'
                .    ( !empty( $this->jsBodyOnload ) ? ' onload="' . implode('', $this->jsBodyOnload ) . '" ':'')
                .    '>' . "\n"
                ;
            
            $output .= $contents;
    
            $output .= '</body>' . "\n";
    
            $output .= '</html>' . "\n";
            
            $this->header->sendHttpHeaders();
    
            return $output;
        }
        catch( Exception $e )
        {
            if ( claro_debug_mode() )
            {
                die( $e->__toString() );
            }
            else
            {
                die( $e->getMessage() );
            }
        }
    }
}


/**
 * Claroline html frame class
 *
 * @access  public
 */
class ClaroFrame implements Display
{
    private $src;
    private $name;
    private $id;
    private $scrolling = false;
    private $autoscroll = false;
    private $noresize = false;
    private $frameborder = true;
    private $framespacing = null;

    /**
     * Constructor
     *
     * @access  public
     * @param   string name, frame name
     * @param   string src, frame content url
     * @param   string id, frame id, optional, if not given the name will be
     *  used as the frame id
     */
    public function __construct( $name, $src, $id = '' )
    {
        $this->name = $name;
        $this->src = $src;
        $this->id = empty( $id ) ? $name : $id;
    }

    /**
     * Allow scrolling in frame
     *
     * @access  public
     * @param   bool auto, set to true to allow auto scrolling
     */
    public function allowScrolling( $auto = false )
    {
        $this->scrolling = true;
        $this->autoscroll = $auto;
    }

    /**
     * Disable frame resizing
     *
     * @access  public
     */
    public function disableResize()
    {
        $this->noresize = true;
    }

    /**
     * Disable frame border
     *
     * @access  public
     */
    public function noFrameBorder()
    {
        $this->frameborder = false;
    }

    /**
     * Set space between frames
     *
     * @access  public
     * @param   int spacing, frame spacing
     */
    public function setFrameSpacing( $spacing )
    {
        $this->framespacing = $spacing;
    }

    /**
     * Render the frame to embed in a HTML frameset
     *
     * @access  public
     * @see     ClaroFramesetElement::render()
     */
    public function render()
    {
        return '<frame src="'.$this->src.'"'
            . ' name="'.$this->name.'"'
            . ' id="'.$this->id.'"'
            . ' scrolling="'.($this->scrolling
                ? ( $this->autoscroll ? 'auto' : 'yes' )
                : 'no' ) . '"'
            . ' frameborder="'.($this->frameborder
                ? '1'
                : '0' ) . '"'
            . ( $this->noresize ? ' noresize="noresize"' : '' )
            . ( is_null($this->framespacing)
                ? ''
                : ' framespacing="'.$this->framespacing.'"' )
            .' />'
            . "\n"
            ;
    }
}


/**
 * Claroline html frameset class
 *
 * @access  public
 */
class ClaroFrameset implements Display
{
    protected $frameset = array();
    protected $rows = array();
    protected $cols = array();

    /**
     * Add a frame or frameset object to the current frameset
     *
     * @access  public
     * @param   ClaroFramesetElement claroFrame, frame to add could be a
     *  ClaroFrame or a ClaroFrameset or any other convenient Object
     *  implementing the ClaroFramesetElement API
     */
    public function addFrame( $claroFrame )
    {
        $this->frameset[] = $claroFrame;
    }

    /**
     * Add a frame or frameset object to the current frameset as a new row
     *
     * @access  public
     * @param   ClaroFramesetElement claroFrame, frame to add could be a
     *  ClaroFrame or a ClaroFrameset or any other convenient Object
     *  implementing the ClaroFramesetElement API
     * @param   mixed size, row size, could be an int or '*'
     */
    public function addRow( $claroFrame, $size )
    {
        $this->rows[] = $size;
        $this->addFrame( $claroFrame );
    }

    /**
     * Add a frame or frameset object to the current frameset as a new colum
     *
     * @access  public
     * @param   ClaroFramesetElement claroFrame, frame to add could be a
     *  ClaroFrame or a ClaroFrameset or any other convenient Object
     *  implementing the ClaroFramesetElement API
     * @param   mixed size, column size, could be an int or '*'
     */
    public function addCol( $claroFrame, $size )
    {
        $this->cols[] = $size;
        $this->addFrame( $claroFrame );
    }

    /**
     * Render the current frameset to be embedded in another HTML frameset
     *
     * @access  public
     * @see     ClaroFramesetElement::render()
     */
    public function render()
    {
        $html = '<frameset '
            . ( ! empty( $this->rows )
                ? 'rows="'. implode(',', $this->rows). '" ' : '' )
            . ( ! empty( $this->cols )
                ? 'cols="'. implode(',', $this->cols). '" ' : '' )
            . '>' . "\n"
            ;

        foreach ( $this->frameset as $element )
        {
            $html .= $element->render();
        }

        $html .= '</frameset>' . "\n";

        return $html;
    }
}


/**
 * Claroline html frameset class
 *
 * @access  public
 */
class ClaroFramesetPage extends ClaroFrameset
{
    public $header;
    
    public function __construct()
    {
        // HACK : force loading translation here to avoid having to rewrite the kernel !
        language::load_translation();
        language::load_locale_settings();
        language::load_module_translation();
        
        $this->header = ClaroHeader::getInstance();
    }

    /**
     * Render the current frameset to be embedded in another HTML frameset
     *
     * @access  public
     * @see     ClaroFramesetElement::render()
     */
    public function render()
    {
        $html = $this->header->render();
        
        $html .= parent::render();

        $html .= '</html>';

        return $html;
    }
}
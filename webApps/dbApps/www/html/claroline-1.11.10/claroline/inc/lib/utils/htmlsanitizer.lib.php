<?php // $Id: htmlsanitizer.lib.php 12634 2010-09-23 10:11:50Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * @author  Frederic Minne <zefredz@claroline.net>
 * @copyright Copyright &copy; 2006-2007, Frederic Minne
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version 1.0
 * @package HTML
 */
 
/**
 * Sanitize HTML body content
 * Remove dangerous tags and attributes that can lead to security issues like
 * XSS or HTTP response splitting
 */
class Claro_Html_Sanitizer
{
    // Private fields
    protected $_allowedTags;
    protected $_allowJavascriptEvents;
    protected $_allowJavascriptInUrls;
    protected $_allowObjects;
    protected $_allowScript;
    protected $_allowStyle;
    protected $_allowInlineStyle;
    protected $_additionalTags;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->resetAll();
    }
    
    /**
     * (re)set all options to default value
     */
    public function resetAll()
    {
        $this->_allowDOMEvents = false;
        $this->_allowJavascriptInUrls = false;
        $this->_allowStyle = false;
        $this->_allowScript = false;
        $this->_allowObjects = false;
        $this->_allowInlineStyle = false;

        $this->_allowedTags = '<a><br><b><h1><h2><h3><h4><h5><h6>'
            . '<img><li><ol><p><strong><table><tr><td><th><u><ul><thead>'
            . '<tbody><tfoot><em><dd><dt><dl><span><div><del><add><i><hr>'
            . '<pre><br><blockquote><address><code><caption><abbr><acronym>'
            . '<cite><dfn><q><ins><sup><sub><kbd><samp><var><tt><small><big>'
            ;
            
        $this->_additionalTags = '';
    }
    
    /**
     * Add additional tags to allowed tags
     * @param   string $tags
     */
    public function addAdditionalTags( $tags )
    {
        $this->_additionalTags .= $tags;
    }

    /**
     * Allow object, embed, applet and param tags in html
     */
    public function allowObjects()
    {
        $this->_allowObjects = true;
    }
    
    /**
     * Allow DOM event on DOM elements
     */
    public function allowDOMEvents()
    {
        $this->_allowDOMEvents = true;
    }
    
    /**
     * Allow script tags
     */
    public function allowScript()
    {
        $this->_allowScript = true;
    }
    
    /**
     * Allow the use of javascript: in urls
     */
    public function allowJavascriptInUrls()
    {
        $this->_allowJavascriptInUrls = true;
    }
    
    /**
     * Allow style tags and attributes
     */
    public function allowStyle()
    {
        $this->_allowStyle = true;
    }
    
    /**
     * Helper to allow all javascript related tags and attributes
     */
    public function allowAllJavascript()
    {
        $this->allowDOMEvents();
        $this->allowScript();
        $this->allowJavascriptInUrls();
    }
    
    /**
     * Allow all tags and attributes
     */
    public function allowAll()
    {
        $this->allowAllJavascript();
        $this->allowObjects();
        $this->allowStyle();
    }
    
    /**
     * Filter URLs to avoid HTTP response splitting attacks
     * @param   string $url
     * @return  string filtered url
     */
    protected function filterHTTPResponseSplitting( $url )
    {
        $dangerousCharactersPattern = '~(\r\n|\r|\n|%0a|%0d|%0D|%0A)~';
        return preg_replace( $dangerousCharactersPattern, '', $url );
    }
    
    /**
     * Remove potential javascript in urls
     * @param   string $url
     * @return  string filtered url
     */
    protected function removeJavascriptURL( $str )
    {
        $HTML_Sanitizer_stripJavascriptURL = 'javascript:[^"]+';

        $str = preg_replace("/$HTML_Sanitizer_stripJavascriptURL/i"
            , ''
            , $str );

        return $str;
    }
    
    /**
     * Remove potential flaws in urls
     * @param   string $url
     * @return  string filtered url
     */
    protected function sanitizeURL( $url )
    {
        if ( ! $this->_allowJavascriptInUrls )
        {
            $url = $this->removeJavascriptURL( $url );
        }
        
        $url = $this->filterHTTPResponseSplitting( $url );

        return $url;
    }
    
    /**
     * Callback for PCRE
     * @param   array $matches
     * @return  string
     * @see     sanitizeURL
     */
    protected function _sanitizeURLCallback( $matches )
    {
        return 'href="'.$this->sanitizeURL( $matches[1] ).'"';
    }
    
    /**
     * Remove potential flaws in href attributes
     * @param   string $str html tag
     * @return  string filtered html tag
     */
    protected function sanitizeHref( $str )
    {
        $HTML_Sanitizer_URL = 'href="([^"]+)"';

        return preg_replace_callback("/$HTML_Sanitizer_URL/i"
            , array( &$this, '_sanitizeURLCallback' )
            , $str );
    }
    
    /**
     * Callback for PCRE
     * @param   array $matches
     * @return  string
     * @see     sanitizeURL
     */
    protected function _sanitizeSrcCallback( $matches )
    {
        return 'src="'.$this->sanitizeURL( $matches[1] ).'"';
    }
    
    /**
     * Remove potential flaws in href attributes
     * @param   string $str html tag
     * @return  string filtered html tag
     */
    protected function sanitizeSrc( $str )
    {
        $HTML_Sanitizer_URL = 'src="([^"]+)"';

        return preg_replace_callback("/$HTML_Sanitizer_URL/i"
            , array( &$this, '_sanitizeSrcCallback' )
            , $str );
    }
    
    /**
     * Remove dangerous attributes from html tags
     * @param   string $str html tag
     * @return  string filtered html tag
     */
    protected function removeEvilAttributes( $str )
    {
        if ( ! $this->_allowDOMEvents )
        {
            $str = preg_replace_callback('/<(.*?)>/i'
                , array( &$this, '_removeDOMEventsCallback' )
                , $str );
        }
        
        if ( ! $this->_allowStyle )
        {
            $str = preg_replace_callback('/<(.*?)>/i'
                , array( &$this, '_removeStyleCallback' )
                , $str );
        }
            
        return $str;
    }
    
    /**
     * Remove DOM events attributes from html tags
     * @param   string $str html tag
     * @return  string filtered html tag
     */
    protected function removeDOMEvents( $str )
    {
        $str = preg_replace ( '/\s*=\s*/', '=', $str );

        $HTML_Sanitizer_stripAttrib = '(onclick|ondblclick|onmousedown|'
            . 'onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|'
            . 'onkeyup|onfocus|onblur|onabort|onerror|onload)'
            ;

        $str = stripslashes( preg_replace("/$HTML_Sanitizer_stripAttrib/i"
            , 'forbidden'
            , $str ) );

        return $str;
    }
    
    /**
     * Callback for PCRE
     * @param   array $matches
     * @return  string
     * @see     removeDOMEvents
     */
    protected function _removeDOMEventsCallback( $matches )
    {
        return '<' . $this->removeDOMEvents( $matches[1] ) . '>';
    }
    
    /**
     * Remove style attributes from html tags
     * @param   string $str html tag
     * @return  string filtered html tag
     */
    protected function removeStyle( $str )
    {
        $str = preg_replace ( '/\s*=\s*/', '=', $str );

        $HTML_Sanitizer_stripAttrib = '(style)'
            ;

        $str = stripslashes( preg_replace("/$HTML_Sanitizer_stripAttrib/i"
            , 'forbidden'
            , $str ) );

        return $str;
    }
    
    /**
     * Callback for PCRE
     * @param   array $matches
     * @return  string
     * @see     removeStyle
     */
    protected function _removeStyleCallback( $matches )
    {
        return '<' . $this->removeStyle( $matches[1] ) . '>';
    }
    
    /**
     * Remove dangerous HTML tags
     * @access  private
     * @param   string $str html code
     * @return  string filtered url
     */
    protected function removeEvilTags( $str )
    {
        $allowedTags = $this->_allowedTags;
        
        if ( $this->_allowScript )
        {
            $allowedTags .= '<script>';
        }
        
        if ( $this->_allowStyle )
        {
            $allowedTags .= '<style>';
        }
        
        if ( $this->_allowObjects )
        {
            $allowedTags .= '<object><embed><applet><param>';
        }
        
        $allowedTags .= $this->_additionalTags;
        
        // $str = strip_tags($str, $allowedTags );
        
        $str = $this->_stripTags( $str, $allowedTags );

        return $str;
    }
    
    /**
     * Remove unwanted tags
     * @param   string $str html
     * @param   string $tagList allowed tag list
     */
    protected function _stripTags( $str, $tagList )
    {
        // 1. prepare allowed tags list
        $tagList = str_replace( '<', ''
            , str_replace( '>', ''
            , str_replace( '><', '|', $tagList ) ) );
            
        // 2. replace </tag> by [[/tag]] in close tags for allowed tags
        $closeTags = '~' . '\</('.$tagList.')([^\>\<]*)\>' . '~'; // close tag
        
        $str = preg_replace ( $closeTags, "[[/\\1]]", $str );
        
        // ?! = do not match
        $autoAndOpenTags = '~('
            . '\<(?!'.$tagList.')[^\>\<]*(/){0,1}\>' // auto
            . ')~';
        
        // 3. replace not allowed tags by ''
        $str = preg_replace ( $autoAndOpenTags, '', $str );
        
        // 4. replace [[/tag]] by </tag> for allowed tags
        $closeTags = '~' . '\[\[/('.$tagList.')([^\]]*)\]\]' . '~'; // close tag

        $str = preg_replace ( $closeTags, "</\\1>", $str );

        return $str;
    }
    
    /**
     * Sanitize HTML
     *  remove dangerous tags and attributes
     *  clean urls
     * @param   string $html html code
     * @return  string sanitized html code
     */
    public function sanitize( $html )
    {
        $html = $this->removeEvilTags( $html );
        
        $html = $this->removeEvilAttributes( $html );
        
        $html = $this->sanitizeHref( $html );
        
        $html = $this->sanitizeSrc( $html );
        
        return $html;
    }
}


/**
 * Sanitize HTML code
 * @param   string $str html code
 * @return  string sanitized code
 */
function claro_html_sanitize_all( $str )
{
    static $san = null;
    
    if ( empty( $san ) )
    {
        $san = new Claro_Html_Sanitizer;
    }
    
    return $san->sanitize( $str );
}

function claro_html_sanitize_editor( $str )
{
    static $san = null;
    
    if ( empty( $san ) )
    {
        $san = new Claro_Html_Sanitizer;
        $san->allowObjects();
    }
    
    return $san->sanitize( $str );
}

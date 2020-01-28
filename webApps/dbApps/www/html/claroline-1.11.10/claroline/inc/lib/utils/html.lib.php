<?php // $Id: html.lib.php 14043 2012-03-02 12:30:24Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Html library
 *
 * @version     1.9 $Revision: 14043 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     utils
 */

/**
 * Generic renderer interface
 * This is now an alias of display
 */
interface Claro_Renderer extends Display
{
    /**
     * @return  string
     */
    // public function render();
}

/**
 * Generic HTML Element class
 */
class Claro_Html_Element implements Claro_Renderer
{
    protected static $ids = array();
    
    protected $autoClose;
    protected $name;
    protected $attributes;
    protected $content;
    
    /**
     * @param   string $name html element name ('input', 'p'...)
     * @param   array $attributes associative array of attributes
     * @param   bool $autoClose set to true for an autoclosed element 
     *  (&lt;input /&gt;, &lt;img /&gt;...), default false
     */
    public function __construct( $name, $attributes = null, $autoClose = false )
    {
        if ( !is_array( $attributes ) || empty( $attributes ) )
        {
            $attributes = array();
        }
        
        if ( array_key_exists( 'id', $attributes ) )
        {
            if ( in_array( $attributes['id'], self::$ids ) )
            {
                throw new Exception("A html element of id {$attributes['id']} already exists");
            }
            else
            {
                self::$ids[] = $attributes['id'];
            }
        }
        
        $this->name = $name;
        $this->attributes = $attributes;
        $this->autoClose = $autoClose;
        $this->content = '';
    }
    
    public function __destruct()
    {
        if ( array_key_exists( 'id', $this->attributes ) )
        {
            if ( in_array( $this->attributes['id'], self::$ids ) )
            {
                foreach ( self::$ids as $key => $value )
                {
                    if ( $value == $this->attributes['id'] )
                    {
                        unset ( self::$ids[$key] );
                        break;
                    }
                }
            }
        }
    }
    
    /**
     * Set the element content
     * @param   string $content
     */
    public function setContent( $content )
    {
        $this->content = $content;
    }
    
    /**
     * @see     Claro_Renderer
     */
    public function render()
    {
        return "<{$this->name}"
            . ( !empty( $this->attributes )
                ? $this->formatAttributes( $this->attributes ) 
                : '' )
            . ( $this->autoClose
                ? " />"
                : ">{$this->content}</{$this->name}>" )
            ;
    }
    
    /**
     * Format attributes
     * @param   array $attributes associative array of attributes
     * @return  string formated attributes
     */
    protected function formatAttributes( $attributes )
    {
        if ( empty( $attributes ) )
        {
            return '';
        }
        else
        {
            $attribs = '';
            
            foreach ( $attributes as $key => $value )
            {
                if ( $value )
                {
                    $attribs .= " {$key}=\"{$value}\"";
                }
            }
            
            return $attribs;
        }
    }
    
    /**
     * Get element id
     * @return  string id
     */
    public function getId()
    {
        if ( array_key_exists( 'id', $this->attributes ) )
        {
            return $this->attributes['id'];
        }
        else
        {
            return null;
        }
    }
    
    /**
     * Return the value of the given attribute
     * @param   string $name attribute name
     * @return  string attribute value or null if not defined
     */
    public function getAttr( $name )
    {
        if ( array_key_exists( $name, $this->attributes ) )
        {
            return $this->attributes[$name];
        }
        else
        {
            return null;
        }
    }
    
    /**
     * Set or modify the value of the given attribute
     * @param   string $name attribute name
     * @param   string $value attribute value
     */
    public function setAttr( $name, $value )
    {
        $this->attributes[$name] = $value;
    }
}

class Claro_Html_Container extends Claro_Html_Element
{
    protected $elems;
    
    public function __construct( $name, $attributes = array() )
    {
        parent::__construct( $name, $attributes );
        
        $this->elems = array();
    }
    
    public function addElement( $element )
    {
        $this->elems[] = $element;
    }
    
    public function render()
    {
        $this->setContent( $this->renderElems() );
        return parent::render();
    }
    
    protected function renderElems()
    {
        $tmp = '';
        foreach ( $this->elems as $elem )
        {
            $tmp .= $elem->render() . "\n";
        }
        return $tmp;
    }
}

class Claro_Html_Composite implements Claro_Renderer
{
    protected $elems;
    
    public function __construct()
    {
        $this->elems = array();
    }
    
    public function addElement( $element )
    {
        $this->elems[] = $element;
    }
    
    public function render()
    {
        $tmp = '';
        foreach ( $this->elems as $elem )
        {
            $tmp .= $elem->render() . "\n";
        }
        return $tmp;
    }
}

<?php // $Id: core.lib.php 13687 2011-10-14 12:50:06Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Main core library.
 *
 * @version     $Revision: 13687 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 */

require_once dirname(__FILE__) . '/../utils/iterators.lib.php';

/**
 * Protect file path against arbitrary file inclusion
 * @param   string path, untrusted path
 * @return  string secured path
 */
function protect_against_file_inclusion( $path )
{
    while ( false !== strpos( $path, '://' )
        || false !== strpos( $path, '..' ) )
    {
        // protect against remote file inclusion
        $path = str_replace( '://', '', $path );
        // protect against arbitrary file inclusion
        $path = str_replace( '..', '.', $path );
    }
        
    return $path;
}

/**
 * Kernel library loader
 */
class FromKernel
{
    /**
     * Load a list of kernel libraries
     * Usage : FromKernel::uses( list of libraries );
     * @params  list of libraries
     * @throws  Exception if a library is not found
     */
    public static function uses()
    {
        $args = func_get_args();
        
        defined('INCLUDES') || define ( 'INCLUDES', dirname(__FILE__) . '/..');
        
        foreach ( $args as $lib )
        {
            if ( substr($lib, -4) !== '.php' )
            {
                $lib .= '.php';
            }
            
            $lib = protect_against_file_inclusion( $lib );
            
            $kernelPath = INCLUDES . '/' . $lib;
            
            if ( file_exists( $kernelPath ) )
            {
                require_once $kernelPath;
            }
            else
            {
                throw new Exception( "Lib not found $lib" );
            }
        }
    }
}

/**
 * Module library loader
 */
class From
{
    protected $moduleLabel;
    
    protected function __construct( $moduleLabel )
    {
        $this->moduleLabel = $moduleLabel;
    }
    
    /**
     * Load a list of libraries from a given module
     * Usage : From::module(ModuleLable)->uses( list of libraries );
     * @params  list of libraries
     * @return  array of not found libraries
     */
    public function uses()
    {
        $args = func_get_args();
        $notFound = array();
        
        foreach ( $args as $lib )
        {
            if ( basename( $lib ) == '*' )
            {
                require_once dirname(__FILE__) . '/../utils/finder.lib.php';
                
                $localPath = get_module_path( $this->moduleLabel ) . '/lib/' . dirname( $lib );
                
                if ( file_exists( $localPath )
                    && is_dir( $localPath )
                    && is_readable( $localPath )
                )
                {
                    $path = $localPath;
                }
                else
                {
                    if ( claro_debug_mode() )
                    {
                        throw new Exception( "Cannot load libraries from {$dir} at {$localPath}" );
                    }
                    
                    $notFound[] = $lib;
                    
                    continue;
                }
                
                $finder = new Claro_FileFinder_Extension( $path, '.php', false );
                
                foreach ( $finder as $file )
                {
                    require_once $file->getPathname();
                }
            }
            else
            {
                if ( substr($lib, -4) !== '.php' ) $lib .= '.php';
                
                $lib = protect_against_file_inclusion( $lib );
                
                $libPath = get_module_path( $this->moduleLabel ) . '/lib/' . $lib;
                
                if ( file_exists( $libPath ) )
                {
                    require_once $libPath;
                }
                else
                {
                    if ( claro_debug_mode() )
                    {
                        throw new Exception( "Cannot load library {$libPath}" );
                    }
                    
                    $notFound[] = $lib;
                    
                    continue;
                }
            }
        }
    }
    
    /**
     * Load a list of plugins from a given module
     * Usage : From::module(ModuleLable)->loadPlugins( list of connectors );
     * @since Claroline 1.9.6
     * @params  list of plugins
     * @return  array of not found plugins
     */
    public function loadPlugins()
    {
        $args = func_get_args();
        $notFound = array();
        
        foreach ( $args as $cnr )
        {
            if ( substr($cnr, -4) !== '.php' && substr( $cnr, -4 ) === '.lib' )
            {
                $cnr .= '.php';
            }
            elseif ( substr($cnr, -8) !== '.lib.php' )
            {
                $cnr .= '.lib.php';
            }
            
            $cnr = protect_against_file_inclusion( $cnr );
            
            $cnrPath = get_module_path( $this->moduleLabel ) . '/plugins/' . $cnr;
            
            if ( file_exists( $cnrPath ) )
            {
                require_once $cnrPath;
            }
            else
            {
                if ( claro_debug_mode() )
                {
                    throw new Exception( "Cannot load plugin {$cnrPath}" );
                }
                
                $notFound[] = $cnr;
                
                continue;
            }
            
        }
        
        return $notFound;
    }
    
    private static $cache = array();
    
    /**
     * Get the loader for a given module
     * @param   string $moduleLabel
     * @return  Loader instance
     */
    public static function module( $moduleLabel = null )
    {
        if ( empty($moduleLabel) )
        {
            $moduleLabel = get_current_module_label();
        }
        
        if ( !array_key_exists( $moduleLabel, self::$cache ) )
        {
            self::$cache[$moduleLabel] = new self($moduleLabel);
        }
        
        return self::$cache[$moduleLabel];
    }
}
